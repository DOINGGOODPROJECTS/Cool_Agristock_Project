<?php

/**
 * ThingsBoard Community Edition connection settings.
 *
 * All sensitive values are read from environment variables.
 * Never hard-code credentials here, and never expose them to the frontend —
 * only App\Services\ThingsBoard\* may talk to ThingsBoard.
 *
 * Add these to your .env file once a ThingsBoard CE instance is available:
 *
 *   THINGSBOARD_URL=https://your-thingsboard-host
 *   THINGSBOARD_USERNAME=tenant@example.com
 *   THINGSBOARD_PASSWORD=your_password
 *   THINGSBOARD_TIMEOUT=15
 *   THINGSBOARD_MOCK=false
 */

return [

    // ThingsBoard instance URL (no trailing slash)
    'url' => rtrim(env('THINGSBOARD_URL', ''), '/'),

    // Tenant/customer user credentials used for server-to-server REST calls
    'username' => env('THINGSBOARD_USERNAME', ''),
    'password' => env('THINGSBOARD_PASSWORD', ''),

    // HTTP timeout in seconds
    'timeout' => (int) env('THINGSBOARD_TIMEOUT', 15),

    // How long a JWT is cached before re-authenticating (ThingsBoard default access token TTL is longer,
    // but we refresh early to avoid ever calling the API with an expired token).
    'token_ttl' => (int) env('THINGSBOARD_TOKEN_TTL', 15 * 60),

    // When true (or when no URL/credentials are configured), the service layer returns
    // deterministic simulated telemetry/alarms instead of calling ThingsBoard. This lets
    // the Smart Sensor module be built, demoed and tested before a real CE instance exists.
    // Set to false once THINGSBOARD_URL/USERNAME/PASSWORD point at a real instance.
    'mock' => (bool) env('THINGSBOARD_MOCK', true),

    // Standardized telemetry keys the Smart Sensor module understands (see SYNC_PROTOCOL-style
    // docs / module spec). Anything else reported by a device is ignored by the UI.
    'telemetry_keys' => [
        'ambient_temp',
        'ambient_rh',
        'chamber_temp',
        'chamber_rh',
        'exhaust_temp',
        'exhaust_rh',
        'airflow',
        'exhaust_fan_speed',
        'circulation_fan',
        'heater',
        'control_mode',
    ],

    // Default stale-telemetry threshold (minutes) when an environment doesn't override it.
    'default_stale_threshold_minutes' => (int) env('THINGSBOARD_STALE_MINUTES', 15),
];
