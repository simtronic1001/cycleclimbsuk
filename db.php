<?php
// Set session cookie to last for 30 days (in seconds)
$sessionLifetime = 30 * 24 * 60 * 60; 

session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'domain' => '', // Automatically uses your current domain
    'secure' => true, // Only sends over HTTPS (Critical for Live Server)
    'httponly' => true, // Protects against XSS attacks
    'samesite' => 'Lax'
]);

// Now the session can start with these persistent settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Database connection settings
$host = 'localhost';
$db   = 'simtechs_cycleclimbsuk'; 
$user = 'simtechs_admin';      
$pass = '6^W0MIBuZr]-!wf^';          

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>