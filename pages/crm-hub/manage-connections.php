<?php
/**
 * Event Manager - CRM Connection Management
 * 
 * SAFETY: Full CRUD for CRM integrations
 * Manages connections to external CRM systems
 */

require_once __DIR__ . '/../../includes/em_auth.php';
require_once __DIR__ . '/../../includes/em_functions.php';
require_once __DIR__ . '/../../includes/em_db.php';

$pageTitle = 'Manage CRM Connections — Event Manager';
$currentPage = 'crm-manage-connections';

// Get all CRM connections
$connections = [];
try {
    $connections = em_fetch_all("SELECT * FROM em_crm_connections ORDER BY created_at DESC");
} catch (Exception $e) {
    // Silent fail
}

require_once __DIR__ . '/../../includes/em_header.php';
?>

<div class="em-page-header">
    <div>
        <h1 class="em-page-title">Manage CRM Connections</h1>
        <p class="em-page-subtitle">Configure and manage external CRM integrations</p>
    </div>
    <button class="em-btn em-btn-primary" onclick="showConnectionModal()">
        <i class="fas fa-plus"></i> New Connection
    </button>
</div>

<!-- CRM Connections Grid -->
<div class="em-card">
    <?php if (empty($connections)): ?>
        <div class="em-empty-state">
            <i class="fas fa-plug"></i>
            <p>No CRM Connections</p>
            <span>Create your first CRM connection to start syncing data</span>
            <button class="em-btn em-btn-primary" onclick="showConnectionModal()" style="margin-top: 16px;">
                <i class="fas fa-plus"></i> Create Connection
            </button>
        </div>
    <?php else: ?>
        <div class="em-table-container">
            <table class="em-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>CRM Type</th>
                        <th>Status</th>
                        <th>Last Sync</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($connections as $conn): 
                        $statusClass = $conn['status'] === 'active' ? 'em-badge-success' : 'em-badge-secondary';
                    ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($conn['name']) ?></strong>
                            </td>
                            <td>
                                <span class="em-badge em-badge-info">
                                    <?= strtoupper($conn['crm_type']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="em-badge <?= $statusClass ?>">
                                    <?= strtoupper($conn['status']) ?>
                                </span>
                            </td>
                            <td><?= $conn['last_sync_at'] ? em_format_date($conn['last_sync_at']) : 'Never' ?></td>
                            <td><?= em_format_date($conn['created_at']) ?></td>
                            <td>
                                <button class="em-btn-sm em-btn-secondary" onclick="editConnection(<?= (int)$conn['id'] ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a class="em-btn-sm em-btn-secondary" href="<?= em_base_url('pages/crm-hub/connection-config.php?id=' . (int)$conn['id']) ?>">
                                    <i class="fas fa-sliders-h"></i> Configure
                                </a>
                                <button class="em-btn-sm em-btn-primary" onclick="testConnection(<?= $conn['id'] ?>)">
                                    <i class="fas fa-vial"></i> Test
                                </button>
                                <button class="em-btn-sm em-btn-danger" onclick="deleteConnection(<?= $conn['id'] ?>, '<?= htmlspecialchars($conn['name']) ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Connection Modal -->
<div id="connectionModal" class="em-modal" style="display: none;">
    <div class="em-modal-content" style="max-width: 600px;">
        <div class="em-modal-header">
            <h3 id="modalTitle">New CRM Connection</h3>
            <button class="em-modal-close" onclick="closeConnectionModal()">&times;</button>
        </div>
        <div class="em-modal-body">
            <form id="connectionForm">
                <input type="hidden" id="connectionId" name="id">
                
                <div class="em-form-group">
                    <label for="connectionName">Connection Name *</label>
                    <input type="text" id="connectionName" name="name" class="em-form-control" required>
                </div>

                <div class="em-form-group">
                    <label for="crmType">CRM Type *</label>
                    <select id="crmType" name="crm_type" class="em-form-control" required>
                        <option value="">Select CRM Type</option>
                        <option value="salesforce">Salesforce</option>
                        <option value="hubspot">HubSpot</option>
                        <option value="zoho">Zoho CRM</option>
                        <option value="dynamics">Microsoft Dynamics</option>
                        <option value="custom">Custom Webhook</option>
                    </select>
                </div>

                <div class="em-form-group">
                    <label for="apiEndpoint">API Endpoint *</label>
                    <input type="url" id="apiEndpoint" name="api_endpoint" class="em-form-control" placeholder="https://api.example.com" required>
                </div>

                <div class="em-form-group">
                    <label for="apiKey">API Key / Token <span id="apiKeyRequired">*</span></label>
                    <input type="password" id="apiKey" name="api_key" class="em-form-control" autocomplete="new-password">
                    <small class="em-form-text" id="apiKeyHint">Stored encrypted (AES-256-GCM). Leave blank when editing to keep the existing key.</small>
                </div>

                <div class="em-form-group">
                    <label for="connectionStatus">Status</label>
                    <select id="connectionStatus" name="status" class="em-form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="em-form-group">
                    <label>
                        <input type="checkbox" id="autoSync" name="auto_sync">
                        Enable automatic synchronization
                    </label>
                </div>
            </form>
        </div>
        <div class="em-modal-footer">
            <button class="em-btn em-btn-secondary" onclick="closeConnectionModal()">Cancel</button>
            <button class="em-btn em-btn-primary" onclick="saveConnection()">
                <i class="fas fa-save"></i> Save Connection
            </button>
        </div>
    </div>
</div>

<script>
const EM_CSRF = '<?= htmlspecialchars(em_get_csrf_token(), ENT_QUOTES) ?>';
const EM_API_BASE = '<?= base_url('event-manager/api') ?>';
const EM_KEY_MASK = '__EM_KEEP__';
let emEditingId = null;

function showConnectionModal() {
    document.getElementById('connectionForm').reset();
    document.getElementById('connectionId').value = '';
    document.getElementById('modalTitle').textContent = 'New CRM Connection';
    document.getElementById('connectionModal').style.display = 'flex';
}

function closeConnectionModal() {
    document.getElementById('connectionModal').style.display = 'none';
}

function editConnection(id) {
    // Load the connection from the server (secret is never sent to the client).
    fetch(EM_API_BASE + '/crm_connections.php?id=' + encodeURIComponent(id), {
        method: 'GET',
        headers: { 'X-EM-CSRF': EM_CSRF }
    })
    .then(r => r.json())
    .then(result => {
        if (!result.success) {
            alert('Error: ' + (result.error || 'Could not load connection'));
            return;
        }
        const conn = result.connection;
        const config = conn.config || {};
        emEditingId = conn.id;
        document.getElementById('connectionForm').reset();
        document.getElementById('connectionId').value = conn.id;
        document.getElementById('connectionName').value = conn.name;
        document.getElementById('crmType').value = conn.crm_type;
        document.getElementById('apiEndpoint').value = config.api_endpoint || '';
        document.getElementById('apiKey').value = '';
        document.getElementById('apiKey').placeholder = conn.has_api_key ? '\u2022\u2022\u2022\u2022\u2022\u2022 (unchanged)' : '';
        document.getElementById('apiKeyRequired').style.display = conn.has_api_key ? 'none' : 'inline';
        document.getElementById('connectionStatus').value = conn.status;
        document.getElementById('autoSync').checked = !!config.auto_sync;
        document.getElementById('modalTitle').textContent = 'Edit CRM Connection';
        document.getElementById('connectionModal').style.display = 'flex';
    })
    .catch(err => alert('Request failed: ' + err.message));
}

function saveConnection() {
    const form = document.getElementById('connectionForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const id = formData.get('id') || null;
    let apiKey = formData.get('api_key') || '';
    // On edit, an empty key field means "keep the existing key".
    if (id && apiKey === '') {
        apiKey = EM_KEY_MASK;
    }
    const data = {
        id: id,
        em_csrf_token: EM_CSRF,
        name: formData.get('name'),
        crm_type: formData.get('crm_type'),
        status: formData.get('status'),
        config: {
            api_endpoint: formData.get('api_endpoint'),
            api_key: apiKey,
            auto_sync: formData.get('auto_sync') === 'on'
        }
    };

    fetch('<?= base_url('event-manager/api/crm_connections.php') ?>', {
        method: id ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json', 'X-EM-CSRF': EM_CSRF },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert(data.id ? 'Connection updated successfully' : 'Connection created successfully');
            location.reload();
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(err => {
        alert('Request failed: ' + err.message);
    });
}

function testConnection(id) {
    if (!confirm('Test this CRM connection?')) return;
    
    fetch('<?= base_url('event-manager/api/crm_test.php') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-EM-CSRF': EM_CSRF },
        body: JSON.stringify({ connection_id: id, em_csrf_token: EM_CSRF })
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert('✓ Connection test successful!\n\n' + (result.message || ''));
        } else {
            alert('✗ Connection test failed:\n\n' + (result.error || 'Unknown error'));
        }
    })
    .catch(err => {
        alert('Test request failed: ' + err.message);
    });
}

function deleteConnection(id, name) {
    if (!confirm(`Delete connection "${name}"?\n\nThis action cannot be undone.`)) return;
    
    fetch('<?= base_url('event-manager/api/crm_connections.php') ?>', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-EM-CSRF': EM_CSRF },
        body: JSON.stringify({ id: id, em_csrf_token: EM_CSRF })
    })
    .then(r => r.json())
    .then(result => {
        if (result.success) {
            alert('Connection deleted successfully');
            location.reload();
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
        }
    })
    .catch(err => {
        alert('Request failed: ' + err.message);
    });
}

// Close modal on outside click
document.getElementById('connectionModal').addEventListener('click', function(e) {
    if (e.target === this) closeConnectionModal();
});
</script>

<style>
.em-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.em-modal-content {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    max-height: 90vh;
    overflow-y: auto;
}

.em-modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.em-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    color: #1f2937;
}

.em-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}

.em-modal-close:hover {
    background: #f3f4f6;
    color: #1f2937;
}

.em-modal-body {
    padding: 24px;
}

.em-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.em-form-group {
    margin-bottom: 20px;
}

.em-form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.em-form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: border-color 0.2s;
}

.em-form-control:focus {
    outline: none;
    border-color: #3B82F6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.em-form-text {
    display: block;
    margin-top: 4px;
    font-size: 0.85rem;
    color: #6b7280;
}
</style>

<?php require_once __DIR__ . '/../../includes/em_footer.php'; ?>
