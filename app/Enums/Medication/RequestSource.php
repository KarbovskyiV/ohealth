<?php

declare(strict_types=1);

namespace App\Enums\Medication;

enum RequestSource: string
{
    case LOCAL = 'local';
    case EHEALTH = 'ehealth';
}
