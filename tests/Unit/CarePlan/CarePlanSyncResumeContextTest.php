<?php

declare(strict_types=1);

namespace Tests\Unit\CarePlan;

use App\Jobs\CarePlanFullSync;
use App\Models\LegalEntity;
use App\Models\User;
use App\Traits\BatchLegalEntityQueries;
use Illuminate\Bus\Batch;
use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class CarePlanSyncResumeContextTest extends TestCase
{
    public function test_resume_preserves_patient_context_and_refreshes_the_login_credentials(): void
    {
        Bus::fake();
        $legalEntity = new LegalEntity();
        $legalEntity->id = 2;
        $this->instance('legalEntity', $legalEntity);
        $user = new User();
        $user->id = 7;
        $context = [
            'sync_entity' => 'care_plan_',
            'patient_uuid' => 'patient-uuid',
            'person_id' => 42,
            'preperson_id' => null,
            'patient_care_plan_registry' => true,
            'care_plan_approvals' => false,
        ];
        $storedBatch = Mockery::mock(Batch::class);
        $storedBatch->options = $context + ['token' => 'expired-token', 'user' => new User(), 'has_patient_access' => true];
        $repository = Mockery::mock(BatchRepository::class);
        $repository->shouldReceive('find')->once()->with('failed-batch')->andReturn($storedBatch);
        $repository->shouldReceive('delete')->once()->with('failed-batch');
        $this->instance(BatchRepository::class, $repository);

        $harness = new class
        {
            use BatchLegalEntityQueries;

            public function resume(object $batch, User $user, LegalEntity $legalEntity): void
            {
                $this->restartFailedBatch($user, $batch, 'fresh-encrypted-token', $legalEntity, [new CarePlanFullSync($legalEntity, standalone: true)]);
            }
        };
        $harness->resume((object) ['id' => 'failed-batch', 'name' => 'CarePlanFullSync_patient-uuid'], $user, $legalEntity);

        Bus::assertBatched(function (PendingBatch $batch) use ($context, $user): bool {
            foreach ($context as $key => $value) {
                $this->assertArrayHasKey($key, $batch->options);
                $this->assertSame($value, $batch->options[$key]);
            }
            $this->assertSame('fresh-encrypted-token', $batch->options['token']);
            $this->assertSame($user, $batch->options['user']);
            $this->assertSame(2, $batch->options['legal_entity_id']);
            $this->assertArrayNotHasKey('has_patient_access', $batch->options);

            return $batch->name === 'CarePlanFullSync_patient-uuid';
        });
    }
}
