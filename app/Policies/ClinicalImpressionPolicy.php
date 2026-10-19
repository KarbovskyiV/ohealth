<?php

declare(strict_types=1);

namespace App\Policies;

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
}
