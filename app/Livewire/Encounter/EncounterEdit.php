<?php

declare(strict_types=1);

namespace App\Livewire\Encounter;

use App\Classes\eHealth\Api\PatientApi;
use App\Classes\eHealth\Exceptions\ApiException;
use App\Core\Arr;
use App\Models\LegalEntity;
use App\Repositories\MedicalEvents\Repository;
use App\Services\MedicalEvents\Mappers\ConditionMapper;
use App\Services\MedicalEvents\Mappers\EncounterMapper;
use App\Services\MedicalEvents\Mappers\EpisodeMapper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Throwable;

class EncounterEdit extends EncounterComponent
{
    #[Locked]
    public int $encounterId;

    public function mount(LegalEntity $legalEntity, int $personId, int $encounterId): void
    {
        $this->initializeComponent($personId);
        $this->encounterId = $encounterId;

        $encounter = Repository::encounter()->get($this->encounterId);

        if (!$encounter) {
            abort(404);
        }

        $this->form->encounter = app(EncounterMapper::class)->fromFhir($encounter);

        $episodeUuid = data_get($encounter, 'episode.identifier.value', '');
        $this->form->episode['id'] = $episodeUuid;

        $this->form->episode = $this->getEpisode();
        $this->form->episode['id'] = $episodeUuid;

        $this->form->episode = app(EpisodeMapper::class)->fromFhir($this->form->episode);

        $this->form->conditions = Repository::condition()->get($this->encounterId);
        $this->form->conditions = Repository::condition()->formatForView(
            $this->form->conditions,
            $this->form->encounter['diagnoses']
        );

        $mapper = app(ConditionMapper::class);
        $this->form->conditions = collect($this->form->conditions)
            ->map(fn (array $fhir) => $mapper->fromFhir($fhir))
            ->toArray();

        $this->form->immunizations = Repository::immunization()->get($this->encounterId);
        $this->form->immunizations = Repository::immunization()->formatForView($this->form->immunizations);

        $this->form->diagnosticReports = Repository::diagnosticReport()->get($this->encounterId);
        $this->form->diagnosticReports = Repository::diagnosticReport()->formatForView($this->form->diagnosticReports);

        $this->form->observations = Repository::observation()->get($this->encounterId);
        $this->form->observations = Repository::observation()->formatForView($this->form->observations);

        $this->form->procedures = Repository::procedure()->get($this->encounterId);
        $this->form->procedures = Repository::procedure()->formatForView($this->form->procedures);

        $this->form->clinicalImpressions = Repository::clinicalImpression()->get($this->encounterId);

        $this->setDefaultDate();
    }

    /**
     * Validate and save data.
     *
     * @return void
     * @throws Throwable
     */
    public function save(): void
    {
        try {
            $this->form->validateForm('encounter', $this->form->encounter);
            $this->form->validateForm('episode', $this->form->episode);
            $this->form->validateForm('conditions', $this->form->conditions);
            $this->form->validateForm('immunizations', $this->form->immunizations);
        } catch (ValidationException $exception) {
            session()?->flash('error', $exception->validator->errors()->first());

            return;
        }

        $uuids = [
            'encounter' => $this->form->encounter['uuid'] ?? (string) Str::uuid(),
            'visit' => data_get($this->form->encounter, 'visit.identifier.value', (string) Str::uuid()),
            'employee' => Auth::user()->getEncounterWriterEmployee()->uuid,
            'episode' => $this->form->episode['id'] ?? '',
        ];

        $mapper = app(ConditionMapper::class);
        $fhirConditions = collect($this->form->conditions)
            ->map(fn (array $c) => $mapper->toFhir($c, $uuids))
            ->toArray();

        $formattedEncounter = app(EncounterMapper::class)->toFhir(
            $this->form->encounter,
            $fhirConditions,
            $uuids
        );

        $createdEncounterId = $encounterRepository->store($formattedEncounter, $this->personId);
        Repository::condition()->store($fhirConditions, $createdEncounterId);
    }

    /**
     * Retrieve the episode from the database, if not found, retrieve it from the API, save it to the database, and set it to the form.
     *
     * @return array
     */
    private function getEpisode(): array
    {
        $episode = Repository::episode()->get($this->encounterId);

        if ($episode) {
            return $episode;
        }

        try {
            $episodeData = PatientApi::getEpisodeById(
                $this->patientUuid,
                $this->form->episode['id']
            );

            Repository::episode()->store(Arr::toCamelCase($episodeData), $this->personId, $this->encounterId);

            return Repository::episode()->get($this->encounterId);
        } catch (ApiException|Throwable) {
            session()?->flash('error', __('messages.database_error'));

            return [];
        }
    }

    /**
     * Set default encounter period date.
     *
     * @return void
     */
    private function setDefaultDate(): void
    {
        $this->form->encounter['period'] = [
            'date' => CarbonImmutable::parse($this->form->encounter['period']['start'])->format('Y-m-d'),
            'start' => CarbonImmutable::parse($this->form->encounter['period']['start'])->format('H:i'),
            'end' => CarbonImmutable::parse($this->form->encounter['period']['end'])->format('H:i')
        ];
    }
}
