<?php

declare(strict_types=1);

namespace App\Services\MedicalEvents\Mappers;

use App\Enums\Person\EpisodeStatus;
use App\Services\MedicalEvents\FhirResource;

class EpisodeMapper
{
    /**
     * Populate flat form keys on $episode from its nested FHIR paths.
     * Used when loading an existing episode for editing.
     *
     * @param  array  $episode
     * @return array
     */
    public function fromFhir(array $episode): array
    {
        $episode['typeCode'] = data_get($episode, 'type.code', '');

        return $episode;
    }

    /**
     * Build a FHIR episode structure ready for the repository or eHealth API.
     * Absorbs the logic previously in EncounterRepository::formatEpisodeRequest.
     *
     * @param  array  $episode
     * @param  array  $uuids
     * @param  string  $periodDate
     * @param  string  $periodStart
     * @return array
     */
    public function toFhir(array $episode, array $uuids, string $periodDate, string $periodStart): array
    {
        return [
            'id' => $uuids['episode'],
            'type' => FhirResource::make()->coding('eHealth/episode_types', $episode['typeCode'])->toCoding(),
            'name' => $episode['name'],
            'status' => EpisodeStatus::ACTIVE->value,
            'managingOrganization' => FhirResource::make()->coding('eHealth/resources', 'legal_entity')->toIdentifier(legalEntity()->uuid),
            'period' => [
                'start' => convertToEHealthISO8601($periodDate . ' ' . $periodStart)
            ],
            'careManager' => FhirResource::make()->coding('eHealth/resources', 'employee')->toIdentifier($uuids['employee'])
        ];
    }
}
