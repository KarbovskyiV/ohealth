<?php

declare(strict_types=1);

namespace App\Enums\Specimen;

use App\Traits\EnumUtils;

enum Status: string
{
    use EnumUtils;

    case AVAILABLE = 'available';
    case UNSATISFACTORY = 'unsatisfactory';
    case UNAVAILABLE = 'unavailable';
    case ENTERED_IN_ERROR = 'entered_in_error';

    /**
     * Get the translated status label.
     *
     * @return string
     */
    public function label(): string
    {
        return __('specimens.statuses.' . $this->value);
    }

    /**
     * Badge CSS class representing the status color.
     *
     * @return string
     */
    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'badge-green',
            self::UNSATISFACTORY => 'badge-yellow',
            self::UNAVAILABLE, self::ENTERED_IN_ERROR => 'badge-red'
        };
    }
}
