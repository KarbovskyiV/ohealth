<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Classes\eHealth\EHealth;
use App\Classes\eHealth\EHealthResponse;
use App\Core\EHealthJob;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use App\Traits\BatchLegalEntityQueries;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Queue\Middleware\RateLimited;

class DeclarationRequestsSync extends EHealthJob
{
    use BatchLegalEntityQueries;

    public const string BATCH_NAME = 'DeclarationRequestsSync';

    public const string SCOPE_REQUIRED = 'declaration_request:read';

    public const string ENTITY = LegalEntity::ENTITY_DECLARATION;

    /**
     * Get the list of the legal entity declaration requests from EHealth API.
     *
     * @param  string  $token
     * @return PromiseInterface|EHealthResponse
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        return EHealth::declarationRequest()->withToken($token)->getMany(query: ['page' => $this->page]);
    }

    /**
     * Store or update all the declaration requests in the database.
     *
     * @param  EHealthResponse|null  $response
     */
    protected function processResponse(?EHealthResponse $response): void
    {
        Repository::declarationRequest()->storeMany($response->validate());
    }

    /**
     * Get additional middleware configurations for the job.
     *
     * @return array Returns an array of middleware configurations to be applied to the job
     */
    protected function getAdditionalMiddleware(): array
    {
        return [
            new RateLimited('ehealth-declaration-request-get')
        ];
    }

    /**
     * Get the next entity job to be scheduled after the whole list is stored.
     *
     * If the job is standalone, returns a CompleteSync job for the current legal entity.
     * Otherwise, returns a chain of DeclarationRequestDetailsSync jobs, which covers the requests
     * discovered by this job as well.
     *
     * @return EHealthJob|null
     */
    protected function getNextEntityJob(): ?EHealthJob
    {
        return $this->standalone
            ? new CompleteSync($this->legalEntity, isFirstLogin: $this->isFirstLogin)
            : $this->getDeclarationRequestsStartJob($this->legalEntity, $this->nextEntity);
    }
}
