<?php
include 'db.php';
include 'config.php';

// 1. Fetch next 5 pending jobs
$stmt = $pdo->prepare("
    SELECT * FROM sync_queue 
    WHERE status = 'pending' 
    ORDER BY created_at ASC 
    LIMIT 5
");
$stmt->execute();
$jobs = $stmt->fetchAll();

if (!$jobs) {
    echo "No jobs to process.\n";
    exit;
}

// Preload climbs into memory for fast matching
$climbsStmt = $pdo->query("SELECT id, segment_id FROM climbs WHERE segment_id IS NOT NULL AND segment_id != 0");
$climbs = $climbsStmt->fetchAll(PDO::FETCH_KEY_PAIR); 
// $climbs[segment_id] = climb_id

foreach ($jobs as $job) {

    $queue_id = $job['id'];
    $user_id  = $job['user_id'];
    $activity_id = $job['activity_id'];

    echo "Processing job #$queue_id (Activity $activity_id)\n";

    // 2. Fetch user token
    $u = $pdo->prepare("SELECT strava_token, strava_refresh_token, token_expires FROM users WHERE id = ?");
    $u->execute([$user_id]);
    $user = $u->fetch();

    if (!$user) {
        echo "User not found.\n";
        continue;
    }

    $token = $user['strava_token'];
    $refresh = $user['strava_refresh_token'];
    $expires = $user['token_expires'];

    // 3. Refresh token if needed
    if (time() > ($expires - 300)) {
        echo "Refreshing token...\n";

        $ch = curl_init("https://www.strava.com/api/v3/oauth/token");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id'     => STRAVA_CLIENT_ID,
            'client_secret' => STRAVA_CLIENT_SECRET,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refresh
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!isset($data['access_token'])) {
            echo "Token refresh failed.\n";
            continue;
        }

        $token = $data['access_token'];

        $upd = $pdo->prepare("UPDATE users SET strava_token=?, strava_refresh_token=?, token_expires=? WHERE id=?");
        $upd->execute([$token, $data['refresh_token'], $data['expires_at'], $user_id]);
    }

    // 4. Fetch activity with segment efforts
    $url = "https://www.strava.com/api/v3/activities/$activity_id?include_all_efforts=true";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $activity = json_decode($response, true);
    curl_close($ch);

    if (!isset($activity['id'])) {
        echo "Activity fetch failed.\n";
        continue;
    }

    // 5. Process segment efforts
    if (!isset($activity['segment_efforts'])) {
        echo "No segment efforts.\n";
        // Mark as complete anyway
        $pdo->prepare("UPDATE sync_queue SET status='complete' WHERE id=?")->execute([$queue_id]);
        continue;
    }

    foreach ($activity['segment_efforts'] as $effort) {

        $segment_id = $effort['segment']['id'];
        $elapsed = $effort['elapsed_time'];

        // Does this segment match one of our climbs?
        if (!isset($climbs[$segment_id])) {
            continue;
        }

        $climb_id = $climbs[$segment_id];

        // Check if user already has a PR recorded
        $check = $pdo->prepare("SELECT user_pr FROM user_climbs WHERE user_id=? AND climb_id=?");
        $check->execute([$user_id, $climb_id]);
        $existing = $check->fetchColumn();

        if ($existing === false) {
            // First time completing this climb
            $ins = $pdo->prepare("INSERT INTO user_climbs (user_id, climb_id, user_pr) VALUES (?, ?, ?)");
            $ins->execute([$user_id, $climb_id, $elapsed]);
        } else {
            // Update PR if faster
            if ($elapsed < $existing) {
                $upd = $pdo->prepare("UPDATE user_climbs SET user_pr=? WHERE user_id=? AND climb_id=?");
                $upd->execute([$elapsed, $user_id, $climb_id]);
            }
        }
    }

    // 6. Mark queue item complete
    $pdo->prepare("UPDATE sync_queue SET status='complete' WHERE id=?")->execute([$queue_id]);

    echo "Job #$queue_id complete.\n";
}

echo "Done.\n";
