<?php

declare(strict_types=1);

namespace App\Livewire\Person\Records;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\JobStatus;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Jobs\EpisodeSync;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\ClinicalImpression;
use App\Models\MedicalEvents\Sql\Condition;
use App\Models\MedicalEvents\Sql\DiagnosticReport;
use App\Models\MedicalEvents\Sql\Encounter;
use App\Models\MedicalEvents\Sql\Episode;
use App\Models\MedicalEvents\Sql\Immunization;
use App\Models\MedicalEvents\Sql\Observation;
use App\Models\User;
use App\Notifications\SyncNotification;
use App\Repositories\MedicalEvents\Repository;
use App\Traits\BatchLegalEntityQueries;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Throwable;

class PatientSummary extends BasePatientComponent
{
    use BatchLegalEntityQueries;

    protected const string EPISODE_BATCH_NAME = 'EpisodeSync';

    public array $episodes = [];

    public array $encounters = [];

    public array $clinicalImpressions = [];

    public array $immunizations = [];

    public array $observations = [];

    public array $diagnoses = [];

    public array $conditions = [];

    public array $diagnosticReports = [];

    public array $allergyIntolerances;

    public array $riskAssessments;

    public array $devices;

    public array $medicationStatements;

    /**
     * Stores synchronization statuses for all entity types.
     *
     * @var array
     */
    public array $syncStatuses = [];

    protected array $dictionaryNames = [
        'eHealth/encounter_classes',
        'eHealth/encounter_types',
        'SPECIALITY_TYPE',
        'eHealth/clinical_impression_patient_categories',
        'eHealth/vaccine_codes',
        'eHealth/vaccination_routes',
        'eHealth/reason_explanations',
        'eHealth/immunization_body_sites',
        'eHealth/observation_categories',
        'eHealth/ICF/observation_categories',
        'eHealth/LOINC/observation_codes',
        'eHealth/report_origins',
        'eHealth/observation_methods',
        'eHealth/observation_interpretations',
        'eHealth/body_sites',
        'eHealth/ICPC2/condition_codes',
        'eHealth/ICD10/condition_codes',
        'eHealth/condition_severities',
        'eHealth/diagnostic_report_categories',
    ];

    /**
     * Generic method to check if any entity is currently syncing.
     *
     * @param  string  $entityConstant  The entity constant from LegalEntity class (e.g., 'ENTITY_EPISODE')
     * @return bool
     */
    public function isEntitySyncing(string $entityConstant): bool
    {
        if ($entityConstant === 'ENTITY_EPISODE') {
            return $this->isEpisodeSyncProcessing();
        }

        return false;
    }

    protected function initializeComponent(): void
    {
        $this->getDictionary();

        $this->dictionaries['eHealth/ICF/classifiers'] = dictionary()->basics()
            ->byName('eHealth/ICF/classifiers')
            ->flattenedChildValues()
            ->toArray();

        // Initialize sync statuses for all entities
        $this->syncStatuses = [
            'ENTITY_EPISODE' => legalEntity()->getEntityStatus(LegalEntity::ENTITY_EPISODE),
        ];
    }

    /**
     * Determine if episode synchronization is currently running.
     *
     * @return bool True if a sync process is actively processing, false otherwise.
     */
    protected function isEpisodeSyncProcessing(): bool
    {
        // Check if there are any active episode sync batches for this patient
        $runningBatches = $this->findRunningBatchesByLegalEntity(legalEntity()->id);

        return $runningBatches->where('name', self::EPISODE_BATCH_NAME . '_' . $this->uuid)->isNotEmpty();
    }

    /**
     * Sync patient episodes from eHealth API to database.
     *
     * @return void
     */
    public function syncEpisodes(): void
    {
        if ($this->isEpisodeSyncProcessing()) {
            Session::flash('error', __('patients.messages.episode_sync_already_running'));

            return;
        }

        $user = Auth::user();
        $token = Session::get(config('ehealth.api.oauth.bearer_token'));

        // Try to resume previous sync if it was paused or failed
        if ($this->syncStatuses['ENTITY_EPISODE'] === JobStatus::PAUSED->value || $this->syncStatuses['ENTITY_EPISODE'] === JobStatus::FAILED->value) {
            $this->resumeEpisodeSynchronization($user, $token);
            Session::flash('success', __('patients.messages.episode_sync_resume_started'));
            $user->notify(new SyncNotification('episode', 'resumed'));

            return;
        }

        try {
            $response = EHealth::episode()->getShortEpisodes($this->uuid);
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error while synchronizing episodes');

            return;
        }

        try {
            $validatedData = $response->validate();

            Repository::episode()->sync($this->id, $validatedData);
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, 'Error while synchronizing episodes');
            Session::flash('error', __('patients.messages.episode_sync_database_error'));

            return;
        }

        // If there are more pages, dispatch a job to handle the rest
        if ($response->isNotLast()) {
            try {
                $user->notify(new SyncNotification('episode', 'started'));
                $this->dispatchNextSyncJobs($user, $token);
                Session::flash('success', __('patients.messages.episodes_first_page_synced_successfully'));
            } catch (Throwable $exception) {
                Log::error('Failed to dispatch EpisodeSync batch', ['exception' => $exception]);
                $user->notify(new SyncNotification('episode', 'failed'));
                Session::flash('error', __('patients.messages.episode_sync_background_dispatch_error'));
            }
        } else {
            legalEntity()->setEntityStatus(JobStatus::COMPLETED, LegalEntity::ENTITY_EPISODE);
            Session::flash('success', __('patients.messages.episodes_synced_successfully'));
        }

        // Refresh data for display
        $this->episodes = Arr::toCamelCase($this->formatDatesForDisplay($validatedData));
    }

    /**
     * Resume the synchronization process for episodes with the provided token.
     *
     * @param  User  $user
     * @param  string  $token
     * @return void
     */
    protected function resumeEpisodeSynchronization(User $user, string $token): void
    {
        $encryptedToken = Crypt::encryptString($token);

        // Find all the Episode's failed batches for this patient and retry them
        $failedBatches = $this->findFailedBatchesByLegalEntity(legalEntity()->id, 'ASC');

        foreach ($failedBatches as $batch) {
            if ($batch->name === self::EPISODE_BATCH_NAME . '_' . $this->uuid) {
                Log::info('Resuming Episode sync batch: ' . $batch->name . ' id: ' . $batch->id);

                legalEntity()->setEntityStatus(JobStatus::PROCESSING, LegalEntity::ENTITY_EPISODE);

                $this->restartBatch($batch, $user, $encryptedToken, legalEntity());

                break;
            }
        }
    }

    /**
     * Dispatch next sync jobs for remaining episode pages.
     *
     * @param  User  $user
     * @param  string  $token
     * @return void
     * @throws Throwable
     */
    protected function dispatchNextSyncJobs(User $user, string $token): void
    {
        Bus::batch([new EpisodeSync(legalEntity(), page: 2)])
            ->withOption('legal_entity_id', legalEntity()->id)
            ->withOption('token', Crypt::encryptString($token))
            ->withOption('user', $user)
            ->withOption('patient_uuid', $this->uuid)
            ->withOption('person_id', $this->id)
            ->then(fn () => $user->notify(new SyncNotification('episode', 'completed')))
            ->catch(function (Batch $batch, Throwable $exception) use ($user) {
                Log::error('Episode sync batch failed.', [
                    'batch_id' => $batch->id,
                    'patient_uuid' => $this->uuid,
                    'exception' => $exception
                ]);

                $user->notify(new SyncNotification('episode', 'failed'));
            })
            ->onQueue('sync')
            ->name(self::EPISODE_BATCH_NAME . '_' . $this->uuid)
            ->dispatch();

        legalEntity()->setEntityStatus(JobStatus::PROCESSING, LegalEntity::ENTITY_EPISODE);
    }

    public function getEpisodes(): void
    {
        $this->episodes = Episode::with('period')->wherePersonId($this->id)->get()->toArray();
    }

    public function syncEncounters(): void
    {
        try {
            $response = EHealth::encounter()->getShortBySearchParams($this->uuid);
            $validatedData = $response->validate();

            try {
                Repository::encounter()->sync($this->id, $validatedData);
                Session::flash('success', __('patients.messages.encounters_synced_successfully'));
            } catch (Throwable $exception) {
                $this->logDatabaseErrors($exception, 'Error while synchronizing encounters');
                Session::flash('error', __('messages.database_error'));

                return;
            }

            // Refresh data for display
            $this->encounters = $validatedData;
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when syncing encounters');

            return;
        }
    }

    public function getEncounters(): void
    {
        $this->encounters = Encounter::wherePersonId($this->id)
            ->with(['class', 'episode.type.coding', 'type.coding', 'period', 'performerSpeciality.coding'])
            ->get()
            ->toArray();
    }

    public function syncClinicalImpressions(): void
    {
        try {
            $response = EHealth::clinicalImpression()->getSummary($this->uuid);
            $validatedData = $response->validate();

            try {
                Repository::clinicalImpression()->sync($this->id, $validatedData);
                Session::flash('success', __('patients.messages.clinical_impressions_synced_successfully'));
            } catch (Throwable $exception) {
                $this->logDatabaseErrors($exception, 'Error while synchronizing clinical impressions');
                Session::flash('error', __('messages.database_error'));

                return;
            }

            // Refresh data for display
            $this->clinicalImpressions = Arr::toCamelCase($this->formatDatesForDisplay($validatedData));
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting clinical impressions');

            return;
        }
    }

    public function getClinicalImpressions(): void
    {
        $this->clinicalImpressions = ClinicalImpression::wherePersonId($this->id)
            ->withAllRelations()
            ->get()
            ->toArray();
    }

    public function syncImmunizations(): void
    {
        try {
            $response = EHealth::immunization()->getSummary($this->uuid);
            $validatedData = $response->validate();

            try {
                Repository::immunization()->sync($this->id, $validatedData);
                Session::flash('success', __('patients.messages.immunizations_synced_successfully'));
            } catch (Throwable $exception) {
                $this->logDatabaseErrors($exception, 'Error while synchronizing immunizations');
                Session::flash('error', __('messages.database_error'));

                return;
            }

            // Refresh data for display
            $this->immunizations = Arr::toCamelCase($this->formatDatesForDisplay($validatedData));
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting immunizations');

            return;
        }
    }

    public function getImmunizations(): void
    {
        $this->immunizations = Immunization::wherePersonId($this->id)
            ->withAllRelations()
            ->get()
            ->toArray();
    }

    public function syncObservations(): void
    {
        try {
            $response = EHealth::observation()->getBySearchParams(
                $this->uuid,
                ['managing_organization_id' => legalEntity()->uuid]
            );
            $validatedData = $response->validate();

            try {
                Repository::observation()->sync($this->id, $validatedData);
                Session::flash('success', __('patients.messages.observations_synced_successfully'));
            } catch (Throwable $exception) {
                $this->logDatabaseErrors($exception, 'Error while synchronizing observations');
                Session::flash('error', __('messages.database_error'));

                return;
            }

            // Refresh data for display
            $this->observations = Arr::toCamelCase($this->formatDatesForDisplay($validatedData));
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting observations');

            return;
        }
    }

    public function getObservations(): void
    {
        $this->observations = Observation::wherePersonId($this->id)
            ->withAllRelations()
            ->get()
            ->toArray();
    }

    public function syncDiagnoses(): void
    {
        try {
            $response = EHealth::patient()->getActiveDiagnoses($this->uuid);

            // Refresh data for display
            $this->diagnoses = Arr::toCamelCase($this->formatDatesForDisplay($response->getData()));
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting diagnoses');

            return;
        }
    }

    public function getDiagnoses(): void
    {
        //
    }

    public function syncConditions(): void
    {
        try {
            $response = EHealth::condition()->getBySearchParams(
                $this->uuid,
                ['managing_organization_id' => legalEntity()->uuid]
            );
            $validatedData = $response->validate();

            try {
                Repository::condition()->sync($this->id, $validatedData);
                Session::flash('success', __('patients.messages.conditions_synced_successfully'));
            } catch (Throwable $exception) {
                $this->logDatabaseErrors($exception, 'Error while synchronizing conditions');
                Session::flash('error', __('messages.database_error'));

                return;
            }

            // Refresh data for display
            $this->conditions = Arr::toCamelCase($this->formatDatesForDisplay($validatedData));
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting conditions');

            return;
        }
    }

    public function getConditions(): void
    {
        $this->conditions = Condition::wherePersonId($this->id)
            ->withAllRelations()
            ->get()
            ->toArray();
    }

    public function syncDiagnosticReports(): void
    {
        try {
            $response = EHealth::diagnosticReport()->getBySearchParams(
                $this->uuid,
                ['managing_organization_id' => legalEntity()->uuid]
            );
            $validatedData = $response->validate();

            try {
                Repository::diagnosticReport()->sync($this->id, $validatedData);
                Session::flash('success', __('patients.messages.diagnostic_reports_synced_successfully'));
            } catch (Throwable $exception) {
                $this->logDatabaseErrors($exception, 'Error while synchronizing diagnostic reports');
                Session::flash('error', __('messages.database_error'));
            }
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting diagnostic reports');

            return;
        }
    }

    public function getDiagnosticReports(): void
    {
        $this->diagnosticReports = DiagnosticReport::wherePersonId($this->id)
            ->withAllRelations()
            ->get()
            ->toArray();
    }

    public function syncAllergyIntolerances(): void
    {
        try {
            $response = EHealth::patient()->getAllergyIntolerances($this->uuid);
            $validatedData = $response->validate();
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting allergy intolerances');

            return;
        }
    }

    public function syncRiskAssessments(): void
    {
        try {
            $response = EHealth::patient()->getRiskAssessments($this->uuid);
            $validatedData = $response->validate();
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting risk assessments');

            return;
        }
    }

    public function syncDevices(): void
    {
        try {
            $response = EHealth::patient()->getDevices($this->uuid);
            $validatedData = $response->validate();
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting devices');

            return;
        }
    }

    public function syncMedicationStatements(): void
    {
        try {
            $response = EHealth::patient()->getMedicationStatements($this->uuid);
            $validatedData = $response->validate();
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting medication statements');

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.person.records.summary');
    }
}
