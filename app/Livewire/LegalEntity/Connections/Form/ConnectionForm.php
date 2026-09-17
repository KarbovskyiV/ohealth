<?php

declare(strict_types=1);

namespace App\Livewire\LegalEntity\Connections\Form;

use Livewire\Form;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ConnectionForm extends Form
{
    public ?string $knedp = null;

    public ?TemporaryUploadedFile $keyContainerUpload = null;

    public string $keyContainerFileName = '';

    public ?string $password = null;

    /**
     * Validation rules required to sign data with a KEP key.
     *
     * @return array
     */
    public function rulesForSign(): array
    {
        return [
            'knedp' => ['required', 'string'],
            'keyContainerUpload' => ['required', 'file', 'extensions:dat,pfx,pk8,zs2,jks,p7s'],
            'password' => ['required', 'string', 'max:255'],
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
