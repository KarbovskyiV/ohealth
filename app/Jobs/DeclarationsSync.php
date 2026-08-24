<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Core\EHealthJob;
use App\Enums\User\Role;
use App\Models\LegalEntity;
use App\Classes\eHealth\EHealth;
use App\Repositories\Repository;
use App\Traits\BatchLegalEntityQueries;
use GuzzleHttp\Promise\PromiseInterface;
use App\Classes\eHealth\EHealthResponse;
use Illuminate\Queue\Middleware\RateLimited;

class DeclarationsSync extends EHealthJob
{
    use BatchLegalEntityQueries;

    public const string BATCH_NAME = 'DeclarationsSync';

    public const string SCOPE_REQUIRED = 'declaration:read';

    public const string ENTITY = LegalEntity::ENTITY_DECLARATION;

    /**
     * Get declarations data from EHealth API
     *
     * @param  string  $token
     * @return PromiseInterface|EHealthResponse
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        $query = ['page' => $this->page];

        // If user is doctor, get only his declarations
        if ($this->user->hasAllowedRole(Role::DOCTOR) && !$this->user->hasAllowedRole(Role::OWNER)) {
            $query['employee_id'] = $this->user
                ->employees()
                ->forParty($this->user->party->id)
                ->first()->uuid;
        }

        return EHealth::declaration()->withToken($token)->getMany(query: $query, groupByEntities: true);
    }

    /**
     * Store or update all the declarations data in the database
     *
     * @param  EHealthResponse|null  $response
     * @throws \Throwable
     */
    protected function processResponse(?EHealthResponse $response): void
    {
        $declarations = $response->validate();

        Repository::declaration()->storeMany($declarations, $this->legalEntity);
    }

    /**
     * Get additional middleware configurations for the job.
     *
     * @return array Returns an array of middleware configurations to be applied to the job
     */
    protected function getAdditionalMiddleware(): array
    {
        return [
            new RateLimited('ehealth-declaration-get')
        ];
    }

    /**
     * Get the next entity job to be scheduled after DeclarationSync completes.
     *
     * The list of the declaration requests is obtained first, so that the requests created outside of the MIS
     * are stored before the chain of DeclarationRequestDetailsSync jobs is built.
     *
     * @return EHealthJob|null
     */
    protected function getNextEntityJob(): ?EHealthJob
    {
        return new DeclarationRequestsSync(
            legalEntity: $this->legalEntity,
            nextEntity: $this->nextEntity,
            isFirstLogin: $this->isFirstLogin,
            standalone: $this->standalone
        );
    }
}
