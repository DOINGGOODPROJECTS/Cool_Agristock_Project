<?php

namespace App\Http\Controllers;

use App\Models\Storage;
use App\Models\Temperature;
use Illuminate\Http\Request;

class TemperatureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        app()->setLocale(auth()->user()->locale);
        $temperatures = Temperature::orderByDesc('id')->get();
        $storages = Storage::all();
        return view('admin.temperatures', compact('temperatures', 'storages'));
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
        
        Temperature::create($data);
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
        $temperature = Temperature::find($id);

        $data = $request->except('_token', '_method');
        $temperature->update($data);
        return redirect()->back()->with('success', 'Temperature updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $temperature = Temperature::find($id);
        $temperature->delete();
        return redirect()->back()->with('success', 'Temperature deleted successfully');
    }
}
