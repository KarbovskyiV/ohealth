<?php

declare(strict_types=1);

namespace App\Services\MedicalEvents\Mappers;

use App\Services\MedicalEvents\FhirResource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class ConditionMapper
{
    /**
     * Convert a flat form condition to a FHIR structure for persistence/API.
     *
     * @param  array  $condition
     * @param  array  $uuids
     * @return array
     */
    public function toFhir(array $condition, array $uuids): array
    {
        // Required params
        $data = [
            'id' => Str::uuid()->toString(),
            'primarySource' => $condition['primarySource'],
            'context' => FhirResource::make()->coding('eHealth/resources', 'encounter')->toIdentifier($uuids['encounter']),
            'code' => FhirResource::make()->coding($condition['codeSystem'], $condition['codeCode'])->toCodeableConcept(),
            'clinicalStatus' => $condition['clinicalStatus'],
            'verificationStatus' => $condition['verificationStatus'],
            'onsetDate' => convertToEHealthISO8601($condition['onsetDate'] . ' ' . $condition['onsetTime']),
        ];

        if ($condition['primarySource']) {
            $data['asserter'] = FhirResource::make()
                ->coding('eHealth/resources', 'employee')
                ->toIdentifier($uuids['employee'], $condition['asserterText'] ?? '');
        } else {
            $data['reportOrigin'] = FhirResource::make()
                ->coding('eHealth/report_origins', $condition['reportOriginCode'])
                ->toCodeableConcept();
        }

        if (!empty($condition['severityCode'])) {
            $data['severity'] = FhirResource::make()
                ->coding('eHealth/condition_severities', $condition['severityCode'])
                ->toCodeableConcept();
        }

        // todo: add  bodySites.*.code check

        if (!empty($condition['assertedDate']) && !empty($condition['assertedTime'])) {
            $data['assertedDate'] = convertToEHealthISO8601(
                $condition['assertedDate'] . ' ' . $condition['assertedTime']
            );
        }

        // todo: add stage

        $evidence = [];

        if (!empty($condition['evidenceCodes'])) {
            $evidence['codes'] = collect($condition['evidenceCodes'])
                ->map(
                    fn (array $cc) => FhirResource::make()
                        ->coding($cc['system'] ?? 'eHealth/ICPC2/reasons', $cc['code'])
                        ->toCodeableConcept()
                )
                ->toArray();
        }

        if (!empty($condition['evidenceDetails'])) {
            $evidence['details'] = collect($condition['evidenceDetails'])
                ->map(
                    fn (array $detail) => FhirResource::make()
                        ->coding('eHealth/resources', $detail['type'])
                        ->toIdentifier($detail['id'])
                )
                ->toArray();
        }

        if (!empty($evidence)) {
            $data['evidences'] = [$evidence];
        }

        return $data;
    }

    /**
     * Convert a FHIR condition (from DB) to a flat form structure.
     *
     * @param  array  $fhir
     * @return array
     */
    public function fromFhir(array $fhir): array
    {
        $onsetRaw = data_get($fhir, 'onsetDate', '');
        $assertedRaw = data_get($fhir, 'assertedDate', '');

        $onset = !empty($onsetRaw) ? CarbonImmutable::parse($onsetRaw) : null;
        $asserted = !empty($assertedRaw) ? CarbonImmutable::parse($assertedRaw) : null;

        return [
            'primarySource' => data_get($fhir, 'primarySource', true),
            'codeSystem' => data_get($fhir, 'code.coding.0.system', ''),
            'codeCode' => data_get($fhir, 'code.coding.0.code', ''),
            'clinicalStatus' => data_get($fhir, 'clinicalStatus', ''),
            'verificationStatus' => data_get($fhir, 'verificationStatus', ''),
            'onsetDate' => $onset?->toDateString() ?? '',
            'onsetTime' => $onset?->format('H:i') ?? '',
            'assertedDate' => $asserted?->toDateString() ?? '',
            'assertedTime' => $asserted?->format('H:i') ?? '',
            'severityCode' => data_get($fhir, 'severity.coding.0.code'),
            'asserterText' => data_get($fhir, 'asserter.identifier.type.text', ''),
            'reportOriginCode' => data_get($fhir, 'reportOrigin.coding.0.code', ''),
            'evidenceCodes' => collect(data_get($fhir, 'evidences.0.codes', []))
                ->map(fn (array $c) => [
                    'code' => data_get($c, 'coding.0.code', ''),
                    'system' => data_get($c, 'coding.0.system', 'eHealth/ICPC2/reasons')
                ])
                ->toArray(),
            'evidenceDetails' => collect(data_get($fhir, 'evidences.0.details', []))
                ->map(fn (array $d) => [
                    'id' => data_get($d, 'identifier.value') ?? data_get($d, 'id', ''),
                    'insertedAt' => data_get($d, 'inserted_at', ''),
                    'codeCode' => data_get($d, 'code.coding.0.code', ''),
                    'type' => data_get($d, 'identifier.type.coding.0.code', ''),
                    'selectedEpisodeId' => ''
                ])
                ->toArray()
        ];
    }
}
