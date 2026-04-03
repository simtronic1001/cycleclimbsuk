<?php 
include 'db.php'; 
$custom_title = "Privacy Policy";
include 'header.php'; 
?>

<div class="container">
    <div class="auth-box" style="max-width: 800px; text-align: left; margin: 40px auto; padding: 40px; background: var(--bg-panel); border-radius: 16px; border: 1px solid var(--border-dark);">
        <h2 style="margin-bottom: 20px; font-weight: 800;">Privacy Policy</h2>
        <p style="color: var(--text-muted); margin-bottom: 30px;">Last Updated: <?php echo date('F d, Y'); ?></p>

        <section style="margin-bottom: 30px;">
            <h3 style="font-size: 1.2rem; margin-bottom: 10px;">1. Introduction</h3>
            <p>Cycle Climbs UK is designed to help you track your cycling progress. We value your privacy and are committed to protecting your personal data in compliance with UK GDPR and Strava API requirements.</p>
        </section>

        <section style="margin-bottom: 30px;">
            <h3 style="font-size: 1.2rem; margin-bottom: 10px;">2. Data We Collect</h3>
            <ul style="padding-left: 20px;">
                <li><strong>Account Information:</strong> We store your name, email address, and date of birth (to verify you are over 13).</li>
                <li><strong>Strava Data:</strong> When you connect your account, we store your Strava Athlete ID and OAuth access tokens. We do NOT store your Strava password.</li>
                <li><strong>Activity Data:</strong> Our system analyzes your activity segment efforts to match them against our list of climbs. We store only the fact that you completed a climb and your best time (PR).</li>
            </ul>
        </section>

        <section style="margin-bottom: 30px;">
            <h3 style="font-size: 1.2rem; margin-bottom: 10px;">3. Cookies</h3>
            <p>We use a single, strictly necessary session cookie to keep you logged in to your account. This cookie does not track you across other websites and is essential for the functionality of the platform.</p>
        </section>

        <section style="margin-bottom: 30px;">
            <h3 style="font-size: 1.2rem; margin-bottom: 10px;">4. Data Deletion</h3>
            <p>You have full control over your data. You may disconnect Strava at any time or use the <strong>"Delete My Account"</strong> button in your settings to permanently erase all personal data, climb history, and Strava tokens from our database immediately.</p>
        </section>

        <section style="margin-bottom: 30px;">
            <h3 style="font-size: 1.2rem; margin-bottom: 10px;">5. Third-Party Sharing</h3>
            <p>We do not sell, trade, or otherwise transfer your personal data to outside parties. Your data is used exclusively for the features of this application.</p>
        </section>

        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--border-light); font-size: 0.9rem; color: var(--text-muted);">
            <p>This application is powered by the Strava API but is not affiliated with or endorsed by Strava, Inc.</p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>