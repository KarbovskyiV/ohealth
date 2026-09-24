<?php

declare(strict_types=1);

namespace App\Enums\MedicalEvents;

use App\Traits\EnumUtils;

/**
 * Types of patient medical records the encounter package can be searched for and refer to.
 */
enum RecordType: string
{
    use EnumUtils;

    case EPISODE = 'episodes';
    case ENCOUNTER = 'encounter';
    case PROCEDURE = 'procedure';
    case DIAGNOSTIC_REPORT = 'diagnosticReport';
    case CONDITION = 'condition';
    case OBSERVATION = 'observation';
}
