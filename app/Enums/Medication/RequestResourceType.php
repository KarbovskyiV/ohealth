<?php

declare(strict_types=1);

namespace App\Enums\Medication;

enum RequestResourceType: string
{
    case REQUEST = 'medication_request_request';
    case PRESCRIPTION = 'medication_request';
}
