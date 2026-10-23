<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MedicalEvents\Sql\Immunization;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ImmunizationPolicy
{
    /**
     * Determine whether the user can view the list of immunizations.
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('immunization:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view the immunization of the patient: one of the immunizations the list shows,
     * which are all of the patient's ones outside a draft encounter.
     */
    public function view(User $user, Immunization $immunization, Person|Preperson $patient): Response
    {
        if ($user->cannot('immunization:read')) {
            return Response::denyWithStatus(404);
        }

        return Immunization::forPatient($patient)->notInDraftEncounter($patient)->whereKey($immunization->id)->exists()
            ? Response::allow()
            : Response::denyWithStatus(404);
    }
}
