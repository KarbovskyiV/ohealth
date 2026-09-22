<?php

declare(strict_types=1);

namespace App\Livewire\DiagnosticReport;

use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Models\MedicalEvents\Sql\DiagnosticReport;
use App\Models\MedicalEvents\Sql\ServiceRequestRequest;
use App\Enums\Person\ServiceRequestStatus;
use App\Repositories\MedicalEvents\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Throwable;

class DiagnosticReportCreate extends DiagnosticReportComponent
{
    public function mount(
        LegalEntity $legalEntity,
        ?Person $person = null,
        ?Preperson $preperson = null
    ): void {
        parent::mount($legalEntity, $person, $preperson);

        $this->loadAvailableReferrals();
    }

    protected function loadAvailableReferrals(): void
    {
        if ($this->referralsLoaded) {
            return;
        }

        if ($this->personId === null) {
            $this->referralsLoaded = true;

            return;
        }

        $services = collect($this->dictionaries['custom/services'] ?? []);
        $diagnosticReportCategories = array_keys($this->dictionaries['eHealth/diagnostic_report_categories'] ?? []);

        $this->availableReferrals = Repository::serviceRequest()
            ->getByPersonIdAndStatus(
                $this->personId,
                ServiceRequestStatus::PROCESSED->value,
                ['uuid', 'request_number', 'service_id', 'category']
            )
            ->map(static function (ServiceRequestRequest $referral) use ($services, $diagnosticReportCategories): array {
                $service = $services->firstWhere('id', $referral->serviceId);

                return [
                    'id' => $referral->uuid,
                    'requisition' => $referral->requestNumber ?: $referral->uuid,
                    'category' => $referral->category
                        ? __('care-plan.referral_category.'.$referral->category)
                        : __('encounters.electronic_referral'),
                    'service' => $service,
                    'isDiagnosticReportAllowed' => $service !== null
                        && in_array($service['category'] ?? null, $diagnosticReportCategories, true),
                ];
            })
            ->values()
            ->toArray();

        $this->referralsLoaded = true;
    }

    /**
     * Validate and save data.
     *
     * @param  array  $diagnosticReportData
     * @return void
     */
    public function save(array $diagnosticReportData): void
    {
        if (Auth::user()->cannot('create', DiagnosticReport::class)) {
            Session::flash('error', __('diagnostic-reports.policy.create'));

            return;
        }

        if (!Auth::user()->getDiagnosticReportWriterEmployee()) {
            Session::flash('error', __('diagnostic-reports.messages.writer_employee_not_found'));

            return;
        }

        parent::save($diagnosticReportData);
    }

    /**
     * Submit encrypted data.
     *
     * @return void
     */
    public function sign(): void
    {
        if (Auth::user()->cannot('create', DiagnosticReport::class)) {
            Session::flash('error', __('diagnostic-reports.policy.create'));

            return;
        }

        parent::sign();
    }

    /**
     * Store the formatted report and return its new identifier.
     *
     * @param  array  $formattedData
     * @return int
     * @throws Throwable
     */
    protected function persist(array $formattedData): int
    {
        return DB::transaction(function () use ($formattedData) {
            $diagnosticReportId = Repository::diagnosticReport()
                ->store([$formattedData['diagnosticReport']], $this->patient());

            if (isset($formattedData['observations'])) {
                Repository::observation()->store($formattedData['observations'], $this->patient(), $diagnosticReportId);
            }

            return $diagnosticReportId;
        });
    }
}
