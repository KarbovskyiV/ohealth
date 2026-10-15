<?php

declare(strict_types=1);

namespace App\Livewire\Preperson;

use App\Core\Arr;
use App\Enums\Preperson\Status;
use App\Models\Preperson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Throwable;

class PrepersonCreate extends PrepersonComponent
{
    public function mount(): void
    {
        $this->getDictionary();
    }

    /**
     * Validate and store an unidentified patient (preperson) draft locally.
     *
     * @return void
     */
    public function createLocally(): void
    {
        if (!$this->ensureCanCreate()) {
            return;
        }

        $validated = $this->validateForm();

        $personData = $validated['person'];
        // note is the eHealth-facing text; reasonContext keeps the raw fields so the draft can be re-edited later
        $personData['note'] = $this->form->buildNote();
        $personData['reasonContext'] = $validated['reasonContext'];
        $personData['status'] = Status::DRAFT->value;
        $personData['createdBy'] = Auth::user()->activeEmployee()?->id;

        if (!empty($personData['birthDate'])) {
            $personData['birthDate'] = convertToYmd($personData['birthDate']);
        }

        try {
            DB::transaction(static function () use ($personData): void {
                $preperson = Preperson::create(Arr::toSnakeCase($personData));
                // external_id follows the mask MIS.NMP.id, so it is assigned only after the insert produces a primary key
                $preperson->externalId = $preperson->buildExternalId();
                $preperson->save();
            });
        } catch (Throwable $exception) {
            $this->handleDatabaseErrors($exception, 'Failed to store preperson');

            return;
        }

        Session::flash('success', __('preperson.messages.draft_created'));
        $this->redirectRoute('prepersons.index', [legalEntity()], navigate: true);
    }

    /**
     * Validate, register the unidentified patient in eHealth and persist it locally.
     *
     * @return void
     */
    public function create(): void
    {
        if (!$this->ensureCanCreate()) {
            return;
        }

        $validated = $this->validateForm();

        $personData = $validated['person'];
        $personData['note'] = $this->form->buildNote();

        if (!empty($personData['birthDate'])) {
            $personData['birthDate'] = convertToYmd($personData['birthDate']);
        }

        // Insert locally first to obtain a primary key for external_id — reserving a key without inserting
        // Reason_context is stored only here, never sent to eHealth.
        $record = Arr::toSnakeCase($personData);
        $record['reason_context'] = Arr::toSnakeCase($validated['reasonContext']);
        $record['created_by'] = Auth::user()->activeEmployee()?->id;

        try {
            $preperson = DB::transaction(static function () use ($record): Preperson {
                $preperson = Preperson::create($record);
                $preperson->externalId = $preperson->buildExternalId();
                $preperson->save();

                return $preperson;
            });
        } catch (Throwable $exception) {
            $this->handleDatabaseErrors($exception, 'Failed to store preperson');

            return;
        }

        $this->createInEHealth($preperson, $personData);
    }

    public function render(): View
    {
        return view('livewire.preperson.preperson-create');
    }
}
