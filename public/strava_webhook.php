<?php
// Load environment + DB
require_once __DIR__ . '/../env.php';
require_once __DIR__ . '/../db.php';

// Log helper
function log_msg($msg) {
    file_put_contents(__DIR__ . '/../strava_webhook.log', date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

// Handle Strava verification (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['hub_challenge']) && isset($_GET['hub_verify_token'])) {
        if ($_GET['hub_verify_token'] === env('STRAVA_WEBHOOK_VERIFY_TOKEN')) {
            echo json_encode(['hub.challenge' => $_GET['hub_challenge']]);
            exit;
        }
    }
    http_response_code(403);
    exit;
}

// Handle Strava event (POST)
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    log_msg("Invalid JSON received");
    http_response_code(400);
    exit;
}

log_msg("Webhook received: " . json_encode($data));

// Only process activity create/update
if (!isset($data['object_type']) || $data['object_type'] !== 'activity') {
    log_msg("Ignoring non-activity event");
    http_response_code(200);
    exit;
}

$userId = $data['owner_id'];
$activityId = $data['object_id'];

// Fetch user tokens
$stmt = $pdo->prepare("SELECT id, strava_refresh_token FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    log_msg("User not found: $userId");
    http_response_code(200);
    exit;
}

// Refresh token
$clientId = env('STRAVA_CLIENT_ID');
$clientSecret = env('STRAVA_CLIENT_SECRET');

$refreshPayload = [
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'grant_type'    => 'refresh_token',
    'refresh_token' => $user['strava_refresh_token']
];

$ch = curl_init("https://www.strava.com/oauth/token");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($refreshPayload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);

if (!isset($tokenData['access_token'])) {
    log_msg("Token refresh failed: " . $response);
    http_response_code(200);
    exit;
}

// Save new refresh token
$stmt = $pdo->prepare("UPDATE users SET strava_refresh_token = ? WHERE id = ?");
$stmt->execute([$tokenData['refresh_token'], $userId]);

$accessToken = $tokenData['access_token'];

// Fetch activity details
$ch = curl_init("https://www.strava.com/api/v3/activities/$activityId?include_all_efforts=true");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$activityResponse = curl_exec($ch);
curl_close($ch);

$activity = json_decode($activityResponse, true);

if (!isset($activity['segment_efforts'])) {
    log_msg("No segment efforts found for activity $activityId");
    http_response_code(200);
    exit;
}

// Process each segment effort
foreach ($activity['segment_efforts'] as $effort) {
    $segmentId = $effort['segment']['id'];
    $elapsed = $effort['elapsed_time'];
    $completedAt = date('Y-m-d H:i:s', strtotime($effort['start_date_local']));

    // Match climb
    $stmt = $pdo->prepare("SELECT id FROM climbs WHERE segment_id = ?");
    $stmt->execute([$segmentId]);
    $climb = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$climb) {
        continue;
    }

    $climbId = $climb['id'];

    // Prevent duplicates
    $stmt = $pdo->prepare("SELECT id FROM user_climbs WHERE user_id = ? AND climb_id = ? AND completed_at = ?");
    $stmt->execute([$userId, $climbId, $completedAt]);
    if ($stmt->fetch()) {
        log_msg("Duplicate ignored for user $userId climb $climbId");
        continue;
    }

    // Get existing PR
    $stmt = $pdo->prepare("SELECT MIN(user_pr) AS pr FROM user_climbs WHERE user_id = ? AND climb_id = ?");
    $stmt->execute([$userId, $climbId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $oldPR = $row['pr'] ?? null;

    // Determine new PR
    if ($oldPR === null || $elapsed < $oldPR) {
        $newPR = $elapsed;
    } else {
        $newPR = $oldPR;
    }

    // Insert climb record
    $stmt = $pdo->prepare("
        INSERT INTO user_climbs (user_id, climb_id, completed_at, source, user_pr)
        VALUES (?, ?, ?, 'strava', ?)
    ");
    $stmt->execute([$userId, $climbId, $completedAt, $newPR]);

    log_msg("Inserted climb for user $userId climb $climbId PR=$newPR");
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
exit;
