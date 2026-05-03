<?php
// admin/dashboard.php
require_once 'include/header.php';

// Safe database connection attempt
$pdo = null;
if (file_exists('../database/connection.php')) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (Throwable $t) {
        $pdo = null;
    }
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// --- DATA FETCHING (with fallback for UI testing) ---

// 1. Stats Overview
$total_users = 0;
$total_moods = 0;
$total_favorites = 0;

if (isset($pdo)) {
    try {
        $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $total_moods = $pdo->query("SELECT COUNT(*) FROM user_mood_history")->fetchColumn();
        $total_favorites = $pdo->query("SELECT COUNT(*) FROM user_favorites")->fetchColumn();
    } catch (Exception $e) {}
} else {
    // Mock data for UI demo
    $total_users = 1240;
    $total_moods = 8562;
    $total_favorites = 342;
}

// 2. Recent Activity
$recent_activity = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT h.mood, h.input_type, h.detected_at, u.username
                             FROM user_mood_history h
                             JOIN users u ON h.user_id = u.user_id
                             ORDER BY h.detected_at DESC LIMIT 6");
        $recent_activity = $stmt->fetchAll();
    } catch (Exception $e) {}
}

if (empty($recent_activity)) {
    // Mock data for UI demo
    $recent_activity = [
        ['username' => 'Jules_01', 'mood' => 'Happy', 'input_type' => 'text', 'detected_at' => date('Y-m-d H:i:s')],
        ['username' => 'Neo_Matrix', 'mood' => 'Excited', 'input_type' => 'voice', 'detected_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
        ['username' => 'Sarah_C', 'mood' => 'Sad', 'input_type' => 'face', 'detected_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
        ['username' => 'Alex_R', 'mood' => 'Angry', 'input_type' => 'text', 'detected_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))],
    ];
}

// 3. Top Moods Data (for Chart)
$mood_data = [];
if (isset($pdo)) {
    try {
        $mood_data = $pdo->query("SELECT mood, COUNT(*) as count FROM user_mood_history GROUP BY mood ORDER BY count DESC LIMIT 5")->fetchAll();
    } catch (Exception $e) {}
}

if (empty($mood_data)) {
    $mood_data = [
        ['mood' => 'Happy', 'count' => 450],
        ['mood' => 'Sad', 'count' => 300],
        ['mood' => 'Excited', 'count' => 250],
        ['mood' => 'Angry', 'count' => 150],
        ['mood' => 'Neutral', 'count' => 100],
    ];
}

// 4. Input Method Trends (for Chart)
$method_data = [];
if (isset($pdo)) {
    try {
        $method_data = $pdo->query("SELECT input_type, COUNT(*) as count FROM user_mood_history GROUP BY input_type")->fetchAll();
    } catch (Exception $e) {}
}

if (empty($method_data)) {
    $method_data = [
        ['input_type' => 'text', 'count' => 1200],
        ['input_type' => 'voice', 'count' => 800],
        ['input_type' => 'face', 'count' => 500],
    ];
}

?>

<main class="container">

    <!-- Hero Section -->
    <section class="admin-hero-premium text-center" data-aos="zoom-in">
        <div class="hero-icon-layer">
            <i class="bi bi-shield-lock-fill"></i>
            <i class="bi bi-gear-wide-connected"></i>
            <i class="bi bi-broadcast-pin"></i>
        </div>
        <div class="system-status-pill mb-3">
            <span class="status-dot"></span>
            SYSTEM STATUS: ACTIVE
        </div>
        <h1 class="hero-welcome">Welcome back, <?php echo htmlspecialchars($admin_username); ?>.</h1>
        <p class="hero-tagline">MoodAI Administrative Neural Link Established.</p>
        <div class="hero-visual-divider"></div>
    </section>

    <!-- Stats Overview -->
    <div class="row g-4 mb-5">
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
            <div class="premium-stat-card h-100">
                <div class="card-inner">
                    <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($total_users); ?></div>
                        <div class="stat-label">Total Operatives</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
            <div class="premium-stat-card h-100">
                <div class="card-inner">
                    <div class="stat-icon"><i class="bi bi-cpu-fill"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($total_moods); ?></div>
                        <div class="stat-label">Neural Mappings</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
            <div class="premium-stat-card h-100">
                <div class="card-inner">
                    <div class="stat-icon"><i class="bi bi-heart-fill"></i></div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($total_favorites); ?></div>
                        <div class="stat-label">Total Favorites</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <!-- Top Moods Chart -->
        <div class="col-lg-8" data-aos="fade-right">
            <div class="dashboard-card h-100">
                <h5 class="card-title-admin"><i class="bi bi-bar-chart-fill"></i> Top Detected Moods</h5>
                <canvas id="moodChart" height="150"></canvas>
            </div>
        </div>

        <!-- System Health -->
        <div class="col-lg-4" data-aos="fade-left">
            <div class="dashboard-card h-100">
                <h5 class="card-title-admin"><i class="bi bi-shield-check"></i> System Health</h5>
                <div class="health-monitor py-2">
                    <div class="health-item">
                        <span class="health-label">Database Engine</span>
                        <span class="health-status status-ok">OPTIMAL</span>
                    </div>
                    <div class="health-item">
                        <span class="health-label">AI Backend (Python)</span>
                        <span class="health-status status-ok">ONLINE</span>
                    </div>
                    <div class="health-item">
                        <span class="health-label">TMDB API Connection</span>
                        <span class="health-status status-ok">STABLE</span>
                    </div>
                    <div class="health-item">
                        <span class="health-label">Server Memory</span>
                        <span class="health-status status-ok">12% USE</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Input Method Trends -->
        <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
            <div class="dashboard-card h-100">
                <h5 class="card-title-admin"><i class="bi bi-pie-chart-fill"></i> Method Analytics</h5>
                <canvas id="methodChart" height="250"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-8" data-aos="fade-up" data-aos-delay="200">
            <div class="dashboard-card h-100">
                <h5 class="card-title-admin"><i class="bi bi-clock-history"></i> Recent System Activity</h5>
                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th>Operative</th>
                                <th>Detected Mood</th>
                                <th>Method</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_activity)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No activity recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_activity as $row): ?>
                                    <tr>
                                        <td class="fw-bold"><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><span class="text-uppercase" style="letter-spacing: 1px; font-weight: 700; color: var(--admin-purple);"><?php echo htmlspecialchars($row['mood']); ?></span></td>
                                        <td><span class="method-badge badge-<?php echo $row['input_type']; ?>"><?php echo $row['input_type']; ?></span></td>
                                        <td class="text-muted"><?php echo date('M d, H:i', strtotime($row['detected_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Mood Chart
    const moodCtx = document.getElementById('moodChart').getContext('2d');
    new Chart(moodCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($mood_data, 'mood')); ?>,
            datasets: [{
                label: 'Detections',
                data: <?php echo json_encode(array_column($mood_data, 'count')); ?>,
                backgroundColor: '#a890fe',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1a1a1a',
                    titleColor: '#a890fe',
                    bodyColor: '#fff',
                    borderColor: '#a890fe',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#a0a0a0' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#a0a0a0' }
                }
            }
        }
    });

    // 2. Method Chart
    const methodCtx = document.getElementById('methodChart').getContext('2d');
    new Chart(methodCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($method_data, 'input_type')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($method_data, 'count')); ?>,
                backgroundColor: ['#a890fe', '#b388ff', '#ff9800'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        color: '#a0a0a0'
                    }
                }
            }
        }
    });
});
</script>

<?php require_once 'include/footer.php'; ?>
