<?php
session_start();
include 'db.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Error: You must be logged in to connect Strava.");
}

if (!isset($_GET['code'])) {
    die("Authorization failed. No code received.");
}

// Exchange code for tokens
$ch = curl_init("https://www.strava.com/api/v3/oauth/token");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'client_id'     => STRAVA_CLIENT_ID,
    'client_secret' => STRAVA_CLIENT_SECRET,
    'code'          => $_GET['code'],
    'grant_type'    => 'authorization_code'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

// Developer-friendly error output
if (!isset($response['access_token'])) {
    echo "<h2>❌ Strava OAuth Error</h2>";
    echo "<p>Strava did not return an access token.</p>";
    echo "<p><strong>Raw Response:</strong></p>";
    echo "<pre>" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT)) . "</pre>";
    echo "<p>Recommendation: Check your STRAVA_CLIENT_ID, STRAVA_CLIENT_SECRET, and redirect URI.</p>";
    exit();
}

// Extract athlete ID
$athlete_id = $response['athlete']['id'] ?? null;

if (!$athlete_id) {
    echo "<h2>❌ ERROR: Missing athlete.id in Strava OAuth response</h2>";
    echo "<p>This should never happen unless Strava changed their API.</p>";
    echo "<pre>" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT)) . "</pre>";
    exit();
}

// Update user record
$stmt = $pdo->prepare("
    UPDATE users 
    SET 
        strava_token = ?, 
        strava_refresh_token = ?, 
        token_expires = ?, 
        strava_athlete_id = ?
    WHERE id = ?
");

$stmt->execute([
    $response['access_token'],
    $response['refresh_token'],
    $response['expires_at'],
    $athlete_id,
    $_SESSION['user_id']
]);

header("Location: index.php?status=connected");
exit();
