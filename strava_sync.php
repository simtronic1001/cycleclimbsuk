<?php
include 'db.php';
include 'config.php'; 


if (!isset($_SESSION['user_id'])) {
    die("Login required.");
}

// 1. Get the user's tokens from the database
$stmt = $pdo->prepare("SELECT strava_token, strava_refresh_token, token_expires FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || empty($user['strava_refresh_token'])) {
    die("❌ Error: No refresh token found. Please go back to the homepage and connect with Strava.");
}

$token = $user['strava_token'];
$refresh_token = $user['strava_refresh_token'];
$expires_at = $user['token_expires'];

// 2. AUTO-REFRESH: Only runs if token is expired (or expires in the next 5 mins)
if (time() > ($expires_at - 300)) { 
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.strava.com/api/v3/oauth/token");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'client_id'     => STRAVA_CLIENT_ID,
        'client_secret' => STRAVA_CLIENT_SECRET,
        'grant_type'    => 'refresh_token',
        'refresh_token' => $refresh_token
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['access_token'])) {
        $token = $data['access_token'];
        $stmt = $pdo->prepare("UPDATE users SET strava_token = ?, strava_refresh_token = ?, token_expires = ? WHERE id = ?");
        $stmt->execute([$token, $data['refresh_token'], $data['expires_at'], $_SESSION['user_id']]);
        echo "✅ Token refreshed automatically.<br><br>";
    } else {
        die("❌ Refresh Failed. Please <a href='strava_disconnect.php'>Disconnect</a> on the homepage and re-connect. Error: " . json_encode($data));
    }
}

// 3. Fetch Climbs from the database
$stmt = $pdo->prepare("SELECT id, name, segment_id FROM climbs WHERE segment_id IS NOT NULL AND segment_id != 0");
$stmt->execute();
$climbs = $stmt->fetchAll();

// Helper function to turn "12:34" into pure seconds
function formatTimeToSeconds($timeInput) {
    if (empty($timeInput)) return 0;
    if (strpos((string)$timeInput, ':') !== false) {
        $parts = explode(':', $timeInput);
        if (count($parts) == 2) {
            return ($parts[0] * 60) + $parts[1]; // MM:SS
        } elseif (count($parts) == 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2]; // HH:MM:SS
        }
    }
    return (int)$timeInput;
}

// 4. The Loop: Check each climb against Strava
foreach ($climbs as $climb) {
    $sid = $climb['segment_id'];
    echo "Checking " . htmlspecialchars($climb['name']) . "... ";

    // Ping Strava
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.strava.com/api/v3/segments/$sid");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    // Process the data
    if (isset($data['id'])) {
        
        // --- 1. EXTRACT DATA FROM STRAVA ---
        $dist = round(($data['distance'] ?? 0) / 1000, 2);
        $grade = $data['average_grade'] ?? 0;
        
        // NEW: Grab the Maximum Gradient and Elevation Gain
        $max_grade = $data['maximum_grade'] ?? 0;
        $elev_gain = $data['total_elevation_gain'] ?? 0;
        
        $kom_raw = $data['xoms']['kom'] ?? $data['kom'] ?? 0;
        $qom_raw = $data['xoms']['qom'] ?? $data['qom'] ?? 0;
        
        $kom = formatTimeToSeconds($kom_raw);
        $qom = formatTimeToSeconds($qom_raw);
        
        $pr = $data['athlete_segment_stats']['pr_elapsed_time'] ?? null;
        $is_done = ($pr !== null && (int)$pr > 0) ? 1 : 0;

        // --- 2. SAVE TO YOUR DATABASE ---
        $update = $pdo->prepare("UPDATE climbs SET 
            distance = ?, 
            avg_gradient = ?, 
            max_gradient = ?, 
            elevation_gain = ?, 
            kom_time = ?, 
            qom_time = ?, 
            user_pr = ?, 
            is_done = ? 
            WHERE id = ?");
            
        $update->execute([
            $dist, 
            $grade, 
            $max_grade, 
            $elev_gain, 
            $kom, 
            $qom, 
            $pr, 
            $is_done, 
            $climb['id']
        ]);

        // If completed, add it to the user_climbs tracking table
        if ($is_done == 1) {
            $check = $pdo->prepare("SELECT id FROM user_climbs WHERE user_id = ? AND climb_id = ?");
            $check->execute([$_SESSION['user_id'], $climb['id']]);
            if (!$check->fetch()) {
                $insert = $pdo->prepare("INSERT INTO user_climbs (user_id, climb_id) VALUES (?, ?)");
                $insert->execute([$_SESSION['user_id'], $climb['id']]);
            }
        }
        echo "✅<br>";
    } else {
        echo "❌ (" . ($data['message'] ?? 'Error') . ")<br>";
    }
    
    // Pause for 0.2 seconds so Strava doesn't block us for requesting too fast
    usleep(200000); 
}

echo "<h3>Sync Complete! <a href='index.php'>Go back to Dashboard</a></h3>";
?>