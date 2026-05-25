<x-app-layout>
    @push('links')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
    <style>
        .spec-section { border-left: 4px solid #556ee6; padding-left: 1rem; margin-bottom: 2rem; }
        .spec-table th { background: #f8f9fa; font-size: 0.8rem; }
        .spec-table td { font-size: 0.85rem; vertical-align: middle; }
        .badge-action { font-size: 0.75rem; }
        .state-diagram { background: #1e1e1e; color: #d4d4d4; border-radius: 8px; padding: 1.5rem; font-family: monospace; font-size: 0.82rem; white-space: pre; overflow-x: auto; }
        .sms-example { background: #f0f4ff; border-radius: 6px; padding: 0.75rem 1rem; font-family: monospace; font-size: 0.85rem; border-left: 3px solid #556ee6; }
        .nav-pills .nav-link.active { background-color: #556ee6; }
        .nav-pills .nav-link { color: #495057; }
    </style>
    @endpush

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0"><i class="fas fa-file-code me-2"></i>Sync Protocol Specification <span class="badge bg-primary ms-2" style="font-size:0.7rem">v1.0</span></h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Sync Protocol</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab navigation --}}
    <div class="row mb-3">
        <div class="col-12">
            <ul class="nav nav-pills" id="specTabs">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="pill" href="#tab-schema">Op Schema</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tab-lamport">Lamport Clock</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tab-reconcile">Reconciliation</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tab-conflict">Conflict Rules</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tab-statemachine">State Machine</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tab-audit">Audit Log</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tab-sms">SMS Format</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="pill" href="#tab-rbac">RBAC Endpoints</a></li>
            </ul>
        </div>
    </div>

    <div class="tab-content">

        {{-- ── Tab 1: Op Schema ────────────────────────────────────────────── --}}
        <div class="tab-pane fade show active" id="tab-schema">
            <div class="card">
                <div class="card-header bg-primary-subtle"><h5 class="mb-0">Operation Schema — Every field, every type, every constraint</h5></div>
                <div class="card-body">
                    <table class="table table-bordered table-hover spec-table">
                        <thead><tr><th>Field</th><th>DB Type</th><th>Nullable</th><th>Constraint</th><th>Description</th></tr></thead>
                        <tbody>
                            <tr><td class="font-monospace">op_id</td><td>char(36)</td><td>NO</td><td>UUID v4, client-generated, globally unique</td><td>Immutable primary key</td></tr>
                            <tr><td class="font-monospace">user_id</td><td>int</td><td>NO</td><td>FK → users.id</td><td>User who created the op</td></tr>
                            <tr><td class="font-monospace">device_id</td><td>varchar(255)</td><td>NO</td><td>alphanumeric or <code>sms:{phone}</code></td><td>Physical device or SMS gateway identifier</td></tr>
                            <tr><td class="font-monospace">logical_seq</td><td>bigint unsigned</td><td>NO</td><td>Monotonically increasing per user</td><td>Lamport clock value — see Lamport tab</td></tr>
                            <tr><td class="font-monospace">storage_id</td><td>int</td><td>NO</td><td>FK → storages.id</td><td>Target storage facility</td></tr>
                            <tr><td class="font-monospace">product_id</td><td>int</td><td>NO</td><td>FK → products.id</td><td>Product being mutated</td></tr>
                            <tr><td class="font-monospace">stock_id</td><td>int</td><td>YES</td><td>FK → stocks.id</td><td>Specific stock entry. Null for new stock ops</td></tr>
                            <tr><td class="font-monospace">op_type</td><td>enum</td><td>NO</td><td>stock_in, stock_out, adjustment, spoilage, transfer</td><td>Nature of mutation</td></tr>
                            <tr><td class="font-monospace">quantity_delta</td><td>decimal(12,3)</td><td>NO</td><td>Signed. Non-zero. + = in, − = out</td><td>Net quantity change</td></tr>
                            <tr><td class="font-monospace">unit</td><td>varchar(255)</td><td>NO</td><td>e.g. kg, tonne, caisse</td><td>Unit of measure</td></tr>
                            <tr><td class="font-monospace">notes</td><td>text</td><td>YES</td><td>—</td><td>Free-text annotation. Editable before apply</td></tr>
                            <tr><td class="font-monospace">sync_status</td><td>enum</td><td>NO</td><td>pending, applied, conflict, superseded, cancelled</td><td>See State Machine tab</td></tr>
                            <tr><td class="font-monospace">client_created_at</td><td>timestamp</td><td>YES</td><td>Client local time</td><td>When op was created on device</td></tr>
                            <tr><td class="font-monospace">server_received_at</td><td>timestamp</td><td>YES</td><td>Server time on receipt</td><td>Set on submit</td></tr>
                            <tr><td class="font-monospace">applied_at</td><td>timestamp</td><td>YES</td><td>Server time</td><td>Set when op becomes applied</td></tr>
                            <tr><td class="font-monospace">conflict_with_op_id</td><td>varchar(255)</td><td>YES</td><td>op_id of conflicting op</td><td>Populated on conflict</td></tr>
                            <tr><td class="font-monospace">conflict_reason</td><td>text</td><td>YES</td><td>—</td><td>Human-readable conflict explanation</td></tr>
                            <tr><td class="font-monospace">resolved_by</td><td>int</td><td>YES</td><td>FK → users.id</td><td>User who accepted or discarded the conflict</td></tr>
                            <tr><td class="font-monospace">resolved_at</td><td>timestamp</td><td>YES</td><td>—</td><td>When conflict was resolved</td></tr>
                            <tr><td class="font-monospace">cancelled_by</td><td>int</td><td>YES</td><td>FK → users.id</td><td>User who cancelled the op</td></tr>
                            <tr><td class="font-monospace">cancelled_at</td><td>timestamp</td><td>YES</td><td>—</td><td>When op was cancelled</td></tr>
                            <tr><td class="font-monospace">edited_from_op_id</td><td>varchar(255)</td><td>YES</td><td>op_id of the op this replaces</td><td>Links edit chain</td></tr>
                        </tbody>
                    </table>
                    <div class="alert alert-info mt-3 mb-0">
                        <strong>Invariants:</strong>
                        <ul class="mb-0 mt-1">
                            <li><code>op_id</code> is set by client before transmission and never changes.</li>
                            <li><code>quantity_delta</code> is never zero.</li>
                            <li><code>logical_seq</code> is strictly increasing per <code>(user_id, device_id)</code>.</li>
                            <li>Once <code>applied</code>, <code>cancelled</code>, or <code>superseded</code> — the op is immutable.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tab 2: Lamport Clock ─────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-lamport">
            <div class="card">
                <div class="card-header bg-primary-subtle"><h5 class="mb-0">Lamport Clock Rules</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Client Rules</h6>
                            <ol>
                                <li class="mb-2"><strong>On write:</strong> Increment local counter by 1.<br><code class="bg-light px-2 py-1 rounded">local_seq = local_seq + 1</code></li>
                                <li class="mb-2"><strong>On pull:</strong> Advance past server's max:<br><code class="bg-light px-2 py-1 rounded">local_seq = max(local_seq, server_max_seq) + 1</code></li>
                                <li class="mb-2"><strong>Counter is per user</strong> — not per device. Reconcile across devices on pull.</li>
                                <li class="mb-2"><strong>Counter is never reset.</strong> Only ever increases.</li>
                                <li><strong>Counter is persisted locally</strong> across app restarts.</li>
                            </ol>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Server Rules</h6>
                            <ol>
                                <li class="mb-2"><strong>On submit:</strong> Record <code>server_received_at</code>. Do not modify <code>logical_seq</code>.</li>
                                <li class="mb-2"><strong>On conflict detection:</strong> Compare client's <code>logical_seq</code> against applied ops for same <code>(storage_id, product_id)</code> since <code>last_sync_seq</code>.</li>
                                <li><strong>Authoritative sequence</strong> = MAX <code>logical_seq</code> of all <code>applied</code> ops for a given <code>(storage_id, product_id)</code>.</li>
                            </ol>
                            <div class="alert alert-warning mt-3">
                                <strong>Why not timestamps?</strong><br>
                                Device clocks drift. Two devices in the same warehouse can disagree by minutes. Lamport counters provide causal ordering without trusting wall-clock time.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tab 3: Reconciliation ────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-reconcile">
            <div class="card">
                <div class="card-header bg-primary-subtle"><h5 class="mb-0">Three Reconciliation Outcomes</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="card border-success h-100">
                                <div class="card-header bg-success text-white"><i class="fas fa-check me-1"></i> applied</div>
                                <div class="card-body">
                                    <p><strong>Condition:</strong> No applied op exists for the same <code>(storage_id, product_id, stock_id)</code> with a higher <code>logical_seq</code> since <code>last_sync_seq</code>.</p>
                                    <p><strong>Actions:</strong></p>
                                    <ul>
                                        <li>Set <code>sync_status = applied</code></li>
                                        <li>Update <code>inventory_stock.quantity</code></li>
                                        <li>Write audit: <code>applied</code></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-secondary h-100">
                                <div class="card-header bg-secondary text-white"><i class="fas fa-copy me-1"></i> already_seen</div>
                                <div class="card-body">
                                    <p><strong>Condition:</strong> An op with the same <code>op_id</code> already exists in <code>inventory_ops</code>.</p>
                                    <p><strong>Actions:</strong></p>
                                    <ul>
                                        <li>Discard silently</li>
                                        <li>Return existing <code>sync_status</code></li>
                                        <li><strong>No audit entry</strong></li>
                                    </ul>
                                    <small class="text-muted">Handles retransmission after network failure.</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-danger h-100">
                                <div class="card-header bg-danger text-white"><i class="fas fa-exclamation-triangle me-1"></i> conflict</div>
                                <div class="card-body">
                                    <p><strong>Condition:</strong> A conflicting op exists (see Conflict Rules tab).</p>
                                    <p><strong>Actions:</strong></p>
                                    <ul>
                                        <li>Set <code>sync_status = conflict</code></li>
                                        <li>Populate <code>conflict_with_op_id</code></li>
                                        <li>Do NOT touch <code>inventory_stock</code></li>
                                        <li>Write audit: <code>conflict_flagged</code></li>
                                        <li>Notify Supervisors</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tab 4: Conflict Rules ────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-conflict">
            <div class="card">
                <div class="card-header bg-primary-subtle"><h5 class="mb-0">Conflict Detection Rules</h5></div>
                <div class="card-body">
                    <div class="spec-section">
                        <h6><span class="badge bg-success me-2">AUTO-MERGE</span>Additive ops — always applied</h6>
                        <p>Op types: <code>stock_in</code>, <code>stock_out</code>, <code>spoilage</code>, <code>transfer</code></p>
                        <p>These are applied regardless of concurrency. Two concurrent <code>stock_in</code> ops each add their quantities independently.</p>
                        <div class="alert alert-light border">Rationale: These represent real physical events. Two deliveries arriving simultaneously are both real.</div>
                    </div>
                    <div class="spec-section">
                        <h6><span class="badge bg-warning text-dark me-2">CONFLICT</span>Adjustment ops — conflict if concurrent adjustment exists</h6>
                        <p>Op type: <code>adjustment</code></p>
                        <p>Conflicts if there is any <code>adjustment</code> with <code>sync_status = applied</code> for the same <code>(storage_id, product_id, stock_id)</code> where <code>logical_seq &gt; client.last_sync_seq</code>.</p>
                        <div class="sms-example mt-2">Conflict reason: "Adjustment concurrent avec op {id} (seq {n}) depuis dernière sync"</div>
                    </div>
                    <div class="spec-section">
                        <h6><span class="badge bg-danger me-2">BUSINESS VIOLATION</span>Negative stock — never auto-applied</h6>
                        <p>For any op type, if <code>inventory_stock.quantity + quantity_delta &lt; 0</code>, the op is flagged as conflict.</p>
                        <div class="sms-example mt-2">Conflict reason: "Violation: stock résultant négatif ({current} + {delta} = {result})"</div>
                        <p class="mt-2">A Supervisor or Admin must <strong>accept</strong> (acknowledge the discrepancy) or <strong>discard</strong> (reject the transaction).</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tab 5: State Machine ─────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-statemachine">
            <div class="card">
                <div class="card-header bg-primary-subtle"><h5 class="mb-0">Queue Action State Machine</h5></div>
                <div class="card-body">
                    <div class="state-diagram">
                    [device] ──submit──► [pending]
                                              │
                          ┌───────────────────┼───────────────────┐
                          │                   │                   │
                       cancel            reconcile           reconcile
                          │          (clean apply)          (conflict)
                          ▼                   │                   │
                     [cancelled]         [applied]           [conflict]
                                                                  │
                                       ┌──────────────────────────┼──────────────────┐
                                       │                          │                  │
                                    accept                     discard             merge
                                       │                          │                  │
                                       ▼                          ▼                  ▼
                                   [applied]              [superseded]    [superseded x2]──► new op
                                                                                             [pending]
                    edit (on pending or conflict)
                          │
                          ▼
                original → [superseded]   new op → [pending] (edited_from_op_id set)
                    </div>
                    <div class="table-responsive mt-4">
                        <table class="table table-bordered spec-table">
                            <thead><tr><th>Action</th><th>Precondition</th><th>Authorization</th><th>Status After</th><th>Touches inventory_stock</th></tr></thead>
                            <tbody>
                                <tr><td><span class="badge bg-primary">submit</span></td><td>op_id not yet on server</td><td>sync.push — all tiers</td><td>pending → applied or conflict</td><td>YES (if applied)</td></tr>
                                <tr><td><span class="badge bg-danger">cancel</span></td><td>sync_status = pending</td><td>sync.cancel — owner or Admin</td><td>cancelled</td><td>NO</td></tr>
                                <tr><td><span class="badge bg-success">accept</span></td><td>sync_status = conflict</td><td>sync.accept — Supervisor + Admin</td><td>applied</td><td>YES</td></tr>
                                <tr><td><span class="badge bg-secondary">discard</span></td><td>sync_status = conflict</td><td>sync.discard — all tiers</td><td>superseded</td><td>NO</td></tr>
                                <tr><td><span class="badge bg-dark">merge</span></td><td>both ops = conflict</td><td>sync.merge — Admin only</td><td>both superseded → new pending</td><td>NO (re-reconciled)</td></tr>
                                <tr><td><span class="badge bg-warning text-dark">edit</span></td><td>pending or conflict</td><td>sync.edit — owner or Admin</td><td>original superseded → new pending</td><td>NO (re-reconciled)</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tab 6: Audit Log ─────────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-audit">
            <div class="card">
                <div class="card-header bg-primary-subtle"><h5 class="mb-0">sync_audit_log — Required entries per action</h5></div>
                <div class="card-body">
                    <div class="alert alert-warning"><i class="fas fa-lock me-1"></i> This table is <strong>append-only</strong>. No UPDATE or DELETE is ever performed on sync_audit_log.</div>
                    <table class="table table-bordered spec-table">
                        <thead><tr><th>Action</th><th>Trigger</th><th>before_value</th><th>after_value</th><th>reason required</th></tr></thead>
                        <tbody>
                            <tr><td><span class="badge bg-primary">submitted</span></td><td>Op arrives at server</td><td>null</td><td>{sync_status, server_received_at}</td><td>NO</td></tr>
                            <tr><td><span class="badge bg-success">applied</span></td><td>Auto-apply on submit</td><td>{quantity before}</td><td>{quantity after, applied_at}</td><td>NO</td></tr>
                            <tr><td><span class="badge bg-info">reconciled</span></td><td>Supervisor views reconciliation</td><td>null</td><td>null</td><td>NO</td></tr>
                            <tr><td><span class="badge bg-danger">conflict_flagged</span></td><td>Conflict detected on submit</td><td>{current inventory_stock.qty}</td><td>{conflict_with_op_id, reason}</td><td>NO</td></tr>
                            <tr><td><span class="badge bg-success">accepted</span></td><td>Supervisor/Admin accepts conflict</td><td>{sync_status: conflict}</td><td>{sync_status: applied, applied_at}</td><td><strong>YES</strong></td></tr>
                            <tr><td><span class="badge bg-secondary">discarded</span></td><td>Any tier discards conflict</td><td>{sync_status: conflict}</td><td>{sync_status: superseded}</td><td><strong>YES</strong></td></tr>
                            <tr><td><span class="badge bg-danger">cancelled</span></td><td>Owner/Admin cancels pending op</td><td>{sync_status: pending}</td><td>{sync_status: cancelled}</td><td>NO</td></tr>
                            <tr><td><span class="badge bg-dark">merged</span></td><td>Admin merges two ops</td><td>{op_id_1, op_id_2, quantities}</td><td>{new_op_id, merged_qty}</td><td><strong>YES</strong></td></tr>
                            <tr><td><span class="badge bg-warning text-dark">edited</span></td><td>Owner/Admin edits pending op</td><td>{quantity_delta, notes}</td><td>{new_op_id}</td><td>NO</td></tr>
                            <tr><td><span class="badge bg-danger">overridden</span></td><td>Admin force-applies over conflict</td><td>{conflict state}</td><td>{applied state}</td><td><strong>YES</strong></td></tr>
                        </tbody>
                    </table>
                    <div class="alert alert-info mt-3 mb-0">
                        <strong>Required on every row:</strong> op_id, actor_id, actor_group_id (snapshot), device_id, ip_address, created_at
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tab 7: SMS Format ────────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-sms">
            <div class="card">
                <div class="card-header bg-primary-subtle"><h5 class="mb-0">SMS Op Format — French Keywords</h5></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Command Structure</h6>
                            <div class="sms-example mb-3">COMMANDE QUANTITE UNITE STOCKAGE PRODUIT [NOTES]</div>
                            <table class="table table-bordered spec-table">
                                <thead><tr><th>SMS Keyword</th><th>op_type</th><th>quantity_delta</th></tr></thead>
                                <tbody>
                                    <tr><td><code>ENTREE</code></td><td>stock_in</td><td>Positive</td></tr>
                                    <tr><td><code>SORTIE</code></td><td>stock_out</td><td>Negative</td></tr>
                                    <tr><td><code>AJUSTEMENT</code></td><td>adjustment</td><td>Signed (prefix − for reduction)</td></tr>
                                    <tr><td><code>PERTE</code></td><td>spoilage</td><td>Negative</td></tr>
                                    <tr><td><code>TRANSFERT</code></td><td>transfer</td><td>Signed</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Example Messages</h6>
                            <div class="sms-example mb-2">ENTREE 500 kg FRAISZO ANANAS livraison matin</div>
                            <div class="sms-example mb-2">SORTIE 200 kg FRAISZO TOMATE commande C-001</div>
                            <div class="sms-example mb-2">AJUSTEMENT -50 kg FRAISZO MANGUE correction inventaire</div>
                            <div class="sms-example mb-3">PERTE 30 kg FRAISZO BANANE pourriture signalée</div>
                            <h6>SMS Responses</h6>
                            <table class="table table-bordered spec-table">
                                <thead><tr><th>Outcome</th><th>Reply</th></tr></thead>
                                <tbody>
                                    <tr><td><span class="badge bg-success">applied</span></td><td><code>OK: {type} {qty} {unit} enregistré. Ref: {id[0:8]}</code></td></tr>
                                    <tr><td><span class="badge bg-warning text-dark">conflict</span></td><td><code>ATTENTE: op en conflit. Ref: {id[0:8]}. Un superviseur doit valider.</code></td></tr>
                                    <tr><td><span class="badge bg-danger">parse error</span></td><td><code>ERREUR: format invalide. Exemple: ENTREE 500 kg STOCKAGE PRODUIT</code></td></tr>
                                    <tr><td><span class="badge bg-danger">neg. stock</span></td><td><code>REFUS: stock insuffisant ({qty} {unit} disponible).</code></td></tr>
                                    <tr><td><span class="badge bg-secondary">unknown sender</span></td><td><code>ERREUR: numéro non enregistré. Contactez votre superviseur.</code></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="alert alert-info mt-2 mb-0">
                        <strong>Parsing rules:</strong> Sender must exist in <code>member_phones</code> with <code>verified_at IS NOT NULL</code>. Storage and product matched case-insensitively. <code>device_id = sms:{phone}</code>.
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tab 8: RBAC Endpoints ────────────────────────────────────────── --}}
        <div class="tab-pane fade" id="tab-rbac">
            <div class="card">
                <div class="card-header bg-primary-subtle"><h5 class="mb-0">RBAC Enforcement Points</h5></div>
                <div class="card-body">
                    <table class="table table-bordered spec-table">
                        <thead><tr><th>Route</th><th>Method</th><th>Permission</th><th>Ownership Check</th><th>Middleware</th></tr></thead>
                        <tbody>
                            <tr><td><code>/inventory-ops</code></td><td>GET</td><td>sync.pull</td><td>Scoped if group_id &gt; 4</td><td>auth</td></tr>
                            <tr><td><code>/inventory-ops</code></td><td>POST</td><td>sync.push</td><td>—</td><td>auth</td></tr>
                            <tr><td><code>/inventory-ops/{id}/accept</code></td><td>POST</td><td>sync.accept</td><td>NO</td><td>auth</td></tr>
                            <tr><td><code>/inventory-ops/{id}/discard</code></td><td>POST</td><td>sync.discard</td><td>NO</td><td>auth</td></tr>
                            <tr><td><code>/inventory-ops/{id}/cancel</code></td><td>POST</td><td>sync.cancel</td><td>YES — owner or Admin</td><td>auth</td></tr>
                            <tr><td><code>/inventory-ops/{id}</code></td><td>PUT</td><td>sync.edit</td><td>YES — owner or Admin</td><td>auth</td></tr>
                            <tr><td><code>/sync-sessions</code></td><td>GET</td><td>sync.reconcile</td><td>—</td><td>auth</td></tr>
                            <tr><td><code>/sync-audit-log</code></td><td>GET</td><td>log.view</td><td>—</td><td>auth</td></tr>
                            <tr><td><code>/sync-audit-log/export</code></td><td>GET</td><td>log.export</td><td>—</td><td>auth + Admin</td></tr>
                            <tr><td><code>/sync/merge</code> (future)</td><td>POST</td><td>sync.merge</td><td>—</td><td>auth + Admin</td></tr>
                        </tbody>
                    </table>
                    <h6 class="mt-4">Permission check helpers (to implement in SyncAuthService)</h6>
                    <pre class="bg-light p-3 rounded"><code>function canSync(string $action): bool {
    return \App\Models\SyncPermission::where('group_id', auth()->user()->group_id)
        ->where('action', $action)
        ->where('allowed', true)
        ->exists();
}

function ownerOrAdmin(InventoryOp $op): bool {
    return auth()->user()->group_id === 1
        || $op->user_id === auth()->id();
}</code></pre>
                    <h6 class="mt-3">Tier Summary</h6>
                    <table class="table table-bordered spec-table">
                        <thead><tr><th>Tier</th><th>Groups</th><th>push</th><th>pull</th><th>reconcile</th><th>accept</th><th>discard</th><th>cancel</th><th>merge</th><th>edit</th><th>log.view</th><th>log.export</th></tr></thead>
                        <tbody>
                            <tr class="table-danger"><td>Admin</td><td>1</td><td>✓</td><td>✓</td><td>✓</td><td>✓</td><td>✓</td><td>✓ all</td><td>✓</td><td>✓ all</td><td>✓</td><td>✓</td></tr>
                            <tr class="table-warning"><td>Supervisor</td><td>2,3,4</td><td>✓</td><td>✓</td><td>✓</td><td>✓</td><td>✓</td><td>✓ own</td><td>✗</td><td>✓ own</td><td>✓</td><td>✗</td></tr>
                            <tr class="table-success"><td>User</td><td>5,6,7,8,10</td><td>✓</td><td>✓</td><td>✓</td><td>✗</td><td>✓</td><td>✓ own</td><td>✗</td><td>✓ own</td><td>✓</td><td>✗</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- end tab-content --}}

    @push('scripts')
    <script>
        // Preserve active tab across page loads
        const tabKey = 'sync_spec_tab';
        const stored = localStorage.getItem(tabKey);
        if (stored) {
            const tab = document.querySelector(`a[href="${stored}"]`);
            if (tab) new bootstrap.Tab(tab).show();
        }
        document.querySelectorAll('#specTabs .nav-link').forEach(el => {
            el.addEventListener('shown.bs.tab', e => {
                localStorage.setItem(tabKey, e.target.getAttribute('href'));
            });
        });
    </script>
    @endpush
</x-app-layout>
