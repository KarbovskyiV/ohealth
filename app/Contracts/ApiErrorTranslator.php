<?php

declare(strict_types=1);

namespace App\Contracts;

interface ApiErrorTranslator
{
    /**
     * Get the Ukrainian text of an error message the API returned, or null when the message is unknown.
     *
     * @param  string  $message
     * @return string|null
     */
    public function translate(string $message): ?string;
}
