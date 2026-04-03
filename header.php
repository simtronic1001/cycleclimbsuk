<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); } 
include_once 'db.php'; 

$is_admin = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_check = $stmt->fetch();
    if ($user_check && $user_check['is_admin'] == 1) { 
        $is_admin = true; 
    }
}

$page_title = isset($custom_title) ? $custom_title . " | Cycle Climbs UK" : "Cycle Climbs UK";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#fc4c02">
    <title><?php echo $page_title; ?></title>
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="index.php" style="text-decoration: none; color: inherit;">
                <div class="logo-temp">⛰️ CYCLE CLIMBS<span> UK</span></div>
            </a>
        </div>
        
        <div class="nav-links">
            <a href="index.php">Home</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if ($is_admin): ?>
                    <a href="admin_add.php" style="color: var(--brand-orange); font-weight: bold;">[Add Climb]</a>
                <?php endif; ?>
                <span class="user-pill">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="account.php" class="outline-btn opac">My Account</a>
                <a href="logout.php" class="outline-btn opac">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="outline-btn opac">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>