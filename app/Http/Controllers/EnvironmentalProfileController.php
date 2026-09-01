<?php

namespace App\Http\Controllers;

use App\Models\EnvironmentalProfile;
use App\Models\Product;
use App\Services\FacilityDashboard\FacilityDashboardClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EnvironmentalProfileController extends Controller
{
    public function __construct(private readonly FacilityDashboardClient $facilityDashboard)
    {
    }

    public function index()
    {
        app()->setLocale(auth()->user()->language);

        $profiles = EnvironmentalProfile::with('product')->get();
        $products = Product::orderBy('name')->get();
        $inUseAt = $this->fetchInUseAt($products);

        $facilities = [];
        try {
            foreach ($this->facilityDashboard->getFacilities() as $facility) {
                $facilities[] = ['id' => $facility['id'], 'name' => $facility['name']];
            }
        } catch (\Throwable $e) {
            Log::warning('[FacilityDashboard] Failed to fetch facility list: ' . $e->getMessage());
        }

        return view('admin.sensors.profiles', compact('profiles', 'products', 'inUseAt', 'facilities'))
            ->with('facilityDashboardUrl', config('facility_dashboard.url'));
    }

    /**
     * Assigns a product to a facility on the facility-monitoring dashboard
     * (adds a new batch there — mirrors using that app's own "add product"
     * form). Existing placements of the product in other facilities are
     * left untouched.
     */
    public function assignFacility(Request $request, string $id)
    {
        $profile = EnvironmentalProfile::with('product')->findOrFail($id);

        $data = $request->validate([
            'facility_id' => 'required|string',
        ]);

        if ($this->assignProductToFacility($profile->product, $data['facility_id'])) {
            return redirect()->back()->with('success', 'Product assigned to facility successfully');
        }

        return redirect()->back()->with('error', 'Could not reach the facility dashboard — please try again.');
    }

    /**
     * Shared by store() and assignFacility(). Resolves the product's id on
     * the facility dashboard (it mirrors this app's own catalog by name)
     * and places it in the given facility. Never throws — failures here
     * shouldn't block saving the profile itself.
     */
    private function assignProductToFacility(Product $product, string $facilityId): bool
    {
        try {
            $remoteProducts = collect($this->facilityDashboard->getProducts());
            $productId = $remoteProducts->firstWhere('name', $product->name)['id'] ?? null;

            if (!$productId) {
                return false;
            }

            $this->facilityDashboard->addProductToFacility($facilityId, $productId);

            return true;
        } catch (\Throwable $e) {
            Log::warning('[FacilityDashboard] Failed to assign facility: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Which real facilities (dryers/cold rooms/storage areas) on the
     * Cool Agristock facility-monitoring dashboard currently have a batch
     * of each product placed in them. That app is the source of truth for
     * physical facilities — this only reads its data, never stores a copy.
     * Matches products by name (the facility dashboard's own product
     * catalog is pulled live from this app's /api/catalog/products, so the
     * names are identical). Fails soft: any problem reaching that app just
     * means an empty "in use at" column, not a broken page.
     *
     * @return array<int, \Illuminate\Support\Collection>
     */
    private function fetchInUseAt($products): array
    {
        try {
            $slugToName = collect($this->facilityDashboard->getProducts())->pluck('name', 'id');

            $facilitiesByProductName = [];
            foreach ($this->facilityDashboard->getFacilities() as $facility) {
                foreach ($facility['products'] ?? [] as $batch) {
                    $name = $slugToName[$batch['productId']] ?? null;
                    if (!$name) {
                        continue;
                    }
                    $facilitiesByProductName[$name][$facility['id']] = [
                        'id'   => $facility['id'],
                        'name' => $facility['name'],
                    ];
                }
            }

            $inUseAt = [];
            foreach ($products as $product) {
                if (isset($facilitiesByProductName[$product->name])) {
                    $inUseAt[$product->id] = collect(array_values($facilitiesByProductName[$product->name]));
                }
            }

            return $inUseAt;
        } catch (\Throwable $e) {
            Log::warning('[FacilityDashboard] Failed to fetch in-use-at data: ' . $e->getMessage());
            return [];
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'      => 'required|integer|exists:products,id',
            'min_temperature' => 'nullable|numeric',
            'max_temperature' => 'nullable|numeric',
            'min_rh'          => 'nullable|numeric|min:0|max:100',
            'max_rh'          => 'nullable|numeric|min:0|max:100',
            'min_airflow'     => 'nullable|numeric|min:0',
            'facility_id'     => 'nullable|string',
        ]);

        $facilityId = $data['facility_id'] ?? null;
        unset($data['facility_id']);

        $profile = EnvironmentalProfile::updateOrCreate(['product_id' => $data['product_id']], $data);

        if ($facilityId && !$this->assignProductToFacility($profile->product, $facilityId)) {
            return redirect()->back()->with('success', 'Environmental profile saved successfully')
                ->with('error', 'Profile saved, but could not reach the facility dashboard to assign the facility.');
        }

        return redirect()->back()->with('success', 'Environmental profile saved successfully');
    }

    public function update(Request $request, string $id)
    {
        $profile = EnvironmentalProfile::findOrFail($id);

        $data = $request->validate([
            'min_temperature' => 'nullable|numeric',
            'max_temperature' => 'nullable|numeric',
            'min_rh'          => 'nullable|numeric|min:0|max:100',
            'max_rh'          => 'nullable|numeric|min:0|max:100',
            'min_airflow'     => 'nullable|numeric|min:0',
        ]);

        $profile->update($data);

        return redirect()->back()->with('success', 'Environmental profile updated successfully');
    }

    public function destroy(string $id)
    {
        EnvironmentalProfile::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Environmental profile deleted successfully');
    }
}
