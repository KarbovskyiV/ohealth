<?php

declare(strict_types=1);

namespace App\Livewire\Person\Records;

use App\Models\MedicalEvents\Sql\ClinicalImpression;
use Illuminate\Contracts\View\View;

class PatientClinicalImpressions extends BasePatientComponent
{
    public string $filterCode = '';

    public string $filterEcozId = '';

    public string $filterMedicalRecordId = '';

    public string $filterStartRange = '';

    public string $filterEndRange = '';

    public string $filterStatus = '';

    public string $filterDoctor = '';

    public bool $showAdditionalParams = false;

    public array $clinicalImpressions = [];

    protected array $dictionaryNames = [
    ];

    protected function initializeComponent(): void
    {
    }

    public function search(): void
    {
    }

    public function resetFilters(): void
    {
        $this->reset([
            'filterCode',
            'filterEcozId',
            'filterMedicalRecordId',
            'filterStartRange',
            'filterEndRange',
            'filterStatus',
            'filterDoctor',
        ]);

        $this->getClinicalImpressions();
    }

    public function syncClinicalImpressions(): void
    {
    }

    public function getClinicalImpressions(): void
    {
    }

    public function render(): View
    {
        return view('livewire.person.records.clinical-impressions');
    }
}
