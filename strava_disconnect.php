<?php
include 'db.php';


if (isset($_SESSION['user_id'])) {
    // We also set strava_athlete_id to NULL to completely wipe the connection
    $stmt = $pdo->prepare("
        UPDATE users 
        SET 
            strava_token = NULL, 
            strava_refresh_token = NULL, 
            token_expires = 0,
            strava_athlete_id = NULL
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
}

// Redirect them back to their account page so they can see the change immediately
header("Location: account.php");
exit();
?>