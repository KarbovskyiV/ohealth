<?php

declare(strict_types=1);

namespace Tests\Unit\CarePlan;

use App\Classes\eHealth\Api\CarePlan as CarePlanApi;
use App\Classes\eHealth\EHealthResponse;
use App\Jobs\CarePlanFullSync;
use App\Models\LegalEntity;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CarePlanFullSyncRequestTest extends TestCase
{
    public static function searchContexts(): array
    {
        return [
            'patient with clinic filter' => ['patient-uuid', false, 'getBySearchParams', ['page' => 2, 'managing_organization_id' => 'clinic-uuid']],
            'patient registry' => ['patient-uuid', true, 'getBySearchParams', ['page' => 2]],
        ];
    }

    public function test_jobs_deserialized_without_running_the_constructor_have_a_safe_declaration_sync_default(): void
    {
        $job = (new \ReflectionClass(CarePlanFullSync::class))->newInstanceWithoutConstructor();

        $this->assertFalse($job->synchronizeDeclarations);
    }

    #[DataProvider('searchContexts')]
    public function test_search_uses_the_endpoint_for_its_context(?string $patientUuid, bool $registry, string $method, array $query): void
    {
        $response = Mockery::mock(EHealthResponse::class);
        $api = Mockery::mock(CarePlanApi::class);
        $api->shouldReceive('withToken')->once()->with('queue-token')->andReturnSelf();
        $arguments = $patientUuid === null ? [$query] : [$patientUuid, $query];
        $api->shouldReceive($method)->once()->with(...$arguments)->andReturn($response);
        $this->instance(CarePlanApi::class, $api);

        $legalEntity = new LegalEntity();
        $legalEntity->uuid = 'clinic-uuid';
        $job = new CarePlanFullSyncRequestHarness($legalEntity, page: 2, standalone: $registry);
        [$job, $batch] = $job->withFakeBatch();
        $batch->options['patient_care_plan_registry'] = $registry;

        $this->assertSame($response, $job->requestForPatient($patientUuid));
    }

    public function test_patient_registry_resource_still_loads_its_details(): void
    {
        $response = Mockery::mock(EHealthResponse::class);
        $api = Mockery::mock(CarePlanApi::class);
        $api->shouldReceive('withToken')->once()->with('queue-token')->andReturnSelf();
        $api->shouldReceive('getDetails')->once()->with('patient-uuid', 'plan-uuid')->andReturn($response);
        $this->instance(CarePlanApi::class, $api);
        $job = new CarePlanFullSyncRequestHarness(new LegalEntity(), standalone: true, resourceUuid: 'plan-uuid');
        [$job, $batch] = $job->withFakeBatch();
        $batch->options['patient_care_plan_registry'] = true;

        $this->assertSame($response, $job->requestForPatient('patient-uuid'));
    }

    public function test_missing_patient_context_is_rejected_before_any_api_request(): void
    {
        $api = Mockery::mock(CarePlanApi::class);
        $api->shouldNotReceive('withToken');
        $api->shouldNotReceive('getMany');
        $this->instance(CarePlanApi::class, $api);
        $job = new CarePlanFullSyncRequestHarness(new LegalEntity(), standalone: true);
        [$job] = $job->withFakeBatch();

        try {
            $job->requestForPatient(null);
            $this->fail('A batch without patient context must not call eHealth.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('patient_uuid', $exception->errors());
        }
    }
}

class CarePlanFullSyncRequestHarness extends CarePlanFullSync
{
    public function requestForPatient(?string $patientUuid): ?EHealthResponse
    {
        $this->patientUuid = $patientUuid;

        return $this->sendRequest('queue-token');
    }
}
