<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\User\Role;
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
