<?php

namespace App\Http\Controllers;

use App\Models\Release;
use App\Services\Sync\SyncStockWriter;
use Illuminate\Http\Request;

class ReleaseController extends Controller
{
    public function __construct(private SyncStockWriter $writer) {}

    public function index()
    {
        app()->setLocale(auth()->user()->locale);
        $releases = Release::with('stock');
        if(auth()->user()->group_id > 4) {
            $releases = Release::whereHas('stock', function ($query) {
                $query->whereHas('customer', function ($q) {
                    $q->where('id', auth()->id());
                });
            });
        }
        $releases = $releases->get();
        return view('admin.releases', compact('releases'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $data    = $request->except('_token');
        $release = Release::create($data);

        // ── Old path (primary) ────────────────────────────────────────────
        $release->detail->update(['qty' => $release->detail->qty - $release->qty]);
        $release->detail->stock->update(['qty' => $release->detail->stock->qty - $release->qty]);

        // ── Sync path (parallel shadow write) ────────────────────────────
        // The negative delta mirrors the stock_out into inventory_stock.
        // Release record is already created above; applyOpToStock() is NOT
        // called here to avoid creating a second Release record.
        $detail = $release->detail;
        $stock  = $detail->stock;
        $this->writer->record(
            'stock_out',
            -(float) $release->qty,
            $stock->storage_id,
            $detail->product_id,
            $stock->id,
            'kg',
            "Release: release #{$release->id}",
        );

        return redirect()->back()->with('success', 'Release created successfully');
    }

    public function show(string $id) { /* */ }
    public function edit(string $id) { /* */ }
    public function update(Request $request, string $id) { /* */ }
    public function destroy(string $id) { /* */ }
}
