<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$game = $pdo->prepare('SELECT * FROM games WHERE id = ?');
$game->execute([$id]); $game = $game->fetch();
if (!$game) { header('Location: index.php'); exit; }

// Post review
$reviewMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $rating  = (int)($_POST['rating']  ?? 0);
    $comment = trim($_POST['comment']  ?? '');
    if ($rating < 1 || $rating > 5) { $reviewMsg = 'Selecciona una puntuación.'; }
    else {
        $uid = $_SESSION['user_id'];
        $pdo->prepare('INSERT INTO reviews (game_id, user_id, rating, comment) VALUES (?,?,?,?)')->execute([$id, $uid, $rating, $comment]);
        header("Location: game_detail.php?id=$id&reviewed=1"); exit;
    }
}

$ratingInfo = getAverageRating($id);
$reviews    = $pdo->prepare('SELECT r.*, u.username, u.avatar FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.game_id=? ORDER BY r.created_at DESC');
$reviews->execute([$id]); $reviews = $reviews->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($game['title']) ?> — APW Juegos</title>
    <meta name="description" content="<?= sanitize(substr($game['description'],0,160)) ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/games.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container">

    <?php if (isset($_GET['reviewed'])): ?><div class="alert alert-success">✅ ¡Reseña publicada!</div><?php endif; ?>

    <!-- Game detail -->
    <div class="game-detail-hero">
        <img src="<?= getGameImageSrc($game['image']) ?>" alt="<?= sanitize($game['title']) ?>"
             class="game-detail-cover" onerror="this.src='assets/img/default_cover.png'">
        <div class="game-detail-info">
            <div style="margin-bottom:.6rem">
                <span class="badge badge-<?= strtolower($game['platform']) ?>"><?= $game['platform'] ?></span>
            </div>
            <h1><?= sanitize($game['title']) ?></h1>
            <div class="game-detail-meta">
                <span class="meta-item">🏢 <?= sanitize($game['developer']) ?></span>
                <span class="meta-item">📅 <?= $game['release_year'] ?></span>
                <span class="meta-item">🎭 <?= sanitize($game['genre']) ?></span>
            </div>
            <div class="avg-rating-display">
                <span class="avg-rating-number"><?= number_format($ratingInfo['avg_rating'],1) ?></span>
                <?= renderStars((float)$ratingInfo['avg_rating']) ?>
                <span class="avg-rating-label">(<?= $ratingInfo['total'] ?> reseñas)</span>
            </div>
            <p class="game-detail-desc"><?= sanitize($game['description']) ?></p>
            <div class="game-detail-actions">
                <a href="chat.php?game_id=<?= $game['id'] ?>" class="btn btn-primary">💬 Ir al chat</a>
                <a href="index.php" class="btn btn-secondary">← Volver</a>
            </div>
        </div>
    </div>

    <!-- Add review form -->
    <div class="section-header"><h2>📝 Dejar una reseña</h2></div>
    <?php if (isLoggedIn()): ?>
    <div class="review-form-card">
        <h3>Tu opinión sobre <?= sanitize($game['title']) ?></h3>
        <?php if ($reviewMsg): ?><div class="alert alert-error"><?= sanitize($reviewMsg) ?></div><?php endif; ?>
        <form method="POST" id="review-form">
            <div class="star-rating-wrapper">
                <label>Puntuación</label>
                <?= renderStars(0, true) ?>
                <input type="hidden" name="rating" id="rating-value-input" value="0">
            </div>
            <div class="form-group">
                <label for="comment">Tu comentario (opcional)</label>
                <textarea id="comment" name="comment" rows="3" placeholder="Cuéntanos qué te pareció…"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Publicar reseña</button>
        </form>
    </div>
    <?php else: ?>
    <div class="alert alert-error" style="margin-bottom:2rem">
        <a href="login.php">Inicia sesión</a> para dejar una reseña.
    </div>
    <?php endif; ?>

    <!-- Reviews list -->
    <div class="section-header"><h2>💬 Reseñas (<?= count($reviews) ?>)</h2></div>
    <?php if (empty($reviews)): ?>
        <div class="empty-state"><p>Aún no hay reseñas. ¡Sé el primero en opinar!</p></div>
    <?php else: ?>
    <div class="reviews-list">
        <?php foreach ($reviews as $rev): ?>
        <div class="review-card">
            <div class="review-header">
                <div class="review-user">
                    <img src="<?= getAvatarSrc($rev['avatar']) ?>" alt="Avatar" class="review-avatar"
                         onerror="this.src='assets/img/default_avatar.png'">
                    <span class="review-username"><?= sanitize($rev['username']) ?></span>
                    <?= renderStars((float)$rev['rating']) ?>
                </div>
                <span class="review-date"><?= timeAgo($rev['created_at']) ?></span>
            </div>
            <?php if ($rev['comment']): ?>
                <p class="review-text"><?= sanitize($rev['comment']) ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
<script>
(function(){
    const stars = document.querySelectorAll('.stars-interactive .star');
    const input = document.getElementById('rating-value-input');
    if (!stars.length) return;
    stars.forEach(s => {
        s.addEventListener('mouseenter', () => {
            const v = +s.dataset.value;
            stars.forEach(x => x.classList.toggle('hovered', +x.dataset.value <= v));
        });
        s.addEventListener('mouseleave', () => stars.forEach(x => x.classList.remove('hovered')));
        s.addEventListener('click', () => {
            const v = +s.dataset.value;
            input.value = v;
            stars.forEach(x => x.classList.toggle('selected', +x.dataset.value <= v));
        });
    });
})();
</script>
</body>
</html>
