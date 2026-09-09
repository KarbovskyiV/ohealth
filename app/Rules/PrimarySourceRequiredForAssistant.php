<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * An assistant records only what they observed themselves, so every record of theirs comes from the primary
 * source. Immunizations are the exception and carry this rule nowhere.
 */
class PrimarySourceRequiredForAssistant implements ValidationRule
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
        if ($value === true || !Auth::user()->isAssistantOnly()) {
            return;
        }

        $fail(__('medical-events.validation.primary_source_required_for_assistant'));
    }
}
