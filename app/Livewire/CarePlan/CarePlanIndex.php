<?php

declare(strict_types=1);

namespace App\Livewire\CarePlan;

use App\Traits\SynchronizesCarePlans;

use App\Classes\eHealth\EHealth;
use App\Repositories\CarePlanRepository;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CarePlanIndex extends Component
{
    use SynchronizesCarePlans;
    use \App\Traits\HandlesCarePlanSync;

    #[\Livewire\Attributes\Locked]
    public array $syncBatchIds = [];

    #[\Livewire\Attributes\Locked]
    public bool $syncInitiationFailed = false;

    public string $searchRequisition = '';

    public string $filterName = '';
    public string $filterEncounterId = '';
    public string $filterStatus = '';
    public string $filterStartDateRange = '';
    public string $filterEndDateRange = '';
    public string $filterIsPartOf = '';
    public string $filterIncludes = '';
    public bool $showAdditionalParams = false;

    public function search(): void
    {
        $this->searchByRequisition();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'searchRequisition',
            'filterName',
            'filterEncounterId',
            'filterStatus',
            'filterStartDateRange',
            'filterEndDateRange',
            'filterIsPartOf',
            'filterIncludes',
        ]);
    }

    /**
     * Search eHealth by public requisition number (per TZ 3.10.3.2.1).
     */
    public function searchByRequisition(): void
    {
        if (empty($this->searchRequisition)) {
            return;
        }

        try {
            $this->synchronizeSearch(['requisition' => $this->searchRequisition], (int) legalEntity()->id);
        } catch (\Throwable $e) {
            Log::error('CarePlan search error: ' . $e->getMessage());
            session()->flash('error', __('care-plan.search_error') . ': ' . $e->getMessage());
        }
    }

    public function mount(): void
    {
        $this->syncBatchIds = $this->findRunningBatchesByLegalEntity(legalEntity()->id)
            ->filter(static fn ($batch): bool => str_starts_with($batch->name, \App\Jobs\CarePlanFullSync::BATCH_NAME.'_'))
            ->pluck('id')->all();
    }

    public function sync(): void
    {
        if (auth()->user()->cannot('care_plan:read') || auth()->user()->activeEmployee() === null) {
            session()->flash('error', __('care-plan.sync_access_denied'));

            return;
        }
        $token = session()->get(config('ehealth.api.oauth.bearer_token'));
        if (!is_string($token) || $token === '') {
            session()->flash('error', __('care-plan.sync_session_expired'));

            return;
        }
        if ($this->syncBatchIds !== [] && $this->carePlanSyncOutcome($this->syncBatchIds) === null) {
            session()->flash('info', __('care-plan.sync_running'));

            return;
        }
        $this->syncBatchIds = [];
        $this->syncInitiationFailed = false;
        try {
            $persons = app(CarePlanRepository::class)->patientsForSync(legalEntity()->id, auth()->user());
            foreach ($persons->values() as $index => $person) {
                $batchId = $this->startCarePlanPatientSync($person, firstPageSynchronously: $index === 0);
                if ($batchId !== null) {
                    $this->syncBatchIds[] = $batchId;
                }
            }
            session()->flash($this->syncBatchIds === [] ? 'success' : 'info', __($this->syncBatchIds === [] ? 'care-plan.sync_success' : 'care-plan.sync_first_page'));
        } catch (\Throwable $exception) {
            $this->syncInitiationFailed = true;
            Log::error('Could not start CarePlan index synchronization', ['exceptionType' => $exception::class]);
            session()->flash('error', __('care-plan.sync_failed'));
        }
    }

    public function checkSyncStatus(): void
    {
        if ($this->syncBatchIds === []) {
            return;
        }
        $outcome = $this->carePlanSyncOutcome($this->syncBatchIds);
        if ($outcome === null) {
            $this->skipRender();

            return;
        }
        $outcome['success'] = $outcome['success'] && !$this->syncInitiationFailed;
        $this->syncBatchIds = [];
        $this->flashCarePlanSyncOutcome($outcome);
    }

    public function render()
    {
        $carePlans = app(CarePlanRepository::class)->listing(legalEntity()->id, ['status' => $this->filterStatus, 'name' => $this->filterName, 'startDate' => $this->filterStartDateRange, 'endDate' => $this->filterEndDateRange, 'encounterId' => $this->filterEncounterId, 'requisition' => $this->searchRequisition]);

        return view('livewire.care-plan.care-plan-index', ['carePlans' => $carePlans]);
    }
}
