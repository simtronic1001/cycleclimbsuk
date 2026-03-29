<?php
include 'db.php';
session_start();
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET strava_token = NULL, strava_refresh_token = NULL, token_expires = 0 WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
}
header("Location: index.php?status=disconnected");
exit();
?>