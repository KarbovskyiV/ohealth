<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\User\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServiceCatalogPolicy
{
    /**
     * Determine if the user can view the service catalog.
     *
     * @param  User  $user
     * @return Response
     */
    public function view(User $user): Response
    {
        if (!$user->hasAllowedRole([Role::DOCTOR, Role::SPECIALIST, Role::LABORANT, Role::ASSISTANT])) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
