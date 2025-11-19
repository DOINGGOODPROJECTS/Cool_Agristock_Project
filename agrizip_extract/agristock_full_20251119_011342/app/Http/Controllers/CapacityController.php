<?php

namespace App\Http\Controllers;

use App\Models\Capacity;
use Illuminate\Http\Request;

class CapacityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $capacities = Capacity::orderBy('name')->get();
        return view('admin.capacities', compact('capacities'));
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
        Capacity::create($data);        
        return redirect()->back()->with(['title'=>__('locale.container', ['suffix'=>'']), 'message'=>'Container created successfully']);
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
        $container = Capacity::find($id);

        $data = $request->except('_token', '_method');
        $container->update($data);
        return redirect()->back()->with('success', 'Container updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $container = Capacity::find($id);
        $container->delete();
        return redirect()->back()->with('success', 'Container deleted successfully');
    }
}
