<?php
include 'db.php';
include  'header.php';
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $forename = trim($_POST['forename']);
    $surname = trim($_POST['surname']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $dob = $_POST['dob'];
    $password = $_POST['password'];

    // --- The 13+ Age Gate Logic ---
    $bday = new DateTime($dob);
    $today = new DateTime('today');
    $age = $bday->diff($today)->y;

    if ($age < 13) {
        $error = "Sorry, you must be at least 13 years old to use Cycle Climbs UK, in accordance with Strava's Terms of Service.";
    } else {
        // Check if Email OR Username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->rowCount() > 0) {
            $error = "That email or username is already taken!";
        } else {
            // Hash password and insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare("INSERT INTO users (forename, surname, username, email, dob, password) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($insert->execute([$forename, $surname, $username, $email, $dob, $hashed_password])) {
                $success = "Account created successfully! You can now log in.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Register - Cycle Climbs UK</title>
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h2>Join Cycle Climbs UK</h2>
            <p style="color: var(--text-muted); font-size: 0.9em; margin-bottom: 20px;">Track your progress across the UK's greatest ascents.</p>
            
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div style="background: var(--success-bg); color: var(--success-text); padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo $success; ?> <br><br> <a href="login.php" style="color: var(--success-text); font-weight: bold;">Go to Login</a>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="input-wrapper">
                        <input type="text" name="forename" placeholder="First Name" required>
                    </div>
                    <div class="input-wrapper">
                        <input type="text" name="surname" placeholder="Last Name" required>
                    </div>
                    <div class="input-wrapper">
                        <input type="text" name="username" placeholder="Display Username" required>
                    </div>
                    <div class="input-wrapper">
                        <input type="email" name="email" placeholder="Email Address" required>
                    </div>
                    <div class="input-wrapper" style="text-align: left;">
                        <label style="font-size: 0.8em; color: var(--text-muted); margin-left: 5px;">Date of Birth</label>
                        <input type="date" name="dob" required>
                    </div>
                    <div class="input-wrapper">
                        <input type="password" name="password" placeholder="Password" required minlength="6">
                    </div>
                    
                    <div style="font-size: 0.8em; color: var(--text-muted); margin: 15px 0;">
                        <input type="checkbox" required id="terms"> 
                        <label for="terms">I agree to the Terms of Use and confirm I am 13 or older.</label>
                    </div>

                    <button type="submit" class="strava-btn">Create Account</button>
                </form>
            <?php endif; ?>
            
            <p style="margin-top: 20px; font-size: 0.9em;">
                Already have an account? <a href="login.php" style="color: var(--brand-orange);">Log in here</a>
            </p>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>