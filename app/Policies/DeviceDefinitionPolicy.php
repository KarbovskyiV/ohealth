<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\User\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DeviceDefinitionPolicy
{
    /**
     * Determine if the user can view device definitions.
     *
     * @param  User  $user
     * @return Response
     */
    public function view(User $user): Response
    {
        // The scope alone is held by more roles than may work with the device list
        if ($user->cannot('device_definition:read') || !$user->hasAllowedRole([Role::DOCTOR, Role::SPECIALIST])) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
