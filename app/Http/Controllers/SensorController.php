<?php

namespace App\Http\Controllers;

use App\Models\DryingBatch;
use App\Models\Storage;
use App\Services\ThingsBoard\ThingsBoardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Smart Sensor Management module — read-only V1 (see module spec, section 14).
 *
 * The frontend NEVER talks to ThingsBoard directly. Every method here goes
 * through ThingsBoardService, which owns all ThingsBoard REST calls and
 * always returns a safe, standardized result even when ThingsBoard/the
 * device is unavailable.
 */
class SensorController extends Controller
{
    public function __construct(private readonly ThingsBoardService $thingsBoard) {}

    /**
     * Overview — all environments the current user is authorized to see.
     */
    public function index()
    {
        app()->setLocale(auth()->user()->language);

        $environments = Storage::visibleTo(auth()->user())
            ->with(['city', 'activeBatch.product', 'activeBatch.environmentalProfile'])
            ->orderBy('name')
            ->get();

        $statuses = $environments->mapWithKeys(
            fn (Storage $env) => [$env->id => $this->thingsBoard->status($env)]
        );

        return view('admin.sensors.overview', compact('environments', 'statuses'));
    }

    /**
     * Environment / dryer detail page.
     */
    public function show(string $id)
    {
        app()->setLocale(auth()->user()->language);

        $environment = Storage::with(['city', 'activeBatch.product', 'activeBatch.environmentalProfile', 'activeBatch.customer', 'activeBatch.operator'])
            ->findOrFail($id);

        $this->authorizeEnvironment($environment);

        $status = $this->thingsBoard->status($environment);
        $alarms = $this->thingsBoard->alarms($environment);
        $alarmHistory = $this->thingsBoard->alarms($environment, false);

        $recentBatches = $environment->dryingBatches()
            ->with(['product', 'customer'])
            ->orderByDesc('start_time')
            ->limit(10)
            ->get();

        return view('admin.sensors.show', compact('environment', 'status', 'alarms', 'alarmHistory', 'recentBatches'));
    }

    /**
     * JSON — latest status for one environment (polled by the detail page,
     * and by the overview page for each visible card).
     */
    public function status(string $id)
    {
        $environment = Storage::findOrFail($id);
        $this->authorizeEnvironment($environment);

        return response()->json($this->thingsBoard->status($environment));
    }

    /**
     * JSON — historical series for the environment's charts.
     */
    public function history(string $id, Request $request)
    {
        $environment = Storage::findOrFail($id);
        $this->authorizeEnvironment($environment);

        $keys = array_values(array_intersect(
            explode(',', $request->query('keys', 'chamber_temp,chamber_rh,airflow,exhaust_fan_speed')),
            array_keys(ThingsBoardService::TELEMETRY_META)
        ));

        [$start, $end] = $this->resolveRange($request->query('range', '24h'), $environment);

        $result = $this->thingsBoard->history($environment, $keys, $start, $end);

        return response()->json($result);
    }

    /**
     * JSON — alarms for one environment (active by default).
     */
    public function alarms(string $id, Request $request)
    {
        $environment = Storage::findOrFail($id);
        $this->authorizeEnvironment($environment);

        $activeOnly = $request->boolean('active_only', true);

        return response()->json($this->thingsBoard->alarms($environment, $activeOnly));
    }

    // -------------------------------------------------------------------------

    private function authorizeEnvironment(Storage $environment): void
    {
        $user = auth()->user();

        if (in_array($user->group_id, [1, 2])) {
            return;
        }

        $owns = DryingBatch::where('storage_id', $environment->id)
            ->where('customer_id', $user->id)
            ->exists();

        abort_unless($owns, 403);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(string $range, Storage $environment): array
    {
        $end = now();

        if ($range === 'batch') {
            $batch = $environment->activeBatch ?? $environment->dryingBatches()->latest('start_time')->first();
            if ($batch) {
                return [$batch->start_time->copy(), $batch->end_time ? $batch->end_time->copy() : $end];
            }
            $range = '24h';
        }

        $start = match ($range) {
            '1h' => $end->copy()->subHour(),
            '6h' => $end->copy()->subHours(6),
            '7d' => $end->copy()->subDays(7),
            default => $end->copy()->subDay(),
        };

        return [$start, $end];
    }
}
