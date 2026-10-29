<?php

declare(strict_types=1);

namespace App\Livewire\Person\Records;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Enums\Person\AuthenticationMethod;
use App\Enums\Specimen\Status as SpecimenStatus;
use App\Models\Icd10;
use App\Enums\Status;
use App\Enums\User\Role;
use App\Models\CarePlan;
use App\Models\MedicalEvents\Sql\Approval;
use App\Models\MedicalEvents\Sql\DiagnosticReport;
use App\Models\MedicalEvents\Sql\Encounter;
use App\Models\MedicalEvents\Sql\Episode;
use App\Models\MedicalEvents\Sql\Procedure;
use App\Models\MedicalEvents\Sql\ServiceRequestRequest;
use App\Models\MedicalEvents\Sql\Specimen;
use App\Models\Person\Person;
use App\Models\Employee\Employee;
use App\Services\MedicalEvents\Fhir;
use App\Services\MedicalEvents\PatientApprovalService;
use App\Livewire\Person\Forms\PatientApprovalForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class PatientApproval extends Component
{
    #[Locked]
    public int $personId;

    public bool $open = false;
    public string $step = 'form';
    public string $mode = '';
    public string $resourceType = 'episode_of_care';
    public string $search = '';
    public array $resourceIds = [];

    public PatientApprovalForm $form;

    #[Locked]
    public array $referralAvailability = [];

    public string $childType = 'encounter';
    public string $childUuid = '';
    public string $groupId = '';
    public string $previewSection = '';
    public string $previewTitle = '';
    public string $previewCodingSystem = 'all';
    public array $previewRows = [];
    public bool $includeDetectedIssue = false;
    public bool $grantToLegalEntity = false;
    public string $accessLevel = 'read';
    public array $authMethods = [];
    public string $selectedAuthUuid = '';
    public bool $leafletConfirmed = false;
    public string $verificationCode = '';
    public bool $smsResent = false;

    #[Locked]
    public ?string $approvalId = null;

    #[Locked]
    public ?int $linkId = null;

    #[Locked]
    public string $selectedAuthType = '';

    #[Locked]
    public ?string $smsPhone = null;

    #[Locked]
    public ?int $smsResendAvailableAt = null;

    public function showGroupDetails(string $section, string $groupId): void
    {
        if (!in_array($section, ['codes', 'services'], true)) {
            return;
        }

        if (!in_array($this->mode, ['forbidden_group', 'diagnoses_group', 'service_group'], true)) {
            return;
        }

        if ($this->mode === 'diagnoses_group' && $section !== 'codes') {
            return;
        }

        if ($this->mode === 'service_group' && $section !== 'services') {
            return;
        }

        $group = collect($this->selectableGroups)->firstWhere('id', $groupId);

        if ($group === null) {
            return;
        }

        try {
            $rows = match ($this->mode) {
                'diagnoses_group' => $this->diagnosisCodeRows(
                    EHealth::diagnoseGroup()
                        ->getDetails($groupId)
                        ->getData()['diagnoses_group_codes']
                ),
                'forbidden_group' => $this->forbiddenGroupRows(
                    $section,
                    EHealth::forbiddenGroup()->getDetails($groupId)->getData()
                ),
                'service_group' => $this->serviceRows($group),
            };

            $this->previewRows = $rows;
            $this->previewCodingSystem = 'all';

            $this->previewTitle = trim(($group['code'] ?? '').' '.$group['name']).' — '.(
                $section === 'codes' ? __('patients.list_of_diagnosis_codes') : __('patients.list_of_services')
            );

            $this->previewSection = $section;
        } catch (Throwable) {
            $this->addError('groupId', __('approval.create.group_details_failed'));
        }
    }

    public function updatedGroupId(): void
    {
        $this->resetValidation('groupId');
    }

    public function closePreview(): void
    {
        $this->reset('previewSection', 'previewRows');
    }

    private function forbiddenGroupRows(string $section, array $details): array
    {
        if ($section === 'codes') {
            return $this->diagnosisCodeRows($details['forbidden_group_codes']);
        }

        $services = dictionary()
            ->services()
            ->flattened()
            ->keyBy('id');

        return collect($details['forbidden_group_services'])
            ->map(static function (array $item) use ($services): array {
                $service = $services->get($item['service_id']);

                return [
                    'code' => $service['code'] ?? $item['service_id'],
                    'name' => $service['name'] ?? '',
                ];
            })
            ->all();
    }

    private function diagnosisCodeRows(array $codes): array
    {
        $icdSystem = 'eHealth/ICD10_AM/condition_codes';
        $icpcSystem = 'eHealth/ICPC2/condition_codes';

        $items = collect($codes)
            ->map(static fn (array $item): array => [
                ...$item,
                'system' => $item['system'] ?? $icdSystem,
            ]);

        $icdDescriptions = Icd10::query()
            ->whereIn('code', $items->where('system', $icdSystem)->pluck('code'))
            ->pluck('description', 'code');

        $icpcDescriptions = dictionary()
            ->basics()
            ->byName($icpcSystem)
            ->whereIn('code', $items->where('system', $icpcSystem)->pluck('code'))
            ->asCodeDescription();

        return $items
            ->map(static fn (array $item): array => [
                'code' => $item['code'],
                'name' => ($item['system'] === $icdSystem ? $icdDescriptions : $icpcDescriptions)->get($item['code'], ''),
                'system' => $item['system'],
            ])
            ->all();
    }

    private function serviceRows(array $node): array
    {
        $rows = [];

        foreach ($node['services'] ?? [] as $service) {
            if (($service['is_active'] ?? true) === false) {
                continue;
            }

            $rows[] = [
                'code' => $service['code'],
                'name' => $service['name'],
            ];

            array_push($rows, ...$this->serviceRows($service));
        }

        foreach ($node['groups'] ?? [] as $group) {
            if (($group['is_active'] ?? true) === false) {
                continue;
            }

            array_push($rows, ...$this->serviceRows($group));
        }

        return $rows;
    }

    public function openDrawer(): void
    {
        if (!Auth::user()->can('create', Approval::class)) {
            return;
        }

        if ($this->linkId !== null) {
            $this->step = $this->approvalId === null ? 'processing' : 'verifying';
        } elseif ($this->approvalId !== null) {
            $this->step = $this->selectedAuthType === AuthenticationMethod::OFFLINE->value ? 'confirming' : 'code';
        } else {
            $this->step = 'form';
        }

        $this->open = true;
    }

    public function closeDrawer(): void
    {
        $this->open = false;
        $this->closePreview();
    }

    public function updatedMode(): void
    {
        $this->reset(
            'resourceIds',
            'groupId',
            'childUuid',
            'search',
            'includeDetectedIssue',
            'grantToLegalEntity',
            'accessLevel'
        );

        $this->closePreview();
        $this->resetValidation();
        $this->referralAvailability = [];
        $this->accessLevel = $this->mode === 'resource' && in_array($this->resourceType, ['encounter', 'procedure'], true) ? 'write' : 'read';

        if ($this->mode === 'service_request') {
            $this->loadReferralAvailability();
        }
    }

    private function loadReferralAvailability(): void // це існує до моменту додавання permitted_resources в ЕН
    {
        try {
            $this->referralAvailability = app(PatientApprovalService::class)
                ->referralAvailability($this->person());
        } catch (Throwable) {
            $this->addError('resourceIds', __('approval.create.referral_check_failed'));
        }
    }

    public function updatedResourceType(): void
    {
        $this->resourceIds = [];
        $this->search = '';
        $this->accessLevel = in_array($this->resourceType, ['encounter', 'procedure'], true) ? 'write' : 'read';
    }

    public function updatedChildType(): void
    {
        $this->childUuid = '';
        $this->search = '';
    }

    public function selectRecord(string $uuid): void
    {
        if (!in_array($this->mode, ['resource', 'child', 'service_request'], true)) {
            return;
        }

        $type = $this->recordType();

        if (!isset(PatientApprovalService::RESOURCE_MODELS[$type])) {
            return;
        }

        $record = PatientApprovalService::RESOURCE_MODELS[$type]::query()
            ->where('person_id', $this->personId)
            ->where('uuid', $uuid)
            ->first();

        if ($record === null) {
            return;
        }

        if ($this->mode !== 'service_request' && !PatientApprovalService::approvalStatusAllowed($type, $record->getRawOriginal('status'))) {
            $this->addError(
                $this->mode === 'child' ? 'childUuid' : 'resourceIds',
                $type === 'specimen' ? __('approval.create.specimen_not_available') : __('approval.create.invalid_record_status')
            );

            return;
        }

        // !($this->referralAvailability[$uuid] ?? false) переробити після появи permitted_resources в ЕН
        if ($this->mode === 'service_request' && ($this->resourceIds[0] ?? null) !== $uuid && !($this->referralAvailability[$uuid] ?? false)) {
            $this->addError(
                'resourceIds',
                array_key_exists($uuid, $this->referralAvailability) ? __('approval.create.referral_no_resources') : __('approval.create.referral_check_failed')
            );

            return;
        }

        switch ($this->mode) {
            case 'resource':
                $this->resourceIds = in_array($uuid, $this->resourceIds, true) ? array_values(array_diff($this->resourceIds, [$uuid])) : [...$this->resourceIds, $uuid];
                break;

            case 'child':
                $this->childUuid = $this->childUuid === $uuid ? '' : $uuid;
                break;

            case 'service_request':
                $this->resourceIds = ($this->resourceIds[0] ?? null) === $uuid ? [] : [$uuid];
                break;
        }

        $this->resetValidation();
    }

    #[Computed]
    public function availableRecords(): array
    {
        $type = $this->recordType();

        if (!isset(PatientApprovalService::RESOURCE_MODELS[$type])) {
            return [];
        }

        $query = PatientApprovalService::RESOURCE_MODELS[$type]::query()
            ->where('person_id', $this->personId)
            ->whereNotNull('uuid');

        if (isset(PatientApprovalService::ALLOWED_STATUSES[$type])) {
            $query->whereIn('status', PatientApprovalService::ALLOWED_STATUSES[$type]);
        }

        $this->applySearchFilter($query, $type);

        return $query
            ->with($this->recordRelations($type))
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn ($record): array => $this->recordOption($record, $type))
            ->all();
    }

    private function applySearchFilter(Builder $query, string $type): void
    {
        $search = trim($this->search);

        if ($search === '') {
            return;
        }

        $like = '%'.$search.'%';

        $matchingCodes = in_array($type, ['encounter', 'specimen'], true)
            ? collect($this->recordTypeNames)
                ->filter(static fn (string $name, string $code): bool =>
                    str_contains(mb_strtolower($name), mb_strtolower($search)) || str_contains(mb_strtolower($code), mb_strtolower($search))
                )
                ->keys()
                ->all()
            : [];

        $query->where(function (Builder $query) use ($type, $like, $matchingCodes): void {
            $query->whereLike('uuid', $like);

            switch ($type) {
                case 'episode_of_care':
                    $query->orWhereLike('name', $like);
                    break;

                case 'diagnostic_report':
                case 'procedure':
                    $query->orWhereHas('code', static function (Builder $code) use ($like): void {
                        $code->whereLike('display_value', $like)
                            ->orWhereLike('value', $like)
                            ->orWhereHas('type.coding', static fn (Builder $coding) => $coding->whereLike('code', $like));
                    });
                    break;

                case 'encounter':
                case 'specimen':
                    $query->orWhereHas('type', static function (Builder $concept) use ($like, $matchingCodes): void {
                        $concept->whereLike('text', $like)
                            ->orWhereHas('coding', static function (Builder $coding) use ($like, $matchingCodes): void {
                                $coding->whereLike('code', $like);

                                if ($matchingCodes !== []) {
                                    $coding->orWhereIn('code', $matchingCodes);
                                }
                            });
                    });
                    break;

                case 'care_plan':
                    $query->orWhereLike('title', $like);
                    break;

                case 'service_request':
                    $query->orWhereLike('request_number', $like);
                    break;
            }
        });
    }

    #[Computed]
    public function selectedRecords(): array
    {
        $type = $this->recordType();

        $ids = match ($this->mode) {
            'resource' => $this->resourceIds,
            'child' => $this->childUuid !== '' ? [$this->childUuid] : [],
            'service_request' => array_slice($this->resourceIds, 0, 1),
            default => [],
        };

        if ($ids === [] || !isset(PatientApprovalService::RESOURCE_MODELS[$type])) {
            return [];
        }

        return PatientApprovalService::RESOURCE_MODELS[$type]::query()
            ->where('person_id', $this->personId)
            ->whereIn('uuid', $ids)
            ->with($this->recordRelations($type))
            ->get()
            ->map(fn ($record): array => $this->recordOption($record, $type))
            ->all();
    }
    
    private function recordType(): string
    {
        return match ($this->mode) {
            'child' => $this->childType,
            'service_request' => 'service_request',
            default => $this->resourceType,
        };
    }

    private function recordRelations(string $type): array
    {
        return match ($type) {
            'episode_of_care' => ['period'],
            'diagnostic_report' => ['code', 'effectivePeriod'],
            'encounter' => ['type.coding', 'period'],
            'procedure' => ['code', 'performedPeriod'],
            'specimen' => ['type.coding', 'collection.collectedPeriod'],
            default => [],
        };
    }

    #[Computed]
    public function recordTypeNames(): array
    {
        $dictionary = match ($this->recordType()) {
            'encounter' => 'eHealth/encounter_types',
            'specimen' => 'specimen_types',
            default => null,
        };

        return $dictionary !== null
            ? dictionary()
                ->basics()
                ->byName($dictionary, false)
                ->asCodeDescription()
                ->all()
            : [];
    }

    private function recordOption($record, string $type): array
    {
        $name = match ($type) {
            'episode_of_care' => $record->name,
            'diagnostic_report', 'procedure' => $record->code?->displayValue ?: $record->code?->value,
            'encounter', 'specimen' => $this->recordTypeNames[$record->type?->coding?->first()?->code] ?? $record->type?->text ?? $record->type?->coding?->first()?->code,
            'care_plan' => $record->title,
            'service_request' => $record->requestNumber,
        };

        $date = match ($type) {
            'episode_of_care', 'encounter' => collect([
                $record->period?->start, $record->period?->end
            ])->filter()->map(fn ($date) => formatDisplayDate($date))->implode(' — '),
            'diagnostic_report' => $record->effectiveDateTime
                ? formatDisplayDate($record->effectiveDateTime)
                : (collect([
                    $record->effectivePeriod?->start,
                    $record->effectivePeriod?->end,
                ])->filter()->map(fn ($date) => formatDisplayDate($date))->implode(' — ') ?: formatDisplayDate($record->issued)),
            'procedure' => $record->performedDateTime
                ? formatDisplayDate($record->performedDateTime)
                : collect([
                    $record->performedPeriod?->start,
                    $record->performedPeriod?->end,
                ])->filter()->map(fn ($date) => formatDisplayDate($date))->implode(' — '),
            'care_plan' => collect([
                $record->periodStart,
                $record->periodEnd,
            ])->filter()->map(fn ($date) => formatDisplayDate($date))->implode(' — '),
            'specimen' => formatDisplayDate($record->collection?->collectedDateTime ?? $record->collection?->collectedPeriod?->start ?? $record->receivedTime ?? $record->ehealthInsertedAt),
            'service_request' => formatDisplayDate($record->startedAt),
        };

        return [
            'uuid' => $record->uuid,
            'name' => $name ?: __('approval.create.'.$type),
            'date' => $date,
            'selectable' => $type !== 'service_request' || ($this->referralAvailability[$record->uuid] ?? false),
            'availability_known' => $type !== 'service_request' || array_key_exists($record->uuid, $this->referralAvailability),
        ];
    }

    #[Computed]
    public function selectableGroups(): array
    {
        return match ($this->mode) {
            'diagnoses_group' => dictionary()
                ->diagnoseGroups()
                ->toArray(),

            'forbidden_group' => dictionary()
                ->forbiddenGroups()
                ->toArray(),

            'service_group' => dictionary()
                ->services()
                ->flatMap(static fn (array $service): array => $service['groups'] ?? [])
                ->filter(static fn (array $group): bool => $group['is_active'] ?? false)
                ->values()
                ->toArray(),

            default => [],
        };
    }

    public function chooseAuthentication(): void
    {
        if (Auth::user()->cannot('create', Approval::class)) {
            return;
        }
        $this->validatedSelection();

        $this->authMethods = app(PatientApprovalService::class)
            ->availableAuthenticationMethods($this->person());

        $this->reset('selectedAuthUuid', 'leafletConfirmed');
        $this->step = 'methods';
    }

    public function create(): void
    {
        if (Auth::user()->cannot('create', Approval::class)) {
            return;
        }

        if ($this->approvalId !== null || $this->linkId !== null) {
            return;
        }

        $this->validate(['selectedAuthUuid' => ['required', 'uuid']]);

        $service = app(PatientApprovalService::class);
        $method = collect($service->availableAuthenticationMethods($this->person()))->firstWhere('uuid', $this->selectedAuthUuid);

        if (!$method) {
            throw ValidationException::withMessages([
                'selectedAuthUuid' => __('approval.create.select_auth_method'),
            ]);
        }

        if ($this->mode === 'person' && $method['type'] === 'OFFLINE') {
            throw ValidationException::withMessages([
                'selectedAuthUuid' => __('approval.create.person_requires_otp'),
            ]);
        }

        if ($method['type'] === 'OFFLINE' && !$this->leafletConfirmed) {
            throw ValidationException::withMessages([
                'leafletConfirmed' => __('approval.create.leaflet_required'),
            ]);
        }

        $employee = $this->employeeForApproval();
        $payload = $this->buildPayload($employee->uuid);

        if ($employee->employee_type === 'ASSISTANT' && $payload['access_level'] === 'write') {
            throw ValidationException::withMessages([
                'accessLevel' => __('approval.create.assistant_read_only'),
            ]);
        }

        $payload['authorize_with'] = $this->selectedAuthUuid;

        try {
            $result = $service->create(
                $this->person(),
                $payload
            );

            $this->selectedAuthType = $method['type'];
            $this->smsPhone = $method['phone_number'] ?? null;
            $this->approvalId = $result['approval_id'];
            $this->linkId = $result['link_id'];
            $this->reset('smsResent', 'smsResendAvailableAt');
            $this->step = $this->linkId !== null ? 'processing' : 'confirming';

            if ($this->step === 'confirming') {
                $this->continueAfterCreation();
            }
        } catch (Throwable) {
            $this->addError('approvalCreation', __('approval.create.create_failed'));
        }
    }

    public function poll(): void
    {
        if ($this->linkId === null || !in_array($this->step, ['processing', 'verifying'], true)) {
            return;
        }

        try {
            $status = app(PatientApprovalService::class)->jobStatus(
                $this->linkId,
                $this->person()
            );

            if ($status['status'] === 'pending') {
                return;
            }

            if ($status['status'] === 'failed') {
                $this->handlePollFailure($status['message']);

                return;
            }

            $this->handlePollSuccess($status);
        } catch (Throwable $exception) {
            $this->addError('approvalCreation', __('approval.create.job_failed'));
        }
    }

    private function handlePollFailure(string $message): void
    {
        $wasVerifying = $this->step === 'verifying';
        $this->linkId = null;
        $this->step = $wasVerifying ? ($this->selectedAuthType === 'OFFLINE' ? 'confirming' : 'code') : 'form';
        $this->addError($this->mode === 'service_request' && !$wasVerifying ? 'resourceIds' : 'approvalCreation', $message);
    }

    private function handlePollSuccess(array $status): void
    {
        if ($this->step === 'verifying') {
            app(PatientApprovalService::class)->syncVerified($this->person(), $this->approvalId);

            $this->linkId = null;
            $this->finish();

            return;
        }

        $this->approvalId = $status['approval_id'];
        $this->linkId = null;
        $this->step = 'confirming';

        $this->continueAfterCreation();
    }

    public function continueAfterCreation(): void
    {
        if ($this->step !== 'confirming' || $this->approvalId === null) {
            return;
        }

        try {
            $service = app(PatientApprovalService::class);

            if ($this->selectedAuthType === 'OFFLINE') {
                $this->linkId = $service->verify(
                    $this->person(),
                    $this->approvalId,
                    null
                );

                if ($this->linkId !== null) {
                    $this->step = 'verifying';
                } else {
                    $this->finish();
                }

                return;
            }

            $details = $service->details($this->person(), $this->approvalId);
            $this->smsPhone = $details['authentication_method_current']['number'] ?? $this->smsPhone;
            $this->smsResendAvailableAt = $service->smsResendAvailableAt($this->person(), $this->approvalId);
            $this->step = 'code';
        } catch (Throwable) {
            $this->addError('approvalCreation', __('approval.create.confirmation_failed'));
        }
    }

    public function verifyCode(): void
    {
        if (Auth::user()->cannot('create', Approval::class)) {
            return;
        }

        if ($this->step !== 'code' || $this->approvalId === null || !in_array($this->selectedAuthType, ['OTP', 'THIRD_PERSON'], true)) {
            return;
        }

        $this->validate(['verificationCode' => ['required', 'regex:/^[0-9]{4}$/']]);

        try {
            $this->linkId = app(PatientApprovalService::class)->verify(
                $this->person(),
                $this->approvalId,
                (int) $this->verificationCode
            );

            if ($this->linkId !== null) {
                $this->step = 'verifying';

                return;
            }

            $this->finish();
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $message = data_get($exception->getDetails(), 'error.message');

            $this->addError(
                'verificationCode',
                $message === 'Invalid verification code' ? __('errors.ehealth.messages.invalid_verification_code') : __('approval.create.verify_ehealth_failed')
            );
        } catch (Throwable) {
            $this->addError('verificationCode', __('approval.create.verify_processing_failed'));
        }
    }
    
    public function resendSms(): void
    {
        if (Auth::user()->cannot('create', Approval::class)) {
            return;
        }

        if ($this->step !== 'code' || $this->approvalId === null || $this->smsResent) {
            return;
        }

        $this->resetValidation('smsResend');

        try {
            app(PatientApprovalService::class)->resendSms($this->person(), $this->approvalId);

            $this->smsResent = true;
        } catch (ValidationException $exception) {
            $this->addError('smsResend', $exception->errors()['smsResend'][0] ?? __('approval.create.resend_failed'));
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->addError('smsResend', __('approval.create.resend_failed'));
        } catch (Throwable $exception) {
            $this->addError('smsResend', __('approval.create.resend_failed'));
        }
    }

    protected function finish(): void
    {
        $this->open = false;
        $this->step = 'form';
        $this->approvalId = null;
        $this->linkId = null;
        $this->smsResent = false;
        $this->smsResendAvailableAt = null;
        $this->verificationCode = '';

        Session::flash('success', __('approval.create.verified'));

        $this->dispatch('patient-approvals-changed');
    }

    private function person(): Person
    {
        return Person::findOrFail($this->personId);
    }

    private function employeeForApproval(): Employee
    {
        $allowed = [Role::DOCTOR->value, Role::SPECIALIST->value, Role::ASSISTANT->value];

        if ($this->mode !== 'person') {
            $allowed[] = Role::MED_COORDINATOR->value;
        }

        $employee = Auth::user()->activeDoctorEmployee();

        if (!$employee || $employee->legalEntityId !== legalEntity()->id || !$employee->isActive || $employee->status !== Status::APPROVED || !in_array($employee->employeeType, $allowed, true)) {
            throw ValidationException::withMessages(['mode' => __('approval.create.role_forbidden')]);
        }

        return $employee;
    }

    private function validatedSelection(): array
    {
        return $this->form->validateSelection(
            $this->person(),
            [
                'mode' => $this->mode,
                'resourceType' => $this->resourceType,
                'resourceIds' => $this->resourceIds,
                'childType' => $this->childType,
                'childUuid' => $this->childUuid,
                'groupId' => $this->groupId,
                'selectableGroups' => in_array($this->mode, ['diagnoses_group', 'forbidden_group', 'service_group'], true) ? $this->selectableGroups : [],
                'includeDetectedIssue' => $this->includeDetectedIssue,
                'grantToLegalEntity' => $this->grantToLegalEntity,
                'accessLevel' => $this->accessLevel,
            ]
        );
    }

    private function buildPayload(string $employeeUuid): array
    {
        $selection = $this->validatedSelection();

        return Fhir::approval()->toFhir(
            $selection,
            $employeeUuid,
            legalEntity()->uuid
        );
    }

    public function render(): View
    {
        return view('livewire.person.records.parts.patient-approval');
    }
}