<?php

namespace App\Http\Controllers;

use App\Models\MemberPhone;
use App\Models\User;
use Illuminate\Http\Request;

class MemberPhoneController extends Controller
{
    public function index()
    {
        $phones    = MemberPhone::with('user')->orderByDesc('id')->get();
        $customers = User::where('group_id', '>=', 5)->orderBy('name')->get();
        return view('admin.member-phones', compact('phones', 'customers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'phone'   => 'required|string|unique:member_phones,phone',
        ]);

        MemberPhone::create([
            'user_id' => $request->user_id,
            'phone'   => $request->phone,
        ]);

        return redirect()->back()->with('success', 'Phone registered.');
    }

    public function verify(string $id)
    {
        MemberPhone::findOrFail($id)->update(['verified_at' => now()]);
        return redirect()->back()->with('success', 'Phone verified.');
    }

    public function destroy(string $id)
    {
        MemberPhone::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Phone removed.');
    }
}
