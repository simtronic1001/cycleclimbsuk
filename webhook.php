// ------------------------------------------------------------
// 1. Developer-friendly diagnostics when opened in a browser
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['hub_challenge'])) {

    echo "<h2>Strava Webhook Endpoint Diagnostics</h2>";
    echo "<p>This page is only shown when you visit the webhook URL manually.</p>";
    echo "<p>Strava will never see this page — it is for developer testing only.</p>";

    echo "<h3>Checks</h3>";
    echo "<ul>";

    // Check HTTPS
    if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
        echo "<li style='color:red;'>❌ WARNING: This endpoint is not being accessed over HTTPS. 
              Strava requires HTTPS for all webhook traffic.</li>";
    } else {
        echo "<li style='color:green;'>✔ HTTPS detected</li>";
    }

    // Check database connection
    try {
        $pdo->query("SELECT 1");
        echo "<li style='color:green;'>✔ Database connection OK</li>";
    } catch (Exception $e) {
        echo "<li style='color:red;'>❌ Database connection failed: " . $e->getMessage() . "</li>";
    }

    // Check if webhook_queue table exists
    try {
        $pdo->query("SELECT 1 FROM webhook_queue LIMIT 1");
        echo "<li style='color:green;'>✔ webhook_queue table found</li>";
    } catch (Exception $e) {
        echo "<li style='color:red;'>❌ webhook_queue table missing. 
              <br>Recommendation: Run the SQL migration to create it.</li>";
    }

    // Check if the URL matches your expected domain
    $expected = "cycleclimbsuk.simtech.site";
    $actual = $_SERVER['HTTP_HOST'] ?? 'unknown';

    if ($actual !== $expected) {
        echo "<li style='color:red;'>❌ WARNING: This webhook URL does not match your expected domain.</li>";
        echo "<li>Expected: <strong>$expected</strong></li>";
        echo "<li>Actual: <strong>$actual</strong></li>";
        echo "<li>Recommendation: Update your Strava webhook subscription to use:<br>
              <code>https://$expected/webhook.php</code></li>";
    } else {
        echo "<li style='color:green;'>✔ Webhook domain matches expected configuration</li>";
    }

    echo "</ul>";

    echo "<h3>Webhook Registration Info</h3>";
    echo "<p>Use this URL when registering your webhook with Strava:</p>";
    echo "<code>https://cycleclimbsuk.simtech.site/webhook.php</code>";

    echo "<p>If Strava reports a 404 or unreachable URL, check:</p>";
    echo "<ul>
            <li>DNS is pointing to the correct server</li>
            <li>HTTPS certificate is valid</li>
            <li>Firewall allows inbound HTTPS traffic</li>
            <li>File permissions allow PHP to run</li>
          </ul>";

    exit();
}
