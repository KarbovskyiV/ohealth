<?php

declare(strict_types=1);

namespace App\Livewire\Person\Records;

use App\Repositories\MedicalEvents\MedicationRequestRepository;
use Illuminate\Contracts\View\View;

class PatientMedicationRequests extends BasePatientComponent
{
    /** @var list<array<string, mixed>> */
    public array $medicationRequests = [];

    public string $filterStatus = '';

    public string $filterStartedAtRange = '';

    public string $filterEndedAtRange = '';

    public string $filterRequestNumber = '';

    public string $filterMedication = '';

    public string $filterInteractionId = '';

    public string $filterCarePlanId = '';

    public string $filterDoctor = '';

    public string $filterEpisodeId = '';

    public string $filterLegalEntity = '';

    public string $filterMedicalProgram = '';

    public string $filterCreatedAtRange = '';

    public string $filterDispenseAvailableFromRange = '';

    public string $filterDispenseAvailableToRange = '';

    public bool $showAdditionalParams = false;

    protected function initializeComponent(): void
    {
        $this->loadMedicationRequests();
    }

    public function loadMedicationRequests(): void
    {
        if ($this->personId === null) {
            $this->medicationRequests = [];

            return;
        }

        $startedAtFrom = $startedAtTo = null;
        if (!empty($this->filterStartedAtRange)) {
            $parts = array_map('trim', explode('—', $this->filterStartedAtRange));
            $startedAtFrom = convertToYmd($parts[0] ?? '') ?: null;
            $startedAtTo = convertToYmd($parts[1] ?? ($parts[0] ?? '')) ?: null;
        }
        $endedAtFrom = $endedAtTo = null;
        if (!empty($this->filterEndedAtRange)) {
            $parts = array_map('trim', explode('—', $this->filterEndedAtRange));
            $endedAtFrom = convertToYmd($parts[0] ?? '') ?: null;
            $endedAtTo = convertToYmd($parts[1] ?? ($parts[0] ?? '')) ?: null;
        }
        $createdAtFrom = $createdAtTo = null;
        if (!empty($this->filterCreatedAtRange)) {
            $parts = array_map('trim', explode('—', $this->filterCreatedAtRange));
            $createdAtFrom = convertToYmd($parts[0] ?? '') ?: null;
            $createdAtTo = convertToYmd($parts[1] ?? ($parts[0] ?? '')) ?: null;
        }
        $dispenseStartFrom = $dispenseStartTo = null;
        if (!empty($this->filterDispenseAvailableFromRange)) {
            $parts = array_map('trim', explode('—', $this->filterDispenseAvailableFromRange));
            $dispenseStartFrom = convertToYmd($parts[0] ?? '') ?: null;
            $dispenseStartTo = convertToYmd($parts[1] ?? ($parts[0] ?? '')) ?: null;
        }
        $dispenseEndFrom = $dispenseEndTo = null;
        if (!empty($this->filterDispenseAvailableToRange)) {
            $parts = array_map('trim', explode('—', $this->filterDispenseAvailableToRange));
            $dispenseEndFrom = convertToYmd($parts[0] ?? '') ?: null;
            $dispenseEndTo = convertToYmd($parts[1] ?? ($parts[0] ?? '')) ?: null;
        }

        $this->medicationRequests = app(MedicationRequestRepository::class)->searchByPersonId(
            $this->personId,
            [
                'status' => $this->filterStatus !== '' ? $this->filterStatus : null,
                'started_at_from' => $startedAtFrom,
                'started_at_to' => $startedAtTo,
                'ended_at_from' => $endedAtFrom,
                'ended_at_to' => $endedAtTo,
            ]
        );
    }

    public function applyFilters(): void
    {
        $this->loadMedicationRequests();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'filterStatus',
            'filterStartedAtRange',
            'filterEndedAtRange',
            'filterRequestNumber',
            'filterMedication',
            'filterInteractionId',
            'filterCarePlanId',
            'filterDoctor',
            'filterEpisodeId',
            'filterLegalEntity',
            'filterMedicalProgram',
            'filterCreatedAtRange',
            'filterDispenseAvailableFromRange',
            'filterDispenseAvailableToRange',
        ]);
        $this->loadMedicationRequests();
    }

    public function render(): View
    {
        return view('livewire.person.records.medication-requests');
    }
}
