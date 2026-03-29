<?php
include 'db.php';
include 'config.php';

/**
 * Helper: log to a file for debugging
 */
function log_line($msg) {
    file_put_contents(
        'webhook_worker_log.txt',
        date('Y-m-d H:i:s') . ' - ' . $msg . "\n",
        FILE_APPEND
    );
}

log_line("=== Webhook queue worker started ===");

// 1. Fetch pending webhook events
try {
    $stmt = $pdo->prepare("
        SELECT * FROM webhook_queue
        WHERE status = 'pending'
        ORDER BY created_at ASC
        LIMIT 10
    ");
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    log_line("❌ ERROR: Failed to fetch webhook_queue rows: " . $e->getMessage());
    echo "❌ ERROR: Could not read webhook_queue. Recommendation: Check DB connection and table structure.\n";
    exit;
}

if (!$events) {
    log_line("No pending webhook events.");
    echo "No pending webhook events.\n";
    exit;
}

// Preload climbs: segment_id => climb_id
try {
    $climbsStmt = $pdo->query("
        SELECT id, segment_id 
        FROM climbs 
        WHERE segment_id IS NOT NULL AND segment_id != 0
    ");
    $climbs = $climbsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    log_line("❌ ERROR: Failed to load climbs: " . $e->getMessage());
    echo "❌ ERROR: Could not load climbs. Recommendation: Check climbs table and DB permissions.\n";
    exit;
}

foreach ($events as $event) {

    $queue_id    = $event['id'];
    $strava_uid  = $event['user_id'];     // Strava athlete ID
    $activity_id = $event['activity_id'];
    $event_type  = $event['event_type'];  // create / update / delete

    log_line("Processing webhook_queue #$queue_id (activity $activity_id, type $event_type)");

    // Mark as processing
    $pdo->prepare("UPDATE webhook_queue SET status='processing' WHERE id=?")
        ->execute([$queue_id]);

    // 2. Map Strava athlete (owner_id) to local user
    try {
        $u = $pdo->prepare("SELECT id, strava_token, strava_refresh_token, token_expires, strava_athlete_id 
                            FROM users 
                            WHERE strava_athlete_id = ?");
        $u->execute([$strava_uid]);
        $user = $u->fetch();
    } catch (Exception $e) {
        log_line("❌ ERROR: Failed to query users for Strava athlete $strava_uid: " . $e->getMessage());
        $pdo->prepare("UPDATE webhook_queue SET status='error' WHERE id=?")->execute([$queue_id]);
        echo "❌ ERROR: Could not map Strava athlete to local user. Recommendation: Ensure users.strava_athlete_id is populated.\n";
        continue;
    }

    if (!$user) {
        log_line("❌ ERROR: No local user found for Strava athlete $strava_uid");
        $pdo->prepare("UPDATE webhook_queue SET status='error' WHERE id=?")->execute([$queue_id]);
        echo "❌ ERROR: No local user for Strava athlete $strava_uid. Recommendation: Store athlete_id at OAuth callback and backfill existing users.\n";
        continue;
    }

    $user_id   = $user['id'];
    $token     = $user['strava_token'];
    $refresh   = $user['strava_refresh_token'];
    $expires   = $user['token_expires'];

    // 3. Handle delete events quickly
    if ($event_type === 'delete') {
        // We don't know which climb(s) were affected, so we can't perfectly undo.
        // For now, we just log it and mark complete.
        log_line("Delete event for activity $activity_id (user $user_id). No PR rollback implemented.");
        $pdo->prepare("UPDATE webhook_queue SET status='complete' WHERE id=?")->execute([$queue_id]);
        continue;
    }

    // 4. Refresh token if needed
    if (time() > ($expires - 300)) {
        log_line("Refreshing token for user $user_id...");

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
            log_line("❌ ERROR: Token refresh failed for user $user_id: " . json_encode($data));
            $pdo->prepare("UPDATE webhook_queue SET status='error' WHERE id=?")->execute([$queue_id]);
            echo "❌ ERROR: Token refresh failed for user $user_id. Recommendation: User may need to reconnect Strava.\n";
            continue;
        }

        $token = $data['access_token'];

        $upd = $pdo->prepare("UPDATE users SET strava_token=?, strava_refresh_token=?, token_expires=? WHERE id=?");
        $upd->execute([$token, $data['refresh_token'], $data['expires_at'], $user_id]);
    }

    // 5. Fetch activity with segment efforts
    $url = "https://www.strava.com/api/v3/activities/$activity_id?include_all_efforts=true";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $activity = json_decode($response, true);
    curl_close($ch);

    if ($httpCode === 404) {
        log_line("Activity $activity_id not found (404). Possibly deleted or private.");
        $pdo->prepare("UPDATE webhook_queue SET status='complete' WHERE id=?")->execute([$queue_id]);
        continue;
    }

    if (!isset($activity['id'])) {
        log_line("❌ ERROR: Failed to fetch activity $activity_id for user $user_id. HTTP $httpCode. Response: $response");
        $pdo->prepare("UPDATE webhook_queue SET status='error' WHERE id=?")->execute([$queue_id]);
        echo "❌ ERROR: Could not fetch activity $activity_id (HTTP $httpCode). Recommendation: Check Strava API status, token validity, and rate limits.\n";
        continue;
    }

    if (!isset($activity['segment_efforts']) || !is_array($activity['segment_efforts'])) {
        log_line("Activity $activity_id has no segment_efforts.");
        $pdo->prepare("UPDATE webhook_queue SET status='complete' WHERE id=?")->execute([$queue_id]);
        continue;
    }

    // 6. Process segment efforts
    foreach ($activity['segment_efforts'] as $effort) {

        if (!isset($effort['segment']['id']) || !isset($effort['elapsed_time'])) {
            continue;
        }

        $segment_id = $effort['segment']['id'];
        $elapsed    = $effort['elapsed_time'];

        if (!isset($climbs[$segment_id])) {
            // Not one of our climbs
            continue;
        }

        $climb_id = $climbs[$segment_id];

        // Check existing PR
        $check = $pdo->prepare("SELECT user_pr FROM user_climbs WHERE user_id=? AND climb_id=?");
        $check->execute([$user_id, $climb_id]);
        $existing = $check->fetchColumn();

        if ($existing === false) {
            // First time completion
            $ins = $pdo->prepare("INSERT INTO user_climbs (user_id, climb_id, user_pr) VALUES (?, ?, ?)");
            $ins->execute([$user_id, $climb_id, $elapsed]);
            log_line("New climb completion: user $user_id, climb $climb_id, time $elapsed");
        } else {
            // Update PR if faster
            if ($elapsed < $existing) {
                $upd = $pdo->prepare("UPDATE user_climbs SET user_pr=? WHERE user_id=? AND climb_id=?");
                $upd->execute([$elapsed, $user_id, $climb_id]);
                log_line("PR improved: user $user_id, climb $climb_id, old $existing, new $elapsed");
            }
        }
    }

    // 7. Mark event complete
    $pdo->prepare("UPDATE webhook_queue SET status='complete' WHERE id=?")->execute([$queue_id]);
    log_line("Webhook_queue #$queue_id processed successfully.");
}

log_line("=== Webhook queue worker finished ===");
echo "Webhook queue processing complete.\n";
