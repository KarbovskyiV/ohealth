<?php

declare(strict_types=1);

namespace App\Livewire\Person\Records;

class PatientSpecimens extends BasePatientComponent
{
    public string $filterStatus = '';
    public string $filterSpecimenType = '';
    public string $filterDateRange = '';
    public string $filterEmployee = '';
    public string $filterContainerId = '';
    public string $filterContainerType = '';
    public string $filterParentSpecimen = '';
    public string $filterElectronicReferral = '';
    public string $filterEncounter = '';
    public bool $showAdditionalParams = false;

    public function render()
    {
        return view('livewire.person.records.patient-specimens');
    }
}