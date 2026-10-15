<?php

declare(strict_types=1);

namespace App\Jobs;

use Throwable;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use App\Contracts\SubmittedToEHealth;
use App\Core\EHealthJob;
use App\Enums\JobStatus;
use App\Enums\ResponseStatus;
use App\Models\EhealthJob as EhealthJobModel;
use App\Models\LegalEntity;
use App\Models\EhealthLink;
use App\Models\User;
use InvalidArgumentException;
use App\Classes\eHealth\EHealth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use GuzzleHttp\Promise\PromiseInterface;
use App\Classes\eHealth\EHealthResponse;
use App\Models\MedicalEvents\Sql\Approval;
use App\Models\MedicalEvents\Sql\Encounter;
use App\Models\MedicalEvents\Sql\Procedure;
use App\Models\MedicalEvents\Sql\DiagnosticReport;
use Illuminate\Queue\Middleware\RateLimited;
use App\Repositories\MedicalEvents\Repository;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Notifications\RemoteEHealthLinksNotification;
use App\Exceptions\EHealth\EHealthValidationException;

class RemoteEHealthLinksProcessing extends EHealthJob
{
    public const string BATCH_NAME = 'RemoteEHealthLinksProcessing';

    public const string SCOPE_REQUIRED = 'approval:create';

    protected const int TIME_TO_RETRY = 5; // Time in seconds to wait before retrying the job

    protected const int MAX_TIME_TO_RETRY = 300; // Longest time in seconds to wait between polls of a pending job

    protected const int POLLING_HOURS = 24; // How long the job is polled while eHealth keeps it pending

    /**
     * Exceptions allowed before the job fails, so polls released while the job is pending are not counted against it.
     *
     * @var int
     */
    public int $maxExceptions = 3;

    protected bool $stillPending = false;
    public function __construct(
        public EhealthLink $eHealthLink,
        public ?LegalEntity $legalEntity,
        protected ?EHealthJob $nextEntity = null,
        public bool $standalone = false,
    ) {
        parent::__construct(legalEntity: $legalEntity, nextEntity: $nextEntity, standalone: $standalone);
    }

    /**
     * Mark the signed record as submitted before it is sent to eHealth, so it is not signed again while the result is unknown.
     *
     * @param  SubmittedToEHealth  $record
     * @param  array  $requestData  Data that was signed
     * @return EhealthLink
     */
    public static function startSubmission(SubmittedToEHealth $record, array $requestData): EhealthLink
    {
        return $record->ehealthLinks()->create([
            'ehealth_job_id' => EhealthJobModel::create([
                'processing_method' => ResponseStatus::ASYNC->name,
                'status' => JobStatus::PENDING->value,
                'request_data' => $requestData
            ])->id,
            'entity' => 'job',
            'status' => JobStatus::PENDING->value
        ]);
    }

    /**
     * Mark the submission eHealth did not receive as failed, so the record can be signed again.
     *
     * @param  EhealthLink  $link
     * @return void
     */
    public static function markSubmissionFailed(EhealthLink $link): void
    {
        $link->job()->update(['status' => JobStatus::FAILED->value]);
        $link->update(['status' => JobStatus::FAILED->value]);
    }

    /**
     * Track the job the signed record was submitted to eHealth with, so the record gets its signed status once eHealth processes it.
     *
     * @param  EhealthLink  $link
     * @param  array  $responseData
     * @return void
     * @throws Throwable
     */
    public static function track(EhealthLink $link, array $responseData): void
    {
        $link->job->update(['response_data' => $responseData]);
        $link->update(['href' => $responseData['links'][0]['href']]);

        Bus::batch([new self(eHealthLink: $link, legalEntity: legalEntity(), standalone: true)])
            ->withOption('legal_entity_id', legalEntity()->id)
            ->withOption('token', Crypt::encryptString(Session::get(config('ehealth.api.oauth.bearer_token'))))
            ->withOption('user', Auth::user())
            ->name(self::BATCH_NAME)
            ->onQueue('sync')
            ->dispatch();
    }

    /**
     * Finish the submission of a signed record once eHealth processed its job. An accepted one gets the status it was signed with,
     * so it leaves the draft status. A rejected one stays a draft the user can fix and sign again.
     *
     * @param  EhealthLink  $link
     * @param  array  $jobData  Processing details of the job
     * @param  User  $user  User notified about the result
     * @return void
     * @throws Throwable
     */
    public static function finishSubmission(EhealthLink $link, array $jobData, User $user): void
    {
        // Both the queued job and the user opening the details finish it, so whichever comes second leaves it as it is
        if ($link->status !== JobStatus::PENDING->value) {
            return;
        }

        $record = $link->linkable;
        $status = strtolower((string) $jobData['status']);
        $statusCode = $jobData['status_code'] ?? null;

        [$signedStatus, $processedMessage, $rejectedMessage] = self::submissionDetails($link, $record);

        // Any final status but processed rejects the record, so one the job statuses don't know is stored as failed
        $link->job()->update([
            'status' => JobStatus::tryFrom(strtoupper($status))?->value ?? JobStatus::FAILED->value
        ]);

        if ($status === 'processed') {
            $record->update(['status' => $signedStatus]);

            // Marked only once the status is set, so a record eHealth accepted is never signed again as a draft
            $link->update(['status' => JobStatus::PROCESSED->value]);

            $user->notify(new RemoteEHealthLinksNotification(__($processedMessage), 'success'));

            return;
        }

        $error = $jobData['error'] ?? null;
        $errorDetails = is_array($error) ? $error : ['message' => $error ?? __('errors.ehealth.messages.request_error')];

        $link->update([
            'status' => JobStatus::FAILED->value,
            'error' => $errorDetails,
            'error_code' => $statusCode
        ]);

        Log::error('EHealthLink: eHealth rejected a signed record', [
            'eHealthLinkId' => $link->id,
            'linkableType' => $record::class,
            'statusCode' => $statusCode,
            'error' => $error
        ]);

        $user->notify(new RemoteEHealthLinksNotification(__($rejectedMessage), 'error'));
    }

    /**
     * Keep polling the job while eHealth keeps it pending, rather than for a fixed number of attempts.
     *
     * @return DateTimeInterface
     */
    public function retryUntil(): DateTimeInterface
    {
        return CarbonImmutable::now()->addHours(self::POLLING_HOURS);
    }

    /**
     * Get data from EHealth API.
     *
     * @param  string  $token
     * @return PromiseInterface|EHealthResponse|null
     * @throws EHealthResponseException|EHealthValidationException
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse|null
    {
        $entity = $this->eHealthLink->entity;

        switch ($entity) {
            case 'job':
                $jobHref = (string) $this->eHealthLink->href;
                $jobUuid = basename($jobHref);
                $jobApi = EHealth::job()->withToken($token);

                try {
                    return $jobApi->getDetails($jobUuid);
                } catch (EHealthResponseException $exception) {
                    if ($exception->response->status() !== 404) {
                        throw $exception;
                    }

                    return $jobApi->getDetailsByHref($jobHref);
                }
                // TODO: fill the entities below with the correct API calls and data processing logic
            case 'encounter':
                return null;
            case 'condition':
                return null;
            case 'observation':
                return null;
            case 'allergy_intolerance':
                return null;
            case 'immunization':
                return null;
            case 'risk_assessment':
                return null;
            case 'device':
                return null;
            case 'medication_statement':
                return null;
            case 'medication_administration':
                return null;
            case 'diagnostic_report':
                return null;
            case 'procedure':
                return null;
            case 'specimen':
                return null;
            case 'device_dispense':
                return null;
            case 'device_association':
                return null;
            case 'detected_issue':
                return null;
            default:
                throw new InvalidArgumentException("Unsupported entity type: $entity");
        }
    }

    /**
     * Store or update data in the database.
     *
     * @param  EHealthResponse|null  $response
     * @return void
     * @throws Throwable
     */
    protected function processResponse(?EHealthResponse $response): void
    {
        if (\is_null($response)) {
            return;
        }

        $responseData = $response->getData();
        $statusCode = $responseData['status_code'] ?? $response->getStatusCode() ?? null;
        $status = strtolower((string) ($responseData['status'] ?? ''));

        if (in_array($status, ['pending', 'processing', 'accepted'], true)) {
            $this->stillPending = true;

            // The wait doubles with each poll, so a job eHealth processes for long is not polled every few seconds
            $this->release(min(self::TIME_TO_RETRY * 2 ** ($this->attempts() - 1), self::MAX_TIME_TO_RETRY));

            return;
        }

        if ($this->eHealthLink->linkable instanceof SubmittedToEHealth) {
            self::finishSubmission($this->eHealthLink->refresh(), $responseData, $this->user);

            return;
        }

        if ($statusCode === 200) {
            if ($this->eHealthLink->linkable_type === Approval::class) {
                $approval = $this->eHealthLink->linkable;
                $approvalData = $responseData['response_data'] ?? $responseData['data'];

                $this->eHealthLink->job()->update(['status' => strtoupper($status)]);
                $this->eHealthLink->update(['status' => strtoupper($status)]);
                $this->eHealthLink->processingData()->create(['response_data' => $approvalData]);
                $this->eHealthLink->refresh();

                Repository::approval()->sync(modelData: $approvalData, approvableModel: $approval->approvable, approvalModel: $approval);
            }

            return;
        } elseif (strtolower($status) === strtolower(JobStatus::FAILED->value)) {
            if ($this->eHealthLink->linkable_type === Approval::class) {
                $this->eHealthLink->linkable->update(['status' => JobStatus::FAILED->value]);
            }

            $this->eHealthLink->job()->update(['status' => strtoupper($status)]);

            $this->eHealthLink->update(
                [
                    'status' => strtoupper($status),
                    'error' => $responseData['error'] ?? null,
                    'error_code' => $statusCode ?? null
                ]
            );

            Log::error('EHealthLink: EHealth reject an link request', [
                'eHealthLinkId' => $this->eHealthLink->id,
                'statusCode' => $statusCode,
                'error' => $responseData['error'] ?? null
            ]);
        }

        // Send notification to the user about the failed request but job will not be retried, so we don't need to release it back to the queue
        $this->user->notify(new RemoteEHealthLinksNotification(__('EhealthLink: система eHealth відхилила запит'), (string)$statusCode));
    }

    /**
     * Get additional middleware configurations for the job.
     *
     * @return array Returns an array of middleware configurations to be applied to the job
     */
    protected function getAdditionalMiddleware(): array
    {
        return [
            new RateLimited('ehealth-remote-job-get')
        ];
    }

    /**
     * Get next entity job if needed.
     *
     * @return EHealthJob|null
     */
    protected function getNextEntityJob(): ?EHealthJob
    {
        if ($this->stillPending || $this->standalone) {
            return null;
        }

        return $this->nextEntity ?? new CompleteSync($this->legalEntity, isFirstLogin: $this->isFirstLogin);
    }

    /**
     * A standalone job follows a single submitted request, which is no synchronization of the legal entity,
     * so the legal entity sync status is left as it is.
     *
     * @param  JobStatus  $status
     * @return void
     */
    protected function setEntityStatus(JobStatus $status): void
    {
        if ($this->standalone) {
            return;
        }

        parent::setEntityStatus($status);
    }

    /**
     * Get the status the record was signed with and the lang keys of the messages about eHealth accepting and rejecting it.
     *
     * @param  EhealthLink  $link
     * @param  SubmittedToEHealth  $record
     * @return array
     */
    private static function submissionDetails(EhealthLink $link, SubmittedToEHealth $record): array
    {
        $signedData = $link->job->request_data;

        return match ($record::class) {
            Encounter::class => [
                $signedData['encounter']['status'],
                'encounters.messages.package_processed',
                'encounters.messages.package_rejected'
            ],
            Procedure::class => [
                $signedData['status'],
                'procedures.messages.create_processed',
                'procedures.messages.create_rejected'
            ],
            DiagnosticReport::class => [
                $signedData['diagnostic_report']['status'],
                'diagnostic-reports.messages.create_processed',
                'diagnostic-reports.messages.create_rejected'
            ]
        };
    }
}
