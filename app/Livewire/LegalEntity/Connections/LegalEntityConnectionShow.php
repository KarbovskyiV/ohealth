<?php

declare(strict_types=1);

namespace App\Livewire\LegalEntity\Connections;

use Livewire\Component;
use App\Models\Connection;
use App\Models\LegalEntity;
use Livewire\Attributes\Title;
use App\Classes\eHealth\EHealth;
use App\Repositories\Repository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;

class LegalEntityConnectionShow extends Component
{
    /**
     * Connection data with the needed relation data.
     *
     * @var Connection
     */
    protected ?Connection $connection=null;

    public int $connectionId;

    /**
     * LegalEntity instance.
     *
     * @var LegalEntity
     */
    protected ?LegalEntity $legalEntity=null;

    public function mount(LegalEntity $legalEntity, Connection $connection)
    {
        $this->legalEntity = $legalEntity;

        $this->connectionId = $connection->id;

        $this->connection = $connection->load([
            'legalEntity',
            'client',
        ]);
    }

    public function sync(): void
    {
        $user = Auth::user();

        if ($user->cannot('sync', Connection::class)) {
            Session::flash('error', __('legal-entity.policy.deny.sync'));

            return;
        }

        $this->connection ??= Connection::find($this->connectionId);

        $clientUuid = $this->connection->clientUuid ?? null;

        // Connection synchronization
        try {
            $response = EHealth::connection()->getConnectionDetails(clientId: $clientUuid, connectionId: $this->connection->uuid);

            $connection = $response->validate();
        } catch (EHealthResponseException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);
            session()->flash('error', __('errors.ehealth.messages.server_error'));

            return;
        } catch (EHealthValidationException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);

            session()->flash('error', __('errors.ehealth.messages.validation_error'));

            return;
        }

        if (!Repository::legalEntity()->syncConnections([$connection], $this->legalEntity)) {
            return;
        }

        $this->connection->refresh();

        // Client synchronization
        try {
            $response = EHealth::connection()->getClientDetails(clientId: $clientUuid);

            $clientData = $response->validate();
        } catch (EHealthResponseException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);
            session()->flash('error', __('errors.ehealth.messages.server_error'));

            return;
        } catch (EHealthValidationException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);

            session()->flash('error', __('errors.ehealth.messages.validation_error'));

            return;
        }

        $client = Repository::legalEntity()->syncClient($clientData);

        if (!$client) {
            return;
        }

        $client->refresh();

        session()->flash('success', __('legal-entity-connection.sync.success_one'));

        return;
    }

    #[Title('Деталі зв\'язку')]
    public function render()
    {
        return view('livewire.legal-entity.connection.connection-show', [
            'connection' => $this->connection,
        ]);
    }
}
