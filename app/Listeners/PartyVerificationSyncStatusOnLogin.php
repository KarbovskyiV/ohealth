<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\User\Role;
use App\Events\EHealthUserLogin;
use App\Jobs\PartyVerificationSync;
use App\Models\LegalEntity;
use App\Models\User;
use App\Notifications\SyncNotification;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

/**
 * On subsequent logins (max once per 24h per LE), queue party verification bulk list sync.
 * Triggers ONLY on HR login without OWNER/ADMIN roles, requiring party_verification:read on OAuth token.
 */
class PartyVerificationSyncStatusOnLogin
{
    public const string SCOPE_REQUIRED = 'party_verification:read';

    private const string CACHE_KEY_PREFIX = 'party_verification_last_run:';

    private const int CACHE_TTL_SECONDS = 86400; // 24 hours

    /**
     * @throws JsonException
     */
    public function handle(EHealthUserLogin $event): void
    {
        if ($event->isFirstLogin) {
            return;
        }

        if (!in_array(self::SCOPE_REQUIRED, $event->scopes, true)) {
            Log::info('Party verification sync skipped: missing party_verification:read on token.', [
                'legal_entity_id' => $event->legalEntity->id,
                'user_id' => $event->user->id,
            ]);

            return;
        }

        $user = $event->user;
        $legalEntity = $event->legalEntity;

        if (!$this->isEligibleHrUser($user, $legalEntity, $event->guard)) {
            Log::info('Party verification sync skipped: User is not HR without OWNER/ADMIN.', [
                'legal_entity_id' => $legalEntity->id,
                'user_id' => $user->id,
            ]);

            return;
        }

        $cacheKey = self::CACHE_KEY_PREFIX . $legalEntity->id;

        if (Cache::has($cacheKey)) {
            Log::info('Party verification sync skipped: Already ran today.', ['legal_entity_id' => $legalEntity->id]);

            return;
        }

        try {
            $token = Crypt::decryptString($event->token);
        } catch (DecryptException) {
            $token = $event->token;
        } catch (Throwable $e) {
            Log::error('Party verification listener: Token decryption failed.', ['error' => $e->getMessage()]);

            return;
        }

        try {
            Log::info('Starting party verification sync (queued).', ['user_id' => $user->id]);

            Bus::batch([new PartyVerificationSync($legalEntity, null, false, standalone: true)])
                ->name('Party Verification Status Sync')
                ->withOption('legal_entity_id', $legalEntity->id)
                ->withOption('token', Crypt::encryptString($token))
                ->withOption('user', $user)
                ->withOption('sync_entity', LegalEntity::ENTITY_PARTY_VERIFICATION)
                ->then(function (Batch $batch) use ($user) {
                    $user->notify(new SyncNotification('party_verification', 'completed'));
                })
                ->catch(function (Batch $batch, Throwable $e) use ($user) {
                    $user->notify(new SyncNotification('party_verification', 'failed'));
                    Log::error('Batch [Party Verification Status Sync] failed.', ['error' => $e->getMessage()]);
                })
                ->onQueue('sync')
                ->dispatch();

            Cache::put($cacheKey, true, self::CACHE_TTL_SECONDS);
            $user->notify(new SyncNotification('party_verification', 'started'));
        } catch (Throwable $e) {
            Log::error('Failed to queue party verification sync on login.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isEligibleHrUser(User $user, LegalEntity $legalEntity, string $guard): bool
    {
        if (config('permission.teams')) {
            setPermissionsTeamId($legalEntity->id);
        }

        $user->unsetRelation('roles');

        $excludedRoles = [
            Role::OWNER->value,
            Role::ADMIN->value,
            Role::REORGANIZATION_OWNER->value,
            Role::PHARMACY_OWNER->value,
        ];

        // 1. Exclude OWNER and ADMIN roles
        if ($user->hasAnyRole($excludedRoles, $guard) || $user->hasAnyRole($excludedRoles)) {
            return false;
        }

        $hasExcludedEmployee = $user->employees()
            ->where('legal_entity_id', $legalEntity->id)
            ->whereIn('employee_type', $excludedRoles)
            ->exists();

        if ($hasExcludedEmployee) {
            return false;
        }

        // 2. Must be HR
        if ($user->hasRole(Role::HR->value, $guard) || $user->hasRole(Role::HR->value)) {
            return true;
        }

        return $user->employees()
            ->where('legal_entity_id', $legalEntity->id)
            ->where('employee_type', Role::HR->value)
            ->exists();
    }
}
