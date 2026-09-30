<?php

declare(strict_types=1);

namespace App\Livewire\DeviceAssociation;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthConnectionException;
use App\Exceptions\EHealth\EHealthException;
use App\Livewire\Person\Records\BasePatientComponent;
use App\Models\LegalEntity;
use App\Models\MedicalEvents\Sql\Device;
use App\Models\MedicalEvents\Sql\DeviceAssociation;
use App\Models\Person\Person;
use App\Models\Preperson;
use App\Repositories\MedicalEvents\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Throwable;

class DeviceAssociationView extends BasePatientComponent
{
    /**
     * ID of the device association being displayed.
     *
     * @var int
     */
    #[Locked]
    public int $deviceAssociationId;

    /**
     * eHealth ID of the device association, kept so that a refresh does not have to read the record to find it.
     *
     * @var string
     */
    #[Locked]
    public string $deviceAssociationUuid;

    /**
     * Request-scoped memoized device association.
     *
     * @var DeviceAssociation|null
     */
    private ?DeviceAssociation $deviceAssociationModel = null;

    protected array $dictionaryNames = [
        'device_association_statuses',
        'device_association_status_reasons',
        'eHealth/body_structures',
        'eHealth/report_origins'
    ];

    /**
     * Bind the route models and load the device association being displayed.
     *
     * @param  LegalEntity  $legalEntity
     * @param  Person|null  $person
     * @param  Preperson|null  $preperson
     * @param  DeviceAssociation|null  $deviceAssociation
     * @return void
     */
    public function mount(
        LegalEntity $legalEntity,
        ?Person $person = null,
        ?Preperson $preperson = null,
        ?DeviceAssociation $deviceAssociation = null
    ): void {
        parent::mount($legalEntity, $person, $preperson);

        $this->getDictionary();

        $this->deviceAssociationId = $deviceAssociation->id;
        $this->deviceAssociationUuid = $deviceAssociation->uuid;

        $this->deviceAssociation();
    }

    /**
     * Refresh the device association from eHealth, so that the page shows the record as it stands there now.
     *
     * @return void
     */
    public function sync(): void
    {
        if (Auth::user()->cannot('view', DeviceAssociation::class)) {
            Session::flash('error', __('device-associations.policy.sync'));

            return;
        }

        try {
            $response = EHealth::deviceAssociation()->getById($this->uuid, $this->deviceAssociationUuid);
        } catch (EHealthException|EHealthConnectionException $exception) {
            $exception->handle('Error while synchronizing the device association');

            return;
        }

        try {
            Repository::deviceAssociation()->sync($this->patient(), [$response->validate()]);
        } catch (Throwable $exception) {
            $this->handleDatabaseErrors($exception, 'Error while synchronizing the device association');

            return;
        }

        // Drop the memoized model so that the page renders what has just been stored
        $this->deviceAssociationModel = null;

        Session::flash('success', __('device-associations.messages.record_synced_successfully'));
    }

    /**
     * Resolve the device association being displayed, scoped to the patient so that a record belonging to somebody else
     * is not reachable by its ID. Loaded again on later requests, where Livewire hydrates without mount().
     *
     * @return DeviceAssociation
     */
    protected function deviceAssociation(): DeviceAssociation
    {
        return $this->deviceAssociationModel ??= DeviceAssociation::forPatient($this->patient())
            ->withAllRelations()
            ->whereId($this->deviceAssociationId)
            ->firstOrFail();
    }

    /**
     * Name of the patient's stored device the association refers to.
     *
     * @param  string|null  $deviceId
     * @return string|null
     */
    protected function deviceName(?string $deviceId): ?string
    {
        if ($deviceId === null) {
            return null;
        }

        return Device::forPatient($this->patient())->whereUuid($deviceId)->with('names')->first()?->names->first()?->value;
    }

    public function render(): View
    {
        $deviceAssociation = $this->deviceAssociation();

        return view('livewire.device-association.device-association-view')->with([
            'deviceAssociation' => $deviceAssociation,
            'deviceName' => $this->deviceName($deviceAssociation->device?->value)
        ]);
    }
}
