<?php

declare(strict_types=1);

namespace App\Rules\MedicalEvents;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Checks that an observation carries a value or at least one filled component, the same fields the observation
 * mapper builds the eHealth value from, as eHealth rejects an observation that has neither.
 */
class ObservationValue implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  Closure  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            return;
        }

        $hasValue = ($value['valueQuantityValue'] ?? '') !== ''
            || filled($value['valueCodeableConcept'] ?? null)
            || filled($value['valueSampledData'] ?? null)
            || filled($value['valueString'] ?? null)
            || isset($value['valueBoolean'])
            || filled($value['valueTime'] ?? null);

        $hasComponent = collect($value['components'] ?? [])->contains(
            static fn (array $component): bool => filled($component['valueCode'] ?? null)
                || ($component['valueQuantityValue'] ?? '') !== ''
        );

        if (!$hasValue && !$hasComponent) {
            $fail(__('observations.validation.value_required', [
                'observation' => __('observations.position', ['position' => (int) last(explode('.', $attribute)) + 1])
            ]));
        }
    }
}
