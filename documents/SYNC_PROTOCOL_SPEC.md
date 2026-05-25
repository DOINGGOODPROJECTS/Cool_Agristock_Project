# Cool AgriStock — Offline Sync Protocol Specification

**Version:** 1.0  
**Date:** 2026-05-25  
**Status:** Authoritative — no sync code may be written that contradicts this document.

---

## 1. Purpose

This document defines the complete wire protocol, data schema, conflict rules, state machine, audit requirements, SMS format, and RBAC enforcement points for the offline inventory sync layer.

Every mobile client, every API endpoint, and every background job must implement exactly what is specified here.

---

## 2. Operation Schema

Every inventory mutation is represented as an **InventoryOp**. This is the atomic unit of the sync layer.

### 2.1 Field Definitions

| Field | DB Type | Nullable | Constraint | Description |
|---|---|---|---|---|
| `op_id` | `char(36)` | NO | UUID v4, client-generated, globally unique | Immutable primary key. Client assigns before sending. |
| `user_id` | `int` | NO | FK → `users.id` | User who created the op. |
| `device_id` | `varchar(255)` | NO | Format: alphanumeric or `sms:{phone}` | Physical device or SMS gateway identifier. |
| `logical_seq` | `bigint unsigned` | NO | Monotonically increasing per user | Lamport clock value at time of creation. See §3. |
| `storage_id` | `int` | NO | FK → `storages.id` | Target storage facility. |
| `product_id` | `int` | NO | FK → `products.id` | Product being mutated. |
| `stock_id` | `int` | YES | FK → `stocks.id` | Specific stock entry. Null for new stock ops. |
| `op_type` | `enum` | NO | `stock_in`, `stock_out`, `adjustment`, `spoilage`, `transfer` | Nature of the mutation. See §2.2. |
| `quantity_delta` | `decimal(12,3)` | NO | Signed. Positive = in, Negative = out | Net quantity change in `unit`. |
| `unit` | `varchar(255)` | NO | e.g. `kg`, `tonne`, `caisse` | Unit of measure. |
| `notes` | `text` | YES | — | Free-text annotation. Editable before apply. |
| `sync_status` | `enum` | NO | Default: `pending` | See §5 state machine. |
| `client_created_at` | `timestamp` | YES | Client local time | When the op was created on device. |
| `server_received_at` | `timestamp` | YES | Server time on receipt | Set by server on `submit`. |
| `applied_at` | `timestamp` | YES | Server time | Set when op transitions to `applied`. |
| `conflict_with_op_id` | `varchar(255)` | YES | op_id of conflicting op | Populated when `sync_status = conflict`. |
| `conflict_reason` | `text` | YES | — | Human-readable conflict explanation. |
| `resolved_by` | `int` | YES | FK → `users.id` | User who accepted or discarded the conflict. |
| `resolved_at` | `timestamp` | YES | — | When conflict was resolved. |
| `cancelled_by` | `int` | YES | FK → `users.id` | User who cancelled the op. |
| `cancelled_at` | `timestamp` | YES | — | When op was cancelled. |
| `edited_from_op_id` | `varchar(255)` | YES | op_id of the op this replaces | Set when this op is the result of an `edit` action. |

### 2.2 Op Type Semantics

| `op_type` | `quantity_delta` | Auto-merge | Description |
|---|---|---|---|
| `stock_in` | Positive | YES | New stock arrives at storage |
| `stock_out` | Negative | YES | Stock leaves storage (sale, transfer out) |
| `adjustment` | Signed | NO | Manual inventory correction — conflict if concurrent |
| `spoilage` | Negative | YES | Loss due to rot or damage (maps to `rottens` table) |
| `transfer` | Signed | YES (pair) | Move between storages — requires paired ops |

### 2.3 Invariants

- `op_id` is set by the client before transmission and never changes.
- `quantity_delta` is never zero.
- `logical_seq` is strictly increasing per `(user_id, device_id)` pair.
- Once `sync_status` is `applied`, `cancelled`, or `superseded`, the op is immutable except for audit fields.
- `edited_from_op_id` creates a chain — follow it to reconstruct the full edit history.

---

## 3. Lamport Clock Rules

The sync layer uses a Lamport logical clock (not a timestamp) for causality ordering.

### 3.1 Client Rules

1. **On write:** Increment local counter by 1, assign to `logical_seq`.  
   `local_seq = local_seq + 1`

2. **On pull response:** Advance local counter past the server's highest seen seq:  
   `local_seq = max(local_seq, server_max_seq) + 1`

3. **Counter is per user, not per device.** A user syncing from two devices must reconcile their counter on pull.

4. **Counter is never reset.** It only ever increases.

5. **Counter is persisted locally** across app restarts.

### 3.2 Server Rules

1. **On submit:** Server records `server_received_at` but does not modify `logical_seq`. The client value is trusted.

2. **On conflict detection:** Server compares the client's `logical_seq` against the `logical_seq` of existing `applied` ops for the same `(storage_id, product_id)` since the client's `last_sync_seq`.

3. **The server's authoritative sequence** is the MAX `logical_seq` across all `applied` ops for a given `(storage_id, product_id)`.

### 3.3 Why Lamport, Not Timestamps

Device clocks drift. Two devices in the same warehouse can disagree by minutes. A Lamport counter provides causal ordering without trusting wall-clock time. If op A's `logical_seq` < op B's `logical_seq`, A happened-before B (or they are concurrent).

---

## 4. Reconciliation Outcomes

Every submitted op is evaluated against the server state and assigned one of three outcomes.

### 4.1 `applied` — Clean apply

**Condition:** No `applied` op exists for the same `(storage_id, product_id, stock_id)` with a `logical_seq` greater than or equal to the submitted op's `logical_seq` since `last_sync_seq`.

**Action:**
- Set `sync_status = applied`
- Set `applied_at = now()`
- Update `inventory_stock` row: `quantity += quantity_delta`, `last_op_id = op_id`, `last_updated_at = now()`
- Write to `sync_audit_log`: action = `applied`

### 4.2 `already_seen` — Duplicate submission

**Condition:** An op with the same `op_id` already exists in `inventory_ops`.

**Action:**
- Discard the incoming op silently.
- Return the existing op's current `sync_status` to the client.
- Do NOT write to `sync_audit_log`.

This handles retransmission after a network failure.

### 4.3 `conflict` — Concurrent mutation

**Condition:** A conflicting op exists (see §5 conflict detection rules).

**Action:**
- Set `sync_status = conflict`
- Populate `conflict_with_op_id` and `conflict_reason`
- Do NOT touch `inventory_stock`
- Write to `sync_audit_log`: action = `conflict_flagged`
- Notify Supervisors via push/SMS

---

## 5. Conflict Detection Rules

### 5.1 Additive ops — always auto-merge

Op types: `stock_in`, `stock_out`, `spoilage`, `transfer`

**Rule:** These ops are always applied regardless of concurrency. Two concurrent `stock_in` ops each add their quantities independently.

**Rationale:** These represent real physical events. Two deliveries arriving simultaneously are both real; merging them arithmetically is correct.

**Exception:** If applying the op would cause `inventory_stock.quantity < 0`, this is a **business violation** — see §5.3.

### 5.2 Adjustment ops — conflict if concurrent adjustment exists

Op type: `adjustment`

**Rule:** An `adjustment` op conflicts if there is any other `adjustment` op with `sync_status = applied` for the same `(storage_id, product_id, stock_id)` where that op's `logical_seq > client.last_sync_seq`.

**Rationale:** An adjustment corrects the current state. If the state was adjusted again after the client last synced, both adjustments may have been responding to the same discrepancy — applying both would double-correct.

**Conflict reason string:**  
`"Adjustment concurrent avec op {conflict_with_op_id} (seq {n}) depuis dernière sync"`

### 5.3 Negative stock — business violation

**Rule:** For any op type, if `inventory_stock.quantity + quantity_delta < 0`, the op is flagged as `conflict` with reason:

`"Violation: stock résultant négatif ({current_qty} + {delta} = {result})"`

**It is never auto-applied.** A Supervisor or Admin must either:
- Accept it (acknowledging the discrepancy), or
- Discard it (rejecting the transaction).

---

## 6. Queue Action State Machine

```
                    submit
    [device] ──────────────► [pending]
                                  │
              ┌───────────────────┼───────────────────┐
              │                   │                   │
           cancel              reconcile           reconcile
              │             (clean apply)        (conflict)
              ▼                   │                   │
         [cancelled]         [applied]           [conflict]
                                                      │
                              ┌───────────────────────┼──────────────────┐
                              │                       │                  │
                           accept                  discard             merge
                              │                       │                  │
                              ▼                       ▼                  ▼
                          [applied]            [superseded]       [superseded] ──► new op [pending]
                                                                 (original x2)
                         edit (any pending or conflict)
                              │
                              ▼
                    original → [superseded]
                    new op  → [pending] (with edited_from_op_id)
```

### 6.1 Action Definitions

#### `submit` — client sends op to server
- Precondition: op does not exist on server yet
- Sets: `server_received_at = now()`
- Outcome: → `pending`, then immediately → `applied` or `conflict`
- Audit: `submitted` then `applied` or `conflict_flagged`

#### `cancel` — abort a pending op before it is applied
- Precondition: `sync_status = pending`
- Authorization: op owner (`user_id = auth()->id()`) OR Admin (group_id = 1)
- Sets: `sync_status = cancelled`, `cancelled_by`, `cancelled_at`
- Does NOT touch `inventory_stock`
- Audit: `cancelled`

#### `accept` — apply a conflicted op
- Precondition: `sync_status = conflict`
- Authorization: Supervisor (2,3,4) or Admin (1)
- Sets: `sync_status = applied`, `applied_at`, `resolved_by`, `resolved_at`
- Updates: `inventory_stock.quantity += quantity_delta`
- Audit: `accepted`

#### `discard` — reject a conflicted op
- Precondition: `sync_status = conflict`
- Authorization: All tiers (see config/sync_permissions.php)
- Sets: `sync_status = superseded`, `resolved_by`, `resolved_at`
- Does NOT touch `inventory_stock`
- Audit: `discarded`

#### `merge` — combine two conflicting ops into one corrected op
- Precondition: Both source ops have `sync_status = conflict`
- Authorization: Admin only (group_id = 1)
- Sets: both source ops → `sync_status = superseded`
- Creates: new op with combined `quantity_delta`, `edited_from_op_id = first_op_id`, `sync_status = pending` (re-enters reconciliation)
- Audit: `merged` on both source ops, `submitted` on new op

#### `edit` — correct a pending op's quantity or notes before apply
- Precondition: `sync_status = pending` OR `conflict`
- Authorization: op owner OR Admin (group_id = 1)
- Sets: original op → `sync_status = superseded`
- Creates: new op with corrected values, `edited_from_op_id = original_op_id`, `sync_status = pending`
- Audit: `edited` on original, `submitted` on new op

---

## 7. Sync Audit Log — Required Entries

Every state transition **must** produce exactly one row in `sync_audit_log`. This table is append-only — no UPDATE or DELETE is ever performed.

| Action | Trigger | `before_value` | `after_value` | `reason` required |
|---|---|---|---|---|
| `submitted` | op arrives at server | `null` | `{sync_status, server_received_at}` | NO |
| `applied` | auto-apply on submit | `{quantity before}` | `{quantity after, applied_at}` | NO |
| `reconciled` | supervisor views reconciliation | `null` | `null` | NO |
| `conflict_flagged` | conflict detected on submit | `{current inventory_stock.quantity}` | `{conflict_with_op_id, conflict_reason}` | NO |
| `accepted` | supervisor/admin accepts conflict | `{sync_status: conflict}` | `{sync_status: applied, applied_at}` | YES |
| `discarded` | any tier discards conflict | `{sync_status: conflict}` | `{sync_status: superseded}` | YES |
| `cancelled` | owner/admin cancels pending op | `{sync_status: pending}` | `{sync_status: cancelled}` | NO |
| `merged` | admin merges two ops | `{op_id_1, op_id_2, their quantities}` | `{new_op_id, merged_quantity}` | YES |
| `edited` | owner/admin edits pending op | `{quantity_delta, notes}` | `{new_op_id}` | NO |
| `overridden` | admin force-applies over conflict | `{conflict state}` | `{applied state}` | YES |

**Required fields for every row:**
- `op_id` — the op being acted upon
- `actor_id` — `auth()->id()`
- `actor_group_id` — snapshot of `auth()->user()->group_id` at time of action
- `device_id` — `'web'` for dashboard actions, device identifier for mobile
- `ip_address` — `request()->ip()`
- `created_at` — `now()` (never null, never updated)

---

## 8. SMS Op Format — French Keywords

SMS ops are submitted by members who cannot access the web interface. The device_id for SMS ops is always `sms:{phone_number}`.

### 8.1 Command Format

```
COMMANDE QUANTITE UNITE STOCKAGE [NOTES]
```

All keywords are case-insensitive. Accented characters are optional (ENTREE = ENTRÉE).

### 8.2 Keyword → op_type Mapping

| SMS Keyword | `op_type` | `quantity_delta` sign |
|---|---|---|
| `ENTREE` | `stock_in` | Positive |
| `SORTIE` | `stock_out` | Negative |
| `AJUSTEMENT` | `adjustment` | Signed (prefix `-` for reduction) |
| `PERTE` | `spoilage` | Negative |
| `TRANSFERT` | `transfer` | Signed |

### 8.3 Example SMS Messages

```
ENTREE 500 kg FRAISZO ANANAS livraison matin
→ op_type=stock_in, quantity_delta=+500, unit=kg, storage=FRAISZO, notes="livraison matin"

SORTIE 200 kg FRAISZO TOMATE commande C-001
→ op_type=stock_out, quantity_delta=-200, unit=kg, storage=FRAISZO, notes="commande C-001"

AJUSTEMENT -50 kg FRAISZO MANGUE correction inventaire
→ op_type=adjustment, quantity_delta=-50, unit=kg, storage=FRAISZO, notes="correction inventaire"

PERTE 30 kg FRAISZO BANANE pourriture signalée
→ op_type=spoilage, quantity_delta=-30, unit=kg, storage=FRAISZO, notes="pourriture signalée"
```

### 8.4 SMS Parsing Rules

1. Parse sender phone against `member_phones.phone` — reject if not found or `verified_at IS NULL`.
2. Parse storage name against `storages.name` (case-insensitive, partial match allowed).
3. Parse product name against `products.name` (case-insensitive, partial match allowed).
4. If parse fails, reply: `ERREUR: format invalide. Exemple: ENTREE 500 kg FRAISZO ANANAS`
5. If sender unknown: `ERREUR: numéro non enregistré. Contactez votre superviseur.`
6. Assign `device_id = sms:{sender_phone}`
7. Assign `logical_seq` using the server's current max for that user + 1.
8. Submit the op normally through the reconciliation pipeline.

### 8.5 SMS Response Messages

| Outcome | Reply |
|---|---|
| `applied` | `OK: {op_type} {quantity_delta} {unit} enregistré. Ref: {op_id[0:8]}` |
| `conflict` | `ATTENTE: op en conflit. Ref: {op_id[0:8]}. Un superviseur doit valider.` |
| `parse error` | `ERREUR: format invalide. Exemple: ENTREE 500 kg STOCKAGE PRODUIT` |
| `negative stock` | `REFUS: stock insuffisant ({current_qty} {unit} disponible).` |

---

## 9. RBAC Enforcement Points

Every endpoint that performs a sync action must check both:
1. **Permission check** — does the user's `group_id` have this action in `sync_permissions`?
2. **Ownership check** — for `owner_only` actions, is `op.user_id == auth()->id()` (unless Admin)?

The source of truth for permissions is `config/sync_permissions.php` mirrored in the `sync_permissions` table.

### 9.1 Endpoint Permission Map

| Route | Method | Required Permission | Ownership Check | Middleware |
|---|---|---|---|---|
| `/inventory-ops` | GET | `sync.pull` | Scoped to own ops if group_id > 4 | `auth` |
| `/inventory-ops` (submit) | POST | `sync.push` | — | `auth` |
| `/inventory-ops/{id}/accept` | POST | `sync.accept` | NO — any authorized tier | `auth` |
| `/inventory-ops/{id}/discard` | POST | `sync.discard` | NO — any authorized tier | `auth` |
| `/inventory-ops/{id}/cancel` | POST | `sync.cancel` | YES — owner or Admin | `auth` |
| `/inventory-ops/{id}` | PUT | `sync.edit` | YES — owner or Admin | `auth` |
| `/sync-sessions` | GET | `sync.reconcile` | — | `auth` |
| `/sync-audit-log` | GET | `log.view` | — | `auth` |
| `/sync-audit-log/export` | GET | `log.export` | — | `auth` + Admin check |
| (future) `/sync/merge` | POST | `sync.merge` | — | `auth` + Admin check |

### 9.2 Permission Check Helper (to implement)

```php
// In SyncAuthService or as a global helper
function canSync(string $action): bool
{
    $groupId = auth()->user()->group_id;
    return \App\Models\SyncPermission::where('group_id', $groupId)
        ->where('action', $action)
        ->where('allowed', true)
        ->exists();
}

function ownerOrAdmin(InventoryOp $op): bool
{
    return auth()->user()->group_id === 1 || $op->user_id === auth()->id();
}
```

### 9.3 Group-tier Summary

| group_id | Name | Tier | sync.push | sync.pull | sync.reconcile | sync.accept | sync.discard | sync.cancel | sync.merge | sync.edit | log.view | log.export |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Admin | admin | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ all | ✓ | ✓ all | ✓ | ✓ |
| 2 | Superviseur | supervisor | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ own | ✗ | ✓ own | ✓ | ✗ |
| 3 | Comptable | supervisor | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ own | ✗ | ✓ own | ✓ | ✗ |
| 4 | Caissière | supervisor | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ own | ✗ | ✓ own | ✓ | ✗ |
| 5–10 | Members | user | ✓ | ✓ | ✓ | ✗ | ✓ | ✓ own | ✗ | ✓ own | ✓ | ✗ |

---

## 10. Sync Session Lifecycle

A `sync_session` row is created at the start of every sync attempt.

```
1. Client opens session  → status = in_progress
2. Client submits N ops  → ops_submitted += N per op
3. Server reconciles     → ops_applied++ or ops_conflicted++ per op
4. Client pulls results  → session closed
5. On success            → status = completed
6. On error/timeout      → status = failed
```

`client_logical_seq` is the client's Lamport counter value at the time the session was opened — used to determine which server ops the client had not yet seen.

---

## 11. Glossary

| Term | Definition |
|---|---|
| **Op** | A single inventory mutation, the atomic unit of the sync layer |
| **Lamport seq** | A logical clock value, not a timestamp — increases monotonically per user |
| **last_sync_seq** | The highest `logical_seq` the client had when it last successfully pulled from the server |
| **Conflict** | Two ops mutating the same `(storage_id, product_id, stock_id)` concurrently with incompatible semantics |
| **Auto-merge** | Additive ops that can be applied independently without human review |
| **Superseded** | An op that was replaced by another (via edit or merge) — its effect is nullified |
| **Pending** | An op that has been received but not yet applied or flagged |
| **Owner** | The `user_id` on the op — the user who submitted it |
| **SMS op** | An op submitted via SMS using French keywords, parsed by the SMS gateway handler |
