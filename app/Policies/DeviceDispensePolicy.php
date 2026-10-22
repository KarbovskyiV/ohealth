<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Declaration;
use App\Models\MedicalEvents\Sql\Approval;
use App\Models\MedicalEvents\Sql\DeviceDispense;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DeviceDispensePolicy
{
    /**
     * Determine whether the user can view device dispenses.
     *
     * @param  User  $user
     * @return Response
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('device_dispense:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view the device dispense of the patient: one of the device dispenses the user may
     * read, or the one based on a care plan the user has an approval on or the current legal entity manages.
     *
     * @param  User  $user
     * @param  DeviceDispense  $deviceDispense
     * @param  Person|Preperson  $patient
     * @return Response
     */
    public function view(User $user, DeviceDispense $deviceDispense, Person|Preperson $patient): Response
    {
        if ($user->cannot('device_dispense:read')) {
            return Response::denyWithStatus(404);
        }

        $hasPatientAccess = Declaration::accessGrantedTo($user, $patient, legalEntity())->exists()
            || Approval::accessGrantedTo($user, $patient, legalEntity())->exists();

        $isReadable = DeviceDispense::readableFor($patient, $hasPatientAccess)->whereKey($deviceDispense->id)->exists()
            || DeviceDispense::basedOnApprovedCarePlan($patient, $user, legalEntity())
                ->whereKey($deviceDispense->id)
                ->exists()
            || DeviceDispense::basedOnCarePlanOfLegalEntity($patient)->whereKey($deviceDispense->id)->exists();

        return $isReadable ? Response::allow() : Response::denyWithStatus(404);
    }
}
