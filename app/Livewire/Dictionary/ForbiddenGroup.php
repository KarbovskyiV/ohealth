<?php

declare(strict_types=1);

namespace App\Livewire\Dictionary;

use App\Classes\eHealth\EHealth;
use App\Models\Icd10;
use App\Models\LegalEntity;
use App\Traits\FormTrait;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ForbiddenGroup extends Component
{
    use FormTrait;

    /**
     * List of available forbidden groups.
     *
     * @var array
     */
    public array $forbiddenGroups;

    /**
     * UUID of selected forbidden group for getting details.
     *
     * @var string
     */
    public string $selectedForbiddenGroup = '';

    public function mount(LegalEntity $legalEntity): void
    {
        $this->forbiddenGroups = dictionary()->forbiddenGroups()->toArray();
    }

    public function search(): void
    {
        try {
            $this->validate(['selectedForbiddenGroup' => 'required']);
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }
    }

    #[Computed]
    public function forbiddenDetails(): array
    {
        if (empty($this->selectedForbiddenGroup)) {
            return [];
        }

        try {
            $details = EHealth::forbiddenGroup()->getDetails($this->selectedForbiddenGroup)->getData();

            // The same code means a different diagnosis in ICD-10 and ICPC-2, while the ICPC-2 reasons share its codes
            $icd10Descriptions = Icd10::whereIn(
                'code',
                collect($details['forbidden_group_codes'])->where('system', 'eHealth/ICD10_AM/condition_codes')->pluck('code')
            )->pluck('description', 'code');
            $icpc2Descriptions = dictionary()->basics()
                ->byName('eHealth/ICPC2/condition_codes', false)
                ->asCodeDescription();

            foreach ($details['forbidden_group_codes'] as &$codeItem) {
                $codeItem['description'] = $codeItem['system'] === 'eHealth/ICD10_AM/condition_codes'
                    ? $icd10Descriptions->get($codeItem['code'], '')
                    : $icpc2Descriptions[$codeItem['code']];
            }

            unset($codeItem);

            // A forbidden entry is either a service or a service group, and it may be inactive in the catalog already
            $catalogNames = $this->catalogNames(dictionary()->services()->all());

            foreach ($details['forbidden_group_services'] as &$serviceItem) {
                $serviceItem['name'] = $catalogNames[$serviceItem['service_id'] ?? $serviceItem['service_group_id']];
            }

            return $details;

        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when searching for forbidden group details.');

            return [];
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['selectedForbiddenGroup']);
    }

    /**
     * Map the id of every service and service group in the catalog to its name, inactive ones included.
     *
     * @param  array  $items
     * @return array
     */
    private function catalogNames(array $items): array
    {
        $names = [];

        foreach ($items as $item) {
            $names[$item['id']] = $item['name'];
            $names += $this->catalogNames([...$item['groups'] ?? [], ...$item['services'] ?? []]);
        }

        return $names;
    }

    public function render(): View
    {
        return view('livewire.dictionary.forbidden-group', [
            'forbiddenDetails' => $this->forbiddenDetails
        ]);
    }
}
