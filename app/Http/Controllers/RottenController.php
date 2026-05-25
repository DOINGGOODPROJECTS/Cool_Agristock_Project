<?php

namespace App\Http\Controllers;

use App\Models\Rotten;
use App\Services\Sync\SyncStockWriter;
use Illuminate\Http\Request;

class RottenController extends Controller
{
    public function __construct(private SyncStockWriter $writer) {}

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

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $data   = $request->except('_token');
        $rotten = Rotten::create($data);

        // ── Old path (primary) ────────────────────────────────────────────
        $rotten->detail->update(['qty' => $rotten->detail->qty - $rotten->qty]);
        $rotten->detail->stock->update(['qty' => $rotten->detail->stock->qty - $rotten->qty]);

        // ── Sync path (parallel shadow write) ────────────────────────────
        // The negative delta mirrors the spoilage into inventory_stock.
        // Rotten record is already created above; applyOpToStock() is NOT
        // called here to avoid creating a second Rotten record.
        $detail = $rotten->detail;
        $stock  = $detail->stock;
        $this->writer->record(
            'spoilage',
            -(float) $rotten->qty,
            $stock->storage_id,
            $detail->product_id,
            $stock->id,
            'kg',
            "Spoilage: rotten #{$rotten->id}",
        );

        return redirect()->back()->with('success', 'Rotten created successfully');
    }

    public function show(string $id) { /* */ }
    public function edit(string $id) { /* */ }
    public function update(Request $request, string $id) { /* */ }
    public function destroy(string $id) { /* */ }
}
