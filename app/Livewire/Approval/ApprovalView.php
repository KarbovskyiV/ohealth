<?php

namespace App\Livewire\Approval;

use Livewire\Component;

class ApprovalView extends Component
{
    public $approval;

    public function mount($approval)
    {
        $this->approval = $approval;
    }

    public function render()
    {
        return view('livewire.approval.approval-view');
    }
}
