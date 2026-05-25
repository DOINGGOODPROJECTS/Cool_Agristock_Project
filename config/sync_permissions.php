<?php

/**
 * Sync RBAC Permission Map — keyed by group_id
 *
 * Groups (from groups table):
 *   1  = Admin                        → Admin tier
 *   2  = Superviseur                  → Supervisor tier
 *   3  = Comptable                    → Supervisor tier
 *   4  = Caissière                    → Supervisor tier
 *   5  = Coopérative Agricole         → User tier
 *   6  = Coopératives de Pêche        → User tier
 *   7  = Grossiste                    → User tier
 *   8  = Entreprises Agroalimentaire  → User tier
 *  10  = Particulier                  → User tier
 *
 * owner_only actions (sync.cancel, sync.edit): non-Admin users may only
 * act on ops where created_by === auth()->id().
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Permission matrix — keyed by group_id
    |--------------------------------------------------------------------------
    */

    'groups' => [

        // ── 1 · Admin ─────────────────────────────────────────────────────
        1 => [
            'name'    => 'Admin',
            'tier'    => 'admin',
            'actions' => [
                'sync.push'      => true,
                'sync.pull'      => true,
                'sync.reconcile' => true,
                'sync.accept'    => true,
                'sync.discard'   => true,
                'sync.cancel'    => true,   // all ops
                'sync.merge'     => true,
                'sync.edit'      => true,   // all ops
                'log.view'       => true,
                'log.export'     => true,
            ],
        ],

        // ── 2 · Superviseur ───────────────────────────────────────────────
        2 => [
            'name'    => 'Superviseur',
            'tier'    => 'supervisor',
            'actions' => [
                'sync.push'      => true,
                'sync.pull'      => true,
                'sync.reconcile' => true,
                'sync.accept'    => true,
                'sync.discard'   => true,
                'sync.cancel'    => true,   // own ops only
                'sync.merge'     => false,
                'sync.edit'      => true,   // own ops only
                'log.view'       => true,
                'log.export'     => false,
            ],
        ],

        // ── 3 · Comptable ─────────────────────────────────────────────────
        3 => [
            'name'    => 'Comptable',
            'tier'    => 'supervisor',
            'actions' => [
                'sync.push'      => true,
                'sync.pull'      => true,
                'sync.reconcile' => true,
                'sync.accept'    => true,
                'sync.discard'   => true,
                'sync.cancel'    => true,   // own ops only
                'sync.merge'     => false,
                'sync.edit'      => true,   // own ops only
                'log.view'       => true,
                'log.export'     => false,
            ],
        ],

        // ── 4 · Caissière ─────────────────────────────────────────────────
        4 => [
            'name'    => 'Caissière',
            'tier'    => 'supervisor',
            'actions' => [
                'sync.push'      => true,
                'sync.pull'      => true,
                'sync.reconcile' => true,
                'sync.accept'    => true,
                'sync.discard'   => true,
                'sync.cancel'    => true,   // own ops only
                'sync.merge'     => false,
                'sync.edit'      => true,   // own ops only
                'log.view'       => true,
                'log.export'     => false,
            ],
        ],

        // ── 5 · Coopérative Agricole ──────────────────────────────────────
        5 => [
            'name'    => 'Coopérative Agricole',
            'tier'    => 'user',
            'actions' => [
                'sync.push'      => true,
                'sync.pull'      => true,
                'sync.reconcile' => true,
                'sync.accept'    => false,
                'sync.discard'   => true,
                'sync.cancel'    => true,   // own ops only
                'sync.merge'     => false,
                'sync.edit'      => true,   // own ops only
                'log.view'       => true,
                'log.export'     => false,
            ],
        ],

        // ── 6 · Coopératives de Pêche ─────────────────────────────────────
        6 => [
            'name'    => 'Coopératives de Pêche',
            'tier'    => 'user',
            'actions' => [
                'sync.push'      => true,
                'sync.pull'      => true,
                'sync.reconcile' => true,
                'sync.accept'    => false,
                'sync.discard'   => true,
                'sync.cancel'    => true,   // own ops only
                'sync.merge'     => false,
                'sync.edit'      => true,   // own ops only
                'log.view'       => true,
                'log.export'     => false,
            ],
        ],

        // ── 7 · Grossiste ─────────────────────────────────────────────────
        7 => [
            'name'    => 'Grossiste',
            'tier'    => 'user',
            'actions' => [
                'sync.push'      => true,
                'sync.pull'      => true,
                'sync.reconcile' => true,
                'sync.accept'    => false,
                'sync.discard'   => true,
                'sync.cancel'    => true,   // own ops only
                'sync.merge'     => false,
                'sync.edit'      => true,   // own ops only
                'log.view'       => true,
                'log.export'     => false,
            ],
        ],

        // ── 8 · Entreprises Agroalimentaire ───────────────────────────────
        8 => [
            'name'    => 'Entreprises Agroalimentaire',
            'tier'    => 'user',
            'actions' => [
                'sync.push'      => true,
                'sync.pull'      => true,
                'sync.reconcile' => true,
                'sync.accept'    => false,
                'sync.discard'   => true,
                'sync.cancel'    => true,   // own ops only
                'sync.merge'     => false,
                'sync.edit'      => true,   // own ops only
                'log.view'       => true,
                'log.export'     => false,
            ],
        ],

        // ── 10 · Particulier ──────────────────────────────────────────────
        10 => [
            'name'    => 'Particulier',
            'tier'    => 'user',
            'actions' => [
                'sync.push'      => true,
                'sync.pull'      => true,
                'sync.reconcile' => true,
                'sync.accept'    => false,
                'sync.discard'   => true,
                'sync.cancel'    => true,   // own ops only
                'sync.merge'     => false,
                'sync.edit'      => true,   // own ops only
                'log.view'       => true,
                'log.export'     => false,
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Owner-only actions
    |--------------------------------------------------------------------------
    | Non-Admin users may only act on ops where created_by === auth()->id().
    | Admin (group_id 1) bypasses the ownership check entirely.
    */

    'owner_only' => [
        'sync.cancel',
        'sync.edit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin-only actions
    |--------------------------------------------------------------------------
    */

    'admin_only' => [
        'sync.merge',
        'log.export',
    ],

];
