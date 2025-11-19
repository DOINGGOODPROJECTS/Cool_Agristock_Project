<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stock;
use App\Models\Detail;
use App\Models\Billing;
use App\Models\Capacity;
use App\Models\Product;
use App\Models\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $storages = Storage::all()->filter(fn($storage) => $storage->available() > 0);

        $products = Product::all();
        $containers = Capacity::all();
        $stocks = Stock::with('customer', 'storage');
        if(auth()->user()->group_id > 4) {
            $stocks = $stocks->where('customer_id', auth()->id());
        }

        if(auth()->user()->group_id > 2) {
            $stocks = $stocks->where('created_by', auth()->id());
        }
        
        $stocks = $stocks->orderByDesc('id')->get();
        $customers = User::where('group_id', '>=', 4)->get();
        return view('admin.stocks', compact('customers', 'storages', 'stocks', 'products', 'containers'));
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
        $data = $request->except('_token', 'qtys', 'product_id', 'containers');
        $data['ref'] = date('Y').'-'.str_pad($data['storage_id'], 4, '0', STR_PAD_LEFT).'-'.mt_rand(1000, 9999);
        $data['created_by'] = auth()->id();
        DB::beginTransaction();
            $stock = Stock::create($data);
            $amount = 0;
            foreach ($request->qtys as $key => $qty) {
                $detail = Detail::create(['stock_id' => $stock->id, 'qty' => $qty, 'product_id' => $request->product_id[$key], 'container_id' => $request->containers[$key]]);
                $amount += getPrice($detail);
            }
            Billing::create(['ref' => 'F-'.$data['ref'], 'stock_id' => $stock->id, 'customer_id' => $stock->customer_id, 'amount' => $amount]);
        DB::commit();
        
        return redirect()->back()->with('success', 'Stock created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $stock = Stock::find($id);
        return view('admin.stock', compact('stock'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $stock = Stock::find($id);
        $storages = Storage::all();
        $containers = Capacity::all();
        $products = Product::all();
        $customers = User::where('group_id', '>=', 4)->get();

        return view('admin.edit-stock', compact('customers', 'storages', 'stock', 'products', 'containers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $stock = Stock::find($id);

        $data = $request->except('_token', '_method', 'qtys', 'product_id', 'containers');
        $billing = Billing::firstwhere('stock_id', $stock->id);

        DB::beginTransaction();
            $stock->update($data);
            $stock->details()->delete();        

            foreach ($request->qtys as $key => $qty) {
                Detail::create([
                    'stock_id' => $stock->id, 
                    'qty' => $qty, 
                    'product_id' => $request->product_id[$key],
                    'container_id' => $request->containers[$key]
                ]);
            }
            $billing->update(['amount' => getBillingAmount($data['qty'], $data['expired_at'], $data['storage_id'])]);
        DB::commit();

        return redirect()->route('stocks.index')->with('success', 'Stock updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $stock = Stock::find($id);
        $stock->delete();
        return redirect()->back()->with('success', 'Stock deleted successfully');
    }
}
