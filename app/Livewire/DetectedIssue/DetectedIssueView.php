<?php

declare(strict_types=1);

namespace App\Livewire\DetectedIssue;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;
use App\Livewire\Person\Records\BasePatientComponent;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\DetectedIssue;
use App\Models\MedicalEvents\Sql\Device;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Repositories\MedicalEvents\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Throwable;

class DetectedIssueView extends BasePatientComponent
{
    /**
     * ID of the detected issue being displayed.
     *
     * @var int
     */
    #[Locked]
    public int $detectedIssueId;

    /**
     * eHealth ID of the detected issue, kept so that a refresh does not have to read the record to find it.
     *
     * @var string
     */
    #[Locked]
    public string $detectedIssueUuid;

    /**
     * Request-scoped memoized detected issue.
     *
     * @var DetectedIssue|null
     */
    private ?DetectedIssue $detectedIssueModel = null;

    protected array $dictionaryNames = [
        'detected_issue_statuses',
        'detected_issue_codes',
        'detected_issue_status_reasons',
        'eHealth/report_origins'
    ];

    /**
     * Bind the route models and load the detected issue being displayed.
     *
     * @param  LegalEntity  $legalEntity
     * @param  Person|null  $person
     * @param  Preperson|null  $preperson
     * @param  DetectedIssue|null  $detectedIssue
     * @return void
     */
    public function mount(
        LegalEntity $legalEntity,
        ?Person $person = null,
        ?Preperson $preperson = null,
        ?DetectedIssue $detectedIssue = null
    ): void {
        parent::mount($legalEntity, $person, $preperson);

        $this->getDictionary();

        $this->detectedIssueId = $detectedIssue->id;
        $this->detectedIssueUuid = $detectedIssue->uuid;

        $this->detectedIssue();
    }

    /**
     * Refresh the detected issue from eHealth, so that the page shows the record as it stands there now.
     *
     * @return void
     */
    public function sync(): void
    {
        if (Auth::user()->cannot('view', DetectedIssue::class)) {
            Session::flash('error', __('detected-issues.policy.sync'));

            return;
        }

        try {
            $response = EHealth::detectedIssue()->getById($this->uuid, $this->detectedIssueUuid);
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error while synchronizing the detected issue');

            return;
        }

        try {
            Repository::detectedIssue()->sync($this->patient(), [$response->validate()]);
        } catch (Throwable $exception) {
            $this->handleDatabaseErrors($exception, 'Error while synchronizing the detected issue');

            return;
        }

        // Drop the memoized model so that the page renders what has just been stored
        $this->detectedIssueModel = null;

        Session::flash('success', __('detected-issues.messages.record_synced_successfully'));
    }

    /**
     * Resolve the detected issue being displayed, scoped to the patient so that a record belonging to somebody else
     * is not reachable by its ID. Loaded again on later requests, where Livewire hydrates without mount().
     *
     * @return DetectedIssue
     */
    protected function detectedIssue(): DetectedIssue
    {
        return $this->detectedIssueModel ??= DetectedIssue::forPatient($this->patient())
            ->withAllRelations()
            ->whereId($this->detectedIssueId)
            ->firstOrFail();
    }

    /**
     * Name of the patient's stored device the detected issue refers to.
     *
     * @param  string|null  $deviceId
     * @return string|null
     */
    protected function deviceName(?string $deviceId): ?string
    {
        if ($deviceId === null) {
            return null;
        }

        return Device::forPatient($this->patient())->whereUuid($deviceId)->with('names')->first()?->names->first()?->value;
    }

    public function render(): View
    {
        $detectedIssue = $this->detectedIssue();

        return view('livewire.detected-issue.detected-issue-view')->with([
            'detectedIssue' => $detectedIssue,
            'deviceName' => $this->deviceName($detectedIssue->subject?->value),
            'implicatedDeviceName' => $this->deviceName($detectedIssue->implicated?->value)
        ]);
    }
}
