<?php

declare(strict_types=1);

namespace App\Livewire\Person\Forms;

use App\Core\BaseForm;
use App\Models\MedicalEvents\Sql\Encounter;
use App\Models\MedicalEvents\Sql\ServiceRequestRequest;
use App\Models\Person\Person;
use App\Services\MedicalEvents\PatientApprovalService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PatientApprovalForm extends BaseForm
{
    private const array WRITABLE_RESOURCES = [
        'diagnostic_report',
        'encounter',
        'procedure',
        'care_plan',
    ];

    private const array CHILD_TYPES = [
        'encounter',
        'diagnostic_report',
        'procedure',
    ];

    public function validateSelection(Person $person, array $data): array
    {
        $selection = match ($data['mode']) {
            'patient', 'person' => [
                'mode' => $data['mode'],
                'person_uuid' => $person->uuid,
                'access_level' => 'read',
                'grant_to_legal_entity' => false,
            ],

            'resource' => $this->validateResources($person, $data),
            'child' => $this->validateChild($person, $data),
            'service_request' => $this->validateReferral($person, $data),

            'diagnoses_group',
            'forbidden_group',
            'service_group' => $this->validateGroup($data),

            'resource_types' => $this->validateResourceTypes($data),

            default => throw ValidationException::withMessages([
                'mode' => __('approval.create.select_access_type'),
            ]),
        };

        if ($data['grantToLegalEntity'] && $data['mode'] !== 'service_request') {
            throw ValidationException::withMessages([
                'grantToLegalEntity' => __('approval.create.legal_entity_referrals_only'),
            ]);
        }

        if (!in_array($selection['access_level'], ['read', 'write'], true)) {
            throw ValidationException::withMessages([
                'accessLevel' => __('approval.create.invalid_access_level'),
            ]);
        }

        return $selection;
    }

    private function validateResources(Person $person, array $data): array
    {
        $type = $data['resourceType'];

        if (!isset(PatientApprovalService::RESOURCE_MODELS[$type]) || $type === 'service_request') {
            throw ValidationException::withMessages([
                'resourceType' => __('approval.create.select_record_type'),
            ]);
        }

        $ids = $data['resourceIds'];

        if ($ids === [] || collect($ids)->contains(static fn (mixed $uuid): bool => !is_string($uuid) || !Str::isUuid($uuid))) {
            throw ValidationException::withMessages([
                'resourceIds' => __('approval.create.select_records'),
            ]);
        }

        $ids = array_values(array_unique($ids));
        $model = PatientApprovalService::RESOURCE_MODELS[$type];

        if (
            $model::query()
                ->where('person_id', $person->id)
                ->whereIn('uuid', $ids)
                ->count() !== count($ids)
        ) {
            throw ValidationException::withMessages([
                'resourceIds' => __('approval.create.record_patient_mismatch'),
            ]);
        }

        $invalidRecord = $model::query()
            ->where('person_id', $person->id)
            ->whereIn('uuid', $ids)
            ->get(['uuid', 'status'])
            ->first(fn ($record): bool => !PatientApprovalService::approvalStatusAllowed($type, $record->getRawOriginal('status')));

        if ($invalidRecord !== null) {
            throw ValidationException::withMessages([
                'resourceIds' => $type === 'specimen' ? __('approval.create.specimen_not_available') : __('approval.create.invalid_record_status'),
            ]);
        }

        $access = in_array($type, self::WRITABLE_RESOURCES, true) ? $data['accessLevel'] : 'read';

        if (in_array($type, ['encounter', 'procedure'], true) && $access !== 'write') {
            throw ValidationException::withMessages([
                'accessLevel' => __('approval.create.write_only_resource'),
            ]);
        }

        if ($type === 'specimen') {
            $access = 'write';
        }

        return [
            'mode' => 'resource',
            'resource_type' => $type,
            'resource_ids' => $ids,
            'access_level' => $access,
            'grant_to_legal_entity' => false,
        ];
    }

    private function validateChild(Person $person, array $data): array
    {
        $type = $data['childType'];
        $uuid = $data['childUuid'];

        if (!in_array($type, self::CHILD_TYPES, true)) {
            throw ValidationException::withMessages([
                'childType' => __('approval.create.invalid_record_type'),
            ]);
        }

        if (!is_string($uuid) || !Str::isUuid($uuid)) {
            throw ValidationException::withMessages([
                'childUuid' => __('approval.create.select_child_record'),
            ]);
        }

        $model = PatientApprovalService::RESOURCE_MODELS[$type];

        $record = $model::query()
            ->where('person_id', $person->id)
            ->where('uuid', $uuid)
            ->first();

        if ($record === null) {
            throw ValidationException::withMessages([
                'childUuid' => __('approval.create.record_not_found'),
            ]);
        }

        if (!PatientApprovalService::approvalStatusAllowed($type, $record->getRawOriginal('status'))) {
            throw ValidationException::withMessages([
                'childUuid' => __('approval.create.invalid_record_status'),
            ]);
        }

        if ($type === 'encounter') {
            $contextUuid = $record->episode?->value;
        } else {
            $contextUuid = null;
            $encounterUuid = $record->encounter?->value;

            if ($encounterUuid) {
                $encounter = Encounter::query()
                    ->where('person_id', $person->id)
                    ->where('uuid', $encounterUuid)
                    ->with('episode')
                    ->first();

                $contextUuid = $encounter?->episode?->value;
            }

            $contextUuid ??= $record->originEpisode?->value;
        }

        if (!$contextUuid) {
            throw ValidationException::withMessages([
                'childUuid' => __('approval.create.missing_context_episode'),
            ]);
        }

        return [
            'mode' => 'child',
            'child_type' => $type,
            'child_uuid' => $record->uuid,
            'context_uuid' => $contextUuid,
            'access_level' => 'read',
            'grant_to_legal_entity' => false,
        ];
    }

    private function validateReferral(Person $person, array $data): array
    {
        $uuid = $data['resourceIds'][0] ?? null;

        if (!is_string($uuid) || !Str::isUuid($uuid)) {
            throw ValidationException::withMessages([
                'resourceIds' => __('approval.create.select_referral'),
            ]);
        }

        $record = ServiceRequestRequest::query()
            ->where('person_id', $person->id)
            ->where('uuid', $uuid)
            ->first();

        if ($record === null) {
            throw ValidationException::withMessages([
                'resourceIds' => __('approval.create.select_referral'),
            ]);
        }

        try {
            $hasResources = app(PatientApprovalService::class)
                ->referralHasPermittedResources($person, $record->uuid);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'resourceIds' => __('approval.create.referral_check_failed'),
            ]);
        }

        if (!$hasResources) {
            throw ValidationException::withMessages([
                'resourceIds' => __('approval.create.referral_no_resources'),
            ]);
        }

        return [
            'mode' => 'service_request',
            'service_request_uuid' => $record->uuid,
            'access_level' => 'read',
            'grant_to_legal_entity' => $data['grantToLegalEntity'],
        ];
    }

    private function validateGroup(array $data): array
    {
        $group = collect($data['selectableGroups'])
            ->firstWhere('id', $data['groupId']);

        if (!$group) {
            throw ValidationException::withMessages([
                'groupId' => __('approval.create.select_catalog_group'),
            ]);
        }

        return [
            'mode' => $data['mode'],
            'group_id' => $data['groupId'],
            'access_level' => 'read',
            'grant_to_legal_entity' => false,
        ];
    }

    private function validateResourceTypes(array $data): array
    {
        $types = ['device', 'device_association'];

        if ($data['includeDetectedIssue']) {
            $types[] = 'detected_issue';
        }

        return [
            'mode' => 'resource_types',
            'resource_types' => $types,
            'access_level' => 'read',
            'grant_to_legal_entity' => false,
        ];
    }
}
