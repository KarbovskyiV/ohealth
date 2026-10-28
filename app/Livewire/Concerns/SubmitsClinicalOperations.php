<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Jobs\RemoteEHealthLinksProcessing;
use App\Models\CarePlan;
use App\Models\CarePlanActivity;
use App\Models\MedicalEvents\Sql\ServiceRequestRequest;
use Illuminate\Support\Facades\Session;

trait SubmitsClinicalOperations
{
    protected function submitClinicalOperation(CarePlan|CarePlanActivity|ServiceRequestRequest $record, array $changes, callable $send, ?string $requiredScope = null): ?array
    {
        try {
            $result = RemoteEHealthLinksProcessing::submitOperation($record, $changes, $send, $requiredScope);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            if (!array_key_exists('submission', $exception->errors())) {
                throw $exception;
            }
            Session::flash('warning', __('care-plan.submission_pending'));
            $this->showSignatureModal = false;

            return null;
        }
        if ($result === null) {
            Session::flash('info', __('care-plan.submission_pending'));
            $this->showSignatureModal = false;
        }

        return $result;
    }
}
