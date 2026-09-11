<?php

namespace App\Livewire\Specimen;

use Livewire\Component;

class SpecimenIndex extends Component
{
    public $searchId = '';

    public function search()
    {
    }

    public function sync()
    {
    }

    public function render()
    {
        return view('livewire.specimen.specimen-index');
    }
}
