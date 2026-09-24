<?php

declare(strict_types=1);

namespace App\Enums;

enum CarePlanTermsOfService: string
{
    case INPATIENT = 'INPATIENT';
    case OUTPATIENT = 'OUTPATIENT';
}
