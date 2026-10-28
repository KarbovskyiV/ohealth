<?php

declare(strict_types=1);

namespace App\Livewire\Person\Records;

use App\Jobs\CarePlanFullSync;
use App\Traits\BatchLegalEntityQueries;
use App\Models\User;
use App\Notifications\SyncNotification;
use App\Notifications\RemoteEHealthLinksNotification;
use App\Exceptions\EHealth\EHealthResponseException;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;

use App\Repositories\CarePlanRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Throwable;

class PatientCarePlans extends BasePatientComponent
{
    use BatchLegalEntityQueries;

    #[Locked]
    public ?string $syncBatchId = null;

    #[Locked]
    public array $carePlans = [];

    public string $filterName = '';
    public string $filterEncounterId = '';
    public string $filterStatus = '';
    public string $filterStartDateRange = '';
    public string $filterEndDateRange = '';
    public string $filterIsPartOf = '';
    public string $filterIncludes = '';
    public bool $showAdditionalParams = false;

    /**
     * Initialize component with care plans for the specific patient.
     */
    protected function initializeComponent(): void
    {
        $this->loadCarePlans();
        $this->syncBatchId = $this->runningSyncBatchId();

        try {
            $basics = app(\App\Services\Dictionary\DictionaryManager::class)->basics();
            $this->dictionaries['care_plan_categories'] = $basics->byName('eHealth/care_plan_categories')
                ?->asCodeDescription()
                ?->toArray() ?? [];
        } catch (\Exception $exception) {
            \Illuminate\Support\Facades\Log::warning('PersonCarePlans: failed to load dictionaries: ' . $exception->getMessage());
        }
    }

    /**
     * Load care plans from the database.
     */
    public function loadCarePlans(): void
    {
        $this->carePlans = app(CarePlanRepository::class)->patientListing($this->personId, [
            'name' => $this->filterName,
            'status' => $this->filterStatus,
            'encounter_id' => $this->filterEncounterId,
            'start_date' => $this->filterStartDateRange,
            'end_date' => $this->filterEndDateRange,
            'is_part_of' => $this->filterIsPartOf,
            'includes' => $this->filterIncludes,
        ], (int) legalEntity()->id);
    }

    private function runningSyncBatchId(): ?string
    {
        return $this->findRunningBatchesByLegalEntity(legalEntity()->id)
            ->firstWhere('name', CarePlanFullSync::BATCH_NAME.'_'.$this->uuid)?->id;
    }

    public function sync(): void
    {
        try {
            $dispatched = Cache::lock('care-plan-sync:'.legalEntity()->id.':'.$this->uuid, 10)
                ->get(function (): bool {
                    $this->dispatchSync();

                    return true;
                });
            if (!$dispatched) {
                Session::flash('info', __('care-plan.sync_running'));
            }
        } catch (Throwable $exception) {
            Log::error('Could not queue patient CarePlan synchronization', ['exceptionType' => $exception::class]);
            Session::flash('error', __('care-plan.sync_failed'));
        }
    }

    private function dispatchSync(): void
    {
        $this->syncBatchId = $this->runningSyncBatchId();
        if ($this->syncBatchId !== null) {
            Session::flash('info', __('care-plan.sync_running'));

            return;
        }
        $token = Session::get(config('ehealth.api.oauth.bearer_token'));
        if (!is_string($token) || $token === '') {
            Session::flash('error', __('care-plan.sync_session_expired'));

            return;
        }
        $user = Auth::user();
        $userId = $user->id;
        try {
            $batch = Bus::batch([new CarePlanFullSync(legalEntity(), standalone: true)])
                ->name(CarePlanFullSync::BATCH_NAME.'_'.$this->uuid)
                ->withOption('legal_entity_id', legalEntity()->id)
                ->withOption('patient_uuid', $this->uuid)
                ->withOption('patient_care_plan_registry', true)
                ->withOption('person_id', $this->personId)
                ->withOption('token', Crypt::encryptString($token))
                ->withOption('user', $user)
                ->then(static function (Batch $batch) use ($userId): void {
                    $denied = (int) Cache::get('care-plan-sync-denied:'.$batch->id, 0);
                    User::find($userId)?->notify($denied > 0
                        ? new RemoteEHealthLinksNotification(__('care-plan.sync_partial_access', ['count' => $denied]), 'warning')
                        : new SyncNotification('care_plan', 'completed'));
                })
                ->catch(static function (Batch $batch, Throwable $exception) use ($userId): void {
                    $user = User::find($userId);
                    if ($exception instanceof EHealthResponseException && in_array($exception->getCode(), [401, 403], true)) {
                        $message = $exception->getCode() === 403 ? 'care-plan.sync_access_denied' : 'care-plan.sync_session_expired';
                        $user?->notify(new RemoteEHealthLinksNotification(__($message), 'error'));

                        return;
                    }
                    $user?->notify(new SyncNotification('care_plan', 'failed'));
                })
                ->onQueue('sync')->dispatch();
            $this->syncBatchId = $batch->id;
            Session::flash('info', __('care-plan.sync_started'));
        } catch (Throwable $exception) {
            Log::error('Could not queue patient CarePlan synchronization', ['exceptionType' => $exception::class]);
            Session::flash('error', __('care-plan.sync_failed'));
        }
    }

    public function checkSyncStatus(): void
    {
        if ($this->syncBatchId === null) {
            return;
        }
        $batch = Bus::findBatch($this->syncBatchId);
        if ($batch !== null && !$batch->finished() && !$batch->cancelled()) {
            return;
        }
        $this->syncBatchId = null;
        $this->loadCarePlans();
        $success = $batch !== null && $batch->finished() && !$batch->hasFailures() && !$batch->cancelled();
        $denied = $batch !== null ? (int) Cache::get('care-plan-sync-denied:'.$batch->id, 0) : 0;
        if ($success && $denied > 0) {
            Session::flash('warning', __('care-plan.sync_partial_access', ['count' => $denied]));

            return;
        }
        Session::flash($success ? 'success' : 'error', __($success ? 'care-plan.sync_success' : 'care-plan.sync_failed'));
    }

    public function search(): void
    {
        $this->loadCarePlans();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'filterName',
            'filterEncounterId',
            'filterStatus',
            'filterStartDateRange',
            'filterEndDateRange',
            'filterIsPartOf',
            'filterIncludes',
        ]);
        $this->loadCarePlans();
    }

    public function render(): View
    {
        return view('livewire.person.records.care-plans');
    }
}
