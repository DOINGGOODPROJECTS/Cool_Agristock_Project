<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Mail\ClaimMail;
use App\Models\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ClaimController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $claims = auth()->user()->group_id == 1 ? Claim::orderByDesc('id')->get() : Claim::where('customer_id', auth()->id())->orderByDesc('id')->get();
        $storages = Storage::all();
        return view('admin.claims', compact('claims', 'storages'));
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

        $data['customer_id'] = auth()->id();
        $claim = Claim::create($data);
        Mail::to(auth()->user()->email)->send(new ClaimMail($claim));

        return redirect()->back()->with(['title'=>__('locale.claim', ['suffix'=>'s']), 'message'=>'Data Saved successfully']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $claim = Claim::find($id);
        $claim->update(['status'=>'TRAITEE']);
        return redirect()->back()->with(['title'=>__('locale.claim', ['suffix'=>'s']), 'message'=>'Data Saved successfully']);
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
        $claim = Claim::find($id);
        $data = $request->except(['_token', '_method']);
        $claim->update($data);

        return redirect()->back()->with(['title'=>__('locale.claim', ['suffix'=>'s']), 'message'=>'Data Updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $claim = Claim::find($id);
        $claim->delete();
        return redirect()->back()->with(['title'=>__('locale.claim', ['suffix'=>'s']), 'message'=>'Data Deleted successfully']);
    }
}
