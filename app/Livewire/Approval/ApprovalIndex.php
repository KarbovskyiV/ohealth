<?php

namespace App\Livewire\Approval;

use Livewire\Component;

class ApprovalIndex extends Component
{
    public $search = '';
    public $filter = [
        'data_id'      => '',
        'reason_id'    => '',
        'granted_to'   => '',
        'data_type'    => '',
        'status'       => '',
        'access_level' => '',
    ];

    public function applyFilters()
    {
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->filter = [
            'data_id'      => '',
            'reason_id'    => '',
            'granted_to'   => '',
            'data_type'    => '',
            'status'       => '',
            'access_level' => '',
        ];
    }

    public function render()
    {
        return view('livewire.approval.approval-index');
    }
}
