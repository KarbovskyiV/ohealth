<?php

declare(strict_types=1);

namespace App\Livewire\CarePlan;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\CarePlan;
use App\Repositories\CarePlanRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class CarePlanCreate extends Component
{
    use WithFileUploads;

    public bool $showSignatureModal = false;



    // Care Plan form data
    public array $form = [
        'patient' => '',
        'medical_number' => '',
        'author' => '',
        'coAuthors' => [],
        'category' => '',
        'clinical_protocol' => '',
        'context' => '',
        'title' => '',
        'intent' => 'order',
        'period_start' => '',
        'period_end' => '',
        'encounter' => '',
        'description' => '',
        'note' => '',
        'inform_with' => '',
        'episodes' => [],
        'medical_records' => [],
        'knedp' => '',
        'keyContainerUpload' => null,
        'password' => '',
    ];

    public string $patientUuid = '';

    public array $categories = [];
    public array $diagnoses = [];
    public array $authMethods = [];
    public array $patientSuggestions = [];
    public array $dictionaries = [];
    public array $doctors = [];

    public function mount(): void
    {
        $this->patientUuid = request()->query('patientUuid', '');
        $encounterUuid = request()->query('encounterUuid', '');
        
        $person = \App\Models\Person\Person::where('uuid', $this->patientUuid)->first();
        if ($person) {
            $this->form['patient'] = trim($person->last_name . ' ' . $person->first_name . ' ' . ($person->second_name ?? ''));
            
            // Load patient's authentication methods for "inform_with" dropdown
            $this->authMethods = $person->authenticationMethods()->get()->map(fn($m) => [
                'value' => $m->type,
                'label' => \App\Enums\Person\AuthenticationMethod::tryFrom($m->type)?->label() ?? $m->type,
            ])->toArray();
        }

        if ($encounterUuid) {
            $this->form['encounter'] = $encounterUuid;
        } else {
            // If no encounter provided, try to find the latest one for this patient
            $latestEncounter = \App\Models\MedicalEvents\Sql\Encounter::where('person_id', $this->resolvePersonId())
                ->latest()
                ->first();
            if ($latestEncounter) {
                $this->form['encounter'] = $latestEncounter->uuid;
                $encounterUuid = $latestEncounter->uuid;
            }
        }

        if ($encounterUuid) {
            $encounter = \App\Models\MedicalEvents\Sql\Encounter::where('uuid', $encounterUuid)
                ->with(['diagnoses.condition'])
                ->first();
            if ($encounter) {
                // Pre-fill medical number
                $this->form['medical_number'] = (string) $encounter->id;
                
                // Pre-fill diagnoses for the UI list
                $this->diagnoses = $encounter->diagnoses->map(fn($d) => [
                    'date' => $d->condition?->asserted_date?->format('d.m.Y') ?? '-',
                    'name' => $d->condition?->code_display ?? $d->condition?->code ?? '-',
                ])->toArray();
            }
        }
        $this->form['period_start'] = now()->format('d.m.Y');

        // Pre-fill author from current employee
        $employee = Auth::user()?->activeEmployee();
        if ($employee) {
            $party = $employee->party;
            $this->form['author'] = implode(' ', array_filter([
                $party?->last_name, $party?->first_name, $party?->second_name,
            ]));
        }

        // Load doctors for co-authors
        $legalEntity = legalEntity();
        if ($legalEntity) {
            $this->doctors = \App\Models\Employee\Employee::where('legal_entity_id', $legalEntity->id)
                ->whereIn('employee_type', [\App\Enums\User\Role::DOCTOR, \App\Enums\User\Role::SPECIALIST])
                ->where('status', \App\Enums\Status::APPROVED)
                ->where('is_active', true)
                ->with('party')
                ->get()
                ->filter(fn($e) => $e->party !== null)
                ->map(fn($e) => [
                    'uuid' => $e->uuid,
                    'name' => ($e->party->full_name ?? 'Unknown') . ' (' . ($e->position ?? '') . ')',
                ])
                ->values()
                ->toArray();
        }

        // Load dictionaries (cached via DictionaryManager)
        try {
            $basics = app(\App\Services\Dictionary\DictionaryManager::class)->basics();
            $this->dictionaries['care_plan_categories'] = $basics->byName('eHealth/care_plan_categories')
                ?->asCodeDescription()
                ?->toArray() ?? [];
            $this->dictionaries['encounter_classes'] = $basics->byName('eHealth/encounter_classes')
                ?->asCodeDescription()
                ?->toArray() ?? [];
            $this->categories = $this->dictionaries['care_plan_categories'];
        } catch (\Exception $exception) {
            report($exception);
            // Dictionaries might not be cached yet; log and continue
            \Illuminate\Support\Facades\Log::warning('CarePlanCreate: failed to load dictionaries: ' . $exception->getMessage());
        }
    }

    /**
     * Search for patients as the user types.
     */
    public function updatedFormPatient(string $value): void
    {
        if (strlen($value) < 3) {
            $this->patientSuggestions = [];
            return;
        }

        $this->patientSuggestions = \App\Models\Person\Person::query()
            ->where('last_name', 'like', "%{$value}%")
            ->orWhere('first_name', 'like', "%{$value}%")
            ->orWhere('tax_id', 'like', "%{$value}%")
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'uuid' => $p->uuid,
                'name' => trim($p->last_name . ' ' . $p->first_name . ' ' . ($p->second_name ?? '')),
                'tax_id' => $p->tax_id,
            ])
            ->toArray();
    }

    /**
     * Select a patient from the suggestions.
     */
    public function selectPatient(string $uuid, string $name): void
    {
        $this->patientUuid = $uuid;
        $this->form['patient'] = $name;
        $this->patientSuggestions = [];

        $person = \App\Models\Person\Person::where('uuid', $uuid)->first();
        if ($person) {
            $this->authMethods = $person->authenticationMethods()->get()->map(fn($m) => [
                'value' => $m->type,
                'label' => \App\Enums\Person\AuthenticationMethod::tryFrom($m->type)?->label() ?? $m->type,
            ])->toArray();
        }
    }

    /**
     * Validation rules for the main form data.
     */
    protected function rules(): array
    {
        return [
            'form.category'         => 'required|string',
            'form.clinical_protocol' => 'nullable|string',
            'form.context'          => 'nullable|string',
            'form.title'            => 'required|string',
            'form.period_start'     => 'required|string',
            'form.period_end'       => 'nullable|string',
            'form.encounter'        => 'nullable|string',
            'form.description'      => 'nullable|string',
            'form.note'             => 'nullable|string',
            'form.inform_with'      => 'nullable|string',
            'form.episodes'         => 'nullable|array',
            'form.medical_records'  => 'nullable|array',
        ];
    }

    /**
     * Human-readable attribute names for validation errors.
     */
    protected function validationAttributes(): array
    {
        return [
            'form.category'          => __('care-plan.category'),
            'form.clinical_protocol' => __('care-plan.clinical_protocol'),
            'form.context'           => __('care-plan.context'),
            'form.title'             => __('care-plan.name_care_plan'),
            'form.period_start'      => __('care-plan.date_and_time_start'),
            'form.period_end'        => __('care-plan.date_and_time_end'),
            'form.encounter'         => __('care-plan.encounter'),
            'form.description'       => __('care-plan.extended_description'),
            'form.note'              => __('care-plan.notes'),
            'form.inform_with'       => __('care-plan.inform_with'),
            'form.knedp'                  => __('forms.knedp') ?? 'КНЕДП',
            'form.keyContainerUpload'     => __('forms.key_container') ?? 'Ключ-контейнер',
            'form.password'               => __('forms.password'),
        ];
    }

    /**
     * Handle validation failure: dispatch flash + scroll events.
     */
    protected function handleValidationFailed(ValidationException $exception, bool $closeModal = false): void
    {
        $errors = collect($exception->validator->errors()->toArray())
            ->map(fn ($msgs) => $msgs[0])
            ->values()
            ->toArray();

        $firstKey = array_key_first($exception->validator->errors()->toArray());

        $this->dispatch('flashMessage', [
            'type'    => 'error',
            'message' => __('validation.failed') ?? 'Форма містить помилки',
            'errors'  => $errors,
        ]);

        $this->dispatch('validation-failed-scroll', firstErrorKey: $firstKey);
        $this->setErrorBag($exception->validator->getMessageBag());

        if ($closeModal) {
            $this->showSignatureModal = false;
        }
    }

    /**
     * Additional validation rules needed before KEP signing.
     */
    protected function rulesForSigning(): array
    {
        return array_merge($this->rules(), [
            'form.knedp' => 'required|string',
            'form.keyContainerUpload' => 'required|file|max:1024',
            'form.password' => 'required|string',
        ]);
    }

    /**
     * Watch period_end and show warning per TZ 3.10.1.2.4.
     */
    public function updatedFormPeriodEnd(): void
    {
        if (!empty($this->form['period_end'])) {
            $this->dispatch('flashMessage', [
                'type'    => 'error',
                'message' => __('care-plan.period_end_warning'),
                'errors'  => [],
            ]);
        }
    }

    /**
     * Save as a local draft (without sending to eHealth).
     */
    public function save(CarePlanRepository $repository): void
    {
        if (Auth::user()?->cannot('create', CarePlan::class)) {
            $this->dispatch('flashMessage', [
                'type'    => 'error',
                'message' => __('care-plan.no_permission_create'),
                'errors'  => [],
            ]);
            return;
        }

        try {
            $validated = $this->validate($this->rules());
        } catch (ValidationException $exception) {
            $this->handleValidationFailed($exception);
            return;
        }

        $legalEntity = legalEntity();

        $encounterData = $this->resolveEncounterData();

        $repository->create([
            'person_id' => $this->resolvePersonId(),
            'author_id' => Auth::user()?->activeEmployee()?->id,
            'legal_entity_id' => $legalEntity?->id,
            'status' => 'NEW',
            'category' => $validated['form']['category'],
            'clinical_protocol' => $validated['form']['clinical_protocol'] ?? null,
            'context' => $validated['form']['context'] ?? null,
            'title' => $validated['form']['title'],
            'period_start' => convertToYmd($validated['form']['period_start']),
            'period_end' => !empty($validated['form']['period_end'])
                ? convertToYmd($validated['form']['period_end']) : null,
            'encounter_id' => $encounterData['id'],
            'addresses' => $encounterData['addresses'],
            'supporting_info' => [
                'episodes' => $validated['form']['episodes'],
                'medical_records' => $validated['form']['medical_records'],
            ],
            'description' => $validated['form']['description'] ?? null,
            'note' => $validated['form']['note'] ?? null,
            'inform_with' => $validated['form']['inform_with'] ?? null,
        ]);

        $this->dispatch('flashMessage', [
            'type'    => 'success',
            'message' => __('care-plan.draft_saved'),
            'errors'  => [],
        ]);
        $this->redirectRoute('persons.index', [legalEntity()], navigate: true);
    }

    /**
     * Sign with KEP and send to eHealth.
     */
    public function sign(CarePlanRepository $repository): void
    {
        if (Auth::user()?->cannot('create', CarePlan::class)) {
            $this->dispatch('flashMessage', [
                'type'    => 'error',
                'message' => __('care-plan.no_permission_create'),
                'errors'  => [],
            ]);
            return;
        }

        try {
            $validated = $this->validate($this->rulesForSigning());
        } catch (ValidationException $exception) {
            $this->handleValidationFailed($exception, closeModal: true);
            return;
        }

        $legalEntity = legalEntity();

        $encounterData = $this->resolveEncounterData();

        // Build eHealth payload
        $carePlanPayload = removeEmptyKeys([
            'intent' => 'order',
            'status' => 'new',
            'category' => $this->form['category'],
            'instantiates_protocol' => $this->form['clinical_protocol'] ? [['display' => $this->form['clinical_protocol']]] : null,
            'context' => $this->form['context'] ? ['identifier' => ['type_code' => $this->form['context']]] : null,
            'title' => $this->form['title'],
            'period' => array_filter([
                'start' => convertToYmd($this->form['period_start']),
                'end' => !empty($this->form['period_end'])
                    ? convertToYmd($this->form['period_end']) : null,
            ]),
            'addresses' => $encounterData['addresses'],
            'supporting_info' => array_merge(
                array_map(fn($e) => ['display' => $e['name']], $this->form['episodes']),
                array_map(fn($m) => ['display' => $m['name']], $this->form['medical_records'])
            ),
            'encounter' => $this->form['encounter'] ? ['identifier' => ['value' => $this->form['encounter']]] : null,
            'care_manager' => ['identifier' => ['value' => Auth::user()?->activeEmployee()?->uuid]],
            'description' => $this->form['description'] ?: null,
            'note' => $this->form['note'] ?: null,
            'inform_with' => $this->form['inform_with'] ?: null,
        ]);

        try {
            $signedContent = signatureService()->signData(
                Arr::toSnakeCase($carePlanPayload),
                $this->form['password'],
                $this->form['knedp'],
                $this->form['keyContainerUpload'],
                Auth::user()->party->taxId
            );

            $eHealthResponse = EHealth::carePlan()->create([
                'signed_content' => $signedContent,
                'signed_content_encoding' => 'base64',
            ]);

            $responseData = $eHealthResponse->getData();

            // Store eHealth response locally
            $repository->create([
                'uuid' => $responseData['id'] ?? null,
                'person_id' => $this->resolvePersonId(),
                'author_id' => Auth::user()?->activeEmployee()?->id,
                'legal_entity_id' => $legalEntity?->id,
                'status' => $responseData['status'] ?? 'new',
                'category' => $this->form['category'],
                'title' => $this->form['title'],
                'period_start' => convertToYmd($this->form['period_start']),
                'period_end' => !empty($this->form['period_end'])
                    ? convertToYmd($this->form['period_end']) : null,
                'requisition' => $responseData['requisition'] ?? null,
            ]);

            $this->dispatch('flashMessage', [
                'type'    => 'success',
                'message' => __('care-plan.signed_and_sent'),
                'errors'  => [],
            ]);
            $this->redirectRoute('persons.index', [legalEntity()], navigate: true);

        } catch (ConnectionException $exception) {
            Log::error('CarePlan: connection error: ' . $exception->getMessage());
            $this->dispatch('flashMessage', ['type' => 'error', 'message' => __('care-plan.connection_error'), 'errors' => []]);
            $this->showSignatureModal = false;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            Log::error('CarePlan: eHealth error: ' . $exception->getMessage());
            $msg = $exception instanceof EHealthValidationException
                ? $exception->getFormattedMessage()
                : 'Помилка від ЕСОЗ: ' . $exception->getMessage();
            $this->dispatch('flashMessage', ['type' => 'error', 'message' => $msg, 'errors' => []]);
            $this->showSignatureModal = false;
        } catch (\Throwable $exception) {
            Log::error('CarePlan: unexpected error: ' . $exception->getMessage());
            $this->dispatch('flashMessage', ['type' => 'error', 'message' => __('care-plan.unexpected_error'), 'errors' => []]);
            $this->showSignatureModal = false;
        }
    }

    /**
     * Resolve the person local ID from patientUuid.
     */
    protected function resolvePersonId(): ?int
    {
        if (empty($this->patientUuid)) {
            return null;
        }
        return \App\Models\Person\Person::where('uuid', $this->patientUuid)->value('id');
    }

    /**
     * Resolve the local Encounter ID and extract Conditions (addresses) from it.
     */
    protected function resolveEncounterData(): array
    {
        $data = ['id' => null, 'addresses' => []];
        if (empty($this->form['encounter'])) {
            return $data;
        }

        $encounter = \App\Models\MedicalEvents\Sql\Encounter::where('uuid', $this->form['encounter'])
            ->with(['diagnoses.condition'])
            ->first();

        if ($encounter) {
            $data['id'] = $encounter->id;
            
            // Extract the UUID of all conditions (addresses for the care plan)
            $conditionUuids = $encounter->diagnoses
                ->map(fn($d) => $d->condition?->value)
                ->filter()
                ->unique()
                ->values()
                ->toArray();
                
            foreach ($conditionUuids as $uuid) {
                $data['addresses'][] = ['identifier' => ['value' => $uuid]];
            }
        }

        return $data;
    }

    public function render()
    {
        return view('livewire.care-plan.care-plan-create');
    }
}
