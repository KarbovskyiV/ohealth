<?php

namespace App\Livewire\MisConnection;

use Livewire\Component;
use Livewire\Attributes\Title;

class MisConnectionIndex extends Component
{
    public bool $showSignatureModal = false;
    public array $form = [];
    public $legalEntity;

    public function mount($legalEntity = null)
    {
        $this->legalEntity = $legalEntity ?? request()->route('legalEntity');
    }

    public function sign()
    {
        $this->showSignatureModal = false;

        session()->flash('success', 'Зв\'язок успішно встановлений!');
        return redirect()->route('mis-ehealth-connections.show', [
            'legalEntity' => $this->legalEntity ?? 1,
            'id' => 'conn-13-1312qe11'
        ]);
    }

    #[Title('Зв\'язки МІС та СГуСОЗ')]
    public function render()
    {
        $connections = collect([
            [
                'id' => 1,
                'name' => 'ТОВ "Класна лікарня"',
                'identifier' => '1331qwee13-1312qe11',
                'mis_id' => 'MIS-12334145',
                'conn_id' => 'conn-13-1312qe11',
                'callback' => 'https://mis.example.com/',
                'status' => 'Активний',
                'created_at' => '22.06.2026',
            ],
            [
                'id' => 2,
                'name' => 'КНП "Лікарня №4"',
                'identifier' => '1331qwee13-1312qe11',
                'mis_id' => 'MIS-12334145',
                'conn_id' => 'conn-13-1312qe11',
                'callback' => 'https://mis.example.com/',
                'status' => 'Активний',
                'created_at' => '22.06.2026',
            ],
        ]);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $connections,
            $connections->count(),
            10,
            1,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        return view('livewire.mis-connection.mis-connection-index', [
            'connections' => $paginator
        ]);
    }
}
