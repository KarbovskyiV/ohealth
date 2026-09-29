<?php

declare(strict_types=1);

namespace App\Enums\DetectedIssue;

use App\Traits\EnumUtils;

enum Status: string
{
    use EnumUtils;

    case PRELIMINARY = 'preliminary';
    case MITIGATED = 'mitigated';
    case ENTERED_IN_ERROR = 'entered_in_error';

    /**
     * Badge class the status is displayed with.
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::PRELIMINARY => 'badge-green',
            self::MITIGATED => 'badge-dark',
            self::ENTERED_IN_ERROR => 'badge-red'
        };
    }
}
