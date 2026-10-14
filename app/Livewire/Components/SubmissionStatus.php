<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Classes\eHealth\EHealth;
use App\Enums\JobStatus;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Jobs\RemoteEHealthLinksProcessing;
use App\Models\EhealthLink;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class SubmissionStatus extends Component
{
    /**
     * Status shown in the block: the last submission status, or the record status without one.
     *
     * @var string
     */
    #[Locked]
    public string $statusLabel;

    /**
     * Job processing the last submission of the signed record, null when the record was never submitted.
     *
     * @var string|null
     */
    #[Locked]
    public ?string $jobId = null;

    /**
     * Last submission of the signed record, null when the record was never submitted.
     *
     * @var int|null
     */
    #[Locked]
    public ?int $submissionId = null;

    /**
     * Edit page of the record while it can still be changed, which the update button leads to.
     *
     * @var string|null
     */
    #[Reactive]
    public ?string $editUrl;

    /**
     * Processing details of the submission job, fetched from eHealth each time they are opened.
     *
     * @var array
     */
    #[Locked]
    public array $job = [];

    public bool $showDetails = false;

    /**
     * @param  string  $recordType
     * @param  int  $recordId
     * @param  string  $recordStatusLabel
     * @param  string|null  $editUrl
     * @return void
     */
    public function mount(string $recordType, int $recordId, string $recordStatusLabel, ?string $editUrl = null): void
    {
        $submission = EhealthLink::whereLinkableType($recordType)
            ->whereLinkableId($recordId)
            ->latest('id')
            ->first();

        $this->statusLabel = $submission !== null ? JobStatus::from($submission->status)->label() : $recordStatusLabel;
        $this->editUrl = $editUrl;

        // A submission eHealth gave no job for has no details to fetch
        if ($submission?->href !== null) {
            $this->jobId = basename($submission->href);
            $this->submissionId = $submission->id;
        }
    }

    /**
     * Fetch the current processing details of the submission job from eHealth and show them.
     *
     * @return void
     */
    public function openDetails(): void
    {
        try {
            $job = EHealth::job()->getDetails($this->jobId)->validate();
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error when getting submission job details');

            return;
        } catch (ValidationException) {
            Session::flash('error', __('errors.ehealth.messages.request_error'));

            return;
        }

        $this->job = [
            'id' => $this->jobId,
            'status' => JobStatus::tryFrom(strtoupper($job['status']))?->label() ?? $job['status'],
            'created' => array_map(static fn (array $link): array => [
                'entity' => Lang::has("encounters.submission.entities.{$link['entity']}")
                    ? __("encounters.submission.entities.{$link['entity']}")
                    : $link['entity'],
                'id' => basename($link['href'])
            ], $job['links'] ?? []),
            // eHealth describes the rejection with an error object, or with a bare message
            'error' => isset($job['error'])
                ? new EHealthValidationException([
                    'error' => is_array($job['error']) ? $job['error'] : ['message' => $job['error']]
                ])->getFormattedMessage()
                : null
        ];

        $this->statusLabel = $this->job['status'];
        $this->showDetails = true;

        if (in_array(strtolower($job['status']), ['pending', 'processing', 'accepted'], true)) {
            return;
        }

        RemoteEHealthLinksProcessing::finishSubmission(EhealthLink::findOrFail($this->submissionId), $job, Auth::user());

        $this->dispatch(
            JobStatus::tryFrom(strtoupper($job['status'])) === JobStatus::PROCESSED
                ? 'submission-processed'
                : 'submission-rejected'
        );
    }

    public function render(): View
    {
        return view('livewire.components.submission-status');
    }
}
