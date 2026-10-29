<?php

declare(strict_types=1);

namespace App\Jobs;

use Throwable;
use RuntimeException;
use App\Core\EHealthJob;
use App\Models\LegalEntity;
use App\Classes\eHealth\EHealth;
use App\Repositories\Repository;
use App\Traits\BatchLegalEntityQueries;
use GuzzleHttp\Promise\PromiseInterface;
use App\Classes\eHealth\EHealthResponse;


class ConnectionSync extends EHealthJob
{
    use BatchLegalEntityQueries;

    public const string BATCH_NAME = 'ConnectionSync';

    public const string SCOPE_REQUIRED = 'connection:read';

    public const string ENTITY = LegalEntity::ENTITY_CONNECTION;

    /**
     * Get connections data from EHealth API
     *
     * @param  string  $token
     * @return PromiseInterface|EHealthResponse
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        // Mark this LegalEntity's connections as unsynced before starting a full sync.
        if ($this->page === 1 && !Repository::legalEntity()->setConnectionSyncStatus($this->legalEntity, false)) {
            throw new RuntimeException('Unable to start connection synchronization.');
        }

        $query = [
            'page' => $this->page,
            'page_size' => config('ehealth.api.page_size_le_connections_max')
        ];

        return EHealth::connection()->withToken($token)->getClientConnections($this->legalEntity->uuid, query: $query);
    }

    /**
     * Store or update all the declarations data in the database
     *
     * @param  EHealthResponse|null  $response
     * @throws Throwable
     */
    protected function processResponse(?EHealthResponse $response): void
    {
        $connections = $response->validate();

        if (!Repository::legalEntity()->syncConnections($connections, $this->legalEntity)) {
            throw new RuntimeException('Unable to save synchronized connections.');
        }
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
     * Get the next entity job to be scheduled after DeclarationSync completes.
     *
     * If the job is standalone, returns a CompleteSync job for the current legal entity.
     * Otherwise, returns a chain of DeclarationRequestDetailsSync jobs.
     *
     * @return EHealthJob|null
     */
    protected function getNextEntityJob(): ?EHealthJob
    {
        // EHealthJob calls this only after the last page succeeds.
        if (!Repository::legalEntity()->setConnectionSyncStatus($this->legalEntity)) {
            throw new RuntimeException('Unable to complete connection synchronization.');
        }

        return $this->standalone
            ? new CompleteSync($this->legalEntity, isFirstLogin: $this->isFirstLogin)
            : $this->getConnectionClientsDataJob($this->legalEntity, $this->nextEntity);
    }
}
