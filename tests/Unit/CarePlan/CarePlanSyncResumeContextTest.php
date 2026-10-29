<?php

declare(strict_types=1);

namespace Tests\Unit\CarePlan;

use App\Classes\eHealth\Api\CarePlan as CarePlanApi;
use App\Classes\eHealth\Api\Declaration;
use App\Classes\eHealth\EHealthResponse;
use App\Jobs\CarePlanFullSync;
use App\Models\LegalEntity;
use App\Repositories\CarePlanRepository;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use ReflectionProperty;
use Tests\TestCase;

class CarePlanSyncResumeContextTest extends TestCase
{
    public function test_recreated_batch_uses_serialized_patient_context_and_fresh_token(): void
    {
        $legalEntity = new LegalEntity();
        $legalEntity->id = 2;
        $context = [
            'patient_uuid' => 'patient-uuid',
            'person_id' => 42,
            'patient_care_plan_registry' => true,
        ];
        $job = unserialize(serialize(new CarePlanFullSync($legalEntity, page: 2, standalone: true, patientContext: $context)));
        [$job, $batch] = $job->withFakeBatch();
        // The shared login restart only transfers credentials and the legal entity.
        $batch->options = ['token' => Crypt::encryptString('fresh-token'), 'legal_entity_id' => 2];

        $response = Mockery::mock(EHealthResponse::class);
        $response->shouldReceive('validate')->once()->andReturn([['id' => 'plan-uuid']]);
        $response->shouldReceive('isNotLast')->once()->andReturnTrue();
        $api = Mockery::mock(CarePlanApi::class);
        $api->shouldReceive('withToken')->once()->with('fresh-token')->andReturnSelf();
        $api->shouldReceive('getBySearchParams')->once()->with('patient-uuid', ['page' => 2])->andReturn($response);
        $api->shouldNotReceive('getDetails');
        $this->instance(CarePlanApi::class, $api);
        $repository = Mockery::mock(CarePlanRepository::class);
        $repository->shouldReceive('storeSummaries')->once()->with([['id' => 'plan-uuid']], 42, 2);
        $this->instance(CarePlanRepository::class, $repository);
        $declarations = Mockery::mock(Declaration::class);
        $declarations->shouldNotReceive('withToken');
        $declarations->shouldNotReceive('getMany');
        $this->instance(Declaration::class, $declarations);

        $job->handle();

        $this->assertCount(2, $batch->added);
        $this->assertSame('plan-uuid', $batch->added[0]->resourceUuid);
        $this->assertNull($batch->added[1]->resourceUuid);
        $this->assertSame(3, (new ReflectionProperty($batch->added[1], 'page'))->getValue($batch->added[1]));
        foreach ($batch->added as $child) {
            $this->assertSame($context, $child->patientContext);
            $this->assertArrayNotHasKey('token', $child->patientContext);
            $this->assertArrayNotHasKey('has_patient_access', $child->patientContext);
        }
    }

    public function test_cancelled_patient_batch_does_not_call_ehealth(): void
    {
        $job = new CarePlanFullSync(new LegalEntity(), standalone: true, patientContext: ['patient_care_plan_registry' => true]);
        [$job, $batch] = $job->withFakeBatch();
        $batch->cancel();
        $api = Mockery::mock(CarePlanApi::class);
        $api->shouldNotReceive('withToken');
        $this->instance(CarePlanApi::class, $api);

        $job->handle();

        $this->assertSame([], $batch->added);
    }
}
