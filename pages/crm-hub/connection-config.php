<?php
/**
 * Event Manager - CRM Connection Configuration
 *
 * Per-connection management of field mappings and trigger rules — the wiring
 * that turns a stored connection into active outbound synchronisation.
 *
 * SAFETY: Admin-only (via em_header), all writes go through CSRF-protected APIs.
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'CRM Connection Configuration — Event Manager';
$currentPage = 'crm-manage-connections';

$connectionId = (int)($_GET['id'] ?? 0);
$connection = null;
if ($connectionId > 0) {
    try {
        $connection = em_fetch("SELECT * FROM em_crm_connections WHERE id = ?", [$connectionId]);
    } catch (Exception $e) {
        error_log('EM_CRM connection-config: ' . $e->getMessage());
    }
}

$mappings = [];
$rules = [];
if ($connection) {
    try {
        $mappings = em_fetch_all("SELECT * FROM em_crm_field_mappings WHERE connection_id = ? ORDER BY id ASC", [$connectionId]);
        $rules    = em_fetch_all("SELECT * FROM em_crm_trigger_rules WHERE connection_id = ? ORDER BY id ASC", [$connectionId]);
    } catch (Exception $e) {
        error_log('EM_CRM connection-config load: ' . $e->getMessage());
    }
}

// Canonical event types that the dispatcher can emit.
$eventTypes = [
    'CUSTOMER_CREATED', 'CUSTOMER_UPDATED',
    'ORDER_CREATED', 'ORDER_UPDATED', 'ORDER_CANCELLED',
    'PAYMENT_SUCCESS', 'PAYMENT_FAILED',
    'PRODUCT_CREATED', 'PRODUCT_UPDATED',
    'WEBHOOK_SENT', 'WEBHOOK_FAILED',
];

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <div>
        <h1 class="em-page-title">Connection Configuration</h1>
        <p class="em-page-subtitle">
            <?php if ($connection): ?>
                <?= htmlspecialchars($connection['name']) ?> &middot; <?= htmlspecialchars(strtoupper($connection['crm_type'])) ?>
                &middot; <?= em_status_badge($connection['status']) ?>
            <?php else: ?>
                Select a connection
            <?php endif; ?>
        </p>
    </div>
    <a href="<?= em_base_url('pages/crm-hub/manage-connections.php') ?>" class="em-btn em-btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Connections
    </a>
</div>

<?php if (!$connection): ?>
    <div class="em-card">
        <div class="em-empty-state">
            <i class="fas fa-plug"></i>
            <p>Connection not found</p>
            <span>Open this page from Manage Connections &rarr; Configure.</span>
        </div>
    </div>
<?php else: ?>

<div class="em-card mb-4">
    <div class="em-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-bolt"></i> Trigger Rules</h5>
        <div>
            <button class="em-btn em-btn-primary em-btn-sm" onclick="syncNow()">
                <i class="fas fa-sync-alt"></i> Run Test Sync
            </button>
            <button class="em-btn em-btn-primary em-btn-sm" onclick="showRuleModal()">
                <i class="fas fa-plus"></i> Add Rule
            </button>
        </div>
    </div>
    <div class="em-card-body p-0">
        <div class="em-table-container">
            <table class="em-table">
                <thead>
                    <tr><th>Event</th><th>Condition</th><th>Action</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody id="rulesBody">
                    <?php if (empty($rules)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No trigger rules yet.</td></tr>
                    <?php else: foreach ($rules as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['trigger_event']) ?></strong></td>
                            <td><code class="small"><?= htmlspecialchars($r['condition'] ?? '—') ?></code></td>
                            <td><code class="small"><?= htmlspecialchars($r['action'] ?? '—') ?></code></td>
                            <td><?= em_status_badge($r['status']) ?></td>
                            <td>
                                <button class="em-btn-sm em-btn-secondary"
                                    onclick='editRule(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="em-btn-sm em-btn-danger" onclick="deleteRule(<?= (int)$r['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="em-card">
    <div class="em-card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Field Mappings</h5>
        <button class="em-btn em-btn-primary em-btn-sm" onclick="showMappingModal()">
            <i class="fas fa-plus"></i> Add Mapping
        </button>
    </div>
    <div class="em-card-body p-0">
        <div class="em-table-container">
            <table class="em-table">
                <thead>
                    <tr><th>Local Field</th><th>CRM Field</th><th>Transformation</th><th>Actions</th></tr>
                </thead>
                <tbody id="mappingsBody">
                    <?php if (empty($mappings)): ?>
                        <tr><td colspan="4" class="text-center text-muted">No field mappings (raw payload will be sent).</td></tr>
                    <?php else: foreach ($mappings as $m): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($m['local_field']) ?></code></td>
                            <td><code><?= htmlspecialchars($m['crm_field']) ?></code></td>
                            <td><code class="small"><?= htmlspecialchars($m['transformation'] ?? '—') ?></code></td>
                            <td>
                                <button class="em-btn-sm em-btn-secondary"
                                    onclick='editMapping(<?= json_encode($m, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="em-btn-sm em-btn-danger" onclick="deleteMapping(<?= (int)$m['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Rule Modal -->
<div class="em-modal" id="ruleModal" style="display:none;">
    <div class="em-modal-content">
        <div class="em-modal-header"><h3 id="ruleModalTitle">Add Trigger Rule</h3>
            <button class="em-modal-close" onclick="closeRuleModal()">&times;</button></div>
        <div class="em-modal-body">
            <input type="hidden" id="ruleId">
            <div class="em-form-group">
                <label>Trigger Event *</label>
                <select id="ruleEvent" class="em-form-control">
                    <?php foreach ($eventTypes as $et): ?>
                        <option value="<?= $et ?>"><?= $et ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="em-form-group">
                <label>Condition (JSON, optional)</label>
                <textarea id="ruleCondition" class="em-form-control" rows="3" placeholder='{"field":"status","operator":"eq","value":"paid"}'></textarea>
                <small class="em-form-text">Leave blank to always run. Operators: eq, neq, gt, gte, lt, lte, contains, in, exists, not_empty. Group with "all"/"any".</small>
            </div>
            <div class="em-form-group">
                <label>Action (JSON, optional)</label>
                <textarea id="ruleAction" class="em-form-control" rows="3" placeholder='{"method":"POST","path":"/leads"}'></textarea>
                <small class="em-form-text">method (POST/PUT/PATCH), path (appended to endpoint), static (extra fields).</small>
            </div>
            <div class="em-form-group">
                <label>Status</label>
                <select id="ruleStatus" class="em-form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div class="em-modal-footer">
            <button class="em-btn em-btn-secondary" onclick="closeRuleModal()">Cancel</button>
            <button class="em-btn em-btn-primary" onclick="saveRule()"><i class="fas fa-save"></i> Save Rule</button>
        </div>
    </div>
</div>

<!-- Mapping Modal -->
<div class="em-modal" id="mappingModal" style="display:none;">
    <div class="em-modal-content">
        <div class="em-modal-header"><h3 id="mappingModalTitle">Add Field Mapping</h3>
            <button class="em-modal-close" onclick="closeMappingModal()">&times;</button></div>
        <div class="em-modal-body">
            <input type="hidden" id="mappingId">
            <div class="em-form-group">
                <label>Local Field *</label>
                <input type="text" id="mapLocal" class="em-form-control" placeholder="customer.email">
                <small class="em-form-text">Supports dot notation for nested payload fields.</small>
            </div>
            <div class="em-form-group">
                <label>CRM Field *</label>
                <input type="text" id="mapCrm" class="em-form-control" placeholder="Email">
            </div>
            <div class="em-form-group">
                <label>Transformation (JSON, optional)</label>
                <textarea id="mapTransform" class="em-form-control" rows="3" placeholder='{"type":"uppercase"}'></textarea>
                <small class="em-form-text">Types: uppercase, lowercase, trim, capitalize, date, concat, static, default, prefix, suffix, number_format, map, boolean.</small>
            </div>
        </div>
        <div class="em-modal-footer">
            <button class="em-btn em-btn-secondary" onclick="closeMappingModal()">Cancel</button>
            <button class="em-btn em-btn-primary" onclick="saveMapping()"><i class="fas fa-save"></i> Save Mapping</button>
        </div>
    </div>
</div>

<script>
const EM_CSRF = '<?= htmlspecialchars(em_get_csrf_token(), ENT_QUOTES) ?>';
const EM_API  = '<?= base_url('event-manager/api') ?>';
const CONNECTION_ID = <?= (int)$connectionId ?>;

function emPost(url, method, data) {
    return fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json', 'X-EM-CSRF': EM_CSRF },
        body: JSON.stringify(Object.assign({ em_csrf_token: EM_CSRF }, data))
    }).then(r => r.json());
}

function validJsonOrNull(str, label) {
    str = (str || '').trim();
    if (str === '') return null;
    try { return JSON.parse(str); }
    catch (e) { alert(label + ' must be valid JSON.'); throw e; }
}

/* ---- Trigger Rules ---- */
function showRuleModal() {
    document.getElementById('ruleId').value = '';
    document.getElementById('ruleEvent').selectedIndex = 0;
    document.getElementById('ruleCondition').value = '';
    document.getElementById('ruleAction').value = '';
    document.getElementById('ruleStatus').value = 'active';
    document.getElementById('ruleModalTitle').textContent = 'Add Trigger Rule';
    document.getElementById('ruleModal').style.display = 'flex';
}
function closeRuleModal() { document.getElementById('ruleModal').style.display = 'none'; }
function editRule(r) {
    document.getElementById('ruleId').value = r.id;
    document.getElementById('ruleEvent').value = r.trigger_event;
    document.getElementById('ruleCondition').value = r.condition || '';
    document.getElementById('ruleAction').value = r.action || '';
    document.getElementById('ruleStatus').value = r.status;
    document.getElementById('ruleModalTitle').textContent = 'Edit Trigger Rule';
    document.getElementById('ruleModal').style.display = 'flex';
}
function saveRule() {
    let condition, action;
    try {
        condition = validJsonOrNull(document.getElementById('ruleCondition').value, 'Condition');
        action = validJsonOrNull(document.getElementById('ruleAction').value, 'Action');
    } catch (e) { return; }
    const id = document.getElementById('ruleId').value;
    const data = {
        id: id || undefined,
        connection_id: CONNECTION_ID,
        trigger_event: document.getElementById('ruleEvent').value,
        condition: condition,
        action: action,
        status: document.getElementById('ruleStatus').value
    };
    emPost(EM_API + '/crm_trigger_rules.php', id ? 'PUT' : 'POST', data)
        .then(res => res.success ? location.reload() : alert('Error: ' + res.error))
        .catch(err => alert('Request failed: ' + err.message));
}
function deleteRule(id) {
    if (!confirm('Delete this trigger rule?')) return;
    emPost(EM_API + '/crm_trigger_rules.php', 'DELETE', { id: id })
        .then(res => res.success ? location.reload() : alert('Error: ' + res.error));
}

/* ---- Field Mappings ---- */
function showMappingModal() {
    document.getElementById('mappingId').value = '';
    document.getElementById('mapLocal').value = '';
    document.getElementById('mapCrm').value = '';
    document.getElementById('mapTransform').value = '';
    document.getElementById('mappingModalTitle').textContent = 'Add Field Mapping';
    document.getElementById('mappingModal').style.display = 'flex';
}
function closeMappingModal() { document.getElementById('mappingModal').style.display = 'none'; }
function editMapping(m) {
    document.getElementById('mappingId').value = m.id;
    document.getElementById('mapLocal').value = m.local_field;
    document.getElementById('mapCrm').value = m.crm_field;
    document.getElementById('mapTransform').value = m.transformation || '';
    document.getElementById('mappingModalTitle').textContent = 'Edit Field Mapping';
    document.getElementById('mappingModal').style.display = 'flex';
}
function saveMapping() {
    let transform;
    try { transform = validJsonOrNull(document.getElementById('mapTransform').value, 'Transformation'); }
    catch (e) { return; }
    const id = document.getElementById('mappingId').value;
    const data = {
        id: id || undefined,
        connection_id: CONNECTION_ID,
        local_field: document.getElementById('mapLocal').value.trim(),
        crm_field: document.getElementById('mapCrm').value.trim(),
        transformation: transform
    };
    if (!data.local_field || !data.crm_field) { alert('Local field and CRM field are required.'); return; }
    emPost(EM_API + '/crm_field_mappings.php', id ? 'PUT' : 'POST', data)
        .then(res => res.success ? location.reload() : alert('Error: ' + res.error))
        .catch(err => alert('Request failed: ' + err.message));
}
function deleteMapping(id) {
    if (!confirm('Delete this field mapping?')) return;
    emPost(EM_API + '/crm_field_mappings.php', 'DELETE', { id: id })
        .then(res => res.success ? location.reload() : alert('Error: ' + res.error));
}

/* ---- Manual test sync ---- */
function syncNow() {
    const evt = prompt('Event type to simulate:', document.getElementById('ruleEvent') ? document.getElementById('ruleEvent').value : 'ORDER_CREATED');
    if (!evt) return;
    const sample = prompt('Sample payload (JSON):', '{"id":1,"status":"paid","email":"test@example.com"}');
    if (sample === null) return;
    let payload;
    try { payload = JSON.parse(sample); } catch (e) { alert('Invalid JSON payload.'); return; }
    emPost(EM_API + '/crm_sync_run.php', 'POST', { connection_id: CONNECTION_ID, event_type: evt, payload: payload })
        .then(res => {
            if (res.success) { alert('Sync result: ' + res.status + '\n' + (res.message || '')); }
            else { alert('Sync failed: ' + (res.error || res.message || 'unknown')); }
        })
        .catch(err => alert('Request failed: ' + err.message));
}

document.querySelectorAll('.em-modal').forEach(function (modal) {
    modal.addEventListener('click', function (e) { if (e.target === this) this.style.display = 'none'; });
});
</script>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
