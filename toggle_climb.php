<?php
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['climb_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];
$climb_id = (int)$_POST['climb_id'];

// Check if it's already marked as done
$stmt = $pdo->prepare("SELECT id FROM user_climbs WHERE user_id = ? AND climb_id = ?");
$stmt->execute([$user_id, $climb_id]);
$record = $stmt->fetch();

if ($record) {
    // If it exists, the user is "un-ticking" it
    $delete = $pdo->prepare("DELETE FROM user_climbs WHERE user_id = ? AND climb_id = ?");
    $delete->execute([$user_id, $climb_id]);
    echo "unmarked";
} else {
    // If it doesn't exist, mark it as done
    $insert = $pdo->prepare("INSERT INTO user_climbs (user_id, climb_id) VALUES (?, ?)");
    $insert->execute([$user_id, $climb_id]);
    echo "marked";
}
?>