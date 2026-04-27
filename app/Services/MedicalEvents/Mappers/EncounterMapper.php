<?php

declare(strict_types=1);

namespace App\Services\MedicalEvents\Mappers;

use App\Enums\Person\EncounterStatus;
use App\Services\MedicalEvents\FhirResource;

class EncounterMapper
{
    /**
     * Populate flat form keys on $encounter from its nested FHIR paths.
     * Used when loading an existing encounter for editing.
     *
     * @param  array  $encounter
     * @return array
     */
    public function fromFhir(array $encounter): array
    {
        $encounter['classCode'] = data_get($encounter, 'class.code', '');
        $encounter['typeCode'] = data_get($encounter, 'type.coding.0.code', '');

        return $encounter;
    }

    /**
     * Build a FHIR encounter structure ready for the repository or eHealth API.
     * Absorbs the logic previously in EncounterRepository::formatEncounterRequest.
     *
     * @param  array  $encounter
     * @param  array  $fhirConditions
     * @param  array  $uuids
     * @return array
     */
    public function toFhir(array $encounter, array $fhirConditions, array $uuids): array
    {
        // Required params
        $data = [
            'id' => $uuids['encounter'],
            'status' => EncounterStatus::FINISHED->value,
            'period' => [
                'start' => convertToEHealthISO8601($encounter['periodDate'] . ' ' . $encounter['periodStart']),
                'end' => convertToEHealthISO8601($encounter['periodDate'] . ' ' . $encounter['periodEnd'])
            ],
            'visit' => FhirResource::make()->coding('eHealth/resources', 'visit')->toIdentifier($uuids['visit']),
            'episode' => FhirResource::make()->coding('eHealth/resources', 'episode')->toIdentifier($uuids['episode']),
            'class' => FhirResource::make()->coding('eHealth/encounter_classes', $encounter['classCode'])->toCoding(),
            'type' => FhirResource::make()->coding('eHealth/encounter_types', $encounter['typeCode'])->toCodeableConcept(),
            'performer' => FhirResource::make()->coding('eHealth/resources', 'employee')->toIdentifier($uuids['employee'])
        ];

        // todo: add incoming_referral and paper_referral

        if (!empty($encounter['priorityCode'])) {
            $data['priority'] = FhirResource::make()->coding('eHealth/encounter_priority', $encounter['priorityCode'])->toCodeableConcept();
        }

        if (!empty($encounter['reasons'])) {
            $data['reasons'] = collect($encounter['reasons'])
                ->map(fn (array $cc) => FhirResource::make()->coding('eHealth/ICPC2/reasons', $cc['code'])->toCodeableConcept())
                ->toArray();
        }

        $data['diagnoses'] = array_map(
            static function (array $fhir, array $diagnosis) {
                $item = [
                    'condition' => FhirResource::make()->coding('eHealth/resources', 'condition')->toIdentifier($fhir['id']),
                    'role' => FhirResource::make()->coding('eHealth/diagnosis_roles', $diagnosis['roleCode'])->toCodeableConcept(),
                ];

                if (!empty($diagnosis['rank'])) {
                    $item['rank'] = $diagnosis['rank'];
                }

                return $item;
            },
            $fhirConditions,
            $encounter['diagnoses']
        );

        if (!empty($encounter['actions'])) {
            $data['actions'] = collect($encounter['actions'])
                ->map(fn (array $cc) => FhirResource::make()->coding('eHealth/ICPC2/actions', $cc['code'])->toCodeableConcept())
                ->toArray();
        }

        // todo: action_references

        if (!empty($encounter['divisionId'])) {
            $data['division'] = FhirResource::make()->coding('eHealth/resources', 'division')->toIdentifier($encounter['divisionId']);
        }

        // todo: prescriptions

        // todo: supporting_info

        // todo: hospitalization

        // todo: participant

        return $data;
    }
}
