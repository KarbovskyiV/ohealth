<?php

declare(strict_types=1);

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

class DocumentIssuedAfterBirthDate implements ValidationRule
{
    public function __construct(private readonly mixed $birthDate)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '' || !is_string($this->birthDate) || $this->birthDate === '') {
            return;
        }

        $format = (string) config('app.date_format');

        try {
            $issuedDate = Carbon::createFromFormat('!' . $format, $value);
            $birthDate = Carbon::createFromFormat('!' . $format, $this->birthDate);
        } catch (Throwable) {
            return;
        }

        // DateFormat validates malformed dates; only compare complete dates in the configured format.
        if (
            $issuedDate === false || $birthDate === false
            || $issuedDate->format($format) !== $value || $birthDate->format($format) !== $this->birthDate
        ) {
            return;
        }

        if ($issuedDate->lt($birthDate)) {
            $fail(__('validation.custom.employee.document_issued_before_birth'));
        }
    }
}
