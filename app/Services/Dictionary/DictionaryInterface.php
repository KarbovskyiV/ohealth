<?php

declare(strict_types=1);

namespace App\Services\Dictionary;

use App\Classes\eHealth\EHealthResponse;

interface DictionaryInterface
{
    /**
     * Get unique dictionary identifier key.
     *
     * @return string The unique key used for caching and registration
     */
    public function getKey(): string;

    /**
     * Fetch full response from API with pagination info.
     *
     * @param  int  $page  Page number (1-based)
     * @return EHealthResponse Full API response with data and pagination
     */
    public function fetch(int $page = 1): EHealthResponse;
}
