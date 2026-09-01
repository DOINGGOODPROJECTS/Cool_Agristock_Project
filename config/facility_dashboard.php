<?php

/**
 * Connection settings for the Cool Agristock facility-monitoring dashboard
 * (Node/Express + Next.js, dashboard.agricarecentres.com). That app owns the
 * physical facilities (dryers/cold rooms/storage areas) and which product
 * batches are currently placed in each — this app only reads that data to
 * show "in use at" on the Environmental Profiles page.
 *
 * Add these to your .env file:
 *
 *   FACILITY_DASHBOARD_URL=https://dashboard.agricarecentres.com
 *   FACILITY_DASHBOARD_EMAIL=service-account@example.com
 *   FACILITY_DASHBOARD_PASSWORD=your_password
 */

return [
    'url'      => rtrim(env('FACILITY_DASHBOARD_URL', ''), '/'),
    'email'    => env('FACILITY_DASHBOARD_EMAIL', ''),
    'password' => env('FACILITY_DASHBOARD_PASSWORD', ''),
    'timeout'  => (int) env('FACILITY_DASHBOARD_TIMEOUT', 10),
];
