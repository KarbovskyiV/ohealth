<?php

declare(strict_types=1);

namespace App\Livewire\Person\Records;

use App\Repositories\CarePlanRepository;
use Illuminate\Contracts\View\View;

class PersonCarePlans extends BasePatientComponent
{
    public $carePlans = [];

    public string $filterName = '';
    public string $filterEncounterId = '';
    public string $filterStatus = '';
    public string $filterStartDateRange = '';
    public string $filterEndDateRange = '';
    public string $filterIsPartOf = '';
    public string $filterIncludes = '';
    public bool $showAdditionalParams = false;

    /**
     * Initialize component with care plans for the specific patient.
     */
    protected function initializeComponent(): void
    {
//        /** @var CarePlanRepository $repository */
//        $repository = app(CarePlanRepository::class);
//        $this->carePlans = $repository->getByPersonId($this->id);
        // Mock data
        $this->carePlans = collect([
            (object)[
                'id' => 1,
                'title' => 'План лікування носової кровотечі',
                'status' => 'active',
                'status_display' => 'Активний',
                'created_at' => \Carbon\Carbon::parse('2025-04-02'),
                'period_start' => \Carbon\Carbon::parse('2025-04-02'),
                'period_end' => \Carbon\Carbon::parse('2025-04-02'),
                'author' => (object)['party' => (object)['full_name' => 'Петров І.І.']],
                'intent' => 'plan',
                'category' => '123',
                'care_provision_conditions' => 'Амбулаторні умови',
                'medical_condition' => 'R04.0 - Носова кровотеча',
                'extended_description' => 'Розширений опис',
                'additional_info' => 'Допоміжна інформація',
                'notes' => 'Нотатки',
                'ehealth_id' => '1231-adsadas-aqeqe-casdda',
                'episode_id' => '1231-adsadas-aqeqe-casdda',
            ]
        ]);

        try {
            $basics = app(\App\Services\Dictionary\DictionaryManager::class)->basics();
            $this->dictionaries['care_plan_categories'] = $basics->byName('eHealth/care_plan_categories')
                ?->asCodeDescription()
                ?->toArray() ?? [];
        } catch (\Exception $exception) {
            \Illuminate\Support\Facades\Log::warning('PersonCarePlans: failed to load dictionaries: ' . $exception->getMessage());
        }
    }

    public function search(): void
    {
        // Mock search logic
    }

    public function resetFilters(): void
    {
        $this->reset([
            'filterName',
            'filterEncounterId',
            'filterStatus',
            'filterStartDateRange',
            'filterEndDateRange',
            'filterIsPartOf',
            'filterIncludes',
        ]);
    }

    public function render(): View
    {
        return view('livewire.person.records.care-plans');
    }
}
