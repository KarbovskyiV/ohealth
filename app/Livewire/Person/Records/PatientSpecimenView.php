<?php

declare(strict_types=1);

namespace App\Livewire\Person\Records;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\Specimen;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Repositories\MedicalEvents\Repository;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Throwable;

class PatientSpecimenView extends BasePatientComponent
{
    /**
     * ID of the specimen being displayed.
     *
     * @var int
     */
    #[Locked]
    public int $specimenId;

    /**
     * eHealth ID of the specimen, kept so that a refresh does not have to read the record to find it.
     *
     * @var string
     */
    #[Locked]
    public string $specimenUuid;

    /**
     * Request-scoped memoized specimen.
     *
     * @var Specimen|null
     */
    private ?Specimen $specimenModel = null;

    protected array $dictionaryNames = [
        'specimen_types',
        'specimen_conditions',
        'specimen_invalidate_reasons',
        'specimen_collection_methods',
        'specimen_container_types',
        'specimen_container_additives',
        'fasting_statuses',
        'eHealth/body_sites'
    ];

    /**
     * Bind the route models and load the specimen being displayed.
     *
     * @param  LegalEntity  $legalEntity
     * @param  Person|null  $person
     * @param  Preperson|null  $preperson
     * @param  Specimen|null  $specimen
     * @return void
     */
    public function mount(
        LegalEntity $legalEntity,
        ?Person $person = null,
        ?Preperson $preperson = null,
        ?Specimen $specimen = null
    ): void {
        parent::mount($legalEntity, $person, $preperson);

        $this->getDictionary();

        $this->specimenId = $specimen->id;
        $this->specimenUuid = $specimen->uuid;

        $this->specimen();
    }

    /**
     * Resolve the specimen being displayed, scoped to the patient so that a specimen belonging to somebody else
     * is not reachable by its ID. Loaded again on later requests, where Livewire hydrates without mount().
     *
     * @return Specimen
     */
    protected function specimen(): Specimen
    {
        return $this->specimenModel ??= Specimen::forPatient($this->patient())
            ->withAllRelations()
            ->whereId($this->specimenId)
            ->firstOrFail();
    }

    /**
     * Refresh the specimen from eHealth, so that the page shows the record as it stands there now.
     *
     * @return void
     */
    public function sync(): void
    {
        try {
            $response = EHealth::specimen()->getDetails($this->uuid, $this->specimenUuid);
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error while synchronizing the specimen');

            return;
        }

        try {
            Repository::specimen()->sync($this->patient(), [$response->validate()]);
        } catch (Throwable $exception) {
            $this->handleDatabaseErrors($exception, 'Error while synchronizing the specimen');

            return;
        }

        // Drop the memoized model so that the page renders what has just been stored
        $this->specimenModel = null;

        Session::flash('success', __('specimens.messages.record_synced_successfully'));
    }

    /**
     * Render the component.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.person.records.patient-specimen-view')->with([
            'specimen' => Arr::toCamelCase($this->specimen()->toArray())
        ]);
    }
}
