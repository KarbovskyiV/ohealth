<?php

declare(strict_types=1);

namespace Tests\Feature\CarePlan;

use App\Classes\eHealth\Api\CarePlan as CarePlanApi;
use App\Classes\eHealth\EHealthResponse;
use App\Jobs\CarePlanFullSync;
use App\Livewire\CarePlan\CarePlanIndex;
use App\Livewire\Person\Records\PatientCarePlans;
use App\Models\CarePlan;
use App\Models\Person\Person;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mockery;
use ReflectionProperty;
use Tests\Feature\Composition\CompositionTestFixtures;
use Tests\Feature\Composition\UsesCompositionDictionaries;
use Tests\TestCase;

class CarePlanAsyncSyncTest extends TestCase
{
    use CompositionTestFixtures;
    use DatabaseTransactions;
    use UsesCompositionDictionaries;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('uk');
        Http::preventStrayRequests();
        Queue::fake();
        session()->put(config('ehealth.api.oauth.bearer_token'), 'synthetic-token');
    }

    public function test_patient_sync_saves_only_first_page_summaries_and_queues_details_and_page_two(): void
    {
        ['person' => $person, 'legalEntity' => $legalEntity] = $this->compositionFixture(scopes: ['care_plan:read']);
        $summary = $this->summary();
        $this->firstPage($person, [$summary], true);
        config()->set('queue.default', 'sync');

        $component = Livewire::test(PatientCarePlans::class, ['legalEntity' => $legalEntity, 'personId' => $person->id])
            ->call('sync')->assertSee(__('care-plan.sync_first_page'))->assertDontSee(__('care-plan.sync_success'))
            ->assertSee('First page summary')->assertNotDispatched('flashMessage');

        $plan = CarePlan::whereUuid($summary['id'])->firstOrFail();
        $this->assertNull($plan->detailsSyncedAt);
        $batch = Bus::findBatch($component->get('syncBatchId'));
        $this->assertSame($person->uuid, $batch->options['patient_uuid']);
        $this->assertSame($person->id, $batch->options['person_id']);
        $this->assertTrue($batch->options['patient_care_plan_registry']);
        $this->assertSame('database', $batch->options['connection']);
        Queue::assertPushed(CarePlanFullSync::class, 2);
        Queue::assertPushed(CarePlanFullSync::class, fn ($job): bool => $job->resourceUuid === $summary['id'] && $job->synchronizeDeclarations);
        Queue::assertPushed(CarePlanFullSync::class, fn ($job): bool => $job->resourceUuid === null && (new ReflectionProperty($job, 'page'))->getValue($job) === 2);

        $component->call('sync')->assertSee(__('care-plan.sync_running'));
        Queue::assertPushed(CarePlanFullSync::class, 2);
        DB::table('job_batches')->where('id', $batch->id)->update(['pending_jobs' => 0, 'finished_at' => time()]);
        $component->call('checkSyncStatus')->assertSet('syncBatchId', null)->assertSee(__('care-plan.sync_success'))
            ->call('resetFilters')->assertDontSee(__('care-plan.sync_success'));
    }

    public function test_index_fetches_one_page_for_the_first_patient_and_queues_other_patients_with_their_context(): void
    {
        ['person' => $person, 'legalEntity' => $legalEntity] = $this->compositionFixture(scopes: ['care_plan:read']);
        $other = Person::create(['uuid' => (string) Str::uuid(), 'birth_date' => '2000-01-01', 'gender' => 'MALE']);
        foreach ([$person, $other] as $patient) {
            CarePlan::forceCreate(['uuid' => (string) Str::uuid(), 'personId' => $patient->id, 'legalEntityId' => $legalEntity->id, 'status' => 'active', 'title' => 'Existing plan']);
        }
        $summary = $this->summary();
        $this->firstPage($person, [$summary], true);

        $component = Livewire::test(CarePlanIndex::class)->call('sync')->assertSee(__('care-plan.sync_first_page'))
            ->assertDontSee(__('care-plan.sync_success'))->assertNotDispatched('flashMessage');

        $ids = $component->get('syncBatchIds');
        $this->assertCount(2, $ids);
        $batches = array_map(fn ($id) => Bus::findBatch($id), $ids);
        $this->assertSame([$person->uuid, $other->uuid], array_column(array_map(fn ($batch) => $batch->options, $batches), 'patient_uuid'));
        foreach ($batches as $batch) {
            $this->assertTrue($batch->options['patient_care_plan_registry']);
            $this->assertSame('database', $batch->options['connection']);
        }
        Queue::assertPushed(CarePlanFullSync::class, 3);
        $component->call('sync')->assertSee(__('care-plan.sync_running'));
        Queue::assertPushed(CarePlanFullSync::class, 3);

        DB::table('job_batches')->whereIn('id', $ids)->update(['pending_jobs' => 0, 'finished_at' => time()]);
        $component->call('checkSyncStatus')->assertSet('syncBatchIds', [])->assertSee(__('care-plan.sync_success'));
    }

    public function test_empty_first_page_finishes_without_queuing_a_page_or_details(): void
    {
        ['person' => $person, 'legalEntity' => $legalEntity] = $this->compositionFixture(scopes: ['care_plan:read']);
        $this->firstPage($person, [], false);

        Livewire::test(PatientCarePlans::class, ['legalEntity' => $legalEntity, 'personId' => $person->id])
            ->call('sync')->assertSet('syncBatchId', null)->assertSee(__('care-plan.sync_success'));
        Queue::assertNothingPushed();
    }

    public function test_dispatch_failure_does_not_report_completion_or_leave_the_button_disabled(): void
    {
        ['person' => $person, 'legalEntity' => $legalEntity] = $this->compositionFixture(scopes: ['care_plan:read']);
        $this->firstPage($person, [$this->summary()], false);
        Bus::partialMock()->shouldReceive('batch')->once()->andThrow(new \RuntimeException('Synthetic dispatch failure'));

        Livewire::test(PatientCarePlans::class, ['legalEntity' => $legalEntity, 'personId' => $person->id])
            ->call('sync')->assertSet('syncBatchId', null)->assertSee(__('care-plan.sync_failed'))
            ->assertDontSee(__('care-plan.sync_success'))->assertDontSee(__('care-plan.sync_first_page'));
    }

    private function summary(): array
    {
        return ['id' => (string) Str::uuid(), 'title' => 'First page summary', 'status' => 'active'];
    }

    public function test_a_summary_does_not_replace_an_existing_complete_resource(): void
    {
        ['person' => $person, 'legalEntity' => $legalEntity] = $this->compositionFixture(scopes: ['care_plan:read']);
        $resource = json_decode(file_get_contents(base_path('tests/Fixtures/CarePlan/plan.json')), true, flags: JSON_THROW_ON_ERROR);
        $repository = app(\App\Repositories\CarePlanRepository::class);
        $plan = $repository->storeResource($resource, $person->id, $legalEntity->id);
        $repository->storeSummaries([['id' => $plan->uuid, 'title' => 'Incomplete summary', 'status' => 'completed']], $person->id, $legalEntity->id);

        $this->assertSame($resource['title'], $plan->fresh()->title);
        $this->assertSame($resource['status'], $plan->fresh()->status);
        $this->assertNotNull($plan->fresh()->detailsSyncedAt);
        $this->assertSame(1, CarePlan::wherePersonId($person->id)->whereUuid($plan->uuid)->count());
    }

    public function test_expired_session_does_not_call_ehealth_or_queue_jobs(): void
    {
        ['person' => $person, 'legalEntity' => $legalEntity] = $this->compositionFixture(scopes: ['care_plan:read']);
        session()->forget(config('ehealth.api.oauth.bearer_token'));

        Livewire::test(PatientCarePlans::class, ['legalEntity' => $legalEntity, 'personId' => $person->id])
            ->call('sync')->assertSet('syncBatchId', null)->assertSee(__('care-plan.sync_session_expired'));
        Http::assertNothingSent();
        Queue::assertNothingPushed();
    }

    private function firstPage(Person $person, array $summaries, bool $more): void
    {
        $response = Mockery::mock(EHealthResponse::class);
        $response->shouldReceive('validate')->once()->andReturn($summaries);
        $response->shouldReceive('isNotLast')->once()->andReturn($more);
        $api = Mockery::mock(CarePlanApi::class);
        $api->shouldReceive('withToken')->once()->with('synthetic-token')->andReturnSelf();
        $api->shouldReceive('getBySearchParams')->once()->with($person->uuid, ['page' => 1])->andReturn($response);
        $api->shouldNotReceive('getDetails');
        $api->shouldNotReceive('getMany');
        $this->instance(CarePlanApi::class, $api);
    }
}
