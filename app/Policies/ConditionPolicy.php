<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Declaration;
use App\Models\MedicalEvents\Sql\Approval;
use App\Models\MedicalEvents\Sql\Condition;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConditionPolicy
{
    /**
     * Determine whether the user can view the list of conditions.
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('condition:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view the condition of the patient: one of the conditions the user may read.
     */
    public function view(User $user, Condition $condition, Person|Preperson $patient): Response
    {
        if ($user->cannot('condition:read')) {
            return Response::denyWithStatus(404);
        }

        $hasPatientAccess = Declaration::accessGrantedTo($user, $patient, legalEntity())->exists()
            || Approval::accessGrantedTo($user, $patient, legalEntity())->exists();

        return Condition::readableFor($patient, $hasPatientAccess)->whereKey($condition->id)->exists()
            ? Response::allow()
            : Response::denyWithStatus(404);
    }
}
