<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

/**
 * Read-only product catalog for external consumers — currently the Cool
 * Agristock smart-monitoring dashboard (dashboard.agricarecentres.com),
 * which needs each product's name and recommended storage/drying
 * environmental range (temperature/RH) but has no reason to see anything
 * else in this system (stock levels, storages, users, etc).
 */
class ProductCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::with('environmentalProfile')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $profile = $product->environmentalProfile;

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'min_temperature' => $profile?->min_temperature,
                    'max_temperature' => $profile?->max_temperature,
                    'min_rh' => $profile?->min_rh,
                    'max_rh' => $profile?->max_rh,
                ];
            })
            ->values();

        return response()->json($products);
    }
}
