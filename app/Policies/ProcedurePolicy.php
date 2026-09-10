<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Status;
use App\Enums\User\Role;
use App\Models\Employee\Employee;
use App\Models\MedicalEvents\Sql\Approval;
use App\Models\MedicalEvents\Sql\Procedure;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;

class ProcedurePolicy
{
    /**
     * Determine whether the user can view the procedure.
     */
    public function view(User $user, Procedure $procedure): Response
    {
        if ($user->cannot('procedure:read')) {
            return Response::denyWithStatus(404);
        }

        if ($procedure->managingOrganization->value !== legalEntity()->uuid) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can create procedure.
     */
    public function create(User $user): Response
    {
        if ($user->cannot('procedure:write')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can cancel procedure.
     */
    public function cancel(User $user, Procedure $procedure): Response
    {
        if ($user->cannot('procedure:cancel')) {
            return Response::denyWithStatus(404);
        }

        if ($procedure->managingOrganization->value !== legalEntity()->uuid) {
            return Response::denyWithStatus(404);
        }

        if (!$this->signsAsEmployeeAllowedToCancel($user, $procedure)) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user signs the cancellation as an employee eHealth accepts for it: the one the
     * procedure names as its author, the holder of an approval that still grants write access to it, or a
     * medical administrator. The signature carries the tax ID of the user's party, so the employees of that
     * party are the ones the signature resolves to, and only the ones still employed count.
     */
    private function signsAsEmployeeAllowedToCancel(User $user, Procedure $procedure): bool
    {
        $employees = Employee::wherePartyId($user->partyId)
            ->whereLegalEntityId(legalEntity()->id)
            ->whereStatus(Status::APPROVED)
            ->whereIsActive(true)
            ->get(['uuid', 'employee_type']);

        if ($employees->contains('employeeType', Role::MED_ADMIN->value)) {
            return true;
        }

        $employeeIds = $employees->pluck('uuid');

        if ($employeeIds->contains($procedure->recordedBy?->value)) {
            return true;
        }

        return Approval::grantingWriteAccessTo($procedure->uuid)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->isVerified()
            ->whereHas(
                'grantedTo',
                static fn (Builder $identifier): Builder => $identifier->whereIn('value', $employeeIds)
            )
            ->exists();
    }
}
