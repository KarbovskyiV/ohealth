<?php

declare(strict_types=1);

namespace App\Livewire\Specimen\Forms;

use App\Core\BaseForm;
use App\Rules\InDictionary;
use App\Rules\PastDateTime;
use Carbon\CarbonImmutable;
use Closure;

class SpecimenActionForm extends BaseForm
{
    public string $receivedDate = '';

    public string $receivedTime = '';

    public string $invalidateReason = '';

    public string $rejectReason = '';

    /**
     * Rules for the time the specimen was received for processing.
     *
     * @param  string|null  $collectedAt  ISO 8601 time the specimen was collected
     * @return array
     */
    public function rulesForProcessing(?string $collectedAt): array
    {
        return [
            'receivedDate' => ['required', 'date', 'before_or_equal:today'],
            'receivedTime' => [
                'required',
                'date_format:H:i',
                new PastDateTime($this->receivedDate),
                $this->validateReceivedAfterCollected($collectedAt)
            ]
        ];
    }

    /**
     * Rules for the reason the specimen is marked as unavailable.
     *
     * @return array
     */
    public function rulesForInvalidating(): array
    {
        return [
            'invalidateReason' => ['required', new InDictionary('specimen_invalidate_reasons')]
        ];
    }

    /**
     * Rules for the reason the specimen is marked as rejected.
     *
     * @return array
     */
    public function rulesForRejecting(): array
    {
        return [
            'rejectReason' => ['required', new InDictionary('specimen_reject_reasons')]
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
            'receivedDate' => __('specimens.date_time_received'),
            'receivedTime' => __('specimens.date_time_received'),
            'invalidateReason' => __('specimens.invalidate_reason'),
            'rejectReason' => __('specimens.reject_reason')
        ];
    }

    /**
     * Validate that the specimen is not received before it was collected.
     *
     * @param  string|null  $collectedAt  ISO 8601 time the specimen was collected
     * @return Closure
     */
    protected function validateReceivedAfterCollected(?string $collectedAt): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($collectedAt): void {
            if (empty($collectedAt) || empty($this->receivedDate)) {
                return;
            }

            $receivedAt = CarbonImmutable::createFromFormat(
                config('app.date_format') . ' H:i',
                $this->receivedDate . ' ' . $value
            );

            $collectedAtLocal = CarbonImmutable::parse($collectedAt)->setTimezone(config('app.timezone'));

            if ($receivedAt->lt($collectedAtLocal)) {
                $fail(__('validation.after_or_equal', ['date' => $collectedAtLocal->format('d.m.Y H:i')]));
            }
        };
    }
}
