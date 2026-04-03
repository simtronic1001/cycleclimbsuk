<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-legal">
                <p>&copy; <?php echo date("Y"); ?> Cycle Climbs UK</p>
                <a href="terms.php">Terms of Use</a> | 
                <a href="privacy.php">Privacy Policy</a>
            </div>
            
            <div class="strava-attribution">
                <img src="assets/api_logo_pwrdBy_strava_stack_light.png" alt="Powered by Strava" width="120">
            </div>
        </div>
        <p class="disclaimer">
            <strong>Disclaimer:</strong> Cycling is a physical activity with inherent risks. Always ride within your 
            abilities, follow local traffic laws, and ensure your equipment is well-maintained. 
            Cycle Climbs UK is not responsible for any injury or damage.
        </p>
    </div>
</footer>
<div id="cookie-banner" style="display: none; position: fixed; bottom: 20px; left: 20px; right: 20px; background: #333; color: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); z-index: 10000; border-left: 5px solid var(--brand-orange);">
    <div class="container d-flex flex-wrap justify-content-between align-items-center">
        <p style="margin: 0; font-size: 0.9rem; flex: 1; min-width: 300px; padding-right: 20px;">
            We use cookies to ensure you stay logged in and to improve your experience. By continuing to use Cycle Climbs UK, you agree to our 
            <a href="privacy.php" style="color: var(--brand-orange); text-decoration: underline;">Privacy Policy</a>.
        </p>
        <button onclick="acceptCookies()" class="strava-btn" style="padding: 8px 25px; font-size: 0.85rem; border-radius: 8px;">Got it</button>
    </div>
</div>

<script>
    // Check if user has already accepted
    if (!localStorage.getItem('cookiesAccepted')) {
        document.getElementById('cookie-banner').style.display = 'block';
    }

    function acceptCookies() {
        localStorage.setItem('cookiesAccepted', 'true');
        document.getElementById('cookie-banner').style.display = 'none';
    }
</script>