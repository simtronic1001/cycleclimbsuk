<?php 
include 'db.php'; 
include 'config.php';
session_start();

// 1. DATA FETCHING
$current_user_id = $_SESSION['user_id'] ?? 0;

$sql = "SELECT c.*, uc.id AS is_done, uc.user_pr 
        FROM climbs c 
        LEFT JOIN user_climbs uc ON c.id = uc.climb_id AND uc.user_id = ?
        ORDER BY c.location ASC, c.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$current_user_id]);
$climbs = $stmt->fetchAll();

// 2. DATA PROCESSING
$groupedClimbs = [];
$completedCount = 0;

foreach ($climbs as $climb) {
    if ($climb['is_done']) {
        $completedCount++;
    }
    $groupedClimbs[$climb['location']][] = $climb;
}

$totalClimbs = count($climbs);
$percent = ($totalClimbs > 0) ? ($completedCount / $totalClimbs) * 100 : 0;
?>


    <?php include 'header.php'; ?>

    <div class="container">
        <header class="main-header">
    <div class="header-inner">

        <h1>Cycle Climbs UK</h1>

        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="strava-connect-area">
                <?php
                $stmt = $pdo->prepare("SELECT strava_refresh_token FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user_data = $stmt->fetch();

                if (!empty($user_data['strava_refresh_token'])): ?>
                    <div class="strava-success-box">
                        <strong>✅ Strava Account Linked</strong>
                    </div>
                    <div class="strava-actions">
                        <a href="start_sync.php" class="strava-sync-btn">
                            🔄 Sync My Progress
                        </a>
                        <a href="strava_disconnect.php" class="strava-disconnect-link" onclick="return confirm('Disconnect Strava? This will reset your connection.')">
                            Disconnect/Reset
                        </a>
                    </div>
                <?php else: ?>
                    <p>Link your Strava account to automatically track your climbs:</p>
                    <a href="<?php echo $strava_auth_url; ?>" class="strava-connect-btn">
                        Connect with Strava
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="auth-buttons">
                <a href="login.php" class="strava-btn">Login</a>
                <a href="register.php" class="outline-btn opac">Register</a>
            </div>
        <?php endif; ?>

    </div>
</header>


        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="progress-section">
                <div class="progress-header">
                    <strong>Overall Progress</strong>
                    <span><?php echo $completedCount; ?> / <?php echo $totalClimbs; ?> Climbs (<?php echo round($percent, 1); ?>%)</span>
                </div>
                <div class="progress-container">
                    <div class="progress-bar" style="width: <?php echo $percent; ?>%;"></div>
                </div>
            </div>

            <?php foreach ($groupedClimbs as $county => $countyClimbs): 
                $cTotal = count($countyClimbs);
                $cDone = 0;
                foreach ($countyClimbs as $c) { if ($c['is_done']) $cDone++; }
            ?>
                <div class="county-section">
                    <button class="accordion" onclick="toggleAccordion(this)">
                        <strong><?php echo htmlspecialchars($county); ?></strong>
                        <span><?php echo "$cDone / $cTotal"; ?> ✅</span>
                    </button>
                    
                    <div class="panel">
                        <?php foreach ($countyClimbs as $climb): ?>
                            <div class="climb-item">
                                
                                <div class="climb-item-header">
                                    <div class="climb-header-main" style="display: flex; align-items: center; gap: 12px;">
                                        
                                        <button 
                                            class="tick-btn <?php echo $climb['is_done'] ? 'completed' : ''; ?>" 
                                            onclick="toggleClimb(<?php echo $climb['id']; ?>, this); event.stopPropagation();">
                                            ✓
                                        </button>
                                        
                                        <span class="climb-item-title" onclick="toggleSubAccordion(this)" style="cursor: pointer;">
                                            ⛰️ <?php echo htmlspecialchars($climb['name']); ?>
                                        </span>
                                    </div>

                                    <?php if ($climb['is_done']): ?>
                                        <span class="badge-done">✅ DONE</span>
                                    <?php else: ?>
                                        <span class="badge-todo">⭕ TO DO</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="climb-details">
                                    <div class="climb-stats-grid">
                                        <div><strong>Dist:</strong> <?php echo $climb['distance'] ?? '0.0'; ?> km</div>
                                        <div><strong>Gain:</strong> <?php echo $climb['elevation_gain'] ?? '0'; ?>m / <?php echo round(($climb['elevation_gain'] ?? 0) * 3.28084); ?>ft</div>
                                        <div><strong>Avg Grade:</strong> <?php echo $climb['avg_gradient'] ?? '0'; ?>%</div>
                                        <div><strong>Max Grade:</strong> <?php echo $climb['max_gradient'] ?? '0'; ?>%</div>
                                        <div><strong>KOM Time:</strong> <?php echo ($climb['kom_time'] > 0) ? gmdate("i:s", $climb['kom_time']) : '--:--'; ?></div>
                                        <div><strong>QOM Time:</strong> <?php echo ($climb['qom_time'] > 0) ? gmdate("i:s", $climb['qom_time']) : '--:--'; ?></div>
                                        <div class="climb-pr">
                                            Your PR: <?php echo (!empty($climb['user_pr']) && $climb['user_pr'] > 0) ? gmdate("i:s", $climb['user_pr']) : 'No attempt yet'; ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($climb['elevation_data'])): ?>
                                        <div class="climb-profile-container" style="position: relative; height: 150px; width: 100%; margin: 15px 0;">
                                            <canvas id="chart-<?php echo $climb['id']; ?>" 
                                                    class="elevation-chart" 
                                                    data-elevation='<?php echo $climb['elevation_data']; ?>'>
                                            </canvas>
                                        </div>
                                    <?php endif; ?>

                                    <div class="strava-view-container">
                                        <a href="https://www.strava.com/segments/<?php echo $climb['segment_id']; ?>" target="_blank" class="strava-view-link">
                                            View Full Segment on Strava 🔗
                                        </a>
                                    </div>
                                </div>
                                </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            <div class="landing-pitch">
                <h2>The Ultimate UK Climbing Companion</h2>
                <p class="pitch-lead">
                    Whether you're tackling the Lakeland passes or the peaks of Scotland, **Cycle Climbs UK** is here to help you track every ascent.
                </p>
                
                <div class="feature-grid">
                    <div class="feature-item">
                        <span>⛰️</span>
                        <h3>Explore Iconic Hills</h3>
                        <p>Access a curated database of the UK's most challenging and legendary cycling climbs.</p>
                    </div>
                    <div class="feature-item">
                        <span>⚡</span>
                        <h3>Instant Strava Sync</h3>
                        <p>Verify your efforts automatically. Connect your Strava account to sync your climb history in seconds.</p>
                    </div>
                    <div class="feature-item">
                        <span>📈</span>
                        <h3>Visual Progress</h3>
                        <p>Watch your progress bar grow as you tick off counties and climb towards your goals.</p>
                    </div>
                </div>

                <div class="pitch-cta">
                    <p>Ready to start your journey?</p>
                    <a href="register.php" class="strava-btn">Create Your Free Account</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
            <?php include 'footer.php'; ?>
<script>
// Accordion Toggles
const toggleAccordion = (btn) => {
    btn.classList.toggle("active");
    const panel = btn.nextElementSibling;
    panel.classList.toggle("open");
};

const toggleSubAccordion = (span) => {
    const details = span.parentElement.parentElement.nextElementSibling;
    details.classList.toggle("open");

    if (details.classList.contains("open")) {
        const canvas = details.querySelector('.elevation-chart');
        
        if (canvas && !canvas.classList.contains('chart-rendered')) {
            const rawData = canvas.getAttribute('data-elevation');
            
            if (rawData) {
                const elevationPoints = JSON.parse(rawData);
                const labels = elevationPoints.map(p => p.d + 'km');
                const data = elevationPoints.map(p => p.e);

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            borderColor: '#fc4c02',
                            backgroundColor: 'rgba(252, 76, 2, 0.1)',
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { display: false },
                            y: { beginAtZero: false, grid: { color: '#f0f0f0' } }
                        }
                    }
                });

                canvas.classList.add('chart-rendered');
            }
        }
    }
};

// NEW: Toggle Climb Function (The Tick Box)
function toggleClimb(climbId, btn) {
    const formData = new FormData();
    formData.append('climb_id', climbId);

    fetch('toggle_climb.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.trim() === 'marked') {
            btn.classList.add('completed');
        } else if (data.trim() === 'unmarked') {
            btn.classList.remove('completed');
        }
        // Optional: Refresh the page to update the progress bar at the top!
        // location.reload(); 
    });
}
</script>
</body>
</html>