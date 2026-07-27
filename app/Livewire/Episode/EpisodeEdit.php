<?php

declare(strict_types=1);

namespace App\Livewire\Episode;

use App\Livewire\Person\Records\BasePatientComponent;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\Episode;
use App\Models\Person\Person;
use App\Models\Preperson;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;

class EpisodeEdit extends BasePatientComponent
{
    /**
     * Local episode ID.
     *
     * @var int
     */
    #[Locked]
    public int $episodeId;

    public string $name = '';

    public string $careManagerUuid = '';

    public string $typeCode = 'treatment';

    public string $statusCode = 'active';

    public string $startDate = '';

    public string $startTime = '';

    public array $employees = [];

    public array $episodeTypes = [];

    public array $episodeStatuses = [];

    /**
     * Bind the route models and the episode being edited.
     *
     * @param  LegalEntity  $legalEntity
     * @param  Person|null  $person
     * @param  Preperson|null  $preperson
     * @param  Episode|null  $episode
     * @return void
     */
    public function mount(
        LegalEntity $legalEntity,
        ?Person $person = null,
        ?Preperson $preperson = null,
        ?Episode $episode = null
    ): void {
        parent::mount($legalEntity, $person, $preperson);

        $this->episodeId = $episode->id;
    }

    public function save(): void
    {
    }

    public function cancel(): void
    {
    }

    public function render(): View
    {
        return view('livewire.episode.episode-edit');
    }
}
