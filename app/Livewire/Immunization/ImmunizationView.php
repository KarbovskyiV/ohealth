<?php

declare(strict_types=1);

namespace App\Livewire\Immunization;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;
use App\Livewire\Person\Records\BasePatientComponent;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\Immunization;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Repositories\MedicalEvents\Repository;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Throwable;

class ImmunizationView extends BasePatientComponent
{
    /**
     * ID of the immunization being displayed.
     *
     * @var int
     */
    #[Locked]
    public int $immunizationId;

    /**
     * eHealth ID of the immunization, kept so that a refresh does not have to read the record to find it.
     *
     * @var string
     */
    #[Locked]
    public string $immunizationUuid;

    /**
     * Request-scoped memoized immunization.
     *
     * @var Immunization|null
     */
    private ?Immunization $immunizationModel = null;

    protected array $dictionaryNames = [
        'eHealth/vaccine_codes',
        'eHealth/vaccination_routes',
        'eHealth/immunization_body_sites',
        'eHealth/immunization_report_origins',
        'eHealth/reason_explanations',
        'eHealth/reason_not_given_explanations',
        'eHealth/immunization_dosage_units',
        'eHealth/vaccination_authorities',
        'eHealth/vaccination_target_diseases'
    ];

    /**
     * Bind the route models and load the immunization being displayed.
     *
     * @param  LegalEntity  $legalEntity
     * @param  Person|null  $person
     * @param  Preperson|null  $preperson
     * @param  Immunization|null  $immunization
     * @return void
     */
    public function mount(
        LegalEntity $legalEntity,
        ?Person $person = null,
        ?Preperson $preperson = null,
        ?Immunization $immunization = null
    ): void {
        parent::mount($legalEntity, $person, $preperson);

        $this->getDictionary();

        $this->immunizationId = $immunization->id;
        $this->immunizationUuid = $immunization->uuid;

        $this->immunization();
    }

    /**
     * Refresh the immunization from eHealth, so that the page shows the record as it stands there now.
     *
     * @return void
     */
    public function sync(): void
    {
        try {
            $response = EHealth::immunization()->getById($this->uuid, $this->immunizationUuid);
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error while synchronizing the immunization');

            return;
        }

        try {
            Repository::immunization()->sync($this->patient(), $response->validate());
        } catch (Throwable $exception) {
            $this->handleDatabaseErrors($exception, 'Error while synchronizing the immunization');

            return;
        }

        // Drop the memoized model so that the page renders what has just been stored
        $this->immunizationModel = null;

        Session::flash('success', __('immunizations.messages.record_synced_successfully'));
    }

    /**
     * Resolve the immunization being displayed, scoped to the patient so that a record belonging to somebody else is
     * not reachable by its ID. Loaded again on later requests, where Livewire hydrates without mount().
     *
     * @return Immunization
     */
    protected function immunization(): Immunization
    {
        return $this->immunizationModel ??= Immunization::forPatient($this->patient())
            ->withAllRelations()
            ->whereId($this->immunizationId)
            ->firstOrFail();
    }

    public function render(): View
    {
        return view('livewire.immunization.immunization-view')->with([
            'immunization' => $this->immunization()
        ]);
    }
}
