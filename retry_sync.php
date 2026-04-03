<?php
include 'db.php';


if (isset($_SESSION['user_id'])) {
    // Reset any failed jobs for this user back to pending, and reset attempt counter to 0
    $stmt = $pdo->prepare("UPDATE sync_queue SET status = 'pending', attempts = 0, error_message = NULL WHERE user_id = ? AND status = 'failed'");
    $stmt->execute([$_SESSION['user_id']]);
}

// Send them back to the dashboard instantly
header("Location: index.php");
exit();
?>