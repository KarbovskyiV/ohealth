<?php

declare(strict_types=1);

namespace App\Livewire\Specimen\Forms;

use App\Core\BaseForm;
use App\Rules\InDictionary;

class SpecimenCancellationForm extends BaseForm
{
    public string $cancellationReason = '';

    /**
     * Rules for the reason the specimen is marked as entered in error.
     *
     * @return array
     */
    public function cancellationRules(): array
    {
        return [
            'cancellationReason' => ['required', new InDictionary('specimen_cancel_reasons')]
        ];
    }

    /**
     * Human-readable names of the validated fields.
     *
     * @return array
     */
    public function validationAttributes(): array
    {
        return [
            'cancellationReason' => __('medical-events.cancel_modal.reason_label')
        ];
    }

    /**
     * Rules applied by a plain validate() call.
     *
     * @return array
     */
    protected function rules(): array
    {
        return $this->cancellationRules();
    }
}
