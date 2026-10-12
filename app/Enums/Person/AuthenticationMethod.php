<?php

declare(strict_types=1);

namespace App\Enums\Person;

use App\Traits\EnumUtils;

enum AuthenticationMethod: string
{
    use EnumUtils;

    case OTP = 'OTP';
    case OFFLINE = 'OFFLINE';
    case THIRD_PERSON = 'THIRD_PERSON';
    case NA = 'NA';

    /**
     * Name of the method as the AUTHENTICATION_METHOD dictionary gives it. NA is not part of the dictionary.
     *
     * @return string
     */
    public function label(): string
    {
        if ($this === self::NA) {
            return __('patients.authentication_method.na');
        }

        return dictionary()->basics()->byName('AUTHENTICATION_METHOD')->asCodeDescription()->get($this->value);
    }
}
