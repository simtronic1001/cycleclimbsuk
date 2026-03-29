<?php include 'db.php'; include 'header.php'; ?>

<div class=\"container\">
    <div class=\"auth-box\" style=\"max-width: 800px; text-align: left;\">
        <h2>Privacy Policy</h2>
        <p><strong>Last Updated: <?php echo date('F Y'); ?></strong></p>

        <h3>1. Data We Collect</h3>
        <p>To provide our service, we collect the following information during registration and Strava connection:</p>
        <ul>
            <li><strong>Personal Info:</strong> Name, Email, and Date of Birth (to verify you are over 13).</li>
            <li><strong>Strava Data:</strong> When you connect your account, we store your Strava Athlete ID and Access Tokens. This allows us to read your activities to identify completed climbs.</li>
        </ul>

        <h3>2. How We Use Your Data</h3>
        <p>Your data is used solely to track your progress against the UK's iconic cycling climbs. We do not sell your data to third parties. We do not use your Strava data for any purpose other than identifying segment efforts that match our database.</p>

        <h3>3. Data Retention & Deletion</h3>
        <p>We keep your data as long as your account is active. You can delete your account at any time via the <strong>My Account</strong> page. Deleting your account immediately removes your personal details and revokes our access to your Strava tokens.</p>

        <h3>4. Strava API Compliance</h3>
        <p>This application uses the Strava API but is not endorsed or certified by Strava. All Strava logos and trademarks are the property of Strava, Inc.</p>
    </div>
</div>

<?php include 'footer.php'; ?>