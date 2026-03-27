<?php

namespace App\Livewire\Layout;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Navbar extends Component
{
    public string $userName = '';

    public function mount(): void
    {
        $this->userName = (string) (Auth::user()->name ?? 'User');
    }

    public function render()
    {
        return view('livewire.layout.navbar');
    }
}
