<?php
include 'db.php';


// --- 1. THE SECURITY GATE ---
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the logged-in user is actually an admin
$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['is_admin'] != 1) {
    die("Access Denied: You do not have permission to view this page.");
}

$message = '';
$error = '';

// --- 2. HANDLE THE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $segment_id = trim($_POST['segment_id']);
    $distance = $_POST['distance'];
    $elevation_gain = $_POST['elevation_gain'];
    $avg_gradient = $_POST['avg_gradient'];
    $max_gradient = $_POST['max_gradient'];

    // Insert into the 'climbs' table
    $insert = $pdo->prepare("INSERT INTO climbs (name, location, segment_id, distance, elevation_gain, avg_gradient, max_gradient) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if ($insert->execute([$name, $location, $segment_id, $distance, $elevation_gain, $avg_gradient, $max_gradient])) {
        $message = "✅ Climb '$name' added successfully to the $location collection!";
    } else {
        $error = "❌ Error adding climb. Please check the data and try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Admin: Add Climb - Cycle Climbs UK</title>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="auth-box" style="max-width: 600px; margin-top: 50px; text-align: left;">
            <h2>Add New Climb</h2>
            <p style="color: var(--text-muted); margin-bottom: 25px;">
                Enter the official stats for the climb. This information is global and will be shown to all users.
            </p>

            <?php if ($message): ?>
                <div style="background: var(--success-bg); color: var(--success-text); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid var(--success-border);">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="input-wrapper">
                    <label>Climb Name</label>
                    <input type="text" name="name" required placeholder="e.g., Cheddar Gorge">
                </div>

                <div class="input-wrapper">
                    <label>County / Region</label>
                    <input type="text" name="location" required placeholder="e.g., Somerset">
                </div>

                <div class="input-wrapper">
                    <label>Strava Segment ID</label>
                    <input type="text" name="segment_id" required placeholder="e.g., 669095">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="input-wrapper">
                        <label>Distance (km)</label>
                        <input type="number" step="0.01" name="distance" required placeholder="3.50">
                    </div>
                    <div class="input-wrapper">
                        <label>Elevation Gain (m)</label>
                        <input type="number" name="elevation_gain" required placeholder="150">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="input-wrapper">
                        <label>Avg Gradient (%)</label>
                        <input type="number" step="0.1" name="avg_gradient" required placeholder="5.5">
                    </div>
                    <div class="input-wrapper">
                        <label>Max Gradient (%)</label>
                        <input type="number" step="0.1" name="max_gradient" required placeholder="12.0">
                    </div>
                </div>

                <button type="submit" class="strava-btn" style="width: 100%; margin-top: 10px;">
                    Add Climb to Database
                </button>
            </form>

            <a href="index.php" style="display: block; margin-top: 30px; font-size: 0.9rem; color: var(--brand-orange); text-align: center;">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>