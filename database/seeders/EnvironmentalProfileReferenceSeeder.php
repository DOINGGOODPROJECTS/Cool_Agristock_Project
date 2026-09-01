<?php

namespace Database\Seeders;

use App\Models\EnvironmentalProfile;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Populates recommended drying/storage temperature and RH ranges for
 * products that already exist in the `products` table, matched by name
 * (case-insensitive). Products with no name match here are left
 * untouched — this only fills in a profile, it never creates a product.
 *
 * Safe to re-run: existing profiles for a matched product are updated in
 * place (updateOrCreate), never duplicated.
 *
 * Reference values mirror the product catalog already curated in the Cool
 * Agristock smart-monitoring dashboard (dashboard.agricarecentres.com).
 */
class EnvironmentalProfileReferenceSeeder extends Seeder
{
    private const REFERENCE = [
        // English reference names (kept in case a product is ever named in English)
        'Chilli' => ['min_temperature' => 30, 'max_temperature' => 45, 'min_rh' => 10, 'max_rh' => 20],
        'Okra' => ['min_temperature' => 35, 'max_temperature' => 45, 'min_rh' => 10, 'max_rh' => 20],
        'Ginger' => ['min_temperature' => 35, 'max_temperature' => 50, 'min_rh' => 8, 'max_rh' => 15],
        'Tomato' => ['min_temperature' => 12, 'max_temperature' => 20, 'min_rh' => 85, 'max_rh' => 90],
        'Mango' => ['min_temperature' => 2, 'max_temperature' => 8, 'min_rh' => 85, 'max_rh' => 95],
        'Pineapple' => ['min_temperature' => 7, 'max_temperature' => 10, 'min_rh' => 85, 'max_rh' => 90],
        'Avocado' => ['min_temperature' => 5, 'max_temperature' => 13, 'min_rh' => 85, 'max_rh' => 95],
        'Green pepper' => ['min_temperature' => 7, 'max_temperature' => 10, 'min_rh' => 90, 'max_rh' => 95],
        'Maize' => ['min_temperature' => 15, 'max_temperature' => 30, 'min_rh' => 0, 'max_rh' => 14],
        'Cassava' => ['min_temperature' => 30, 'max_temperature' => 40, 'min_rh' => 10, 'max_rh' => 20],
        'Groundnut' => ['min_temperature' => 15, 'max_temperature' => 28, 'min_rh' => 0, 'max_rh' => 12],
        'Cowpea' => ['min_temperature' => 15, 'max_temperature' => 28, 'min_rh' => 0, 'max_rh' => 13],
        'Sorghum' => ['min_temperature' => 15, 'max_temperature' => 30, 'min_rh' => 0, 'max_rh' => 13],

        // French names — matches the actual COOL AGRISTOCK product catalog
        'Piment' => ['min_temperature' => 30, 'max_temperature' => 45, 'min_rh' => 10, 'max_rh' => 20],
        'Gombo' => ['min_temperature' => 35, 'max_temperature' => 45, 'min_rh' => 10, 'max_rh' => 20],
        'Tomate' => ['min_temperature' => 12, 'max_temperature' => 20, 'min_rh' => 85, 'max_rh' => 90],
        'Mangue' => ['min_temperature' => 2, 'max_temperature' => 8, 'min_rh' => 85, 'max_rh' => 95],
        'Ananas' => ['min_temperature' => 7, 'max_temperature' => 10, 'min_rh' => 85, 'max_rh' => 90],
        'Poivron' => ['min_temperature' => 7, 'max_temperature' => 10, 'min_rh' => 90, 'max_rh' => 95],
        'Maïs (Grain Sec)' => ['min_temperature' => 15, 'max_temperature' => 30, 'min_rh' => 0, 'max_rh' => 14],
        'Manioc Frais' => ['min_temperature' => 30, 'max_temperature' => 40, 'min_rh' => 10, 'max_rh' => 20],
        'Arachide (Non Transformée)' => ['min_temperature' => 15, 'max_temperature' => 28, 'min_rh' => 0, 'max_rh' => 12],
        'Arachide (décortiquée)' => ['min_temperature' => 15, 'max_temperature' => 28, 'min_rh' => 0, 'max_rh' => 12],
        'Niébé (haricot à œil noir)' => ['min_temperature' => 15, 'max_temperature' => 28, 'min_rh' => 0, 'max_rh' => 13],
        'Sorgho' => ['min_temperature' => 15, 'max_temperature' => 30, 'min_rh' => 0, 'max_rh' => 13],

        // ── Placeholder/mock reference values for the rest of the catalog ──
        // General category defaults (fresh chilled produce, dry grains,
        // curing tubers, oils, fibers/latex) — not lab-verified postharvest
        // specs, just reasonable starting points so every product has a
        // profile to work from. Admins should refine these per product.
        'Banane' => ['min_temperature' => 13, 'max_temperature' => 15, 'min_rh' => 90, 'max_rh' => 95],
        'Orange' => ['min_temperature' => 3, 'max_temperature' => 8, 'min_rh' => 85, 'max_rh' => 90],
        'Aubergine' => ['min_temperature' => 8, 'max_temperature' => 12, 'min_rh' => 90, 'max_rh' => 95],
        'Epinards (Amarante)' => ['min_temperature' => 0, 'max_temperature' => 4, 'min_rh' => 95, 'max_rh' => 98],
        'Feuilles de Manioc' => ['min_temperature' => 0, 'max_temperature' => 4, 'min_rh' => 90, 'max_rh' => 95],
        'Feuilles de Patate Douce' => ['min_temperature' => 0, 'max_temperature' => 4, 'min_rh' => 90, 'max_rh' => 95],
        'Oignon' => ['min_temperature' => 0, 'max_temperature' => 4, 'min_rh' => 65, 'max_rh' => 70],
        'Huile de Palme' => ['min_temperature' => 20, 'max_temperature' => 25, 'min_rh' => 40, 'max_rh' => 60],
        'Huile de Palmiste' => ['min_temperature' => 20, 'max_temperature' => 25, 'min_rh' => 40, 'max_rh' => 60],
        'Noix de Cajou' => ['min_temperature' => 10, 'max_temperature' => 20, 'min_rh' => 0, 'max_rh' => 10],
        'Graine de Coton' => ['min_temperature' => 15, 'max_temperature' => 25, 'min_rh' => 0, 'max_rh' => 14],
        'Igname' => ['min_temperature' => 13, 'max_temperature' => 16, 'min_rh' => 70, 'max_rh' => 80],
        'Attieké Transformé' => ['min_temperature' => 20, 'max_temperature' => 30, 'min_rh' => 10, 'max_rh' => 20],
        'Gari' => ['min_temperature' => 20, 'max_temperature' => 30, 'min_rh' => 10, 'max_rh' => 20],
        'Placali' => ['min_temperature' => 0, 'max_temperature' => 4, 'min_rh' => 85, 'max_rh' => 90],
        'Taro' => ['min_temperature' => 7, 'max_temperature' => 10, 'min_rh' => 85, 'max_rh' => 90],
        'Patate Douce' => ['min_temperature' => 13, 'max_temperature' => 15, 'min_rh' => 85, 'max_rh' => 90],
        'Pomme de terre' => ['min_temperature' => 4, 'max_temperature' => 10, 'min_rh' => 90, 'max_rh' => 95],
        'Riz (Non Cuit)' => ['min_temperature' => 15, 'max_temperature' => 30, 'min_rh' => 0, 'max_rh' => 14],
        'Mil' => ['min_temperature' => 15, 'max_temperature' => 30, 'min_rh' => 0, 'max_rh' => 14],
        'Cacao (fèves sèches)' => ['min_temperature' => 18, 'max_temperature' => 25, 'min_rh' => 60, 'max_rh' => 70],
        'Café (grains)' => ['min_temperature' => 15, 'max_temperature' => 25, 'min_rh' => 50, 'max_rh' => 60],
        'Hévéa (latex)' => ['min_temperature' => 15, 'max_temperature' => 25, 'min_rh' => 40, 'max_rh' => 60],
        'Coton (fibres)' => ['min_temperature' => 20, 'max_temperature' => 25, 'min_rh' => 45, 'max_rh' => 55],
        'Canne à sucre (fraîche)' => ['min_temperature' => 5, 'max_temperature' => 10, 'min_rh' => 85, 'max_rh' => 90],
        'Papaye' => ['min_temperature' => 7, 'max_temperature' => 13, 'min_rh' => 85, 'max_rh' => 90],
        'Goyave' => ['min_temperature' => 5, 'max_temperature' => 10, 'min_rh' => 85, 'max_rh' => 90],
        'Fruit du dragon (Pitaya)' => ['min_temperature' => 6, 'max_temperature' => 8, 'min_rh' => 85, 'max_rh' => 90],
        'Jacquier' => ['min_temperature' => 11, 'max_temperature' => 13, 'min_rh' => 85, 'max_rh' => 90],
        'Durian' => ['min_temperature' => 4, 'max_temperature' => 6, 'min_rh' => 85, 'max_rh' => 90],
        'Noix de coco' => ['min_temperature' => 20, 'max_temperature' => 25, 'min_rh' => 70, 'max_rh' => 80],
        'Tamarin' => ['min_temperature' => 20, 'max_temperature' => 30, 'min_rh' => 10, 'max_rh' => 20],
        'Cherimoya (Pomme cannelle)' => ['min_temperature' => 8, 'max_temperature' => 13, 'min_rh' => 85, 'max_rh' => 90],
        'Longan' => ['min_temperature' => 1, 'max_temperature' => 5, 'min_rh' => 90, 'max_rh' => 95],
        'Fruit à pain' => ['min_temperature' => 12, 'max_temperature' => 16, 'min_rh' => 85, 'max_rh' => 90],
    ];

    public function run(): void
    {
        foreach (self::REFERENCE as $name => $range) {
            $product = Product::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();

            if (! $product) {
                continue;
            }

            EnvironmentalProfile::updateOrCreate(['product_id' => $product->id], $range);
        }
    }
}
