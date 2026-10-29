<?php

declare(strict_types=1);

namespace App\Livewire\DeviceRequest;

use App\Classes\eHealth\EHealth;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\DeviceRequestRequest;
use App\Models\Person\Person;
use App\Repositories\LegalEntityRepository;
use App\Repositories\PersonRepository;
use App\Services\MedicalEvents\DeviceRequestLifecycleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class DeviceRequestIndex extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $legalEntityId;

    #[Locked]
    public ?int $personId = null;

    public string $patientSearch = '';

    #[Locked]
    public array $patients = [];

    public array $filters = ['requester_legal_entity' => '', 'status' => '', 'code' => '', 'context_episode_id' => '', 'encounter' => '', 'program' => ''];

    public string $requesterEntitySearch = '';

    #[Locked]
    public array $legalEntityOptions = [];

    #[Locked]
    public array $requests = [];

    #[Locked]
    public array $drafts = [];

    #[Locked]
    public array $catalog = [];

    #[Locked]
    public array $selected = [];

    #[Locked]
    public array $dispenses = [];

    #[Locked]
    public array $dispenseDetails = [];

    #[Locked]
    public array $reasons = [];

    public string $reason = '';

    public string $reasonText = '';

    #[Locked]
    public string $phone = '';

    public bool $phoneConfirmed = false;

    public int $page = 1;

    public int $totalPages = 1;

    #[Locked]
    public ?string $selectedId = null;

    #[Locked]
    public string $action = '';

    public bool $showSignatureModal = false;

    public array $form = ['knedp' => '', 'keyContainerUpload' => null, 'keyContainerFileName' => '', 'password' => ''];

    public function mount(LegalEntity $legalEntity, ?Person $person = null): void
    {
        $this->legalEntityId = $legalEntity->id;
        $this->personId = $person?->exists ? $person->id : null;
        $this->filters['requester_legal_entity'] = $legalEntity->uuid;
        $this->legalEntityOptions = app(LegalEntityRepository::class)->getLegalEntitiesList();
        $this->requesterEntitySearch = collect($this->legalEntityOptions)->firstWhere('uuid', $legalEntity->uuid)['name'] ?? $legalEntity->name ?? $legalEntity->uuid;

        if ($person?->exists) {
            if (Auth::user()->can('create', [DeviceRequestRequest::class, $legalEntity])) {
                $employee = app(DeviceRequestLifecycleService::class)->authorizeAction($legalEntity, 'device_request:write');
                $this->drafts = DeviceRequestRequest::wherePersonId($person->id)->whereEmployeeId($employee->id)
                    ->whereStatus('draft')->whereNotNull('request_payload')->latest()->get()
                    ->map(fn ($draft): array => ['uuid' => $draft->uuid, 'device' => $draft->deviceId, 'updatedAt' => $draft->updatedAt->format('d.m.Y H:i')])->all();
            }

            $this->search();
        }
    }

    #[Computed]
    public function legalEntity(): LegalEntity
    {
        return app(LegalEntityRepository::class)->getById($this->legalEntityId);
    }

    #[Computed]
    public function person(): ?Person
    {
        return $this->personId === null ? null : app(PersonRepository::class)->getById($this->personId);
    }

    public function searchPatients(): void
    {
        $this->authorize('perform', [DeviceRequestRequest::class, $this->legalEntity, 'device_request:read']);
        $this->authorize('viewAny', Person::class);
        $this->validate(['patientSearch' => 'required|string|min:3|max:100']);
        $search = $this->patientSearch;
        $this->patients = Person::whereHas('names', fn ($query) => $query->where('last_name', 'ilike', '%'.$search.'%'))
            ->with('names')->limit(20)->get()->map(fn (Person $person): array => ['id' => $person->id, 'name' => $person->fullName])->all();
    }

    public function openPatient(int $id): void
    {
        $this->authorize('perform', [DeviceRequestRequest::class, $this->legalEntity, 'device_request:read']);
        $this->authorize('viewAny', Person::class);
        $this->redirectRoute('device-requests.index', ['legalEntity' => $this->legalEntity, 'person' => Person::findOrFail($id)]);
    }

    public function search(bool $resetPage = true): void
    {
        $this->authorize('perform', [DeviceRequestRequest::class, $this->legalEntity, 'device_request:read']);
        abort_unless($this->person?->exists, 404);
        $this->validate(['requesterEntitySearch' => 'nullable|string|max:500']);
        $entitySearch = trim($this->requesterEntitySearch);
        if ($entitySearch === '' || \Illuminate\Support\Str::isUuid($entitySearch)) {
            $this->filters['requester_legal_entity'] = $entitySearch;
        } else {
            $matches = collect($this->legalEntityOptions)->filter(
                fn (array $entity): bool => mb_strtolower($entity['name']) === mb_strtolower($entitySearch)
            );
            if ($matches->count() !== 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'requesterEntitySearch' => __('device-requests.labels.choose_entity_from_list'),
                ]);
            }
            $this->filters['requester_legal_entity'] = $matches->first()['uuid'];
        }
        $this->validate([
            'filters.requester_legal_entity' => 'nullable|uuid', 'filters.context_episode_id' => 'nullable|uuid',
            'filters.encounter' => 'nullable|uuid', 'filters.program' => 'nullable|uuid',
            'filters.status' => 'nullable|in:active,completed,revoked,entered-in-error', 'filters.code' => 'nullable|string|max:100',
        ]);

        if ($resetPage) {
            $this->page = 1;
        }

        try {
            $response = EHealth::deviceRequest()->getBySearchParams($this->person->uuid, array_filter($this->filters) + ['page' => $this->page]);
            $this->requests = $response->getData();
            $this->totalPages = (int) ($response->getPaging()['total_pages'] ?? 1);
        } catch (Throwable $exception) {
            $this->requests = [];
            $this->error($exception);
        }
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, min($this->totalPages, $page));
        $this->search(false);
    }

    public function showDetails(string $id, bool $loadCatalog = true): void
    {
        $service = app(DeviceRequestLifecycleService::class);
        $this->authorize('perform', [DeviceRequestRequest::class, $this->legalEntity, 'device_request:read']);
        abort_unless($this->person?->exists, 404);

        try {
            abort_unless(\Illuminate\Support\Str::isUuid($id), 404);
            $response = EHealth::deviceRequest()->getById($this->person->uuid, $id);
            $this->selected = $response->getData();
            $this->selectedId = $id;
            $this->dispenses = $this->dispenseDetails = [];
            $this->catalog = [];
            $this->phoneConfirmed = false;
            $this->phone = data_get($response->getUrgent(), 'authentication_method_current.phone_number', '');
            $modelId = data_get($this->selected, 'code_reference.identifier.value');

            if ($modelId && $loadCatalog) {
                $this->catalog = [EHealth::deviceDefinition()->getById($modelId)->getData()];
            } else {
                foreach ($this->selected['code']['coding'] ?? [] as $coding) {
                    $labels = $service->dictionary($coding['system']);
                    $models = $loadCatalog ? $service->collectPages(fn (int $page) => EHealth::deviceDefinition()->getMany([
                        'classification_type_system' => $coding['system'], 'classification_type_code' => $coding['code'], 'is_active' => true, 'page' => $page,
                    ])) : [];

                    foreach ($models as $model) {
                        $this->catalog[] = $model;
                    }

                    foreach ($this->selected['code']['coding'] as &$selectedCoding) {
                        if ($selectedCoding['system'] === $coding['system'] && $selectedCoding['code'] === $coding['code']) {
                            $selectedCoding['display'] = $labels[$coding['code']] ?? $coding['code'];
                        }
                    }
                    unset($selectedCoding);
                }
            }
        } catch (Throwable $exception) {
            $this->selected = [];
            $this->selectedId = null;
            $this->error($exception);
        }
    }

    public function startAction(string $action): void
    {
        abort_unless($this->selectedId !== null && $this->person?->exists && in_array($action, ['revoke', 'mark_in_error'], true), 404);
        $service = app(DeviceRequestLifecycleService::class);
        $this->authorize('perform', [DeviceRequestRequest::class, $this->legalEntity, 'device_request:'.$action]);

        try {
            $service->assertTransition($service->details($this->person, $this->selectedId), $action);
            $this->action = $action;
            $this->reason = $this->reasonText = '';
            $this->reasons = $service->dictionary($action === 'revoke' ? 'device_request_revoke_reasons' : 'device_request_mark_in_error_reasons');
        } catch (Throwable $exception) {
            $this->error($exception);
        }
    }

    public function openSignatureModal(): void
    {
        try {
            app(DeviceRequestLifecycleService::class)->actionContent($this->person, $this->legalEntity, $this->selectedId, $this->action, $this->reason, $this->reasonText);
            $this->showSignatureModal = true;
        } catch (Throwable $exception) {
            $this->error($exception);
        }
    }

    public function sign(): void
    {
        $this->validate(['form.knedp' => 'required|string', 'form.keyContainerUpload' => 'required|file|max:1024|extensions:dat,pfx,zs2,jks', 'form.password' => 'required|string']);

        try {
            $service = app(DeviceRequestLifecycleService::class);
            $content = $service->actionContent($this->person, $this->legalEntity, $this->selectedId, $this->action, $this->reason, $this->reasonText);
            $signedData = signatureService()->signData($content, $this->form['password'], $this->form['knedp'], $this->form['keyContainerUpload'], (string) Auth::user()->party->taxId);
            $this->selected = $service->submitAction($this->person, $this->legalEntity, $this->selectedId, $this->action, $signedData);
            $this->action = '';
            Session::flash('success', __('device-requests.messages.status_changed'));
            $this->search(false);
        } catch (Throwable $exception) {
            $this->error($exception);
        } finally {
            $this->showSignatureModal = false;
            $this->form['password'] = '';
            $this->form['keyContainerUpload'] = null;
            $this->form['keyContainerFileName'] = '';
        }
    }

    public function complete(): void
    {
        try {
            $this->selected = app(DeviceRequestLifecycleService::class)->submitAction($this->person, $this->legalEntity, $this->selectedId, 'complete');
            Session::flash('success', __('device-requests.messages.completed'));
            $this->search(false);
        } catch (Throwable $exception) {
            $this->error($exception);
        }
    }

    public function resendSms(): void
    {
        try {
            app(DeviceRequestLifecycleService::class)->resendOnce($this->person, $this->legalEntity, $this->selectedId, $this->phoneConfirmed, $this->phone);
            Session::flash('success', __('device-requests.messages.sms_resent'));
            $this->phoneConfirmed = false;
        } catch (Throwable $exception) {
            $this->error($exception);
        }
    }

    public function loadDispenses(): void
    {
        $service = app(DeviceRequestLifecycleService::class);
        $this->authorize('perform', [DeviceRequestRequest::class, $this->legalEntity, 'device_dispense:read']);
        abort_unless($this->selectedId !== null, 404);

        try {
            $this->dispenses = $service->collectPages(fn (int $page) => EHealth::deviceRequest()->getDispenses($this->person->uuid, ['based_on' => $this->selectedId, 'page' => $page]));
        } catch (Throwable $exception) {
            $this->error($exception);
        }
    }

    public function showDispense(string $id): void
    {
        abort_unless(\Illuminate\Support\Str::isUuid($id), 404);
        $this->authorize('perform', [DeviceRequestRequest::class, $this->legalEntity, 'device_dispense:read']);
        $this->validate(['selectedId' => 'required|uuid']);

        try {
            $record = EHealth::deviceRequest()->getDispense($this->person->uuid, $id)->getData();
            abort_unless(data_get($record, 'based_on.identifier.value') === $this->selectedId || collect($record['based_on'] ?? [])->contains(fn ($reference) => data_get($reference, 'identifier.value') === $this->selectedId), 404);
            $this->dispenseDetails = $record;
        } catch (Throwable $exception) {
            $this->error($exception);
        }
    }

    public function printout(): void
    {
        $this->authorize('perform', [DeviceRequestRequest::class, $this->legalEntity, 'device_request:read']);

        try {
            $html = app(DeviceRequestLifecycleService::class)->printoutHtml($this->person, $this->selectedId);
            $this->dispatch('print-device-request', html: $html);
        } catch (Throwable $exception) {
            $this->error($exception);
        }
    }

    private function error(Throwable $exception): void
    {
        if ($exception instanceof \Illuminate\Validation\ValidationException) {
            $this->setErrorBag($exception->validator->errors());
        } else {
            report($exception);
        }
        Session::flash('error', $exception->getMessage());
    }

    public function render()
    {
        return view('livewire.device-request.device-request-index', [
            'legalEntity' => $this->legalEntity,
            'person' => $this->person,
            'legalEntityNames' => collect($this->legalEntityOptions)->pluck('name', 'uuid')->all(),
        ])->layout('layouts.app');
    }
}
