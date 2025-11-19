<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        app()->setLocale(auth()->user()->locale);
        $groups = Group::where('id', '<', 5)->get();
        $users = User::where('group_id', '<', 5)->get();
        return view('admin.users', compact('groups', 'users'));
    }

    /**
     * Display a listing of the resource.
     */
    public function customers()
    {
        $groups = Group::where('id', '>=', 5)->get();
        $customers = User::where('group_id', '>=', 5)->get();
        return view('admin.customers', compact('groups', 'customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->except('_token');

        if (empty($data['password'])) {
            $data['password'] = Hash::make('CoolAgriStock@225');
        }
        
        User::create($data);
        return redirect()->back()->with(['title'=>__('locale.user', ['suffix'=>'']), 'message'=>'User created successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        $data = $request->except('_token', '_method');
        $user->update($data);
        return redirect()->back()->with(['title'=>__('locale.user', ['suffix'=>'']), 'message'=>'User updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        $user->delete();
        return redirect()->back()->with('success', 'User deleted successfully');
    }
}
