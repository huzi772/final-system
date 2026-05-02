<?php
// admin/user_details.php
require_once 'include/header.php';

$user_id = $_GET['id'] ?? 0;

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

// --- DATA FETCHING ---
$user = null;
$history = [];
$favorites = [];

if ($pdo && $user_id) {
    try {
        // User info
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user) {
            // Mood History
            $stmt = $pdo->prepare("SELECT * FROM user_mood_history WHERE user_id = ? ORDER BY detected_at DESC");
            $stmt->execute([$user_id]);
            $history = $stmt->fetchAll();

            // Favorites
            $stmt = $pdo->prepare("SELECT * FROM user_favorites WHERE user_id = ? ORDER BY saved_at DESC");
            $stmt->execute([$user_id]);
            $favorites = $stmt->fetchAll();
        }
    } catch (Exception $e) {}
}

// Mock data if user not found or for testing
if (!$user) {
    $user = ['username' => 'Jules_01', 'email' => 'jules@example.com', 'status' => 'active', 'created_at' => '2026-04-29 10:00:00'];
    $history = [
        ['mood' => 'Happy', 'input_type' => 'text', 'detected_at' => '2026-04-30 01:00:00'],
        ['mood' => 'Excited', 'input_type' => 'voice', 'detected_at' => '2026-04-30 00:00:00'],
        ['mood' => 'Sad', 'input_type' => 'face', 'detected_at' => '2026-04-29 23:00:00'],
    ];
    $favorites = [
        ['movie_title' => 'Inception', 'movie_poster' => 'https://image.tmdb.org/t/p/w500/9gk7Fn9sVAsS9Te6B1pU3O9SB34.jpg', 'mood_tag' => 'Excited', 'saved_at' => '2026-04-29 23:10:00'],
        ['movie_title' => 'The Hangover', 'movie_poster' => 'https://image.tmdb.org/t/p/w500/ul689Y96L77p6S9vI9P96pA20S.jpg', 'mood_tag' => 'Happy', 'saved_at' => '2026-04-29 23:15:00'],
    ];
}
?>

<main class="container pb-5">
    <!-- Header with Back Button -->
    <div class="d-flex align-items-center mb-5" data-aos="fade-right">
        <a href="user.php" class="btn btn-light rounded-circle me-3 p-2" style="width: 45px; height: 45px;">
            <i class="bi bi-arrow-left" style="font-size: 1.2rem;"></i>
        </a>
        <div>
            <h2 class="fw-800 mb-0">Operative Profile</h2>
            <p class="text-muted mb-0">Diving into the digital shadow of <span class="text-danger fw-700"><?php echo htmlspecialchars($user['username']); ?></span></p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar: User Info Card -->
        <div class="col-lg-4" data-aos="fade-up">
            <div class="dashboard-card text-center h-100">
                <div class="user-profile-avatar mx-auto mb-3">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
                <h3 class="fw-800 mb-1"><?php echo htmlspecialchars($user['username']); ?></h3>
                <p class="text-muted small mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                
                <div class="status-indicator-large mb-4">
                    <?php if ($user['status'] == 'active'): ?>
                        <span class="badge bg-success w-100 py-2 rounded-pill">ACCESS GRANTED</span>
                    <?php else: ?>
                        <span class="badge bg-danger w-100 py-2 rounded-pill">ACCESS TERMINATED</span>
                    <?php endif; ?>
                </div>

                <div class="row g-2 text-start mb-4">
                    <div class="col-6">
                        <div class="bg-light p-3 rounded-4">
                            <div class="small opacity-50 fw-700 text-uppercase" style="font-size: 0.65rem;">Neural Scans</div>
                            <div class="fw-800 h4 mb-0"><?php echo count($history); ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light p-3 rounded-4">
                            <div class="small opacity-50 fw-700 text-uppercase" style="font-size: 0.65rem;">Fav Records</div>
                            <div class="fw-800 h4 mb-0"><?php echo count($favorites); ?></div>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-4 text-start">
                    <p class="small text-muted mb-1">Joined Date</p>
                    <p class="fw-700 mb-3"><?php echo date('F d, Y @ H:i', strtotime($user['created_at'])); ?></p>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-outline-danger fw-700 py-2 rounded-pill" onclick="handleToggle(<?php echo $user_id; ?>, '<?php echo $user['status']; ?>')">
                        <i class="bi bi-shield-<?php echo $user['status'] == 'active' ? 'slash' : 'check'; ?> me-2"></i>
                        <?php echo $user['status'] == 'active' ? 'Ban Operative' : 'Restore Access'; ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content: History & Favorites -->
        <div class="col-lg-8 mb-5">
            <!-- Neural History -->
            <div class="dashboard-card mb-4" data-aos="fade-up" data-aos-delay="100">
                <h5 class="card-title-admin mb-4"><i class="bi bi-cpu-fill"></i> Neural Detection History</h5>
                <div class="table-responsive history-scroll-container">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Mood Detected</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted">No history found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($history as $row): ?>
                                    <tr>
                                        <td class="text-muted"><?php echo date('M d, H:i', strtotime($row['detected_at'])); ?></td>
                                        <td><span class="fw-800 text-danger text-uppercase" style="letter-spacing: 1px;"><?php echo htmlspecialchars($row['mood']); ?></span></td>
                                        <td><span class="method-badge badge-<?php echo $row['input_type']; ?>"><?php echo $row['input_type']; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Favorite Archives -->
            <div class="dashboard-card" data-aos="fade-up" data-aos-delay="200">
                <h5 class="card-title-admin mb-4"><i class="bi bi-heart-fill"></i> Cinematic Favorites</h5>
                <div class="row g-3 favorites-scroll-container">
                    <?php if (empty($favorites)): ?>
                        <div class="col-12 text-center py-4 text-muted">No favorite records found.</div>
                    <?php else: ?>
                        <?php foreach ($favorites as $fav): ?>
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded-4 d-flex align-items-center">
                                    <div class="fav-poster-mini me-3">
                                        <img src="<?php echo htmlspecialchars($fav['movie_poster']); ?>" alt="Poster" class="img-fluid rounded-3">
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h6 class="fw-800 text-truncate mb-0"><?php echo htmlspecialchars($fav['movie_title']); ?></h6>
                                        <div class="badge bg-danger-subtle text-danger small mt-1"><?php echo htmlspecialchars($fav['mood_tag']); ?></div>
                                        <div class="text-muted x-small mt-1" style="font-size: 0.65rem;"><?php echo date('M d, Y', strtotime($fav['saved_at'])); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function handleToggle(userId, currentStatus) {
    toggleStatus(userId, currentStatus, 'user_details.php?id=' + userId);
}
</script>
<?php require_once 'include/footer.php'; ?>
