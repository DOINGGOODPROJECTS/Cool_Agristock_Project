<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Storage;
use App\Models\StorageType;
use Illuminate\Http\Request;

class StorageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        app()->setLocale(auth()->user()->locale);
        $storages = Storage::all();
        return view('admin.storages', compact('storages'));
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
        
        Storage::create($data);
        return redirect()->back()->with('success', 'Storage created successfully');
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
        $storage = Storage::find($id);

        $data = $request->except('_token', '_method');
        $storage->update($data);
        return redirect()->back()->with('success', 'Storage updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $storage = Storage::find($id);
        $storage->delete();
        return redirect()->back()->with('success', 'Storage deleted successfully');
    }
}
