<?php

declare(strict_types=1);

namespace App\Listeners;

use Throwable;
use RuntimeException;
use App\Events\EHealthUserLogin;
use App\Classes\eHealth\EHealth;
use App\Repositories\Repository;
use Illuminate\Support\Facades\Log;
use App\Enums\LegalEntity\ConnectionStatus;

/**
 * Verifies pending connections on eHealth login and marks matching records active.
 * If none match, saves verified replacements for the facility and retires stale pending records.
 */
class VerifyCreatedConnection
{
    public function handle(EHealthUserLogin $event): void
    {
        // Load the facility's local connections that still require confirmation from eHealth.
        $pendingConnections = $event->legalEntity
            ->connections()
            ->where('status', ConnectionStatus::PENDING->value)
            ->get();

        // Skip this verification step when no local connections are awaiting confirmation.
        if ($pendingConnections->isEmpty()) {
            return;
        }

        // Verifying pending connections only requires the scope for Get Client Connections.
        if (!\in_array('connection:read', $event->scopes, true)) {
            throw new RuntimeException(__('legal-entity-connection.connection_verification_forbidden'));
        }

        // The bearer token limits results to this MIS, whose connections as usual fit in a single page.
        // Fetch and validate the facility's connections before changing local statuses.
        try {
            $response = EHealth::connection()->getClientConnections(
                clientId: $event->legalEntity->uuid,
                query: [
                    'page' => 1,
                    'page_size' => config('ehealth.api.page_size_le_connections_max')
                ]
            );

            $connections = $response->validate();
        } catch (Throwable $err) {
            Log::channel('e_health_errors')->error(self::class . ':verifyConnection', [
                'legal_entity_id' => $event->legalEntity->id,
                'error' => $err->getMessage()
            ]);

            throw new RuntimeException(__('errors.ehealth.messages.server_error'), 0, $err);
        }

        // Compare connection UUIDs first to confirm the original pending records.
        $connectionUuids = array_column($connections, 'uuid');

        // If pending connection(s) for $event->legalEntity is present there is need the confirmation from eHealth.
        // If no matching connections are found in eHealth, the pending connections has last chance to be verified.
        if ($pendingConnections->pluck('uuid')->intersect($connectionUuids)->isEmpty()) {
            // The original connections are absent. Look for replacements belonging to this facility.
            // client_uuid identifies the facility; uuid identifies an individual connection.
            $legalEntityConnections = collect($connections)
                ->whereStrict('client_uuid', $event->legalEntity->uuid)
                ->values()
                ->all();

            // Block login if eHealth returned no connection for the facility.
            if (empty($legalEntityConnections)) {
                throw new RuntimeException(__('legal-entity-connection.connection_absent'));
            }

            // Create or update the verified replacements locally as ACTIVE before retiring stale rows.
            if (!Repository::legalEntity()->syncConnections($legalEntityConnections, $event->legalEntity, ConnectionStatus::ACTIVE)) {
                throw new RuntimeException(__('errors.ehealth.messages.server_error'));
            }

            // Retire the original pending rows only after their replacements were saved successfully.
            $event->legalEntity->connections()
                ->whereIn('id', $pendingConnections->modelKeys())
                ->where('status', ConnectionStatus::PENDING->value)
                ->update(['status' => ConnectionStatus::TERMINATED->value]);

            return;
        }

        // Activate only the original pending records whose UUIDs were confirmed by eHealth.
        $event->legalEntity->connections()
            ->whereIn('id', $pendingConnections->modelKeys())
            ->whereIn('uuid', $connectionUuids)
            ->where('status', ConnectionStatus::PENDING->value)
            ->update(['status' => ConnectionStatus::ACTIVE->value]);

        // Log unmatched pending records for investigation without blocking the verified login.
        $remainingPendingConnectionUuids = $event->legalEntity->connections()
            ->where('status', ConnectionStatus::PENDING->value)
            ->pluck('uuid');

        if ($remainingPendingConnectionUuids->isNotEmpty()) {
            Log::channel('e_health_errors')->error(self::class . ':verifyConnection', [
                'legal_entity_id' => $event->legalEntity->id,
                'pending_connection_uuids' => $remainingPendingConnectionUuids->all()
            ]);
        }
    }
}
