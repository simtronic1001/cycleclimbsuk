<?php
include 'db.php';
session_start();

// Redirect to login if they try to click without being logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['id'])) {
    $climb_id = $_POST['id'];
    $user_id = $_SESSION['user_id'];

    // 1. Check if this user has ALREADY completed this climb
    $check = $pdo->prepare("SELECT id FROM user_climbs WHERE user_id = ? AND climb_id = ?");
    $check->execute([$user_id, $climb_id]);

    if ($check->rowCount() > 0) {
        // 2. If it exists, they are "un-ticking" it, so DELETE the record
        $sql = "DELETE FROM user_climbs WHERE user_id = ? AND climb_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $climb_id]);
    } else {
        // 3. If it doesn't exist, they are "ticking" it, so INSERT a record
        $sql = "INSERT INTO user_climbs (user_id, climb_id) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $climb_id]);
    }
}

header("Location: index.php");
exit();