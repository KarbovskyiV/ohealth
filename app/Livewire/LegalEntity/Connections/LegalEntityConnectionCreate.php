<?php
declare(strict_types=1);

namespace App\Livewire\LegalEntity\Connections;

use Throwable;
use RuntimeException;
use App\Enums\Status;
use App\Models\Connection;
use App\Models\LegalEntity;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use App\Models\LegalEntityType;
use Livewire\Attributes\Locked;
use App\Classes\eHealth\EHealth;
use App\Repositories\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Traits\BatchLegalEntityQueries;
use App\Enums\LegalEntity\ConnectionStatus;
use App\Auth\EHealth\Services\TokenStorage;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Classes\eHealth\Api\Connection as ConnectionApi;
use App\Livewire\LegalEntity\Connections\Form\ConnectionForm;

class LegalEntityConnectionCreate extends LegalEntityConnectionComponent
{
    use BatchLegalEntityQueries,
        WithFileUploads;

    protected const array LEGAL_ENTITY_TYPES = [
        LegalEntity::TYPE_PRIMARY_CARE,
        LegalEntity::TYPE_OUTPATIENT,
        LegalEntity::TYPE_EMERGENCY,
        LegalEntity::TYPE_PHARMACY
    ];

    /**
     * @var object|null
     */
    public ?object $file = null;

    #[Locked]
    public ?int $legalEntityId = null;

    #[Locked]
    public ?int $connectionId = null;

    public const string BATCH_NAME = 'ConnectionSync';

    public const string BATCH_SUBNAME = 'ConnectionClientSync';

    public bool $showSignatureModal = false;

    public bool $showConnectionData = false;

    public string $clientUuid = '';

    #[Locked]
    public string $redirectUri = '';

    public string $clientName = '';

    public ?int $clientType = null;

    public ConnectionForm $form;

    public array $legalEntityTypes = [];

    #[Locked]
    public array $dataToSign = [
        'client_id' => '',
        'redirect_uri' => ''
    ];

    public function mount(LegalEntity $legalEntity, ?Connection $connection=null)
    {
        $legalEntity ??= null;

        parent::mount($legalEntity, $connection);

        $this->form->taxId = Auth::user()?->party?->taxId ?? null;

        $this->redirectUri = ConnectionApi::redirectUri();

        $this->clientUuid = $this->legalEntity?->clientId ?? '';

        $edrData = \is_string($legalEntity->edr)
            ? json_decode($legalEntity->edr, true)
            : (\is_array($legalEntity->edr) ? $legalEntity->edr : []);

        $this->clientName = $edrData['name'] ?? '';

        $this->setLegalEntityTypes();
    }

    /**
     * Populates `legalEntityTypes` with allowed types keyed by id, localized and reverse-ordered for display.
     *
     * @return void
     */
    protected function setLegalEntityTypes(): void
    {
        $types = LegalEntityType::whereIn('name', self::LEGAL_ENTITY_TYPES)
            ->pluck('localized_name', 'id')
            ->map(fn ($label) => __($label))
            ->reverse()
            ->toArray();

        foreach ($types as $id => $localizedName) {
            $this->legalEntityTypes[$id] = $localizedName;
        }
    }

    public function create(string $clientUuid, string $clientName): void
    {
        if (Auth::user()->cannot('create', Connection::class)) {
            session()->flash('error', __('legal-entity-connection.policy.restrict.create'));

            return;
        }

        // Resume saving the connection already created in eHealth before accepting new creation data.
        if (session()->has($this->pendingCreationKey())) {
            $this->localSave();

            return;
        }

        $this->clientUuid = $clientUuid;
        $this->redirectUri = ConnectionApi::redirectUri();
        $this->clientName = $clientName;

        $this->validate($this->form->rulesForCreate(self::LEGAL_ENTITY_TYPES));

        $this->dataToSign = [
            'client_id' => $clientUuid,
            'redirect_uri' => $this->redirectUri
        ];

        $this->showSignatureModal = true;
    }

    public function sign()
    {
        if (Auth::user()->cannot('create', Connection::class)) {
            session()->flash('error', __('legal-entity-connection.policy.restrict.create'));

            return;
        }

        // Reuse the saved eHealth response to avoid signing and creating the same connection again.
        if (session()->has($this->pendingCreationKey())) {
            $this->localSave();

            return;
        }

        $this->form->validate($this->form->rulesForSign());

        try {
            $signedData = signatureService()->signData(
                $this->dataToSign,
                $this->form->password,
                $this->form->knedp,
                $this->form->keyContainerUpload,
                $this->form->taxId
            );
        } catch (Throwable $err) {
            Log::error('Failed to sign connection data.', ['exception' => $err]);
            session()->flash('error', $err->getMessage());

            return;
        }

        // Handle errors from encrypted data
        if (isset($signedData['errors'])) {
            $this->dispatchErrorMessage($signedData['errors']);

            Log::channel('e_health_errors')->error(self::class . ':createConnection', ['error' => $signedData['errors']]);

            session()->flash('error', __('forms.signature_validation_error'));

            return;
        }

        // Revalidate mutable creation fields before creating the connection in eHealth.
        $this->validate($this->form->rulesForCreate(self::LEGAL_ENTITY_TYPES));

        $this->showSignatureModal = false;

        try {
            $response = EHealth::connection()->createConnection($signedData);

            $connectionData = $response->validate();
        } catch (EHealthResponseException $err) {
            $code = $err->getCode();
            $errorMessage = $err->getDetails()['error']['message'] ?? ($err->getMessage() ?? __('errors.ehealth.messages.server_error'));

            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $errorMessage]);

            $code === 409
                ? session()->flash('error', __('legal-entity-connection.sync.error.already_exist'))
                : session()->flash('error', $errorMessage);

            return;
        } catch (EHealthValidationException $err) {
            Log::channel('e_health_errors')->error(self::class . ':syncConnections', ['error' => $err->getDetails()]);

            session()->flash('error', __('errors.ehealth.messages.validation_error'));

            return;
        } catch (Throwable $err) {
            Log::channel('e_health_errors')->error(self::class . ':createConnection', ['error' => $err->getMessage()]);

            session()->flash('error', __('errors.ehealth.messages.server_error'));

            return;
        }

        // Retain the secret and original facility details until all local records are saved.
        session()->put($this->pendingCreationKey(), Crypt::encrypt([
            'connection' => $connectionData,
            'client_type' => $this->clientType,
            'client_name' => $this->clientName
        ]));

        // Persist the encrypted response before attempting database writes so a failure can be retried.
        session()->save();

        $this->localSave();
    }

    /**
     * Session key for the current user's encrypted eHealth creation response and original facility details.
     * The pending data is retained until local persistence succeeds.
     */
    protected function pendingCreationKey(): string
    {
        return 'connection_creation.' . Auth::id();
    }

    /**
     * Save a created eHealth connection locally (without repeating its creation in eHealth for the same connection).
     */
    public function localSave(): void
    {
        if (Auth::user()->cannot('create', Connection::class)) {
            session()->flash('error', __('legal-entity-connection.policy.restrict.create'));

            return;
        }

        $pendingCreation = session()->get($this->pendingCreationKey());

        if ($pendingCreation === null) {
            return;
        }

        $this->showSignatureModal = false;

        try {
            $creation = Crypt::decrypt($pendingCreation);
            $connectionData = $creation['connection'];

            $legalEntity = LegalEntity::where('uuid', $connectionData['client_uuid'])->first()
                ?? Repository::legalEntity()->initLegalEntity($connectionData['client_uuid'], $creation['client_type'], $creation['client_name'], $connectionData['secret']);

            if (!$legalEntity || !Repository::legalEntity()->syncConnections([$connectionData], $legalEntity, ConnectionStatus::PENDING)) {
                throw new RuntimeException('Unable to save the created connection locally.');
            }

            $connection = Connection::whereUuid($connectionData['uuid'])->first();

            if (!$connection) {
                throw new RuntimeException('Created connection was not found locally.');
            }

            DB::transaction(function () use ($connectionData, $connection, $legalEntity) {
                Repository::legalEntity()->updateLegalEntitySecret($connection->legalEntity, $connectionData['secret']);

                // Refresh facility details on the next eHealth login, including after reconnecting.
                if (!$legalEntity->update(['status' => Status::NEW->value])) {
                    throw new RuntimeException('Unable to mark the facility for a details refresh.');
                }
            });
        } catch (Throwable $err) {
            Log::error('Connection creation: local persistence failed; retry required.', [
                'connection_uuid' => $connectionData['uuid'] ?? null,
                'exception_type' => $err::class
            ]);

            // Keep the pending response in the session for another local-save attempt.
            session()->flash('error', __('legal-entity-connection.local_save_retry_required'));

            return;
        }

        $this->legalEntity = $legalEntity;
        $this->legalEntityId = $legalEntity->id;
        $this->connection = $connection;
        $this->connectionId = $connection->id;

        // Local records and the secret are saved; connection verification no longer needs the retry data.
        session()->forget($this->pendingCreationKey());

        $token = app(TokenStorage::class)->getBearerToken();

        $isSuccessfulConnect = false;

        // Verify the connection for case when user is logined by the eHealth system
        // Otherwise, verification will be done via the VerifyCreatedConnection listener
        if ($token) {
            try {
                $response = EHealth::connection()->getClientConnections(
                    clientId: $this->legalEntity->uuid,
                    query: [
                        'page' => 1,
                        'page_size' => config('ehealth.api.page_size_le_connections_max')
                    ]
                );

                $connections = $response->validate();

                $isSuccessfulConnect = \in_array($this->connection->uuid, array_column($connections, 'uuid'), true);
            } catch (Throwable $err) {
                Log::channel('e_health_errors')->error(self::class . ':verifyConnection', ['error' => $err->getMessage()]);

                session()->flash('error', __('errors.ehealth.messages.server_error'));

                return;
            }

            if (!$isSuccessfulConnect) {
                session()->flash('error', __('legal-entity-connection.connection_error'));

                return;
            }

            $this->connection->update(['status' => ConnectionStatus::ACTIVE->value]);
        }

        session()->flash('success', __('legal-entity-connection.connection_established'));
    }

    #[Title('Зв\'язки МІС та СГуСОЗ')]
    public function render()
    {
        return view('livewire.legal-entity.connection.connection-create', [
            'connection' => $this->connection,
            'hasPendingCreation' => session()->has($this->pendingCreationKey())
        ]);
    }
}
