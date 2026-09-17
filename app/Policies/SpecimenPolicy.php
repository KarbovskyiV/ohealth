<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class SpecimenPolicy
{
    /**
     * Determine whether the user can view the specimen.
     *
     * @param  User  $user
     * @return Response
     */
    public function view(User $user): Response
    {
        if ($user->cannot('specimen:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
