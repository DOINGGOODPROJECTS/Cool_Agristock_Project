<?php

namespace App\Services\ThingsBoard;

use RuntimeException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Thin ThingsBoard CE REST API client.
 *
 * This is the ONLY class in the application that talks HTTP to ThingsBoard.
 * Everything else (controllers, views) goes through ThingsBoardService.
 */
class ThingsBoardApiClient
{
    private ?string $token = null;

    public function __construct(
        private readonly string $url,
        private readonly string $username,
        private readonly string $password,
        private readonly int $timeout,
        private readonly int $tokenTtl,
    ) {}

    public function getLatestTimeseries(string $deviceId, array $keys): array
    {
        return $this->get("/api/plugins/telemetry/DEVICE/{$deviceId}/values/timeseries", [
            'keys' => implode(',', $keys),
        ]);
    }

    public function getTimeseriesHistory(string $deviceId, array $keys, int $startTsMs, int $endTsMs, ?int $intervalMs = null, int $limit = 1000): array
    {
        $query = [
            'keys'    => implode(',', $keys),
            'startTs' => $startTsMs,
            'endTs'   => $endTsMs,
            'limit'   => $limit,
            'orderBy' => 'ASC',
        ];

        if ($intervalMs) {
            $query['interval'] = $intervalMs;
            $query['agg'] = 'AVG';
        }

        return $this->get("/api/plugins/telemetry/DEVICE/{$deviceId}/values/timeseries", $query);
    }

    /**
     * Device connectivity + metadata (includes "active" flag on TB 3.x+).
     */
    public function getDeviceInfo(string $deviceId): array
    {
        return $this->get("/api/device/info/{$deviceId}");
    }

    public function getAlarms(string $deviceId, bool $activeOnly = true, int $pageSize = 50): array
    {
        return $this->get("/api/alarm/DEVICE/{$deviceId}", [
            'pageSize'     => $pageSize,
            'page'         => 0,
            'sortProperty' => 'createdTime',
            'sortOrder'    => 'DESC',
            'searchStatus' => $activeOnly ? 'ACTIVE' : 'ANY',
        ]);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function authenticate(): string
    {
        if ($this->token !== null) {
            return $this->token;
        }

        $cacheKey = $this->tokenCacheKey();
        $cached = Cache::get($cacheKey);
        if ($cached) {
            $this->token = $cached;
            return $this->token;
        }

        if ($this->url === '' || $this->username === '') {
            throw new RuntimeException('ThingsBoard is not configured. Set THINGSBOARD_URL, THINGSBOARD_USERNAME and THINGSBOARD_PASSWORD.');
        }

        $response = Http::timeout($this->timeout)->post("{$this->url}/api/auth/login", [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        if ($response->failed() || !$response->json('token')) {
            throw new RuntimeException("ThingsBoard authentication failed [{$response->status()}]. Check THINGSBOARD_URL/USERNAME/PASSWORD.");
        }

        $this->token = $response->json('token');
        Cache::put($cacheKey, $this->token, $this->tokenTtl);

        return $this->token;
    }

    private function get(string $path, array $query = [], bool $retried = false): array
    {
        $token = $this->authenticate();

        $response = Http::timeout($this->timeout)
            ->withHeaders(['X-Authorization' => "Bearer {$token}"])
            ->get("{$this->url}{$path}", $query);

        if ($response->status() === 401 && !$retried) {
            $this->token = null;
            Cache::forget($this->tokenCacheKey());
            return $this->get($path, $query, true);
        }

        if ($response->failed()) {
            throw new RuntimeException("ThingsBoard API error [{$response->status()}] calling {$path}.");
        }

        return $response->json() ?? [];
    }

    private function tokenCacheKey(): string
    {
        return 'thingsboard.jwt.' . md5($this->username . '|' . $this->url);
    }
}
