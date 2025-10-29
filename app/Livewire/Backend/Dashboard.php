<?php

namespace App\Livewire\Backend;

use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.backend.dashboard')
        ->layout('backend.pages.layouts.master', [
            'title' => 'Database Management',
        ]);
    }
}
