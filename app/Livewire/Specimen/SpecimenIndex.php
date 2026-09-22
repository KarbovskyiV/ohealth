<?php

declare(strict_types=1);

namespace App\Livewire\Specimen;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;
use App\Models\MedicalEvents\Sql\Specimen;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Repositories\MedicalEvents\Repository;
use App\Rules\InDictionary;
use App\Services\MedicalEvents\Fhir;
use App\Traits\FormTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

class SpecimenIndex extends Component
{
    use FormTrait;

    public string $searchId = '';

    /**
     * Specimen found by its accession identifier.
     *
     * @var array
     */
    public array $specimen = [];

    protected array $dictionaryNames = [
        'specimen_types',
        'specimen_container_types',
        'specimen_invalidate_reasons',
        'specimen_reject_reasons'
    ];

    public bool $showReceivedForResearchModal = false;

    public bool $showInvalidateModal = false;

    public bool $showRejectModal = false;

    public ?string $receivedForResearchDate = null;

    public ?string $receivedForResearchTime = null;

    public ?string $invalidateReason = null;

    public ?string $rejectReason = null;

    /**
     * Component mount.
     */
    public function mount(): void
    {
        $this->getDictionary();
    }

    /**
     * Find a specimen by its accession identifier.
     *
     * @return void
     */
    public function search(): void
    {
        $this->validate(['searchId' => ['required', 'string', 'regex:/^\d{4}-\d{4}-\d{4}-\d{4}$/']]);

        $this->specimen = [];

        try {
            $response = EHealth::specimen()->getByAccessionIdentifier($this->searchId);
            $this->specimen = Arr::toCamelCase(
                $this->formatDatesForDisplay([$response->validate()], 'd.m.Y H:i')[0]
            );
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error while searching specimen by accession identifier');
        }
    }

    /**
     * Open the page of the found specimen under its patient, storing the specimen first when it is not in the database yet.
     * The page lives under the patient, so a specimen of a patient unknown to the system cannot be opened.
     *
     * @return void
     */
    public function view(): void
    {
        $patientId = data_get($this->specimen, 'subject.identifier.value');
        $specimenId = data_get($this->specimen, 'uuid');

        $patient = Person::whereUuid($patientId)->first() ?? Preperson::whereUuid($patientId)->first();

        if ($patient === null) {
            Session::flash('error', __('specimens.messages.patient_not_found'));

            return;
        }

        $specimen = Specimen::forPatient($patient)->whereUuid($specimenId)->first()
            ?? $this->storeFoundSpecimen($patient, $specimenId);

        if ($specimen === null) {
            return;
        }

        if ($patient instanceof Preperson) {
            $this->redirectRoute(
                'prepersons.specimens.view',
                [legalEntity(), 'preperson' => $patient->id, 'specimen' => $specimen->id],
                navigate: true
            );

            return;
        }

        $this->redirectRoute(
            'persons.specimens.view',
            [legalEntity(), 'person' => $patient->id, 'specimen' => $specimen->id],
            navigate: true
        );
    }

    /**
     * Store the found specimen under its patient, so that it has a page to open.
     *
     * @param  Person|Preperson  $patient
     * @param  string  $specimenId
     * @return Specimen|null
     */
    protected function storeFoundSpecimen(Person|Preperson $patient, string $specimenId): ?Specimen
    {
        try {
            $response = EHealth::specimen()->getDetails($patient->uuid, $specimenId);
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error while loading the specimen');

            return null;
        }

        try {
            Repository::specimen()->sync($patient, [$response->validate()]);
        } catch (Throwable $exception) {
            $this->handleDatabaseErrors($exception, 'Error while storing the specimen');

            return null;
        }

        return Specimen::forPatient($patient)->whereUuid($specimenId)->first();
    }

    public function markReceivedForResearch(): void
    {
        // TODO: implement actual logic
        $this->showReceivedForResearchModal = false;
    }

    /**
     * Send the request to mark the selected specimen as unavailable.
     *
     * @return void
     */
    public function invalidate(): void
    {
        if (Auth::user()->cannot('invalidate', [Specimen::class, $this->specimen])) {
            Session::flash('error', __('specimens.policy.invalidate'));

            return;
        }

        $validated = $this->validate([
            'invalidateReason' => ['required', new InDictionary('specimen_invalidate_reasons')]
        ]);

        try {
            $response = EHealth::specimen()->invalidate(
                data_get($this->specimen, 'subject.identifier.value'),
                data_get($this->specimen, 'uuid'),
                Arr::toSnakeCase(Fhir::specimen()->toInvalidateFhir($validated))
            );
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error while invalidating the specimen');

            return;
        }

        logger()->debug('Job ID to further debug', $response->getData());

        $this->showInvalidateModal = false;
        Session::flash('success', __('specimens.messages.invalidate_request_sent'));
    }

    /**
     * Send the request to mark the selected specimen as rejected.
     *
     * @return void
     */
    public function reject(): void
    {
        if (Auth::user()->cannot('reject', [Specimen::class, $this->specimen])) {
            Session::flash('error', __('specimens.policy.reject'));

            return;
        }

        $validated = $this->validate(['rejectReason' => ['required', new InDictionary('specimen_reject_reasons')]]);

        try {
            $response = EHealth::specimen()->reject(
                data_get($this->specimen, 'subject.identifier.value'),
                data_get($this->specimen, 'uuid'),
                Arr::toSnakeCase(Fhir::specimen()->toRejectFhir($validated))
            );
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error while rejecting the specimen');

            return;
        }

        logger()->debug('Job ID to further debug', $response->getData());

        $this->showRejectModal = false;
        Session::flash('success', __('specimens.messages.reject_request_sent'));
    }

    /**
     * Human-readable names of the validated fields.
     *
     * @return array
     */
    public function validationAttributes(): array
    {
        return [
            'invalidateReason' => __('specimens.invalidate_reason'),
            'rejectReason' => __('specimens.reject_reason')
        ];
    }

    /**
     * Render the component.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.specimen.specimen-index');
    }
}
