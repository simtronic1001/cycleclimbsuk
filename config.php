<?php
// config.php
// Secure configuration loader for Cycle Climbs UK
// - Reads STRAVA_CLIENT_ID, STRAVA_CLIENT_SECRET, STRAVA_REDIRECT_URI from environment
// - Optionally loads a local .env file for development
// - Exposes $strava_auth_url for index.php and other pages

// Simple .env loader for local development (optional)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        // Remove surrounding quotes if present
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }
        if (getenv($name) === false) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Helper to fetch required env var or throw a clear error
function env_required(string $key, string $hint = ''): string {
    $val = getenv($key);
    if ($val === false || $val === '') {
        $msg = "Missing required environment variable: $key.";
        if ($hint !== '') $msg .= " Hint: $hint";
        // In development you may want to show the message; in production log and fail gracefully.
        die($msg);
    }
    return $val;
}

// Load Strava config from environment
define('STRAVA_CLIENT_ID', env_required('STRAVA_CLIENT_ID', 'Set this in your .env or environment'));
define('STRAVA_CLIENT_SECRET', env_required('STRAVA_CLIENT_SECRET', 'Keep this out of source control'));
define('STRAVA_REDIRECT_URI', env_required('STRAVA_REDIRECT_URI', 'e.g. http://localhost/strava_callback.php'));

// Optional verify token for webhook verification (create a random string and set as env)
$STRAVA_WEBHOOK_VERIFY_TOKEN = getenv('STRAVA_WEBHOOK_VERIFY_TOKEN') ?: null;

// Strava OAuth endpoints and scopes
define('STRAVA_OAUTH_AUTHORIZE', 'https://www.strava.com/oauth/authorize');
define('STRAVA_OAUTH_TOKEN', 'https://www.strava.com/oauth/token');

// Scopes: request the minimum you need. For initial sync and activity reads:
// activity:read_all is required to read private activities; adjust if you only need public.
$strava_scopes = urlencode('activity:read_all,profile:read_all');

// Build the authorization URL used in index.php
// approval_prompt=auto avoids forcing re-approval every time
$client_id = urlencode(STRAVA_CLIENT_ID);
$redirect = urlencode(STRAVA_REDIRECT_URI);
$strava_auth_url = STRAVA_OAUTH_AUTHORIZE
    . "?client_id={$client_id}"
    . "&response_type=code"
    . "&redirect_uri={$redirect}"
    . "&approval_prompt=auto"
    . "&scope={$strava_scopes}";

// Expose webhook verify token if set
if ($STRAVA_WEBHOOK_VERIFY_TOKEN) {
    define('STRAVA_WEBHOOK_VERIFY_TOKEN', $STRAVA_WEBHOOK_VERIFY_TOKEN);
}

// Optional: development flags
if (getenv('APP_ENV') === 'development') {
    // Show warnings and enable verbose errors locally only
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}
