<?php

declare(strict_types=1);

namespace App\Jobs;

use Throwable;
use App\Core\EHealthJob;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use App\Classes\eHealth\EHealth;
use GuzzleHttp\Promise\PromiseInterface;
use App\Classes\eHealth\EHealthResponse;
use App\Models\Connection as ConnectionModel;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;

class ConnectionClientSync extends EHealthJob
{
    public const string BATCH_NAME = 'ConnectionClientSync';

    public const string SCOPE_REQUIRED = 'connection:read';

    public function __construct(
        public ConnectionModel $misConnection,
        public ?LegalEntity $legalEntity,
        protected ?EHealthJob $nextEntity = null,
        public bool $standalone = false,
    ) {
        parent::__construct(legalEntity: $legalEntity, nextEntity: $nextEntity, standalone: $standalone);
    }

    /**
     * Get data from EHealth API.
     *
     * @param  string  $token
     * @return PromiseInterface|EHealthResponse
     * @throws EHealthResponseException|EHealthValidationException
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        return EHealth::connection()
            ->withToken($token)
            ->getClientDetails($this->misConnection->client_uuid);
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
        $clientData = $response?->validate();

        if (empty($clientData)) {
            return;
        }

        Repository::legalEntity()->syncClient($clientData);
    }

    /**
     * Get additional middleware configurations for the job.
     *
     * @return array Returns an array of middleware configurations to be applied to the job
     */
    protected function getAdditionalMiddleware(): array
    {
        return [];
    }

    /**
     * Get next entity job if needed.
     *
     * @return EHealthJob|null
     */
    protected function getNextEntityJob(): ?EHealthJob
    {
        return $this->standalone || !$this->nextEntity
            ? new CompleteSync($this->legalEntity, isFirstLogin: $this->isFirstLogin)
            : $this->nextEntity;
    }
}
