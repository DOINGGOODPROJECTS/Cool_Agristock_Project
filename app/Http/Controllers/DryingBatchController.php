<?php

namespace App\Http\Controllers;

use App\Models\DryingBatch;
use App\Models\EnvironmentalProfile;
use App\Models\Product;
use App\Models\Storage;
use App\Models\User;
use Illuminate\Http\Request;

class DryingBatchController extends Controller
{
    public function index()
    {
        app()->setLocale(auth()->user()->language);

        $batches = DryingBatch::with(['storage', 'product', 'customer', 'operator'])
            ->orderByDesc('start_time')
            ->get();

        $environments = Storage::whereNotNull('thingsboard_device_id')->orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $profiles = EnvironmentalProfile::all()->keyBy('product_id');
        $customers = User::whereNotIn('group_id', [1, 2])->orderBy('name')->get();

        return view('admin.sensors.batches', compact('batches', 'environments', 'products', 'profiles', 'customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'batch_code'  => 'required|string|max:255|unique:drying_batches,batch_code',
            'storage_id'  => 'required|integer|exists:storages,id',
            'product_id'  => 'required|integer|exists:products,id',
            'customer_id' => 'nullable|integer|exists:users,id',
            'start_time'  => 'required|date',
        ]);

        $data['environmental_profile_id'] = EnvironmentalProfile::where('product_id', $data['product_id'])->value('id');
        $data['operator_id'] = auth()->id();
        $data['status'] = 'in_progress';

        DryingBatch::create($data);

        return redirect()->back()->with('success', 'Batch created successfully');
    }

    public function update(Request $request, string $id)
    {
        $batch = DryingBatch::findOrFail($id);

        $data = $request->validate([
            'status'   => 'required|in:in_progress,completed,cancelled',
            'end_time' => 'nullable|date',
            'outcome'  => 'nullable|string|max:255',
            'notes'    => 'nullable|string',
        ]);

        if ($data['status'] !== 'in_progress' && empty($data['end_time'])) {
            $data['end_time'] = now();
        }

        $batch->update($data);

        return redirect()->back()->with('success', 'Batch updated successfully');
    }

    public function destroy(string $id)
    {
        DryingBatch::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Batch deleted successfully');
    }
}
