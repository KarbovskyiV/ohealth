<?php

declare(strict_types=1);

namespace App\Livewire\Specimen\Forms;

use Livewire\Form;

class SpecimenForm extends Form
{
    public string $uuid = '';
    public string $typeCode = '';
    public string $conditionCode = '';
    public string $note = '';
    public array $parentIds = [];
    public string $collectorType = 'current';
    public string $collectorId = '';
    public string $collectedType = 'date_time';
    public string $collectedDate = '';
    public string $collectedTime = '';
    public string $collectedPeriodRange = '';
    public string $collectedPeriodStartTime = '';
    public string $collectedPeriodEndTime = '';
    public ?int $durationValue = null;
    public string $durationCode = '';
    public ?int $quantityValue = null;
    public string $quantityCode = '';
    public string $methodCode = '';
    public string $bodySiteCode = '';
    public string $fastingStatusCode = '';
    public string $procedureId = '';
    public array $containers = [
        [
            'identifier' => '',
            'description' => '',
            'typeCode' => '',
            'additiveCode' => '',
            'capacityValue' => null,
            'capacityCode' => '',
            'specimenQuantityValue' => null,
            'specimenQuantityCode' => '',
        ]
    ];
}
