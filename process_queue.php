<?php
include 'db.php';
include 'config.php';

// 1. Fetch next 5 pending jobs that haven't failed 3 times
$stmt = $pdo->prepare("
    SELECT * FROM sync_queue 
    WHERE status = 'pending' AND attempts < 3
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

foreach ($jobs as $job) {
    $queue_id = $job['id'];
    $user_id  = $job['user_id'];
    $activity_id = $job['activity_id'];
    $current_attempts = $job['attempts'];

    echo "Processing job #$queue_id (Activity $activity_id)\n";

    try {
        // Fetch user token
        $u = $pdo->prepare("SELECT strava_token, strava_refresh_token, token_expires FROM users WHERE id = ?");
        $u->execute([$user_id]);
        $user = $u->fetch();

        if (!$user || empty($user['strava_token'])) {
            throw new Exception("User token missing or user disconnected.");
        }

        $token = $user['strava_token'];
        
        // Refresh token if expired
        if (time() > ($user['token_expires'] - 300)) {
            $ch = curl_init("https://www.strava.com/api/v3/oauth/token");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'client_id'     => STRAVA_CLIENT_ID,
                'client_secret' => STRAVA_CLIENT_SECRET,
                'grant_type'    => 'refresh_token',
                'refresh_token' => $user['strava_refresh_token']
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $refresh = json_decode(curl_exec($ch), true);
            curl_close($ch);
            
            if (isset($refresh['access_token'])) {
                $token = $refresh['access_token'];
                $pdo->prepare("UPDATE users SET strava_token=?, strava_refresh_token=?, token_expires=? WHERE id=?")
                    ->execute([$token, $refresh['refresh_token'], $refresh['expires_at'], $user_id]);
            } else {
                throw new Exception("Failed to refresh Strava token.");
            }
        }

        // Fetch Activity from Strava
        $ch = curl_init("https://www.strava.com/api/v3/activities/$activity_id?include_all_efforts=true");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $activity = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || isset($activity['errors'])) {
            throw new Exception("Strava API Error: HTTP " . $http_code);
        }

        // Process Segments
        if (isset($activity['segment_efforts'])) {
            foreach ($activity['segment_efforts'] as $effort) {
                $segment_id = $effort['segment']['id'];
                $elapsed = $effort['elapsed_time'];

                if (isset($climbs[$segment_id])) {
                    $climb_id = $climbs[$segment_id];

                    $check = $pdo->prepare("SELECT user_pr FROM user_climbs WHERE user_id=? AND climb_id=?");
                    $check->execute([$user_id, $climb_id]);
                    $existing = $check->fetchColumn();

                    if ($existing === false) {
                        $ins = $pdo->prepare("INSERT INTO user_climbs (user_id, climb_id, user_pr) VALUES (?, ?, ?)");
                        $ins->execute([$user_id, $climb_id, $elapsed]);
                    } elseif ($elapsed < $existing) {
                        $upd = $pdo->prepare("UPDATE user_climbs SET user_pr=? WHERE user_id=? AND climb_id=?");
                        $upd->execute([$elapsed, $user_id, $climb_id]);
                    }
                }
            }
        }

        // Mark as Complete
        $pdo->prepare("UPDATE sync_queue SET status='complete', error_message=NULL WHERE id=?")->execute([$queue_id]);

    } catch (Exception $e) {
        $error_msg = $e->getMessage();
        $new_attempts = $current_attempts + 1;
        $new_status = ($new_attempts >= 3) ? 'failed' : 'pending';

        // Update queue with error data
        $pdo->prepare("UPDATE sync_queue SET status=?, attempts=?, error_message=?, last_attempt=NOW() WHERE id=?")
            ->execute([$new_status, $new_attempts, $error_msg, $queue_id]);

        // Email admin if it permanently failed
        if ($new_status === 'failed') {
            $admin_email = "admin@cycleclimbsuk.simtech.site"; // Update to your real email
            $subject = "Cycle Climbs UK: Sync Error Alert";
            $message = "A background sync job failed 3 times and has been suspended.\n\nUser ID: $user_id\nActivity ID: $activity_id\nError: $error_msg\n\nThe user has been prompted to 'Try Again' via their dashboard.";
            @mail($admin_email, $subject, $message);
        }
        
        echo "Job Failed: " . $error_msg . "\n";
    }
}
?>