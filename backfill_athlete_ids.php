<?php
include 'db.php';
include 'config.php';

echo "<h2>Backfilling Strava Athlete IDs</h2>";

$stmt = $pdo->query("
    SELECT id, strava_token 
    FROM users 
    WHERE strava_token IS NOT NULL 
      AND strava_athlete_id IS NULL
");

$users = $stmt->fetchAll();

if (!$users) {
    echo "No users need backfilling.";
    exit();
}

foreach ($users as $u) {

    $user_id = $u['id'];
    $token   = $u['strava_token'];

    echo "<p>Fetching athlete profile for user $user_id...</p>";

    $ch = curl_init("https://www.strava.com/api/v3/athlete");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!isset($response['id'])) {
        echo "<p style='color:red;'>❌ Failed to fetch athlete profile for user $user_id</p>";
        continue;
    }

    $athlete_id = $response['id'];

    $upd = $pdo->prepare("UPDATE users SET strava_athlete_id=? WHERE id=?");
    $upd->execute([$athlete_id, $user_id]);

    echo "<p style='color:green;'>✔ Updated user $user_id → athlete_id $athlete_id</p>";
}

echo "<h3>Backfill complete.</h3>";
