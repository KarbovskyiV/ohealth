<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Person\DiagnosticReportStatus;
use App\Enums\Status;
use App\Enums\User\Role;
use App\Models\Employee\Employee;
use App\Models\MedicalEvents\Sql\DiagnosticReport;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DiagnosticReportPolicy
{
    /**
     * Determine whether the user can view the diagnostic report.
     */
    public function view(User $user, DiagnosticReport $diagnosticReport): Response
    {
        if ($user->cannot('diagnostic_report:read')) {
            return Response::denyWithStatus(404);
        }

        if ($diagnosticReport->managingOrganization->value !== legalEntity()->uuid) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can create diagnostic report.
     */
    public function create(User $user): Response
    {
        if ($user->cannot('diagnostic_report:write')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can cancel diagnostic report.
     */
    public function cancel(User $user, DiagnosticReport $diagnosticReport): Response
    {
        if ($user->cannot('diagnostic_report:cancel')) {
            return Response::denyWithStatus(404);
        }

        if ($diagnosticReport->managingOrganization?->value !== legalEntity()?->uuid) {
            return Response::denyWithStatus(404);
        }

        if ($diagnosticReport->status !== DiagnosticReportStatus::FINAL) {
            return Response::denyWithStatus(404);
        }

        if ($diagnosticReport->encounter_id !== null) {
            return Response::denyWithStatus(404);
        }

        // A medical administrator cancels what the employees of their legal entity recorded, the patient's
        // approval not being needed for it
        $isMedicalAdministrator = Employee::wherePartyId($user->partyId)
            ->whereLegalEntityId(legalEntity()->id)
            ->whereStatus(Status::APPROVED)
            ->whereIsActive(true)
            ->whereEmployeeType(Role::MED_ADMIN->value)
            ->exists();

        if ($isMedicalAdministrator) {
            return Response::allow();
        }

        $currentEmployeeUuid = $user->getDiagnosticReportWriterEmployee()?->uuid;

        if (!$currentEmployeeUuid || $diagnosticReport->recordedBy?->value !== $currentEmployeeUuid) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
