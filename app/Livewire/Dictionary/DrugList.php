<?php

declare(strict_types=1);

namespace App\Livewire\Dictionary;

use App\Models\LegalEntity;
use Illuminate\View\View;
use Livewire\Component;

class DrugList extends Component
{
    /**
     * List of programs for choosing 'medical_program_id'
     *
     * @var array
     */
    public array $programs;

    public string $search = '';

    public string $inn = '';

    public string $atcCode = '';

    public string $dosageForm = '';

    public string $prescriptionFormType = '';

    public function mount(LegalEntity $legalEntity): void
    {
        $this->programs = dictionary()->medicalPrograms()->toArray();
    }

    public function search(): void
    {
        // TODO: implement search logic
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'inn', 'atcCode', 'dosageForm', 'prescriptionFormType']);
    }

    public function render(): View
    {
        return view('livewire.dictionary.drug-list');
    }
}
