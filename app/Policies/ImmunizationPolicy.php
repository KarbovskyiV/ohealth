<?php

declare(strict_types=1);

namespace App\Policies;

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
}
