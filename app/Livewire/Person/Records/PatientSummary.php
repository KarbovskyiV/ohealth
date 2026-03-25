<?php

declare(strict_types=1);

namespace App\Livewire\Person\Records;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\MedicalEvents\Sql\ClinicalImpression;
use App\Models\MedicalEvents\Sql\Encounter;
use App\Models\MedicalEvents\Sql\Episode;
use App\Models\MedicalEvents\Sql\Immunization;
use App\Repositories\MedicalEvents\Repository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Session;
use Throwable;

class PatientSummary extends BasePatientComponent
{
    public array $episodes;

    public array $encounters;

    public array $clinicalImpressions;

    public array $immunizations;

    public array $diagnoses;

    public array $observations;

    /**
     * Sync patient episodes from eHealth API to database.
     *
     * @return void
     */
    public function syncEpisodes(): void
    {
        try {
            $response = EHealth::patient()->getShortEpisodes($this->uuid);
            $validatedData = $response->validate();

            try {
                Repository::episode()->sync($this->id, $validatedData);
                Session::flash('success', __('patients.messages.episodes_synced_successfully'));
            } catch (Throwable $exception) {
                $this->logDatabaseErrors($exception, 'Error while synchronizing episodes');
                Session::flash('error', __('messages.database_error'));

                return;
            }

            // Refresh data for display
            $this->episodes = $validatedData;
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when syncing episodes');

            return;
        }
    }

    public function getEpisodes(): void
    {
        $this->episodes = Episode::with('period')->wherePersonId($this->id)->get()->toArray();
    }

    public function syncEncounters(): void
    {
        try {
            $response = EHealth::patient()->getShortEncounters($this->uuid);
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
            $response = EHealth::patient()->getClinicalImpressions($this->uuid);
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
            $this->clinicalImpressions = $validatedData;
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
            $response = EHealth::patient()->getImmunizations($this->uuid);
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
            $this->immunizations = $validatedData;
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

    /**
     * Get patient diagnoses.
     *
     * @return void
     */
    public function getDiagnoses(): void
    {
        try {
            $response = EHealth::patient()->getActiveDiagnoses($this->uuid);

            $this->diagnoses = $response->getData();
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting active diagnoses');

            return;
        }
    }

    /**
     * Get patient observations.
     *
     * @return void
     */
    public function getObservations(): void
    {
        try {
            $response = EHealth::patient()->getObservations($this->uuid);

            $this->observations = $response->getData();
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting observations');

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.person.records.summary');
    }
}
