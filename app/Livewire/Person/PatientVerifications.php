<?php

declare(strict_types=1);

namespace App\Livewire\Person;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class PatientVerifications extends Component
{
    public array $searchForm = [
        'employee_id' => '',
        'verification_status' => '',
        'status' => ''
    ];

    public bool $hasResults = false;
    public bool $isSync = false;
    public string $syncStatus = '';

    public function search(): void
    {
        $this->hasResults = true;
    }

    public function resetFilters(): void
    {
        $this->reset('searchForm');
        $this->hasResults = false;
    }

    public function sync(): void
    {

    }

    public function render(): View
    {
        return view('livewire.person.patient-verifications');
    }
}
