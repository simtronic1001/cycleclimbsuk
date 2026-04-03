<?php
include 'db.php';
include 'config.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$custom_title = "My Account";

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// --- 1. HANDLE PROFILE UPDATE ---
if (isset($_POST['update_profile'])) {
    $new_forename = trim($_POST['forename']);
    $new_surname = trim($_POST['surname']);
    $new_email = trim($_POST['email']);

    // Check if the new email is already taken by ANOTHER user
    $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check_email->execute([$new_email, $user_id]);
    
    if ($check_email->fetch()) {
        $error = "This email address is already in use by another account.";
    } else {
        $update = $pdo->prepare("UPDATE users SET forename = ?, surname = ?, email = ? WHERE id = ?");
        if ($update->execute([$new_forename, $new_surname, $new_email, $user_id])) {
            $message = "Profile updated successfully!";
        } else {
            $error = "Could not update profile. Please try again.";
        }
    }
}

// --- 2. HANDLE ACCOUNT DELETION ---
// --- 2. HANDLE ACCOUNT DELETION ---
if (isset($_POST['delete_account'])) {
    try {
        // Start a database transaction (if one step fails, it safely rolls back)
        $pdo->beginTransaction();

        // 1. Delete all of the user's ticked climbs
        $pdo->prepare("DELETE FROM user_climbs WHERE user_id = ?")->execute([$user_id]);
        
        // 2. Delete any background sync jobs belonging to the user
        $pdo->prepare("DELETE FROM sync_queue WHERE user_id = ?")->execute([$user_id]);
        
        // 3. Delete any webhook events (we wrap this in a silent try/catch just in case)
        try {
            $pdo->prepare("DELETE FROM webhook_queue WHERE user_id = ?")->execute([$user_id]);
        } catch(Exception $e) { /* Ignore if table doesn't exist */ }

        // 4. Finally, delete the user's main account
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);

        // Lock in the changes
        $pdo->commit();

        // Destroy the persistent cookie and session, then redirect
        session_destroy();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        header("Location: index.php?status=deleted");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error deleting account. Please try again or contact support.";
    }
}

// Fetch current user details (ADDED strava_token to this query)
$stmt = $pdo->prepare("SELECT forename, surname, email, dob, strava_token FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Check if they have a Strava connection
$has_strava = !empty($user['strava_token']);

// Calculate age safely
$age = '--';
if (!empty($user['dob']) && $user['dob'] !== '0000-00-00') {
    $dob = new DateTime($user['dob']);
    $today = new DateTime('today');
    $age = $dob->diff($today)->y;
}
?>

<?php include 'header.php'; ?>

<div class="container">
    <div class="profile-box" style="max-width: 600px; margin: 0 auto;">
        <h2 style="border-bottom: 2px solid var(--brand-orange); padding-bottom: 10px; margin-bottom: 20px;">Profile Details</h2>
        
        <?php if ($message): ?>
            <div class="success-msg" style="background: var(--success-bg); color: var(--success-text); padding: 10px; border-radius: 6px; margin-bottom: 15px;"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-msg" style="background: var(--danger-bg); color: var(--danger-text); padding: 10px; border-radius: 6px; margin-bottom: 15px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="account.php">
            <div class="input-wrapper">
                <label>First Name</label>
                <input type="text" name="forename" value="<?php echo htmlspecialchars($user['forename']); ?>" required>
            </div>
            
            <div class="input-wrapper">
                <label>Last Name</label>
                <input type="text" name="surname" value="<?php echo htmlspecialchars($user['surname']); ?>" required>
            </div>
            
            <div class="input-wrapper">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="input-wrapper" style="margin-bottom: 20px;">
                <div style="background: var(--bg-main); padding: 15px; border-radius: 6px; border: 1px solid var(--border-light);">
                    <div style="margin-bottom: 10px;">
                        <label style="font-size: 0.75rem; color: var(--text-muted);">Username (Cannot be changed)</label>
                        <div style="font-weight: bold; color: #666;"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                    </div>
                    <div>
                        <label style="font-size: 0.75rem; color: var(--text-muted);">Date of Birth</label>
                        <div style="font-weight: bold; color: #666;"><?php echo date("d M Y", strtotime($user['dob'])); ?> (Age <?php echo $age; ?>)</div>
                    </div>
                </div>
            </div>

            <button type="submit" name="update_profile" class="strava-btn" style="width: 100%;">
                Save Changes
            </button>
        </form>

        <hr style="border: 0; border-top: 1px solid var(--border-light); margin: 40px 0;">

        <h3 style="color: var(--bg-dark); font-size: 1.2rem; margin-bottom: 15px;">Strava Connection</h3>
        
        <?php if ($has_strava): ?>
            <div class="danger-zone" style="background: rgba(46, 125, 50, 0.05); border-color: var(--success-border); text-align: center; margin-bottom: 40px;">
                <p style="margin-bottom: 15px; color: var(--text-main);">
                    ✅ Your account is currently linked to Strava. We automatically sync your new climbs in the background.
                </p>
                <a href="strava_disconnect.php" class="outline-btn" style="color: var(--danger-text); border-color: var(--danger-border); width: 100%;" onclick="return confirm('Are you sure you want to disconnect from Strava? Your existing climbs will be saved.');">
                    Disconnect from Strava
                </a>
            </div>
        <?php else: ?>
            <div class="text-center" style="padding: 20px; border: 1px solid var(--border-dark); border-radius: 8px; background: var(--bg-alt); margin-bottom: 40px;">
                <p style="margin-bottom: 15px;">You are not currently connected to Strava.</p>
                <a href="<?php echo $strava_auth_url; ?>" class="strava-btn">
    Connect with Strava
</a>
            </div>
        <?php endif; ?>
        <div class="danger-zone">
            <h3 style="color: var(--danger-text); font-size: 1rem;">Danger Zone</h3>
            <form method="POST" onsubmit="return confirm('Permanently delete your account? This cannot be undone.');">
                <button type="submit" name="delete_account" class="outline-btn" style="color: var(--danger-text); border-color: var(--danger-border); width: 100%;">
                    Delete My Account
                </button>
            </form>
        </div>

        <a href="index.php" style="display: block; margin-top: 30px; font-size: 0.9rem; color: var(--brand-orange); text-align: center;">← Back to Dashboard</a>
    </div>
</div>

<?php include 'footer.php'; ?>