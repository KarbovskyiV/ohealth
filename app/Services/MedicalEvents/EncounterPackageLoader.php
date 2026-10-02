<?php

declare(strict_types=1);

namespace App\Services\MedicalEvents;

use App\Enums\DeviceDispense\Status as DeviceDispenseStatus;
use App\Repositories\MedicalEvents\Repository;
use Carbon\CarbonImmutable;
use RuntimeException;

class EncounterPackageLoader
{
    /**
     * Read the encounter and everything recorded alongside it back out of the local database, in the flat shape
     * the encounter form holds and the package builder turns into FHIR. Rebuilding the package through the same
     * builder that created it is what keeps the content identical to what eHealth stored.
     *
     * @param  array  $encounter  Encounter with its relationships, as stored locally
     * @return array
     */
    public function load(array $encounter): array
    {
        $encounterId = $encounter['uuid'];

        return [
            'encounter' => Fhir::encounter()->fromFhir($encounter, $this->supportingInfoDetailsMap($encounter)),
            'conditions' => $this->loadConditions($encounter),
            'immunizations' => $this->loadImmunizations($encounterId),
            'diagnosticReports' => $this->loadDiagnosticReports($encounterId),
            'observations' => $this->loadObservations($encounterId),
            'procedures' => $this->loadProcedures($encounterId),
            'deviceDispenses' => $this->loadDeviceDispenses($encounterId),
            'specimens' => $this->loadSpecimens($encounterId),
            'devices' => $this->loadDevices($encounterId),
            'deviceAssociations' => $this->loadDeviceAssociations($encounterId),
            'detectedIssues' => $this->loadDetectedIssues($encounterId),
            'clinicalImpressions' => $this->loadClinicalImpressions($encounterId)
        ];
    }

    /**
     * @param  array  $encounter
     * @return array
     * @throws RuntimeException When a condition named by a diagnosis is not stored locally
     */
    private function loadConditions(array $encounter): array
    {
        $diagnoses = data_get($encounter, 'diagnoses') ?? [];
        $conditionIds = collect($diagnoses)
            ->pluck('condition.identifier.value')
            ->filter()
            ->values();

        $encounterConditions = Repository::condition()->get($encounter['uuid']);
        $encounterConditionIds = collect($encounterConditions)->pluck('uuid');

        $conditions = array_merge(
            Repository::condition()->getByUuids($conditionIds->toArray()),
            $encounterConditions
        );

        $conditionsById = collect($conditions)->keyBy('uuid');

        // The encounter pairs its diagnoses with the conditions by position, so a diagnosis whose condition is
        // not at hand would leave every diagnosis after it pointing at the wrong condition. Rather than sign a
        // package that says something the encounter never said, refuse to rebuild it at all.
        if (
            $conditionIds->count() !== count($diagnoses)
            || $conditionIds->contains(static fn (string $conditionId): bool => !$conditionsById->has($conditionId))
        ) {
            throw new RuntimeException(
                sprintf('Conditions of the diagnoses of encounter %s are missing locally', $encounter['uuid'])
            );
        }

        if (!$conditions) {
            return [];
        }

        $detailsMap = Repository::condition()->getDetailsMapForEvidences($conditions);

        // The diagnoses set the order; a condition of the encounter that is no diagnosis of it follows them
        return $conditionIds
            ->merge($conditionsById->keys()->diff($conditionIds))
            ->map(static fn (string $conditionId): array => $conditionsById->get($conditionId))
            ->map(static fn (array $condition): array => [
                ...Fhir::condition()->fromFhir($condition, $detailsMap),
                // A diagnosis may reference a condition registered before this encounter
                'isRegistered' => !$encounterConditionIds->contains($condition['uuid'])
            ])
            ->values()
            ->toArray();
    }

    /**
     * @param  string  $encounterId
     * @return array
     */
    private function loadImmunizations(string $encounterId): array
    {
        return collect(Repository::immunization()->get($encounterId))
            ->map(static fn (array $immunization) => Fhir::immunization()->fromFhir($immunization))
            ->toArray();
    }

    /**
     * @param  string  $encounterId
     * @return array
     */
    private function loadDiagnosticReports(string $encounterId): array
    {
        return collect(Repository::diagnosticReport()->get($encounterId))
            ->map(static fn (array $diagnosticReport) => Fhir::diagnosticReport()->fromFhir($diagnosticReport))
            ->toArray();
    }

    /**
     * @param  string  $encounterId
     * @return array
     */
    private function loadObservations(string $encounterId): array
    {
        return collect(Repository::observation()->get($encounterId))
            ->map(static fn (array $observation) => Fhir::observation()->fromFhir($observation))
            ->toArray();
    }

    /**
     * @param  string  $encounterId
     * @return array
     */
    private function loadProcedures(string $encounterId): array
    {
        $procedures = Repository::procedure()->get($encounterId);

        if (!$procedures) {
            return [];
        }

        $conditionUuids = collect($procedures)
            ->flatMap(static fn (array $procedure) => array_merge(
                collect(data_get($procedure, 'reasonReferences', []))
                    ->filter(static fn (array $reference) => data_get($reference, 'identifier.type.coding.0.code') === 'condition')
                    ->pluck('identifier.value')
                    ->toArray(),
                collect(data_get($procedure, 'complicationDetails', []))
                    ->pluck('identifier.value')
                    ->toArray()
            ))
            ->filter()->unique()->values()->toArray();

        $observationUuids = collect($procedures)
            ->flatMap(
                static fn (array $procedure) => collect(data_get($procedure, 'reasonReferences', []))
                    ->filter(static fn (array $reference) => data_get($reference, 'identifier.type.coding.0.code') === 'observation')
                    ->pluck('identifier.value')
                    ->toArray()
            )
            ->filter()->unique()->values()->toArray();

        $detailsMap = array_merge(
            Repository::condition()->getProcedureReferenceDetailsMapByUuids($conditionUuids),
            Repository::observation()->getDetailsMapByUuids($observationUuids)
        );

        return collect($procedures)
            ->map(static fn (array $procedure) => Fhir::procedure()->fromFhir($procedure, $detailsMap))
            ->toArray();
    }

    /**
     * Load specimens created within the encounter as flat form data.
     *
     * @param  string  $encounterId
     * @return array
     */
    private function loadSpecimens(string $encounterId): array
    {
        return collect(Repository::specimen()->get($encounterId))
            ->map(static fn (array $specimen): array => Fhir::specimen()->fromFhir($specimen))
            ->toArray();
    }

    /**
     * @param  string  $encounterId
     * @return array
     */
    private function loadDeviceDispenses(string $encounterId): array
    {
        $deviceDispenses = Repository::deviceDispense()->get($encounterId);

        if (!$deviceDispenses) {
            return [];
        }

        $allSupportingInfo = collect($deviceDispenses)
            ->flatMap(static fn (array $deviceDispense) => data_get($deviceDispense, 'supportingInfo', []))
            ->filter();

        $uuidsByType = $allSupportingInfo
            ->groupBy(static fn (array $item) => data_get($item, 'identifier.type.coding.0.code'))
            ->map(
                static fn ($group) =>
                $group
                    ->pluck('identifier.value')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray()
            );

        $detailsMap = array_merge(
            Repository::condition()->getDetailsMapByUuids($uuidsByType->get('condition', [])),
            Repository::observation()->getDetailsMapByUuids($uuidsByType->get('observation', [])),
            Repository::diagnosticReport()->getDetailsMapByUuids($uuidsByType->get('diagnostic_report', [])),
            Repository::procedure()->getDetailsMapByUuids($uuidsByType->get('procedure', [])),
            Repository::encounter()->getDetailsMapByUuids($uuidsByType->get('encounter', [])),
            Repository::episode()->getDetailsMapByUuids($uuidsByType->get('episode', []))
        );

        return collect($deviceDispenses)
            ->map(fn (array $deviceDispense): array => $this->mapDeviceDispenseFromFhir($deviceDispense, $detailsMap))
            ->toArray();
    }

    /**
     * Flat form shape for one stored device dispense. Only the package loader reads this resource back.
     *
     * @param  array  $data  FHIR device dispense as stored locally
     * @param  array  $detailsMap  Supporting-info details keyed by UUID
     * @return array
     */
    private function mapDeviceDispenseFromFhir(array $data, array $detailsMap): array
    {
        $detail = data_get($data, 'details.0', []);
        $whenHandedOver = data_get($data, 'whenHandedOver');

        $supportingInfo = collect(data_get($data, 'supportingInfo', []))
            ->map(function (array $item) use ($detailsMap): array {
                $uuid = data_get($item, 'identifier.value');
                $type = data_get($item, 'identifier.type.coding.0.code');
                $details = $detailsMap[$uuid] ?? [];

                return [
                    'uuid' => $uuid,
                    'type' => $type,
                    'ehealthInsertedAt' => $details['ehealthInsertedAt'] ?? null,
                    'code' => $details['codeCode'] ?? null
                ];
            })
            ->values()
            ->toArray();

        return [
            'uuid' => data_get($data, 'uuid'),
            'basedOnId' => data_get($data, 'basedOn.identifier.value', ''),
            'partOfId' => data_get($data, 'partOf.identifier.value', ''),
            'performerId' => data_get($data, 'performer.identifier.value', ''),
            'locationId' => data_get($data, 'location.identifier.value', ''),
            'whenHandedOverDate' => convertToAppDateFormat($whenHandedOver),
            'whenHandedOverTime' => $whenHandedOver ? CarbonImmutable::parse($whenHandedOver)->format('H:i') : '',
            'quantity' => (int) data_get($detail, 'quantity.value', 1),
            'quantityCode' => data_get($detail, 'quantity.code', 'piece'),
            'deviceSelectionType' => data_get($detail, 'device.identifier.value') ? 'model' : 'type',
            'deviceCode' => data_get($detail, 'deviceCode.coding.0.code', ''),
            'deviceDefinitionId' => data_get($detail, 'device.identifier.value', ''),
            'note' => data_get($data, 'note', ''),
            'supportingInfo' => $supportingInfo,
            'status' => data_get($data, 'status', DeviceDispenseStatus::COMPLETED->value),
            'originEpisodeId' => data_get($data, 'originEpisodeId', ''),
            'contextEpisodeId' => data_get($data, 'contextEpisodeId', ''),
            'legalEntityName' => data_get($data, 'performerLegalEntity.displayValue', ''),
            'performerName' => data_get($data, 'performer.displayValue', ''),
            'createdDate' => convertToAppDateFormat(data_get($data, 'ehealthInsertedAt'))
        ];
    }

    /**
     * @param  string  $encounterId
     * @return array
     */
    private function loadDevices(string $encounterId): array
    {
        return collect(Repository::device()->get($encounterId))
            ->map(static fn (array $device) => Fhir::device()->fromFhir($device))
            ->toArray();
    }

    /**
     * @param  string  $encounterId
     * @return array
     */
    private function loadDeviceAssociations(string $encounterId): array
    {
        return collect(Repository::deviceAssociation()->get($encounterId))
            ->map(static fn (array $deviceAssociation) => Fhir::deviceAssociation()->fromFhir($deviceAssociation))
            ->toArray();
    }

    /**
     * @param  string  $encounterId
     * @return array
     */
    private function loadDetectedIssues(string $encounterId): array
    {
        return collect(Repository::detectedIssue()->get($encounterId))
            ->map(static fn (array $detectedIssue): array => Fhir::detectedIssue()->fromFhir($detectedIssue))
            ->toArray();
    }

    /**
     * @param  string  $encounterId
     * @return array
     */
    private function loadClinicalImpressions(string $encounterId): array
    {
        $clinicalImpressions = Repository::clinicalImpression()->get($encounterId);

        if (!$clinicalImpressions) {
            return [];
        }

        $allSupportingInfo = collect($clinicalImpressions)
            ->flatMap(static fn (array $clinicalImpression) => data_get($clinicalImpression, 'supportingInfo', []))
            ->filter();

        $conditionUuids = collect($clinicalImpressions)
            ->flatMap(static fn (array $clinicalImpression) => array_merge(
                collect(data_get($clinicalImpression, 'problems', []))
                    ->pluck('identifier.value')
                    ->toArray(),
                collect(data_get($clinicalImpression, 'findings', []))
                    ->filter(static fn (array $finding) => data_get($finding, 'itemReference.identifier.type.coding.0.code') === 'condition')
                    ->pluck('itemReference.identifier.value')
                    ->toArray()
            ))
            ->filter()->unique()->values()->toArray();

        $observationUuids = collect($clinicalImpressions)
            ->flatMap(
                static fn (array $clinicalImpression) => collect(data_get($clinicalImpression, 'findings', []))
                    ->filter(static fn (array $finding) => data_get($finding, 'itemReference.identifier.type.coding.0.code') === 'observation')
                    ->pluck('itemReference.identifier.value')
                    ->toArray()
            )
            ->filter()->unique()->values()->toArray();

        $previousUuids = collect($clinicalImpressions)
            ->pluck('previous.identifier.value')
            ->filter()->unique()->values()->toArray();

        $uuidsByType = $allSupportingInfo
            ->groupBy(static fn (array $item) => data_get($item, 'identifier.type.coding.0.code'))
            ->map(static fn ($group) => $group->pluck('identifier.value')->filter()->unique()->values()->toArray());

        $detailsMap = array_merge(
            Repository::condition()->getDetailsMapByUuids($conditionUuids),
            Repository::observation()->getDetailsMapByUuids($observationUuids),
            Repository::clinicalImpression()->getDetailsMapByUuids($previousUuids),
            Repository::diagnosticReport()->getDetailsMapByUuids($uuidsByType->get('diagnostic_report', [])),
            Repository::procedure()->getDetailsMapByUuids($uuidsByType->get('procedure', [])),
            Repository::encounter()->getDetailsMapByUuids($uuidsByType->get('encounter', [])),
            Repository::episode()->getDetailsMapByUuids($uuidsByType->get('episode_of_care', []))
        );

        return collect($clinicalImpressions)
            ->map(static fn (array $clinicalImpression) => Fhir::clinicalImpression()->fromFhir($clinicalImpression, $detailsMap))
            ->toArray();
    }

    /**
     * Get details for the records the encounter points at as supporting info.
     *
     * @param  array  $encounter
     * @return array
     */
    private function supportingInfoDetailsMap(array $encounter): array
    {
        $uuidsByType = collect(data_get($encounter, 'supporting_info', []))
            ->groupBy(static fn (array $item) => data_get($item, 'identifier.type.coding.0.code'))
            ->map(static fn ($group) => $group->pluck('identifier.value')->filter()->unique()->values()->toArray());

        return array_merge(
            Repository::condition()->getDetailsMapByUuids($uuidsByType->get('condition', [])),
            Repository::observation()->getDetailsMapByUuids($uuidsByType->get('observation', [])),
            Repository::diagnosticReport()->getDetailsMapByUuids($uuidsByType->get('diagnostic_report', []))
        );
    }
}
