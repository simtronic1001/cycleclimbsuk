<?php
include 'db.php';

echo "<h2>Webhook Queue Debug</h2>";

$rows = $pdo->query("
    SELECT * 
    FROM webhook_queue 
    ORDER BY created_at DESC 
    LIMIT 50
")->fetchAll();

echo "<table border='1' cellpadding='6'>
<tr>
    <th>ID</th>
    <th>User</th>
    <th>Activity</th>
    <th>Type</th>
    <th>Status</th>
    <th>Created</th>
</tr>";

foreach ($rows as $r) {
    echo "<tr>
        <td>{$r['id']}</td>
        <td>{$r['user_id']}</td>
        <td>{$r['activity_id']}</td>
        <td>{$r['event_type']}</td>
        <td>{$r['status']}</td>
        <td>{$r['created_at']}</td>
    </tr>";
}

echo "</table>";

echo "<h2>Webhook Worker Log</h2>";

if (file_exists("webhook_worker_log.txt")) {
    echo "<pre style='background:#eee;padding:10px;max-height:400px;overflow:auto;'>";
    echo htmlspecialchars(file_get_contents("webhook_worker_log.txt"));
    echo "</pre>";
} else {
    echo "<p>No log file found.</p>";
}
