<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Episode\Status;
use App\Models\MedicalEvents\Sql\Episode;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EpisodePolicy
{
    /**
     * Determine whether the user can view the episode.
     */
    public function view(User $user): Response
    {
        if ($user->cannot('episode:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
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
     */
    public function update(User $user, Episode $episode): Response
    {
        if ($user->cannot('episode:write') || $episode->managingOrganization->value !== legalEntity()->uuid) {
            return Response::denyWithStatus(404);
        }

        return in_array($episode->status, [Status::DRAFT, Status::ACTIVE], true)
            ? Response::allow()
            : Response::denyWithStatus(404);
    }

    /**
     * Determine whether the user can delete the episode. Only a draft that never reached eHealth can be deleted.
     */
    public function delete(User $user, Episode $episode): Response
    {
        if ($user->cannot('episode:write') || $episode->managingOrganization->value !== legalEntity()->uuid) {
            return Response::denyWithStatus(404);
        }

        return $episode->status === Status::DRAFT
            ? Response::allow()
            : Response::denyWithStatus(404);
    }
}
