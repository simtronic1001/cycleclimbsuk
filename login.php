<?php

include 'db.php';


$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check for user by EMAIL instead of username
    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username']; // Kept for the welcome message
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
include  'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Login - Cycle Climbs UK</title>
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h2>Welcome Back</h2>
            
            <?php if ($error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-wrapper">
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="input-wrapper">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="strava-btn">Log In</button>
            </form>
            
            <p style="margin-top: 20px; font-size: 0.9em;">
                Don't have an account? <a href="register.php" style="color: var(--brand-orange);">Register here</a>
            </p>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html>