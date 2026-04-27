<?php

declare(strict_types=1);

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

readonly class PastDateTime implements ValidationRule
{
    public function __construct(private string $date)
    {
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($this->date) || empty($value)) {
            return;
        }

        $datetime = Carbon::createFromFormat('Y-m-d H:i', $this->date . ' ' . $value);

        if ($datetime->isFuture()) {
            $fail(__('validation.before_or_equal', ['date' => __('validation.attributes.now')]));
        }
    }
}
