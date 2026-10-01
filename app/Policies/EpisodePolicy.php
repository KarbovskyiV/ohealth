<?php

declare(strict_types=1);

namespace App\Policies;

use App\Abac\Rule1Declaration;
use App\Abac\Rule4Approval;
use App\Abac\Rule5EpisodeApproval;
use App\Enums\Episode\Status;
use App\Models\Employee\Employee;
use App\Models\MedicalEvents\Sql\Episode;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Models\User;
use Illuminate\Auth\Access\Response;

readonly class EpisodePolicy
{
    /**
     * Inject the ABAC rules the episode access is decided by.
     */
    public function __construct(
        private Rule1Declaration $rule1Declaration,
        private Rule4Approval $rule4Approval,
        private Rule5EpisodeApproval $rule5EpisodeApproval
    ) {
    }

    /**
     * Determine whether the user can search the episodes.
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('episode:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view the episode of the patient: one of the episodes the user may read, or
     * the patient's episode with an approval on it, whichever legal entity manages it.
     */
    public function view(User $user, Episode $episode, Person|Preperson $patient): Response
    {
        if ($user->cannot('episode:read')) {
            return Response::denyWithStatus(404);
        }

        $employees = Employee::forUserInLegalEntity($user, legalEntity())->get(['id', 'uuid']);

        $hasPatientAccess = $this->rule1Declaration->allows($employees, $patient, legalEntity())
            || $this->rule4Approval->allows($employees, $patient);

        $isReadable = Episode::readableFor($patient, $hasPatientAccess)->whereKey($episode->id)->exists()
            || ($this->rule5EpisodeApproval->allows($employees, $episode, legalEntity())
                && Episode::forPatient($patient)->whereKey($episode->id)->exists());

        return $isReadable ? Response::allow() : Response::denyWithStatus(404);
    }

    /**
     * Determine whether the user can create an episode.
     */
    public function create(User $user): Response
    {
        if ($user->cannot('episode:write')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can edit the episode. Closed and cancelled episodes are read-only.
     * An episode without a managing organization came from the short sync and is treated as our own.
     */
    public function update(User $user, Episode $episode): Response
    {
        $managingOrganization = $episode->managingOrganization?->value;

        if ($user->cannot('episode:write')
            || ($managingOrganization !== null && $managingOrganization !== legalEntity()->uuid)
            || !Employee::managedByUser($user, $episode->careManager?->value)) {
            return Response::denyWithStatus(404);
        }

        return in_array($episode->status, [Status::DRAFT, Status::ACTIVE], true)
            ? Response::allow()
            : Response::denyWithStatus(404);
    }

    /**
     * Determine whether the user can close the episode. A closed or cancelled episode cannot be closed again,
     * and a draft never reached eHealth, so an active episode is the only one left to close.
     * An episode without a managing organization came from the short sync and is treated as our own.
     */
    public function close(User $user, Episode $episode): Response
    {
        $managingOrganization = $episode->managingOrganization?->value;

        if ($user->cannot('episode:write')
            || ($managingOrganization !== null && $managingOrganization !== legalEntity()->uuid)
            || !Employee::managedByUser($user, $episode->careManager?->value)) {
            return Response::denyWithStatus(404);
        }

        return $episode->status === Status::ACTIVE
            ? Response::allow()
            : Response::denyWithStatus(404);
    }

    /**
     * Determine whether the user can mark the episode as entered in error.
     * An episode already marked as such cannot be cancelled again, and a draft is deleted instead.
     * An episode without a managing organization came from the short sync and is treated as our own.
     */
    public function cancel(User $user, Episode $episode): Response
    {
        $managingOrganization = $episode->managingOrganization?->value;

        if ($user->cannot('episode:write')
            || ($managingOrganization !== null && $managingOrganization !== legalEntity()->uuid)
            || !Employee::managedByUser($user, $episode->careManager?->value)) {
            return Response::denyWithStatus(404);
        }

        return in_array($episode->status, [Status::ACTIVE, Status::CLOSED], true)
            ? Response::allow()
            : Response::denyWithStatus(404);
    }

    /**
     * Determine whether the user can delete the episode. Only a draft that never reached eHealth can be deleted.
     * An episode without a managing organization came from the short sync and is treated as our own.
     */
    public function delete(User $user, Episode $episode): Response
    {
        $managingOrganization = $episode->managingOrganization?->value;

        if ($user->cannot('episode:write')
            || ($managingOrganization !== null && $managingOrganization !== legalEntity()->uuid)) {
            return Response::denyWithStatus(404);
        }

        return $episode->status === Status::DRAFT
            ? Response::allow()
            : Response::denyWithStatus(404);
    }
}
