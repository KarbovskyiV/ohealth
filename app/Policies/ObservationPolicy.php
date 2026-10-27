<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Declaration;
use App\Models\MedicalEvents\Sql\Approval;
use App\Models\MedicalEvents\Sql\Observation;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ObservationPolicy
{
    /**
     * Determine whether the user can view the list of observations.
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('observation:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view the observation of the patient: one of the observations the user may read.
     */
    public function view(User $user, Observation $observation, Person|Preperson $patient): Response
    {
        if ($user->cannot('observation:read')) {
            return Response::denyWithStatus(404);
        }

        $hasPatientAccess = Declaration::accessGrantedTo($user, $patient, legalEntity())->exists()
            || Approval::accessGrantedTo($user, $patient, legalEntity())->exists();

        return Observation::readableFor($patient, $hasPatientAccess, $user)->whereKey($observation->id)->exists()
            ? Response::allow()
            : Response::denyWithStatus(404);
    }
}
