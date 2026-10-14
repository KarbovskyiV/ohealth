<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface SubmittedToEHealth
{
    /**
     * Async eHealth job links the signed record was submitted with.
     *
     * @return MorphMany
     */
    public function ehealthLinks(): MorphMany;
}
