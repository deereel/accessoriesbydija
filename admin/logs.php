<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

// Restrict access to superadmin only
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
    exit('Access Denied: You do not have permission to access this page.');
}

$page_title = 'Log Viewer';
$active_nav = 'logs';

require_once '../config/database.php';
?>

<?php include '_layout_header.php'; ?>

<style>
.log-container {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 20px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
    max-height: 600px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.log-entry {
    margin-bottom: 10px;
    padding: 8px;
    border-bottom: 1px solid #eee;
}
.log-level-error { color: #dc3545; }
.log-level-warning { color: #ffc107; }
.log-level-info { color: #007bff; }
.log-level-debug { color: #6c757d; }
.controls {
    margin-bottom: 20px;
}
.controls select, .controls button {
    padding: 8px 12px;
    margin-right: 10px;
    border: 1px solid var(--border);
    border-radius: 4px;
    background: var(--card);
}
</style>

<div class="card">
    <div class="card-header"><i class="fas fa-file-alt"></i> Application Logs</div>
    <div class="card-body">
        <p>View application logs for debugging and monitoring purposes.</p>

        <div class="controls">
            <label for="log-file">Select Log File:</label>
            <select id="log-file">
                <option value="app.log">Production Log (app.log)</option>
                <option value="app_dev.log">Development Log (app_dev.log)</option>
            </select>
            <button onclick="loadLogs()">Load Logs</button>
            <button onclick="clearLogs()">Clear Selected Log</button>
        </div>

        <div id="log-container" class="log-container">
            <p>Select a log file and click "Load Logs" to view entries.</p>
        </div>
    </div>
</div>

<script>
function loadLogs() {
    const file = document.getElementById('log-file').value;
    fetch(`/api/get_logs.php?file=${encodeURIComponent(file)}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('log-container');
            if (data.success) {
                container.innerHTML = data.logs.map(entry => {
                    const levelClass = `log-level-${entry.level.toLowerCase()}`;
                    return `<div class="log-entry ${levelClass}">[${entry.timestamp}] ${entry.level}: ${entry.message}</div>`;
                }).join('');
            } else {
                container.innerHTML = `<p style="color: red;">${data.message}</p>`;
            }
        })
        .catch(error => {
            document.getElementById('log-container').innerHTML = '<p style="color: red;">Error loading logs.</p>';
        });
}

function clearLogs() {
    const file = document.getElementById('log-file').value;
    if (confirm(`Are you sure you want to clear the ${file} log file?`)) {
        fetch(`api/clear_logs.php?file=${encodeURIComponent(file)}`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) loadLogs();
            })
            .catch(error => alert('Error clearing logs.'));
    }
}

// Load production logs by default
document.addEventListener('DOMContentLoaded', () => {
    loadLogs();
});
</script>

<?php include '_layout_footer.php'; ?></content>
</xai:function_call name="update_todo_list">
<parameter name="todos">["Create a web-based log viewer page in admin/logs.php that displays application logs","Create API endpoints for fetching and clearing logs","Add a link to the log viewer on the admin settings page"]