<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class FlashMessage extends Component
{
    #[Locked]
    public string $message = '';

    #[Locked]
    public string $type = 'success';

    #[Locked]
    public array $errors = [];

    #[Locked]
    public int $notificationId = 0;

    public function mount(): void
    {
        foreach (['error', 'warning', 'success', 'info'] as $type) {
            if (session()->has($type)) {
                $this->flashMessage(['message' => session()->pull($type), 'type' => $type]);

                return;
            }
        }
    }

    #[On('flashMessage')]
    public function flashMessage($flash): void
    {
        $this->message = $flash['message'] ?? '';
        $this->type = $flash['type'];
        $this->errors = $flash['errors'] ?? [];
        $this->notificationId++;
    }

    public function render(): View
    {
        return view('livewire.components.flash-message');
    }
}
