<?php

declare(strict_types=1);

namespace App\Livewire\Person\Records;

use App\Enums\MedicalProgram\Type;
use App\Livewire\DeviceRequest\DeviceRequestIndex;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\DeviceRequestRequest;
use App\Models\Person\Person;
use App\Services\MedicalEvents\DeviceRequestLifecycleService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class PatientDeviceRequests extends DeviceRequestIndex
{
    public function mount(LegalEntity $legalEntity, ?Person $person = null): void
    {
        abort_unless($person?->exists, 404);
        $this->legalEntityId = $legalEntity->id;
        $this->personId = $person->id;
        $this->filters['requester_legal_entity'] = $legalEntity->uuid;
        if ($this->canReadRequests) {
            $this->search();
        }
    }

    #[Computed]
    public function canReadRequests(): bool
    {
        return Auth::user()?->can('viewAny', [DeviceRequestRequest::class, $this->legalEntity]) ?? false;
    }

    public function search(bool $resetPage = true): void
    {
        $this->authorize('perform', [DeviceRequestRequest::class, $this->legalEntity, 'device_request:read']);
        $this->requesterEntitySearch = $this->filters['requester_legal_entity'];
        $this->validate(['filters.requester_legal_entity' => 'nullable|uuid']);

        if ($this->filters['status'] === 'draft') {
            abort_unless($this->person?->exists, 404);
            $this->validate([
                'filters.requester_legal_entity' => 'nullable|uuid',
                'filters.context_episode_id' => 'nullable|uuid',
                'filters.encounter' => 'nullable|uuid',
                'filters.program' => 'nullable|uuid',
                'filters.code' => 'nullable|string|max:100',
            ]);
            $this->requests = [];
            $this->page = $this->totalPages = 1;
        } else {
            parent::search($resetPage);
        }

        $this->drafts = [];
        if (!in_array($this->filters['status'], ['', 'draft'], true)
            || !Auth::user()->can('create', [DeviceRequestRequest::class, $this->legalEntity])) {
            return;
        }

        $employee = app(DeviceRequestLifecycleService::class)->authorizeAction($this->legalEntity, 'device_request:write');
        $this->drafts = DeviceRequestRequest::wherePersonId($this->personId)->whereEmployeeId($employee->id)
            ->whereStatus('draft')->whereNotNull('request_payload')->latest()->get()
            ->map(static fn (DeviceRequestRequest $draft): array => array_merge($draft->requestPayload, [
                'uuid' => $draft->uuid, 'status' => 'draft', 'isDraft' => true,
                'authored_on' => $draft->createdAt->toIso8601String(),
            ]))
            ->filter(function (array $draft): bool {
                foreach (['program' => 'program.identifier.value', 'encounter' => 'encounter.identifier.value',
                    'context_episode_id' => 'context.identifier.value', 'requester_legal_entity' => 'requester_legal_entity.identifier.value'] as $filter => $path) {
                    if ($this->filters[$filter] !== '' && data_get($draft, $path) !== $this->filters[$filter]) {
                        return false;
                    }
                }

                return $this->filters['code'] === '' || collect(data_get($draft, 'code.coding', []))->contains('code', $this->filters['code']);
            })->values()->all();
    }

    public function resetFilters(): void
    {
        $this->filters = array_fill_keys(array_keys($this->filters), '');
        $this->resetValidation();
        if ($this->canReadRequests) {
            $this->search();
        }
    }

    #[Computed]
    public function deviceTypes(): array
    {
        return dictionary()->basics()->byName('eHealth/assistive_devices')->flattenedChildValues()->toArray()
            + dictionary()->basics()->byName('device_definition_classification_type')->flattenedChildValues()->toArray();
    }

    #[Computed]
    public function programs(): array
    {
        return dictionary()->medicalPrograms()->where('is_active', true)->where('type', Type::DEVICE->value)->values()->toArray();
    }

    public function render()
    {
        return view('livewire.person.records.device-requests', [
            'person' => $this->person,
            'records' => array_merge($this->drafts, $this->requests),
            'deviceTypes' => $this->deviceTypes,
            'programs' => $this->programs,
            'canReadRequests' => $this->canReadRequests,
        ])->layout('layouts.app');
    }
}
