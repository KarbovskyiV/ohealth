<?php

declare(strict_types=1);

namespace App\Livewire\LegalEntity\Connections\Form;

use Livewire\Form;
use App\Models\LegalEntityType;
use Illuminate\Validation\Rule;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ConnectionForm extends Form
{
    public string $clientUuid = '';

    public ?string $knedp = null;

    public ?TemporaryUploadedFile $keyContainerUpload = null;

    public string $keyContainerFileName = '';

    public ?string $password = null;

    public ?string $taxId = null;

    /**
     * Validation rules required to sign data with a KEP key.
     *
     * @return array
     */
    public function rulesForSign(bool $skipTaxIdValidation = false): array
    {
        $rules = [
            'knedp' => ['required', 'string'],
            'keyContainerUpload' => ['required', 'file', 'extensions:dat,pfx,pk8,zs2,jks,p7s'],
            'password' => ['required', 'string', 'max:255'],
        ];

        if (!$skipTaxIdValidation) {
            $rules['taxId'] = ['required', 'string', 'max:10'];
        }

        return $rules;
    }

    public function rulesForClientId(): array
    {
        return [
            'clientUuid' => ['required', 'string'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'clientUuid' => __('legal-entity-connection.client_id_label_lower'),
        ];
    }

    public function rulesForCreate(array $allowedLegalEntityTypes): array
    {
        return [
            'clientUuid' => ['required', 'string'],
            'redirectUri' => ['required', 'string'],
            'clientName' => ['required', 'string'],
            'clientType' => [
                'required',
                'integer',
                Rule::exists(LegalEntityType::class, 'id')->whereIn('name', $allowedLegalEntityTypes)
            ]
        ];
    }

    public function rulesForCredentials(): array
    {
        return [
            'client_id' => ['required', 'uuid'],
            'secret' => ['required', 'string']
        ];
    }

    /**
     * Stores the uploaded key container's original filename for display purposes.
     * (Livewire lifecycle hook, auto-invoked when `keyContainerUpload` is updated)
     *
     * @param  ?TemporaryUploadedFile  $upload
     *
     * @return void
     */
    public function updatedKeyContainerUpload(?TemporaryUploadedFile $upload): void
    {
        $this->keyContainerFileName = $upload?->getClientOriginalName() ?? '';
    }
}
