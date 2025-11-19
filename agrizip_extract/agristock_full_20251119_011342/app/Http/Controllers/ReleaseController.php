<?php

namespace App\Http\Controllers;

use App\Models\Release;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        app()->setLocale(auth()->user()->locale);
        $releases = Release::with('stock');
        if(auth()->user()->group_id > 4) {
            $rottens = Release::whereHas('stock', function ($query) {
                $query->whereHas('customer', function ($q) {
                    $q->where('id', auth()->id());
                });
            });
        }
        $releases = $releases->get();
        return view('admin.releases', compact('releases'));
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
        $release = Release::create($data);
        $release->detail->update(['qty' => $release->detail->qty - $release->qty]);
        $release->detail->stock->update(['qty' => $release->detail->stock->qty - $release->qty]);
        return redirect()->back()->with('success', 'Release created successfully');
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
