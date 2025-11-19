<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $incidents = Incident::all();
        return view('admin.incidents', compact('incidents'));
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
        Incident::create($data);        
        return redirect()->back()->with(['title'=>__('locale.category', ['suffix'=>app()->getLocale() == 'en' ? 'y' : '']), 'message'=>'User created successfully']);
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
        $incident = Incident::find($id);

        $data = $request->except('_token', '_method');
        $incident->update($data);
        return redirect()->back()->with('success', 'Incident updated successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function setStatus(string $status, string $id)
    {
        $incident = Incident::find($id);

        $incident->update(compact('status'));
        return redirect()->back()->with('success', 'Incident updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $incident = Incident::find($id);
        $incident->delete();
        return redirect()->back()->with('success', 'Category deleted successfully');
    }
}
