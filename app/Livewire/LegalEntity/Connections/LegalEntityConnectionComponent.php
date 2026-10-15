<?php

declare(strict_types=1);

namespace App\Livewire\LegalEntity\Connections;

use Throwable;
use RuntimeException;
use Livewire\Component;
use App\Models\Connection;
use App\Models\LegalEntity;
use Livewire\Attributes\Locked;
use App\Classes\eHealth\EHealth;
use App\Repositories\Repository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Livewire\Features\SupportRedirects\Redirector;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Classes\eHealth\Api\Connection as ConnectionApi;

class LegalEntityConnectionComponent extends Component
{
    /**
     * The ID of the legal entity.
     *
     * @var ?int
     */
    #[Locked]
    public ?int $legalEntityId = null;

    /**
     * The ID of the connection.
     *
     * @var ?int
     */
    #[Locked]
    public ?int $connectionId = null;

    /**
     * Controls the visibility of the signature modal.
     *
     * @var bool
     */
    public bool $showSignatureModal = false;

    /**
     * The legal entity model instance.
     *
     * @var ?LegalEntity
     */
    protected ?LegalEntity $legalEntity = null;

    /**
     * The connection model instance.
     *
     * @var ?Connection
     */
    protected ?Connection $connection = null;

    public function mount(LegalEntity $legalEntity, ?Connection $connection=null)
    {
        $this->legalEntity = $legalEntity ?? request()->route('legalEntity');

        $this->legalEntityId = $this->legalEntity?->id;

        if ($connection) {
            abort_unless($connection->legalEntityId === $this->legalEntityId, 404);

            $this->connection = $connection?->load([
                'legalEntity',
                'client',
            ]);

            $this->connectionId = $connection->id;
        }
    }

    /**
     * Rehydrates the protected legalEntity/connection properties on every request.
     *
     * Livewire does not rehydrate protected typed properties on subsequent requests
     * (e.g. action calls), only on the initial mount().
     *
     * @return void
     */
    public function boot(): void
    {
        // Sets the connection model instance if not already set.
        if (!isset($this->connection) && $this->connectionId !== null) {
            $this->connection ??= Connection::where('legal_entity_id', $this->legalEntityId)->findOrFail($this->connectionId);
        }

        // Sets the legal entity model instance if not already set.
        if (!isset($this->legalEntity) && $this->legalEntityId !== null) {
            $this->legalEntity ??= LegalEntity::find($this->legalEntityId);
        }
    }

    /**
     * Updates the secret for a connection in eHealth and synchronizes it with the local database.
     *
     * @param  Connection  $connection  The connection model instance to update The new redirect URI to set for the connection
     *
     * @return void
     *
     * @throws EHealthResponseException  If eHealth API returns a server error
     * @throws EHealthValidationException  If eHealth API returns a validation error
     */
    public function refreshSecret(Connection $connection): void
    {
        if (Auth::user()->cannot('updateSecret', $connection)) {
            session()->flash('error', __('legal-entity-connection.policy.restrict.update_secret'));

            return;
        }

        try {
            $response = EHealth::connection()->refreshConnectionToken($connection->legalEntity->uuid, $connection->uuid);

            $connectionData = $response->validate();
        } catch (EHealthConnectionException $err) {
            $err->handle(self::class . ':refreshSecret');

            return;
        } catch (EHealthResponseException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);
            session()->flash('error', $err->getDetails()['error']['message'] ?? $err->getMessage());

            return;
        } catch (EHealthValidationException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);

            session()->flash('error', $this->validationErrorMessage($err));

            return;
        }

        if (!Repository::legalEntity()->syncConnectionSecret($connection, $connectionData['secret'])) {
            return;
        }

        $this->connection?->refresh();

        session()->flash('success', __('legal-entity-connection.secret_updated_title'));

        $this->dispatch('connection-secret-updated', connectionId: $connection->id, secret: $connectionData['secret']);
    }

    /**
     * Updates the redirect URI for a connection in eHealth and synchronizes it with the local database.
     *
     * @param  Connection  $connection  The connection model instance to update
     *
     * @return void
     *
     * @throws EHealthResponseException  If eHealth API returns a server error
     * @throws EHealthValidationException  If eHealth API returns a validation error
     */
    public function update(Connection $connection): void
    {
        if (Auth::user()->cannot('update', $connection)) {
            session()->flash('error', __('legal-entity-connection.policy.restrict.update_connection'));

            return;
        }

        try {
            $response = EHealth::connection()->updateConnectionRedirectUri(
                $connection->legalEntity->uuid,
                $connection->uuid,
                ConnectionApi::redirectUri()
            );

            $connectionData = $response->validate();
        } catch (EHealthConnectionException $err) {
            $err->handle(self::class . ':update');

            return;
        } catch (EHealthResponseException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);
            session()->flash('error', $err->getDetails()['error']['message'] ?? $err->getMessage());

            return;
        } catch (EHealthValidationException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);

            session()->flash('error', $this->validationErrorMessage($err));

            return;
        }

        if (!Repository::legalEntity()->syncRedirectUri($connection, $connectionData['redirect_uri'])) {
            return;
        }

        $this->connection?->refresh();

        session()->flash('success', __('legal-entity-connection.callback_updated_title'));

        $this->dispatch('connection-callback-updated', connectionId: $connection->id);
    }

     /**
     * Deletes a connection in eHealth and synchronizes it with the local database.
     *
     * @param  Connection  $connection  The connection model instance to delete
     *
     * @return RedirectResponse|Redirector|null
     *
     * @throws EHealthResponseException  If eHealth API returns a server error
     * @throws EHealthValidationException  If eHealth API returns a validation error
     */
    public function deleteConnection(Connection $connection): RedirectResponse|Redirector|null
    {
        if (Auth::user()->cannot('delete', $connection)) {
            session()->flash('error', __('legal-entity-connection.policy.restrict.delete'));

            return null;
        }

        $clientUuid = $connection->legalEntity->uuid;

        try {
            $response = EHealth::connection()->deleteConnection($clientUuid, $connection->uuid);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
                throw new EHealthResponseException($response);
            }
        } catch (EHealthConnectionException $err) {
            $err->handle(self::class . ':deleteConnection');

            return null;
        } catch (EHealthResponseException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);
            session()->flash('error', $err->getDetails()['error']['message'] ?? $err->getMessage());

            return null;
        } catch (EHealthValidationException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);

            session()->flash('error', $this->validationErrorMessage($err));

            return null;
        }

        $deleteStatusCode = $response->getStatusCode();
        $verificationStage = 'get_client_connections';
        $verificationError = null;

        try {
            $connectionsData = $this->getClientConnections();

            if (!$connectionsData) {
                $verificationError = session('error');

                throw new RuntimeException('Unable to fetch client connections after accepted DELETE.');
            }

            $verificationStage = 'verify_connection_absence';

            if (\in_array($connection->uuid, array_column($connectionsData['responseData'], 'uuid'), true)) {
                throw new RuntimeException('Connection is still present in eHealth after accepted DELETE.');
            }

            $verificationStage = 'save_terminated_status';

            if (!Repository::legalEntity()->syncConnectionDelete($connection)) {
                throw new RuntimeException('Unable to save TERMINATED status after accepted DELETE.');
            }

            $verificationStage = 'refresh_connection';
            $connection->refresh();
        } catch (Throwable $err) {
            Log::channel('e_health_errors')->error(self::class . ':deleteConnection:verificationRequired', [
                'legal_entity_id' => $connection->legalEntityId,
                'legal_entity_uuid' => $clientUuid,
                'connection_id' => $connection->id,
                'connection_uuid' => $connection->uuid,
                'delete_http_status' => $deleteStatusCode,
                'verification_required' => true,
                'stage' => $verificationStage,
                'error' => $err->getMessage()
            ]);

            session()->flash('error', __('legal-entity-connection.sync.error.terminate_verification_required') . ($verificationError ? "\n" . $verificationError : ''));

            return null;
        }

        return Redirect::route('connection.index', [legalEntity()])->with('success', __('legal-entity-connection.terminate_success')) ?? null;
    }

     /**
     * Synchronize a single client's details from eHealth.
     *
     * @param  string  $clientUuid  The UUID of the client to synchronize
     *
     * @return bool  Returns true if synchronization was successful, false otherwise
     *
     * @throws EHealthResponseException  If eHealth API returns a server error
     * @throws EHealthValidationException  If eHealth API returns a validation error
     */
    protected function syncSingleClient(string $clientUuid): bool
    {
        try {
            $response = EHealth::connection()->getClientDetails(clientId: $clientUuid);

            $clientData = $response->validate();
        } catch (EHealthResponseException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);
            session()->flash('error', $err->getDetails()['error']['message'] ?? $err->getMessage());

            return false;
        } catch (EHealthValidationException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);

            session()->flash('error', $this->validationErrorMessage($err));

            return false;
        }

        $client = Repository::legalEntity()->syncClient($clientData);

        if (!$client) {
            return false;
        }

        $client->refresh();

        return true;
    }

    /**
     * Retrieves the legal entity's client connections from eHealth.
     *
     * @return array{response: mixed, responseData: array}|null
     *
     * @throws EHealthResponseException
     * @throws EHealthValidationException
     */
    protected function getClientConnections(): array|null
    {
        $syncQuery = [
            'page' => 1,
            'page_size' => config('ehealth.api.page_size_le_connections_max')
        ];

        try {
            $response = EHealth::connection()->getClientConnections(clientId: $this->legalEntity->uuid, query: $syncQuery);

            $responseData = $response->validate();
        } catch (EHealthResponseException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);
            session()->flash('error', $err->getDetails()['error']['message'] ?? $err->getMessage());

            return null;
        } catch (EHealthValidationException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);

            session()->flash('error', $this->validationErrorMessage($err));

            return null;
        }

        return [
            'response' => $response,
            'responseData' => $responseData
        ];
    }

    protected function validationErrorMessage(EHealthValidationException $exception): string
    {
        $message = $exception->getDetails()['error']['message'] ?? null;
        $formattedMessage = $exception->getFormattedMessage();

        if (\is_string($message) && $message !== '' && !str_contains($formattedMessage, $message)) {
            return $message . "\n" . $formattedMessage;
        }

        return $formattedMessage;
    }
}
