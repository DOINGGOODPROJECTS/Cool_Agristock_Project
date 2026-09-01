<?php

namespace App\Services\ThingsBoard;

use App\Models\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Business-layer wrapper around ThingsBoardApiClient.
 *
 * This is what controllers use — it never throws. Every public method
 * returns a standardized ['ok' => bool, ...data..., 'error' => ?string]
 * array so the UI can render a graceful degraded state (offline device,
 * ThingsBoard unavailable, stale telemetry, missing mapping, ...) instead
 * of crashing the whole dashboard when one environment has a problem.
 *
 * Also owns stale-telemetry detection: COOL AGRISTOCK decides "stale",
 * it does not just trust whatever ThingsBoard last reported as connected.
 */
class ThingsBoardService
{
    public const TELEMETRY_META = [
        'ambient_temp'      => ['label' => 'Ambient Temperature', 'unit' => '°C'],
        'ambient_rh'        => ['label' => 'Ambient RH', 'unit' => '%'],
        'chamber_temp'      => ['label' => 'Chamber Temperature', 'unit' => '°C'],
        'chamber_rh'        => ['label' => 'Chamber RH', 'unit' => '%'],
        'exhaust_temp'      => ['label' => 'Exhaust Temperature', 'unit' => '°C'],
        'exhaust_rh'        => ['label' => 'Exhaust RH', 'unit' => '%'],
        'airflow'           => ['label' => 'Exhaust Airflow', 'unit' => 'm/s'],
        'exhaust_fan_speed' => ['label' => 'Exhaust Fan Speed', 'unit' => '%'],
        'circulation_fan'   => ['label' => 'Circulation Fan', 'unit' => ''],
        'heater'            => ['label' => 'Auxiliary Heater', 'unit' => ''],
        'control_mode'      => ['label' => 'Control Mode', 'unit' => ''],
    ];

    private const RECOMMENDED_ACTIONS = [
        'HIGH_TEMPERATURE'    => 'Check exhaust airflow and heater control.',
        'HIGH_HUMIDITY'       => 'Check exhaust airflow and fan operation.',
        'LOW_AIRFLOW'         => 'Inspect the exhaust fan and ducting for obstructions.',
        'FAN_AIRFLOW_FAILURE' => 'Inspect fan wiring/power and the airflow sensor.',
        'DEVICE_OFFLINE'      => 'Check ESP32 power and network/MQTT connectivity.',
        'STALE_TELEMETRY'     => 'Device has not reported recently — verify connectivity.',
    ];

    public function __construct(
        private readonly ThingsBoardApiClient $client,
        private readonly bool $mockMode,
        private readonly int $defaultStaleThresholdMinutes,
        private readonly array $telemetryKeys,
    ) {}

    /**
     * Latest telemetry + connectivity + staleness for one environment.
     */
    public function latest(Storage $storage): array
    {
        if (!$storage->isSensorEnabled()) {
            return $this->unmappedResult();
        }

        $threshold = $storage->stale_threshold_minutes ?: $this->defaultStaleThresholdMinutes;

        try {
            $telemetry = $this->useMock($storage)
                ? $this->mockTelemetry($storage)
                : $this->fetchLiveTelemetry($storage->thingsboard_device_id);

            $lastUpdate = collect($telemetry)->max('ts');
            $lastUpdate = $lastUpdate ? Carbon::createFromTimestampMs($lastUpdate) : null;
            $stale = $lastUpdate === null || $lastUpdate->diffInMinutes(now()) > $threshold;

            return [
                'ok'           => true,
                'connectivity' => $stale ? 'OFFLINE' : 'ONLINE',
                'stale'        => $stale,
                'last_update'  => $lastUpdate,
                'telemetry'    => $telemetry,
                'error'        => null,
            ];
        } catch (Throwable $e) {
            Log::warning("[ThingsBoard] latest() failed for device {$storage->thingsboard_device_id}: {$e->getMessage()}");

            return [
                'ok'           => false,
                'connectivity' => 'OFFLINE',
                'stale'        => true,
                'last_update'  => null,
                'telemetry'    => [],
                'error'        => $this->useMock($storage)
                    ? $e->getMessage()
                    : 'ThingsBoard is temporarily unavailable.',
            ];
        }
    }

    /**
     * Historical time series for a set of keys, over [$start, $end].
     */
    public function history(Storage $storage, array $keys, Carbon $start, Carbon $end): array
    {
        if (!$storage->isSensorEnabled()) {
            return ['ok' => false, 'series' => [], 'error' => 'No ThingsBoard device is linked to this environment.'];
        }

        try {
            $series = $this->useMock($storage)
                ? $this->mockHistory($storage, $keys, $start, $end)
                : $this->fetchLiveHistory($storage->thingsboard_device_id, $keys, $start, $end);

            return ['ok' => true, 'series' => $series, 'error' => null];
        } catch (Throwable $e) {
            Log::warning("[ThingsBoard] history() failed for device {$storage->thingsboard_device_id}: {$e->getMessage()}");

            return [
                'ok'     => false,
                'series' => [],
                'error'  => $this->useMock($storage) ? $e->getMessage() : 'ThingsBoard is temporarily unavailable.',
            ];
        }
    }

    /**
     * Operator-friendly alarms: whatever ThingsBoard reports, plus
     * COOL AGRISTOCK's own connectivity/staleness alarms layered on top.
     */
    public function alarms(Storage $storage, bool $activeOnly = true): array
    {
        if (!$storage->isSensorEnabled()) {
            return ['ok' => false, 'alarms' => [], 'error' => 'No ThingsBoard device is linked to this environment.'];
        }

        $latest = $this->latest($storage);
        $alarms = [];

        try {
            if ($latest['ok']) {
                $alarms = $this->useMock($storage)
                    ? $this->mockAlarms($storage, $latest['telemetry'])
                    : $this->fetchLiveAlarms($storage->thingsboard_device_id, $activeOnly);
            }

            // COOL AGRISTOCK-owned connectivity alarms — always evaluated locally,
            // never trusted purely from ThingsBoard's last-known state.
            if (!$latest['ok'] || $latest['connectivity'] === 'OFFLINE') {
                $alarms[] = $this->buildAlarm(
                    $latest['last_update'] ? 'STALE_TELEMETRY' : 'DEVICE_OFFLINE',
                    $latest['last_update'] ? 'WARNING' : 'CRITICAL',
                    $latest['last_update']
                        ? 'Last reading ' . $latest['last_update']->diffForHumans()
                        : 'No telemetry has ever been received for this device.',
                    now()
                );
            }

            return ['ok' => true, 'alarms' => $alarms, 'error' => null];
        } catch (Throwable $e) {
            Log::warning("[ThingsBoard] alarms() failed for device {$storage->thingsboard_device_id}: {$e->getMessage()}");

            return [
                'ok'     => false,
                'alarms' => [],
                'error'  => $this->useMock($storage) ? $e->getMessage() : 'ThingsBoard is temporarily unavailable.',
            ];
        }
    }

    /**
     * Cheap combined status for overview cards (connectivity + severity),
     * without pulling the full telemetry payload the detail page needs.
     */
    public function status(Storage $storage): array
    {
        $latest = $this->latest($storage);
        $alarmResult = $this->alarms($storage);

        $severity = 'NORMAL';
        foreach ($alarmResult['alarms'] as $alarm) {
            if ($alarm['severity'] === 'CRITICAL') {
                $severity = 'CRITICAL';
                break;
            }
            if ($alarm['severity'] === 'WARNING') {
                $severity = 'WARNING';
            }
        }

        return [
            'connectivity'  => $latest['connectivity'],
            'stale'         => $latest['stale'],
            'last_update'   => $latest['last_update'],
            'telemetry'     => $latest['telemetry'],
            'severity'      => $severity,
            'active_alarms' => count($alarmResult['alarms']),
            'ok'            => $latest['ok'],
            'error'         => $latest['error'],
        ];
    }

    // -------------------------------------------------------------------------
    // Live ThingsBoard calls + response normalization
    // -------------------------------------------------------------------------

    private function fetchLiveTelemetry(string $deviceId): array
    {
        $raw = $this->client->getLatestTimeseries($deviceId, $this->telemetryKeys);

        $telemetry = [];
        foreach ($raw as $key => $points) {
            if (!empty($points[0])) {
                $telemetry[$key] = [
                    'value' => $points[0]['value'],
                    'ts'    => (int) $points[0]['ts'],
                ];
            }
        }

        return $telemetry;
    }

    private function fetchLiveHistory(string $deviceId, array $keys, Carbon $start, Carbon $end): array
    {
        $intervalMs = $this->intervalForRange($start, $end);

        $raw = $this->client->getTimeseriesHistory(
            $deviceId,
            $keys,
            $start->getTimestampMs(),
            $end->getTimestampMs(),
            $intervalMs
        );

        $series = [];
        foreach ($keys as $key) {
            $series[$key] = collect($raw[$key] ?? [])
                ->map(fn ($point) => ['ts' => (int) $point['ts'], 'value' => (float) $point['value']])
                ->values()
                ->all();
        }

        return $series;
    }

    private function fetchLiveAlarms(string $deviceId, bool $activeOnly): array
    {
        $raw = $this->client->getAlarms($deviceId, $activeOnly);

        return collect($raw['data'] ?? [])->map(function ($alarm) {
            return $this->buildAlarm(
                $alarm['type'] ?? 'UNKNOWN',
                $this->mapTbSeverity($alarm['severity'] ?? 'WARNING'),
                $alarm['details']['message'] ?? ($alarm['type'] ?? 'Alarm'),
                Carbon::createFromTimestampMs($alarm['createdTime'] ?? now()->getTimestampMs())
            );
        })->all();
    }

    private function mapTbSeverity(string $tbSeverity): string
    {
        return match (strtoupper($tbSeverity)) {
            'CRITICAL', 'MAJOR' => 'CRITICAL',
            default => 'WARNING',
        };
    }

    private function intervalForRange(Carbon $start, Carbon $end): int
    {
        $minutes = $end->diffInMinutes($start);
        return match (true) {
            $minutes <= 60 * 2  => 60_000,        // 1 min buckets for <= 2h
            $minutes <= 60 * 24 => 5 * 60_000,     // 5 min buckets for <= 24h
            default             => 30 * 60_000,    // 30 min buckets beyond that
        };
    }

    private function buildAlarm(string $type, string $severity, string $message, Carbon $timestamp): array
    {
        return [
            'type'                => $type,
            'severity'            => $severity,
            'message'             => $message,
            'recommended_action'  => self::RECOMMENDED_ACTIONS[$type] ?? 'Review the environment and equipment status.',
            'timestamp'           => $timestamp,
        ];
    }

    // -------------------------------------------------------------------------
    // Mock mode — deterministic simulated data used until a real ThingsBoard
    // CE instance is provisioned (THINGSBOARD_MOCK=true, the default).
    // -------------------------------------------------------------------------

    private function useMock(Storage $storage): bool
    {
        return $this->mockMode;
    }

    private function mockTelemetry(Storage $storage): array
    {
        $seed = crc32($storage->thingsboard_device_id);

        // ~1 in 7 devices simulate a stale/offline unit, deterministically, so
        // the "device offline" / "stale telemetry" UI states are demoable too.
        if ($seed % 7 === 0) {
            $ts = now()->subMinutes(45)->getTimestampMs();
            return [
                'chamber_temp' => ['value' => 46.5, 'ts' => $ts],
                'chamber_rh'   => ['value' => 40.0, 'ts' => $ts],
            ];
        }

        $minuteBucket = intdiv(now()->getTimestamp(), 60);
        $wave = sin(($minuteBucket + $seed) / 20);
        $ts = now()->getTimestampMs();

        $ambientTemp = round(29 + ($seed % 6) + $wave * 2, 1);
        $chamberTemp = round(48 + ($seed % 5) - $wave * 3, 1);
        $exhaustTemp = round($chamberTemp - 5 + $wave, 1);
        $chamberRh = round(38 - $wave * 4 + ($seed % 4), 1);
        $airflow = round(2.0 + $wave * 0.4, 2);
        $fanSpeed = round(70 + $wave * 10);

        return [
            'ambient_temp'      => ['value' => $ambientTemp, 'ts' => $ts],
            'ambient_rh'        => ['value' => round(70 + $wave * 5, 1), 'ts' => $ts],
            'chamber_temp'      => ['value' => $chamberTemp, 'ts' => $ts],
            'chamber_rh'        => ['value' => max(5, $chamberRh), 'ts' => $ts],
            'exhaust_temp'      => ['value' => $exhaustTemp, 'ts' => $ts],
            'exhaust_rh'        => ['value' => round($chamberRh + 8, 1), 'ts' => $ts],
            'airflow'           => ['value' => max(0, $airflow), 'ts' => $ts],
            'exhaust_fan_speed' => ['value' => max(0, min(100, $fanSpeed)), 'ts' => $ts],
            'circulation_fan'   => ['value' => true, 'ts' => $ts],
            'heater'            => ['value' => $wave < -0.5, 'ts' => $ts],
            'control_mode'      => ['value' => 'AUTO', 'ts' => $ts],
        ];
    }

    private function mockHistory(Storage $storage, array $keys, Carbon $start, Carbon $end): array
    {
        $seed = crc32($storage->thingsboard_device_id);
        $stepMinutes = max(1, intdiv($end->diffInMinutes($start), 60));
        $series = [];

        foreach ($keys as $key) {
            $points = [];
            for ($t = $start->copy(); $t->lte($end); $t->addMinutes($stepMinutes)) {
                $minuteBucket = intdiv($t->getTimestamp(), 60);
                $wave = sin(($minuteBucket + $seed) / 20);
                $value = match ($key) {
                    'chamber_temp'      => round(48 + ($seed % 5) - $wave * 3, 1),
                    'chamber_rh'        => round(max(5, 38 - $wave * 4 + ($seed % 4)), 1),
                    'airflow'           => round(max(0, 2.0 + $wave * 0.4), 2),
                    'exhaust_fan_speed' => max(0, min(100, round(70 + $wave * 10))),
                    default             => round(30 + $wave * 5, 1),
                };
                $points[] = ['ts' => $t->getTimestampMs(), 'value' => $value];
            }
            $series[$key] = $points;
        }

        return $series;
    }

    private function mockAlarms(Storage $storage, array $telemetry): array
    {
        $profile = $storage->activeBatch?->environmentalProfile
            ?? $storage->dryingBatches()->latest('start_time')->first()?->environmentalProfile;

        if (!$profile) {
            return [];
        }

        $alarms = [];
        $chamberTemp = $telemetry['chamber_temp']['value'] ?? null;
        $chamberRh = $telemetry['chamber_rh']['value'] ?? null;
        $airflow = $telemetry['airflow']['value'] ?? null;
        $circulationFan = $telemetry['circulation_fan']['value'] ?? null;

        if ($chamberTemp !== null && $profile->max_temperature !== null && $chamberTemp > $profile->max_temperature) {
            $over = $chamberTemp - $profile->max_temperature;
            $alarms[] = $this->buildAlarm(
                'HIGH_TEMPERATURE',
                $over > 5 ? 'CRITICAL' : 'WARNING',
                "Chamber temperature {$chamberTemp}°C exceeds target max {$profile->max_temperature}°C.",
                now()
            );
        }

        if ($chamberRh !== null && $profile->max_rh !== null && $chamberRh > $profile->max_rh) {
            $over = $chamberRh - $profile->max_rh;
            $alarms[] = $this->buildAlarm(
                'HIGH_HUMIDITY',
                $over > 10 ? 'CRITICAL' : 'WARNING',
                "Chamber RH {$chamberRh}% exceeds target max {$profile->max_rh}%.",
                now()
            );
        }

        if ($airflow !== null && $profile->min_airflow !== null && $airflow < $profile->min_airflow) {
            $alarms[] = $this->buildAlarm(
                'LOW_AIRFLOW',
                'WARNING',
                "Airflow {$airflow} m/s is below target minimum {$profile->min_airflow} m/s.",
                now()
            );
        }

        if ($circulationFan === true && $airflow !== null && $airflow < 0.3) {
            $alarms[] = $this->buildAlarm(
                'FAN_AIRFLOW_FAILURE',
                'CRITICAL',
                'Circulation fan is ON but almost no airflow is measured.',
                now()
            );
        }

        return $alarms;
    }

    private function unmappedResult(): array
    {
        return [
            'ok'           => false,
            'connectivity' => 'OFFLINE',
            'stale'        => true,
            'last_update'  => null,
            'telemetry'    => [],
            'error'        => 'This environment is not linked to a ThingsBoard device yet.',
        ];
    }
}
