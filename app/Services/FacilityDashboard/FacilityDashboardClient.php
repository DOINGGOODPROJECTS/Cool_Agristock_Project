<?php

namespace App\Services\FacilityDashboard;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin client for the Cool Agristock facility-monitoring dashboard's
 * internal API (dashboard.agricarecentres.com/api/*, proxied by Next.js to
 * its Express backend). Session-cookie auth, not a bearer token — that app
 * doesn't support tokens, only browser-style login.
 */
class FacilityDashboardClient
{
    private ?string $sessionCookie = null;

    public function __construct(
        private readonly string $url,
        private readonly string $email,
        private readonly string $password,
        private readonly int $timeout,
    ) {}

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function getProducts(): array
    {
        return $this->get('/api/products');
    }

    /**
     * @return array<int, array{id: string, name: string, products: array}>
     */
    public function getFacilities(): array
    {
        return $this->get('/api/facilities');
    }

    /**
     * Places a batch of a product into a facility (the same action as the
     * "add product" form on the facility dashboard itself). Adds a new
     * batch entry — it does not remove any existing placement of this
     * product in other facilities.
     */
    public function addProductToFacility(string $facilityId, string $productId, string $batch = 'New batch', string $qty = '—'): array
    {
        return $this->post("/api/facilities/{$facilityId}/products", [
            'productId' => $productId,
            'batch'     => $batch,
            'qty'       => $qty,
        ]);
    }

    // -------------------------------------------------------------------------

    private function authenticate(): string
    {
        if ($this->sessionCookie !== null) {
            return $this->sessionCookie;
        }

        $cacheKey = $this->cacheKey();
        $cached = Cache::get($cacheKey);
        if ($cached) {
            $this->sessionCookie = $cached;
            return $this->sessionCookie;
        }

        if ($this->url === '' || $this->email === '') {
            throw new RuntimeException('Facility dashboard is not configured. Set FACILITY_DASHBOARD_URL/EMAIL/PASSWORD.');
        }

        $response = Http::timeout($this->timeout)->post("{$this->url}/api/auth/login", [
            'email'    => $this->email,
            'password' => $this->password,
        ]);

        if ($response->failed()) {
            throw new RuntimeException("Facility dashboard authentication failed [{$response->status()}].");
        }

        $setCookie = $response->header('Set-Cookie');
        if (!$setCookie || !preg_match('/session=([^;]+)/', $setCookie, $matches)) {
            throw new RuntimeException('Facility dashboard did not return a session cookie.');
        }

        $this->sessionCookie = $matches[1];
        Cache::put($cacheKey, $this->sessionCookie, now()->addDays(6));

        return $this->sessionCookie;
    }

    private function get(string $path, bool $retried = false): array
    {
        return $this->request('get', $path, null, $retried);
    }

    private function post(string $path, array $body, bool $retried = false): array
    {
        return $this->request('post', $path, $body, $retried);
    }

    private function request(string $method, string $path, ?array $body, bool $retried): array
    {
        $session = $this->authenticate();

        $request = Http::timeout($this->timeout)->withHeaders(['Cookie' => "session={$session}"]);
        $response = $body !== null ? $request->post("{$this->url}{$path}", $body) : $request->get("{$this->url}{$path}");

        if ($response->status() === 401 && !$retried) {
            $this->sessionCookie = null;
            Cache::forget($this->cacheKey());
            return $this->request($method, $path, $body, true);
        }

        if ($response->failed()) {
            throw new RuntimeException("Facility dashboard API error [{$response->status()}] calling {$path}.");
        }

        return $response->json() ?? [];
    }

    private function cacheKey(): string
    {
        return 'facility_dashboard.session.' . md5($this->email . '|' . $this->url);
    }
}
