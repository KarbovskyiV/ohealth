<?php

declare(strict_types=1);

namespace App\Livewire\Specimen;

use App\Livewire\Specimen\Forms\SpecimenForm;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SpecimenCreate extends Component
{
    public SpecimenForm $form;

    #[Locked]
    public ?int $personId = null;

    #[Locked]
    public ?int $prepersonId = null;

    public ?string $patientFullName = null;

    public array $specimens = [];
    public array $procedures = [];
    public array $employees = [];

    public function mount(): void
    {
    }

    public function save(): void
    {
    }

    public function render(): View
    {
        return view('livewire.specimen.specimen-create');
    }
}
