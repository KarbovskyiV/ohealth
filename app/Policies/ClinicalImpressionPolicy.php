<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\User\Role;
use App\Models\Declaration;
use App\Models\MedicalEvents\Sql\Approval;
use App\Models\MedicalEvents\Sql\ClinicalImpression;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClinicalImpressionPolicy
{
    /**
     * Determine whether the user can view the list of clinical impressions.
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('clinical_impression:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view the clinical impression of the patient: one of the clinical impressions the
     * user may read.
     */
    public function view(User $user, ClinicalImpression $clinicalImpression, Person|Preperson $patient): Response
    {
        if ($user->cannot('clinical_impression:read')) {
            return Response::denyWithStatus(404);
        }

        $hasPatientAccess = Declaration::accessGrantedTo($user, $patient, legalEntity())->exists()
            || Approval::accessGrantedTo($user, $patient, legalEntity())->exists();

        return ClinicalImpression::readableFor($patient, $hasPatientAccess)->whereKey($clinicalImpression->id)->exists()
            ? Response::allow()
            : Response::denyWithStatus(404);
    }

    /**
     * Determine whether the user can create a clinical impression.
     */
    public function create(User $user): Response
    {
        // The scope alone is held by more roles than may create a clinical impression
        if ($user->cannot('clinical_impression:write') || !$user->hasAllowedRole([Role::DOCTOR, Role::SPECIALIST])) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
