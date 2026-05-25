<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stock;
use App\Models\Detail;
use App\Models\Billing;
use App\Models\Capacity;
use App\Models\Product;
use App\Models\Storage;
use App\Services\Sync\SyncStockWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function __construct(private SyncStockWriter $writer) {}

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

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $data = $request->except('_token', 'qtys', 'product_id', 'containers');
        $data['ref'] = date('Y').'-'.str_pad($data['storage_id'], 4, '0', STR_PAD_LEFT).'-'.mt_rand(1000, 9999);
        $data['created_by'] = auth()->id();

        DB::beginTransaction();
            $stock  = Stock::create($data);
            $amount = 0;
            foreach ($request->qtys as $key => $qty) {
                $detail = Detail::create([
                    'stock_id'    => $stock->id,
                    'qty'         => $qty,
                    'product_id'  => $request->product_id[$key],
                    'container_id'=> $request->containers[$key],
                ]);
                $amount += getPrice($detail);

                // ── Sync path ─────────────────────────────────────────────
                // Mirrors the stock_in into inventory_ops + inventory_stock.
                // Non-fatal: a sync failure never aborts the primary write.
                $this->writer->record(
                    'stock_in',
                    (float) $qty,
                    $stock->storage_id,
                    (int) $request->product_id[$key],
                    $stock->id,
                );
            }
            Billing::create(['ref' => 'F-'.$data['ref'], 'stock_id' => $stock->id, 'customer_id' => $stock->customer_id, 'amount' => $amount]);
        DB::commit();

        return redirect()->back()->with('success', 'Stock created successfully');
    }

    public function show(string $id)
    {
        $stock = Stock::find($id);
        return view('admin.stock', compact('stock'));
    }

    public function edit(string $id)
    {
        $stock      = Stock::find($id);
        $storages   = Storage::all();
        $containers = Capacity::all();
        $products   = Product::all();
        $customers  = User::where('group_id', '>=', 4)->get();

        return view('admin.edit-stock', compact('customers', 'storages', 'stock', 'products', 'containers'));
    }

    public function update(Request $request, string $id)
    {
        $stock   = Stock::find($id);
        $data    = $request->except('_token', '_method', 'qtys', 'product_id', 'containers');
        $billing = Billing::firstwhere('stock_id', $stock->id);

        // ── Sync path: capture state BEFORE the old-path wipes details ────
        $oldStorageId = $stock->storage_id;
        $oldQtys      = $stock->details()->pluck('qty', 'product_id'); // product_id → qty

        DB::beginTransaction();
            $stock->update($data);
            $stock->details()->delete();

            $newStorageId = (int) ($data['storage_id'] ?? $oldStorageId);
            foreach ($request->qtys as $key => $qty) {
                Detail::create([
                    'stock_id'    => $stock->id,
                    'qty'         => $qty,
                    'product_id'  => $request->product_id[$key],
                    'container_id'=> $request->containers[$key],
                ]);

                // ── Sync path: emit a correction op for the net qty change ─
                // If the storage also changed, the delta still reflects the
                // net movement for the new storage (cross-storage transfer
                // accounting is left for a future dedicated transfer op type).
                $oldQty = (float) ($oldQtys[$request->product_id[$key]] ?? 0.0);
                $delta  = (float) $qty - $oldQty;
                if ($delta != 0.0) {
                    $this->writer->record(
                        'correction',
                        $delta,
                        $newStorageId,
                        (int) $request->product_id[$key],
                        $stock->id,
                    );
                }
            }
            $billing->update(['amount' => getBillingAmount($data['qty'], $data['expired_at'], $data['storage_id'])]);
        DB::commit();

        return redirect()->route('stocks.index')->with('success', 'Stock updated successfully');
    }

    public function destroy(string $id)
    {
        $stock = Stock::find($id);
        $stock->delete();
        return redirect()->back()->with('success', 'Stock deleted successfully');
    }
}
