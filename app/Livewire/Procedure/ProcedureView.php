<?php

declare(strict_types=1);

namespace App\Livewire\Procedure;

use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Preperson;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class ProcedureView extends ProcedureEdit
{
    /**
     * Edit page of a draft that is not waiting for eHealth.
     *
     * @var string|null
     */
    #[Locked]
    public ?string $editUrl = null;

    /**
     * Load the procedure for viewing, offering its edit page while it can still be changed.
     *
     * @param  LegalEntity  $legalEntity
     * @param  Person|null  $person
     * @param  Preperson|null  $preperson
     * @param  int|null  $procedureId
     * @return void
     */
    public function mount(
        LegalEntity $legalEntity,
        ?Person $person = null,
        ?Preperson $preperson = null,
        ?int $procedureId = null
    ): void {
        parent::mount($legalEntity, $person, $preperson, $procedureId);

        // The edit page leaves only a draft that is not waiting for eHealth editable
        $this->editUrl = $this->isReadonly ? null : $this->editPageUrl();

        $this->isReadonly = true;
    }

    /**
     * Offer the edit page once eHealth rejected the submitted procedure, which stays a draft to fix.
     *
     * @return void
     */
    #[On('submission-rejected')]
    public function offerEditing(): void
    {
        $this->editUrl = $this->editPageUrl();
    }

    /**
     * Get the edit page of the procedure.
     *
     * @return string
     */
    private function editPageUrl(): string
    {
        return route(
            $this->prepersonId !== null ? 'prepersons.procedure.edit' : 'procedure.edit',
            $this->prepersonId !== null
                ? [legalEntity(), 'preperson' => $this->prepersonId, 'procedureId' => $this->procedureId]
                : [legalEntity(), 'person' => $this->personId, 'procedureId' => $this->procedureId]
        );
    }

    /**
     * Render the procedure form, which is readonly here.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.procedure.procedure-view');
    }
}
