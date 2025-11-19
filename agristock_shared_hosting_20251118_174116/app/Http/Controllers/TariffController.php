<?php

namespace App\Http\Controllers;

use App\Models\Capacity;
use App\Models\Tariff;
use App\Models\Storage;
use Illuminate\Http\Request;

class TariffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        app()->setLocale(auth()->user()->locale);
        $tariffs = Tariff::all();
        $storages = Storage::all();
        $capacities = Capacity::all();
        return view('admin.tariffs', compact('capacities', 'tariffs', 'storages'));
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
        
        Tariff::create($data);
        return redirect()->back()->with('success', 'Tarif created successfully');
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
        $tariff = Tariff::find($id);

        $data = $request->except('_token', '_method');
        $tariff->update($data);
        return redirect()->back()->with('success', 'Tarif updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tariff = Tariff::find($id);
        $tariff->delete();
        return redirect()->back()->with('success', 'Tarif deleted successfully');
    }
}
