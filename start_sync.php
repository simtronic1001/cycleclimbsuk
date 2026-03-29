<?php
include 'db.php';
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Login required.");
}

// 1. Get user tokens
$stmt = $pdo->prepare("SELECT strava_token, strava_refresh_token, token_expires FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || empty($user['strava_refresh_token'])) {
    die("❌ No Strava connection found.");
}

$token = $user['strava_token'];
$refresh_token = $user['strava_refresh_token'];
$expires_at = $user['token_expires'];

// 2. Refresh token if needed
if (time() > ($expires_at - 300)) {
    $ch = curl_init("https://www.strava.com/api/v3/oauth/token");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id'     => STRAVA_CLIENT_ID,
        'client_secret' => STRAVA_CLIENT_SECRET,
        'grant_type'    => 'refresh_token',
        'refresh_token' => $refresh_token
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!isset($data['access_token'])) {
        die("❌ Token refresh failed.");
    }

    $token = $data['access_token'];

    $stmt = $pdo->prepare("UPDATE users SET strava_token=?, strava_refresh_token=?, token_expires=? WHERE id=?");
    $stmt->execute([$token, $data['refresh_token'], $data['expires_at'], $_SESSION['user_id']]);
}

// 3. Fetch ALL user activities (Strava paginated)
$page = 1;
$allActivities = [];

do {
    $ch = curl_init("https://www.strava.com/api/v3/athlete/activities?page=$page&per_page=200");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $activities = json_decode($response, true);
    curl_close($ch);

    if (!is_array($activities) || count($activities) == 0) break;

    $allActivities = array_merge($allActivities, $activities);
    $page++;

} while (count($activities) == 200);

// 4. Insert into sync_queue
$insert = $pdo->prepare("INSERT INTO sync_queue (user_id, activity_id) VALUES (?, ?)");

foreach ($allActivities as $act) {
    $insert->execute([$_SESSION['user_id'], $act['id']]);
}

echo "<h3>Sync started!</h3>";
echo "We are processing your activities in the background.<br>";
echo "This may take 2–3 hours depending on your ride history.<br><br>";
echo "<a href='index.php'>Return to Dashboard</a>";
