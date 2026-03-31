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
use Livewire\WithFileUploads;

class CarePlanUpdate extends CarePlanCreate
{
    use WithFileUploads;

    public CarePlan $carePlan;

    public function mount(): void
    {
        $carePlan = request()->route('carePlan');
        if (!$carePlan instanceof CarePlan) {
            // Fallback for cases where route binding might not have resolved to model yet
            $carePlan = CarePlan::findOrFail($carePlan);
        }

        $this->carePlan = $carePlan;
        $this->patientUuid = $carePlan->person?->uuid ?? '';
        
        // Hydrate form from model
        $this->form = [
            'patient' => $carePlan->person?->full_name ?? '',
            'medical_number' => (string) ($carePlan->encounter_id ?? ''),
            'author' => $carePlan->author?->party?->full_name ?? '',
            'coAuthors' => [], // TODO: if co-authors are implemented
            'category' => is_array($carePlan->category) ? ($carePlan->category['coding'][0]['code'] ?? '') : $carePlan->category,
            'clinical_protocol' => $carePlan->clinical_protocol ?? '',
            'context' => $carePlan->context ?? '',
            'title' => $carePlan->title ?? '',
            'intent' => 'order',
            'period_start' => $carePlan->period_start?->format('d.m.Y') ?? '',
            'period_end' => $carePlan->period_end?->format('d.m.Y') ?? '',
            'encounter' => $carePlan->encounter?->uuid ?? '',
            'description' => $carePlan->description ?? '',
            'note' => $carePlan->note ?? '',
            'inform_with' => $carePlan->inform_with ?? '',
            'episodes' => $carePlan->supporting_info['episodes'] ?? [],
            'medical_records' => $carePlan->supporting_info['medical_records'] ?? [],
            'knedp' => '',
            'keyContainerUpload' => null,
            'password' => '',
        ];

        // Load patient auth methods
        if ($carePlan->person) {
            $this->authMethods = $carePlan->person->authenticationMethods()->get()->map(fn($m) => [
                'value' => $m->type,
                'label' => \App\Enums\Person\AuthenticationMethod::tryFrom($m->type)?->label() ?? $m->type,
            ])->toArray();
        }

        // Load encounter diagnoses for UI
        if ($carePlan->encounter) {
            $this->diagnoses = $carePlan->encounter->diagnoses->map(fn($d) => [
                'date' => $d->condition?->asserted_date?->format('d.m.Y') ?? '-',
                'name' => $d->condition?->code_display ?? $d->condition?->code ?? '-',
            ])->toArray();
        }

        // Load doctors for co-authors (copied from Create)
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

        // Load dictionaries
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
            Log::warning('CarePlanUpdate: failed to load dictionaries: ' . $exception->getMessage());
        }
    }

    /**
     * Update existing local draft.
     */
    public function save(CarePlanRepository $repository): void
    {
        if (Auth::user()?->cannot('update', $this->carePlan)) {
            $this->dispatch('flashMessage', [
                'type'    => 'error',
                'message' => __('care-plan.no_permission_update'),
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

        $encounterData = $this->resolveEncounterData();

        $repository->updateById($this->carePlan->id, [
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
            'message' => __('care-plan.draft_updated'),
            'errors'  => [],
        ]);
        
        $this->redirectRoute('care-plan.show', [legalEntity(), $this->carePlan->id], navigate: true);
    }

    /**
     * Sign with KEP and send to eHealth (Update current plan).
     */
    public function sign(CarePlanRepository $repository): void
    {
        if (Auth::user()?->cannot('update', $this->carePlan)) {
            $this->dispatch('flashMessage', [
                'type'    => 'error',
                'message' => __('care-plan.no_permission_update'),
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

            // Update local model with eHealth response
            $repository->updateById($this->carePlan->id, [
                'uuid' => $responseData['id'] ?? null,
                'status' => $responseData['status'] ?? 'new',
                'requisition' => $responseData['requisition'] ?? null,
                // Update other fields too just in case they were changed before signing
                'category' => $this->form['category'],
                'title' => $this->form['title'],
                'period_start' => convertToYmd($this->form['period_start']),
                'period_end' => !empty($this->form['period_end'])
                    ? convertToYmd($this->form['period_end']) : null,
                'encounter_id' => $encounterData['id'],
                'addresses' => $encounterData['addresses'],
                'supporting_info' => [
                    'episodes' => $this->form['episodes'],
                    'medical_records' => $this->form['medical_records'],
                ],
            ]);

            $this->dispatch('flashMessage', [
                'type'    => 'success',
                'message' => __('care-plan.signed_and_sent'),
                'errors'  => [],
            ]);
            
            $this->redirectRoute('care-plan.show', [legalEntity(), $this->carePlan->id], navigate: true);

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

    public function render()
    {
        // Reuse the same view as Create
        return view('livewire.care-plan.care-plan-create');
    }
}
