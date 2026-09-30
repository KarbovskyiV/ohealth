<?php

declare(strict_types=1);

namespace App\Enums\DeviceAssociation;

use App\Traits\EnumUtils;

enum Status: string
{
    use EnumUtils;

    case ATTACHED = 'attached';
    case UNATTACHED = 'unattached';
    case IMPLANTED = 'implanted';
    case EXPLANTED = 'explanted';
    case ENTERED_IN_ERROR = 'entered_in_error';

    /**
     * Badge class the status is displayed with.
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::ATTACHED, self::IMPLANTED => 'badge-green',
            self::UNATTACHED, self::EXPLANTED => 'badge-dark',
            self::ENTERED_IN_ERROR => 'badge-red'
        };
    }
}
