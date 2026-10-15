<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\User\Role;
use App\Models\Declaration;
use App\Models\MedicalEvents\Sql\Approval;
use App\Models\Person\Person;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PersonPolicy
{
    /**
     * Determine whether the user can view the person request.
     */
    public function viewAny(User $user): Response
    {
        // The scope alone is held by more roles than may search for a patient record
        if ($user->cannot('person:read') || !$user->hasAllowedRole([
            Role::DOCTOR,
            Role::SPECIALIST,
            Role::ASSISTANT,
            Role::RECEPTIONIST,
            Role::MED_ADMIN,
            Role::MED_COORDINATOR
        ])) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view the patient data.
     */
    public function view(User $user): Response
    {
        if ($user->cannot('patient_summary:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view the personal data of the patient stored locally: only with an active
     * declaration or approval on the patient, the way eHealth itself would give it out.
     */
    public function viewPersonalData(User $user, Person $person): Response
    {
        if ($user->cannot('personal_data:read')) {
            return Response::denyWithStatus(404);
        }

        $hasPatientAccess = Declaration::accessGrantedTo($user, $person, legalEntity())->exists()
            || Approval::accessGrantedTo($user, $person, legalEntity())->exists();

        return $hasPatientAccess ? Response::allow() : Response::denyWithStatus(404);
    }

    /**
     * Determine whether the user can view the persons merged into the patient.
     */
    public function viewMergedPersons(User $user): Response
    {
        if ($user->cannot('person:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view the patient emergency contact.
     */
    public function viewEmergencyContact(User $user): Response
    {
        if ($user->cannot('person_emergency_contact:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can sync the person data.
     */
    public function syncPersonData(User $user): Response
    {
        // The scopes alone are held by more roles than may look at the personal data of a patient
        if ($user->can('personal_data:read')
            && $user->can('confidant_person_relationship:read')
            && $user->hasAllowedRole([Role::DOCTOR, Role::SPECIALIST])
        ) {
            return Response::allow();
        }

        return Response::denyWithStatus(404);
    }
}
