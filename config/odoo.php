<?php

/**
 * Odoo API connection settings (API-01).
 *
 * All sensitive values are read from environment variables.
 * Never hard-code credentials here.
 *
 * Add these to your .env file:
 *
 *   ODOO_URL=https://your-odoo-instance.odoo.com
 *   ODOO_DATABASE=your_database_name
 *   ODOO_USERNAME=api_user@example.com
 *   ODOO_API_KEY=your_api_key_here
 *   ODOO_COMPANY_ID=1
 *   ODOO_PROTOCOL=xmlrpc
 *   ODOO_TIMEOUT=30
 *   ODOO_RETRY_MAX=3
 *   ODOO_RETRY_DELAY_MS=500
 *   ODOO_DRY_RUN=false
 */

return [

    // Odoo instance URL (no trailing slash)
    'url'        => rtrim(env('ODOO_URL', ''), '/'),

    // Odoo database name
    'database'   => env('ODOO_DATABASE', ''),

    // API user email
    'username'   => env('ODOO_USERNAME', ''),

    // API key (preferred) or password — stored in .env only, never in code
    'api_key'    => env('ODOO_API_KEY', ''),

    // Odoo company ID (res.company.id) for multi-company setups
    'company_id' => (int) env('ODOO_COMPANY_ID', 1),

    // Protocol: 'xmlrpc' (default, widely supported) or 'jsonrpc'
    'protocol'   => env('ODOO_PROTOCOL', 'xmlrpc'),

    // HTTP timeout in seconds
    'timeout'    => (int) env('ODOO_TIMEOUT', 30),

    // Retry policy for transient failures (API-04)
    'retry_max'       => (int) env('ODOO_RETRY_MAX', 3),
    'retry_delay_ms'  => (int) env('ODOO_RETRY_DELAY_MS', 500),

    // When true, API calls are simulated and logged but never sent to Odoo.
    // Use for local development and testing before staging access is available.
    'dry_run' => (bool) env('ODOO_DRY_RUN', false),

    // Odoo model names used in this integration
    'models' => [
        'partner'  => 'res.partner',
        'invoice'  => 'account.move',
        'payment'  => 'account.payment',
        'move_line'=> 'account.move.line',
    ],

    // External ID module prefix used for all Odoo import IDs
    'external_id_module' => env('ODOO_EXTERNAL_ID_MODULE', 'coolagristock'),

    /*
    |--------------------------------------------------------------------------
    | SYSCOHADA Account Mapping (verified against coolagristock.odoo.com)
    |--------------------------------------------------------------------------
    | These codes are looked up automatically by event type and debit/credit
    | side. Finance officers never need to enter account codes.
    |
    | To update a code: change the value here and run php artisan config:clear
    */
    'accounts' => [
        // Third-party accounts (Class 4)
        'receivable'       => '411100',   // Clients — amounts owed to Cool Agristock
        'advance_received' => '419100',   // Customers, advances and deposits received
        'rebates_granted'  => '419800',   // Customers, discounts/rebates to be granted
        'vat_output'       => '443100',   // VAT invoiced on sales (TVA collectée)

        // Cash & bank accounts (Class 5)
        'bank'             => '521001',   // Bank
        'cash'             => '571100',   // Cash in national currency
        'mobile_money'     => '571100',   // Mobile money (uses cash — no dedicated account)

        // Revenue accounts (Class 7)
        'storage_revenue'  => '706100',   // Services sold in the Region (storage fees)
        'drying_revenue'   => '706200',   // Services sold outside the Region (drying fees)
        'handling_revenue' => '706300',   // Services sold to group entities (handling fees)

        // Catch-all for "Other" event types — Finance reviews in Odoo
        'other_debit'      => '471000',   // Suspense / transit account (debit side)
        'other_credit'     => '471000',   // Suspense / transit account (credit side)

        // Single revenue account used for all invoice lines pushed to Odoo
        'invoice_revenue'  => '706100',   // Services sold — covers storage, drying, handling
    ],

];
