<?php 
include 'db.php'; 
include 'config.php';
session_start();

function formatTime($seconds) {
    if (empty($seconds)) return '--';
    $m = floor($seconds / 60);
    $s = $seconds % 60;
    return sprintf("%d:%02d", $m, $s);
}

$is_logged_in = isset($_SESSION['user_id']);
$groupedClimbs = [];
$completedCount = 0;
$totalClimbs = 0;
$percent = 0;

if ($is_logged_in) {
    $current_user_id = $_SESSION['user_id'];
    $sql = "SELECT c.*, uc.id AS is_done, uc.user_pr 
            FROM climbs c 
            LEFT JOIN user_climbs uc ON c.id = uc.climb_id AND uc.user_id = ?
            ORDER BY c.location ASC, c.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$current_user_id]);
    $climbs = $stmt->fetchAll();

    foreach ($climbs as $climb) {
        if ($climb['is_done']) $completedCount++;
        $groupedClimbs[$climb['location']][] = $climb;
    }
    $totalClimbs = count($climbs);
    $percent = ($totalClimbs > 0) ? ($completedCount / $totalClimbs) * 100 : 0;
}
?>

<?php include 'header.php'; ?>

<div class="container">
    <header class="main-header">
        <h1>Cycle Climbs UK</h1>
        <p>Track your progress across the UK's most iconic cycling climbs.</p>
    </header>

    <?php if (!$is_logged_in): ?>
        <div class="feature-grid">
            <div class="feature-item">
                <span>⛰️</span>
                <h3>300+ Iconic Climbs</h3>
                <p>From the brutal gradients of Hardknott Pass to the sweeping bends of Box Hill.</p>
            </div>
            <div class="feature-item">
                <span>⏱️</span>
                <h3>Strava Integration</h3>
                <p>Connect your account once, and we'll automatically scan your history.</p>
            </div>
        </div>
        
        <div class="pitch-cta text-center">
            <p>Ready to start ticking off the list?</p>
            <a href="register.php" class="strava-btn">Create Free Account</a>
            <a href="login.php" class="outline-btn" style="margin-left: 10px;">Log In</a>
        </div>

    <?php else: ?>
        <div class="progress-box">
            <h3>Your Progress: <?php echo $completedCount; ?> / <?php echo $totalClimbs; ?> Climbs</h3>
            <div class="progress-bar-container">
                <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
            </div>
        </div>

        <?php foreach ($groupedClimbs as $county => $countyClimbs): ?>
            <div class="county-section">
                <h2 class="county-title" onclick="toggleCounty(this)">
                    <?php echo htmlspecialchars($county); ?>
                    <span class="county-toggle-icon">▼</span>
                </h2>
                
                <div class="county-climbs" style="display: none;">
                    <?php foreach ($countyClimbs as $climb): ?>
                        <div class="accordion-item">
                            <div class="accordion-header" onclick="toggleAccordion(this)">
                                <div class="climb-title"><?php echo htmlspecialchars($climb['name']); ?></div>
                                <div class="climb-preview-stats">
                                    <?php if($climb['user_pr']): ?> 
                                        <span style="color: var(--brand-orange); font-weight: bold;">PR: <?php echo formatTime($climb['user_pr']); ?></span> | 
                                    <?php endif; ?>
                                    <?php echo number_format($climb['distance'], 1); ?> km | <?php echo number_format($climb['avg_gradient'], 1); ?>%
                                </div>
                                <div class="tick-box <?php echo $climb['is_done'] ? 'completed' : ''; ?>" 
                                     onclick="event.stopPropagation(); toggleClimb(<?php echo $climb['id']; ?>, this);">
                                </div>
                            </div>
                            
                            <div class="accordion-content">
                                <div class="stats-grid">
                                    <div class="stat-box">
                                        <span class="stat-label">Distance</span>
                                        <span class="stat-value"><?php echo number_format($climb['distance'], 2); ?> km</span>
                                    </div>
                                    <div class="stat-box">
                                        <span class="stat-label">Elevation Gain</span>
                                        <span class="stat-value"><?php echo $climb['elevation_gain']; ?> m</span>
                                    </div>
                                    <div class="stat-box">
                                        <span class="stat-label">Avg Grade</span>
                                        <span class="stat-value"><?php echo number_format($climb['avg_gradient'], 1); ?>%</span>
                                    </div>
                                    <div class="stat-box">
                                        <span class="stat-label">Your PR</span>
                                        <span class="stat-value" style="color: var(--brand-orange);"><?php echo $climb['user_pr'] ? formatTime($climb['user_pr']) : 'None'; ?></span>
                                    </div>
                                </div>
                                <div class="text-center mt-20">
                                    <a href="https://www.strava.com/segments/<?php echo $climb['segment_id']; ?>" target="_blank" class="strava-view-link">View on Strava</a>
                                </div>
                                <?php if (!empty($climb['elevation_data'])): ?>
                                    <div class="chart-container">
                                        <canvas class="elevationChart" data-points='<?php echo htmlspecialchars($climb['elevation_data'], ENT_QUOTES, 'UTF-8'); ?>'></canvas>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script>
function toggleCounty(element) {
    const climbsDiv = element.nextElementSibling;
    const icon = element.querySelector('.county-toggle-icon');
    if (climbsDiv.style.display === 'block') {
        climbsDiv.style.display = 'none';
        icon.textContent = '▼';
    } else {
        climbsDiv.style.display = 'block';
        icon.textContent = '▲';
    }
}

function toggleAccordion(element) {
    const details = element.nextElementSibling;
    const isOpen = details.style.display === 'block';
    
    // Close other open accordions in this county
    const parentCounty = element.closest('.county-climbs');
    if (parentCounty) {
        parentCounty.querySelectorAll('.accordion-content').forEach(el => el.style.display = 'none');
    }
    
    if (!isOpen) {
        details.style.display = 'block';
        const canvas = details.querySelector('canvas.elevationChart');
        if (canvas && !canvas.classList.contains('chart-rendered')) {
            const rawData = canvas.getAttribute('data-points');
            if (rawData) {
                try {
                    const elevationPoints = JSON.parse(rawData);
                    new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: elevationPoints.map(p => p.d + 'km'),
                            datasets: [{
                                data: elevationPoints.map(p => p.e),
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
                            scales: { x: { display: false }, y: { beginAtZero: false, grid: { color: '#f0f0f0' } } }
                        }
                    });
                    canvas.classList.add('chart-rendered');
                } catch (e) {
                    console.error("No elevation data found.");
                }
            }
        }
    }
}

function toggleClimb(climbId, btn) {
    const formData = new FormData();
    formData.append('climb_id', climbId);
    fetch('toggle_climb.php', { method: 'POST', body: formData })
    .then(response => response.text())
    .then(data => {
        if (data.trim() === 'marked') btn.classList.add('completed');
        else if (data.trim() === 'unmarked') btn.classList.remove('completed');
    });
}
</script>
</body>
</html>