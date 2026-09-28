<?php

declare(strict_types=1);

namespace App\Livewire\Specimen;

use App\Enums\Specimen\Status;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\Specimen;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Services\MedicalEvents\Fhir;

class SpecimenEdit extends SpecimenComponent
{
    public function mount(
        LegalEntity $legalEntity,
        ?Person $person = null,
        ?Preperson $preperson = null,
        ?int $specimenId = null
    ): void {
        parent::mount($legalEntity, $person, $preperson);

        $specimen = Specimen::withAllRelations()
            ->whereKey($specimenId)
            ->forPatient($this->patient())
            ->whereStatus(Status::DRAFT)
            ->firstOrFail();

        $this->specimenId = $specimen->uuid;

        $specimenData = Fhir::specimen()->fromFhir($specimen->toArray());

        // The collector the specimen is registered by is picked as the current employee
        if ($specimenData['collectorId'] === $specimenData['registeredById']) {
            $specimenData['collectorType'] = 'current';
        }

        $this->form->specimen = $specimenData;
    }
}
