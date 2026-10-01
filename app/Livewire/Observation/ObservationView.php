<?php

declare(strict_types=1);

namespace App\Livewire\Observation;

use App\Livewire\Person\Records\BasePatientComponent;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\Observation;
use App\Models\Person\Person;
use App\Models\Preperson;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

class ObservationView extends BasePatientComponent
{
    #[Locked]
    public int $observationId;

    protected array $dictionaryNames = [
        'eHealth/cancellation_reasons',
        'eHealth/observation_categories',
        'eHealth/ICF/observation_categories',
        'eHealth/LOINC/observation_codes',
        'eHealth/custom/observation_codes',
        'eHealth/ICF/classifiers',
        'eHealth/observation_methods',
        'eHealth/observation_interpretations',
        'eHealth/body_sites',
        'eHealth/ucum/units',
        'eHealth/report_origins',
    ];

    public function mount(
        LegalEntity $legalEntity,
        ?Person $person = null,
        ?Preperson $preperson = null,
        ?int $observationId = null
    ): void {
        parent::mount($legalEntity, $person, $preperson);
        $this->getDictionary();
        $this->observationId = (int) $observationId;
    }

    public function render(): View
    {
        $observation = Observation::forPatient($this->patient())
            ->withAllRelations()
            ->whereId($this->observationId)
            ->firstOrFail();

        return view('livewire.observation.observation-view', compact('observation'));
    }
}
