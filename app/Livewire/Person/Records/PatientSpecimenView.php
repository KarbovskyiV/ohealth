<?php

declare(strict_types=1);

namespace App\Livewire\Person\Records;

use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Preperson;
use Illuminate\Contracts\View\View;

class PatientSpecimenView extends BasePatientComponent
{
    public string $specimenId;

    public function mount(LegalEntity $legalEntity, ?Person $person = null, ?Preperson $preperson = null, ?string $specimenId = null): void
    {
        parent::mount($legalEntity, $person, $preperson);
        $this->specimenId = $specimenId ?? '';
    }

    public function render(): View
    {
        return view('livewire.person.records.patient-specimen-view');
    }
}

