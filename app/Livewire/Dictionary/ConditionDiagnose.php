<?php

declare(strict_types=1);

namespace App\Livewire\Dictionary;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;
use App\Models\Icd10;
use App\Models\LegalEntity;
use App\Traits\FormTrait;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ConditionDiagnose extends Component
{
    use FormTrait;

    /**
     * List of available diagnose groups.
     *
     * @var array
     */
    public array $diagnoseGroups;

    /**
     * UUID of selected diagnose group for getting details.
     *
     * @var string
     */
    public string $selectedDiagnoseGroup = '';

    public function mount(LegalEntity $legalEntity): void
    {
        $this->diagnoseGroups = dictionary()->diagnoseGroups()->toArray();
    }

    public function search(): void
    {
        try {
            $this->validate(['selectedDiagnoseGroup' => 'required']);
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }
    }

    #[Computed]
    public function diagnoseDetails(): array
    {
        if (empty($this->selectedDiagnoseGroup)) {
            return [];
        }

        try {
            $details = EHealth::diagnoseGroup()->getDetails($this->selectedDiagnoseGroup)->getData();
            $codes = collect($details['diagnoses_group_codes']);

            // A group holds either ICD-10 or ICPC-2 codes, and the same code means a different diagnosis in each
            $descriptions = [
                'eHealth/ICD10_AM/condition_codes' => Icd10::whereIn(
                    'code',
                    $codes->where('system', 'eHealth/ICD10_AM/condition_codes')->pluck('code')
                )->pluck('description', 'code'),
                'eHealth/ICPC2/condition_codes' => dictionary()->basics()
                    ->byName('eHealth/ICPC2/condition_codes', false)
                    ->asCodeDescription()
            ];

            foreach ($details['diagnoses_group_codes'] as &$codeItem) {
                $codeItem['description'] = $descriptions[$codeItem['system']][$codeItem['code']] ?? '';
            }

            return $details;
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when searching for group of diagnoses details.');

            return [];
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['selectedDiagnoseGroup']);
    }

    public function render(): View
    {
        return view('livewire.dictionary.condition-diagnose', [
            'diagnoseDetails' => $this->diagnoseDetails
        ]);
    }
}
