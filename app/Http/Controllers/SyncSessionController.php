<?php

namespace App\Http\Controllers;

use App\Models\SyncSession;

class SyncSessionController extends Controller
{
    public function index()
    {
        $sessions = SyncSession::with('user')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.sync-sessions', compact('sessions'));
    }
}
