<?php

namespace App\View\Components;

use App\Models\Claim;
use Illuminate\View\View;
use Illuminate\View\Component;

class AppLayout extends Component
{
    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        $notifications = Claim::where('status', 'EN COURS')->get();
        return view('layouts.app', compact('notifications'));
    }
}
