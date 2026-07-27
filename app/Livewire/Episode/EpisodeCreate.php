<?php

declare(strict_types=1);

namespace App\Livewire\Episode;

use App\Enums\Episode\Status;
use App\Enums\Status as EmployeeStatus;
use App\Livewire\Episode\Forms\EpisodeForm;
use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;
use App\Livewire\Person\Records\BasePatientComponent;
use App\Models\Employee\Employee;
use App\Models\MedicalEvents\Sql\Episode;
use App\Repositories\MedicalEvents\Repository;
use App\Services\MedicalEvents\Fhir;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class EpisodeCreate extends BasePatientComponent
{
    public EpisodeForm $form;

    /**
     * Employees of the current legal entity that can be picked as a care manager.
     *
     * @var array
     */
    public array $employees = [];

    /**
     * Episode types allowed for the legal entity, keyed by code.
     *
     * @var array
     */
    public array $episodeTypes = [];

    /**
     * Codes of the episode types each care manager may use, keyed by employee UUID.
     *
     * @var array
     */
    public array $employeeEpisodeTypes = [];

    protected array $dictionaryNames = ['eHealth/episode_types', 'POSITION'];

    /**
     * Load the dictionaries and select options the form depends on.
     *
     * @return void
     */
    protected function initializeComponent(): void
    {
        $this->getDictionary();

        $this->form->id = Str::uuid()->toString();

        $this->loadEmployees();
        $this->loadEpisodeTypes();
    }

    /**
     * Validate the form, create the episode in eHealth and persist it locally.
     *
     * @return void
     */
    public function create(): void
    {
        if (Auth::user()->cannot('create', Episode::class)) {
            Session::flash('error', __('episodes.policy.create'));

            return;
        }

        $validated = $this->validateForm();

        if ($validated === null) {
            return;
        }

        $formattedData = $this->formatEpisode($validated, Status::ACTIVE);

        try {
            EHealth::episode()->create($this->uuid, Arr::toSnakeCase($formattedData));
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error while creating episode');

            return;
        }

        // eHealth accepted the episode; only now persist it locally
        try {
            Repository::episode()->store($formattedData, $this->patient());
        } catch (Throwable $exception) {
            $this->handleDatabaseErrors($exception, 'Failed to store created episode');

            return;
        }

        Session::flash('success', __('episodes.messages.created'));

        $this->redirectToEpisodes();
    }

    /**
     * Validate the form and keep the episode as a local draft.
     *
     * @return void
     */
    public function createLocally(): void
    {
        if (Auth::user()->cannot('create', Episode::class)) {
            Session::flash('error', __('episodes.policy.create'));

            return;
        }

        $validated = $this->validateForm();

        if ($validated === null) {
            return;
        }

        try {
            Repository::episode()->store($this->formatEpisode($validated, Status::DRAFT), $this->patient());
        } catch (Throwable $exception) {
            $this->handleDatabaseErrors($exception, 'Failed to store episode draft');

            return;
        }

        Session::flash('success', __('episodes.messages.draft_created'));

        $this->redirectToEpisodes();
    }

    /**
     * Leave the form and get back to the patient episode list.
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->redirectToEpisodes();
    }

    /**
     * Validate the form, flashing the first error; returns `null` when the data is invalid.
     *
     * @return array|null
     */
    protected function validateForm(): ?array
    {
        try {
            return $this->form->validate($this->form->rulesForCreate());
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return null;
        }
    }

    /**
     * Build the eHealth structure of the episode out of the validated form data.
     *
     * @param  array  $validated
     * @param  Status  $status
     * @return array
     */
    protected function formatEpisode(array $validated, Status $status): array
    {
        return Fhir::episode()->toFhir(
            $validated,
            ['episode' => $validated['id'], 'employee' => $validated['careManagerUuid']],
            $validated['startDate'],
            $validated['startTime'],
            $status
        );
    }

    /**
     * Get back to the patient episode list.
     *
     * @return void
     */
    protected function redirectToEpisodes(): void
    {
        if ($this->prepersonId !== null) {
            $this->redirectRoute(
                'prepersons.episodes',
                [legalEntity(), 'preperson' => $this->prepersonId],
                navigate: true
            );

            return;
        }

        $this->redirectRoute('persons.episodes', [legalEntity(), 'person' => $this->personId], navigate: true);
    }

    /**
     * Build the episode types allowed for the legal entity and, for every care manager, the subset of those
     * types their employee type may use. The subsets let the view narrow the list down without a request.
     *
     * @return void
     */
    protected function loadEpisodeTypes(): void
    {
        $this->episodeTypes = Arr::only(
            $this->dictionaries['eHealth/episode_types'],
            config('ehealth.legal_entity_episode_types.' . legalEntity()->type->name, [])
        );

        $legalEntityTypes = array_keys($this->episodeTypes);

        $this->employeeEpisodeTypes = collect($this->employees)
            ->mapWithKeys(static fn (array $employee): array => [
                $employee['uuid'] => array_values(array_intersect(
                    $legalEntityTypes,
                    config('ehealth.employee_episode_types.' . $employee['employeeType'], [])
                ))
            ])
            ->toArray();
    }

    /**
     * Get the active employees of the authenticated user within the current legal entity
     * that are allowed to be a care manager of an episode.
     *
     * @return void
     */
    protected function loadEmployees(): void
    {
        $this->employees = Auth::user()->party->employees()
            ->whereLegalEntityId(legalEntity()->id)
            ->whereIn('employee_type', config('ehealth.allowed_episode_care_manager_employee_types', []))
            ->whereStatus(EmployeeStatus::APPROVED)
            ->whereIsActive(true)
            ->select(['uuid', 'position', 'party_id', 'employee_type'])
            ->with('party:id,last_name,first_name,second_name')
            ->get()
            ->map(static fn (Employee $employee): array => [
                'uuid' => $employee->uuid,
                'name' => $employee->fullName,
                'position' => $employee->position,
                'employeeType' => $employee->employeeType
            ])
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.episode.episode-create');
    }
}
