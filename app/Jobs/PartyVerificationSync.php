<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Classes\eHealth\EHealth;
use App\Classes\eHealth\EHealthResponse;
use App\Core\EHealthJob;
use App\Services\Party\PartyVerificationCache;
use App\Traits\BatchLegalEntityQueries;
use App\Traits\ProcessesPartyVerificationResponses;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Throwable;

class PartyVerificationSync extends EHealthJob
{
    use BatchLegalEntityQueries;
    use ProcessesPartyVerificationResponses;

    public const string BATCH_NAME = 'PartyVerificationFullSync';

    public const string SCOPE_REQUIRED = 'party_verification:details';

    /**
     * Sync via GET /api/parties/verifications (Get Party Verification Statuses List).
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse|null
    {
        return EHealth::party()
            ->withToken($token)
            ->getMany(['legal_entity_id' => $this->legalEntity->uuid], $this->page);
    }

    protected function processResponse(?EHealthResponse $response): void
    {
        if ($response === null) {
            return;
        }

        $this->processPartyVerificationResponse($response, $this->legalEntity);

        foreach ($response->map($response->validate()) as $partyUuid => $item) {
            if (is_string($partyUuid) && is_array($item)) {
                PartyVerificationCache::put($partyUuid, $item);
            }
        }
    }

    protected function getAdditionalMiddleware(): array
    {
        return [new RateLimited('ehealth-party-verification-get')];
    }

    public function failed(Throwable|null $exception): void
    {
        Log::error('Job [PartyVerificationSync] failed.', [
            'legal_entity_id' => $this->legalEntity->id ?? 'unknown',
            'error' => $exception?->getMessage(),
            'trace' => $exception?->getTraceAsString(),
        ]);
    }

    protected function getNextEntityJob(): ?EHealthJob
    {
        return $this->standalone || !$this->nextEntity
            ? new CompleteSync($this->legalEntity, isFirstLogin: $this->isFirstLogin)
            : $this->nextEntity;
    }
}
