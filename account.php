<?php
include 'db.php';
session_start();

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
if (isset($_POST['delete_account'])) {
    $delete_progress = $pdo->prepare("DELETE FROM user_climbs WHERE user_id = ?");
    $delete_progress->execute([$user_id]);

    $delete_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($delete_user->execute([$user_id])) {
        session_destroy();
        header("Location: register.php?account_deleted=true");
        exit();
    }
}

// --- 3. FETCH USER DETAILS ---
$stmt = $pdo->prepare("SELECT forename, surname, username, email, dob FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$age = (new DateTime($user['dob']))->diff(new DateTime('today'))->y;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>My Account - Cycle Climbs UK</title>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="auth-box" style="max-width: 500px; margin-top: 50px;">
            <h2>My Account</h2>
            
            <?php if ($message): ?>
                <div style="background: var(--success-bg); color: var(--success-text); padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="account-info-grid" style="text-align: left; margin-bottom: 30px;">
                    
                    <div class="input-wrapper">
                        <label style="font-size: 0.8rem; color: var(--text-muted);">First Name</label>
                        <input type="text" name="forename" value="<?php echo htmlspecialchars($user['forename']); ?>" required>
                    </div>

                    <div class="input-wrapper">
                        <label style="font-size: 0.8rem; color: var(--text-muted);">Last Name</label>
                        <input type="text" name="surname" value="<?php echo htmlspecialchars($user['surname']); ?>" required>
                    </div>

                    <div class="input-wrapper">
                        <label style="font-size: 0.8rem; color: var(--text-muted);">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div style="display: flex; gap: 20px; margin-top: 10px; background: var(--bg-alt); padding: 15px; border-radius: 8px;">
                        <div>
                            <label style="font-size: 0.75rem; color: var(--text-muted);">Username</label>
                            <div style="font-weight: bold; color: #666;">@<?php echo htmlspecialchars($user['username']); ?></div>
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

            <div class="danger-zone">
                <h3 style="color: var(--danger-text); font-size: 1rem;">Danger Zone</h3>
                <form method="POST" onsubmit="return confirm('Permanently delete your account? This cannot be undone.');">
                    <button type="submit" name="delete_account" class="outline-btn" style="color: var(--danger-text); border-color: var(--danger-border); width: 100%;">
                        Delete My Account
                    </button>
                </form>
            </div>

            <a href="index.php" style="display: block; margin-top: 30px; font-size: 0.9rem; color: var(--brand-orange);">← Back to Dashboard</a>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>