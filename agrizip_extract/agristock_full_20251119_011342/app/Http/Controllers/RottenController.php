<?php

namespace App\Http\Controllers;

use App\Models\Rotten;
use Illuminate\Http\Request;

class RottenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        app()->setLocale(auth()->user()->locale);
        $rottens = Rotten::with('stock');
        if(auth()->user()->group_id > 4) {
            $rottens = Rotten::whereHas('stock', function ($query) {
                $query->whereHas('customer', function ($q) {
                    $q->where('id', auth()->id());
                });
            });
        }
        $rottens = $rottens->get();
        return view('admin.rottens', compact('rottens'));
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
        $rotten = Rotten::create($data);
        $rotten->detail->update(['qty' => $rotten->detail->qty - $rotten->qty]);
        $rotten->detail->stock->update(['qty' => $rotten->detail->stock->qty - $rotten->qty]);
        return redirect()->back()->with('success', 'Rotten created successfully');
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
