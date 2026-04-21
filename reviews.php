<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$pdo   = getDB();
$games = $pdo->query('SELECT id, title, platform FROM games ORDER BY title')->fetchAll();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn()) { header('Location: login.php'); exit; }
    $game_id = (int)($_POST['game_id'] ?? 0);
    $rating  = (int)($_POST['rating']  ?? 0);
    $comment = trim($_POST['comment']  ?? '');
    if (!$game_id || $rating < 1 || $rating > 5) {
        $error = 'Selecciona un juego y una puntuación.';
    } else {
        $pdo->prepare('INSERT INTO reviews (game_id, user_id, rating, comment) VALUES (?,?,?,?)')
            ->execute([$game_id, $_SESSION['user_id'], $rating, $comment]);
        $success = '¡Reseña publicada!';
    }
}

// Recent reviews
$recent = $pdo->query('SELECT r.*, u.username, u.avatar, g.title as game_title, g.platform FROM reviews r JOIN users u ON r.user_id=u.id JOIN games g ON r.game_id=g.id ORDER BY r.created_at DESC LIMIT 20')->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseñas — APW Juegos</title>
    <meta name="description" content="Lee y escribe reseñas sobre tus juegos favoritos de PS4, PS5 y Xbox.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/games.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container">
    <h1 class="page-title">⭐ Reseñas de la comunidad</h1>
    <p class="page-subtitle">Comparte tu opinión y lee lo que otros gamers piensan</p>

    <div style="display:grid;grid-template-columns:380px 1fr;gap:2rem;align-items:start">

        <!-- POST REVIEW FORM -->
        <?php if (isLoggedIn()): ?>
        <div class="review-form-card" style="position:sticky;top:80px">
            <h3>✍️ Escribir reseña</h3>
            <?php if ($error):   ?><div class="alert alert-error"><?=   sanitize($error)   ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>
            <form method="POST" id="review-form">
                <div class="form-group">
                    <label for="game_id">Juego</label>
                    <select id="game_id" name="game_id" required>
                        <option value="">Selecciona un juego…</option>
                        <?php foreach ($games as $g): ?>
                            <option value="<?= $g['id'] ?>"
                                <?= ($_POST['game_id']??'')==$g['id']?'selected':'' ?>>
                                <?= sanitize($g['title']) ?> (<?= $g['platform'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="star-rating-wrapper">
                    <label>Puntuación</label>
                    <?= renderStars(0, true) ?>
                    <input type="hidden" name="rating" id="rating-value-input" value="0">
                </div>
                <div class="form-group">
                    <label for="comment">Comentario (opcional)</label>
                    <textarea id="comment" name="comment" rows="4" placeholder="Cuéntanos tu experiencia, estrategias, recomendaciones…"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Publicar reseña</button>
            </form>
        </div>
        <?php else: ?>
        <div class="review-form-card" style="text-align:center;padding:2.5rem">
            <p style="font-size:2rem;margin-bottom:.5rem">🎮</p>
            <h3 style="margin-bottom:.75rem">¿Quieres opinar?</h3>
            <p style="color:var(--text2);margin-bottom:1.2rem">Crea tu cuenta gratis o inicia sesión.</p>
            <div style="display:flex;gap:.6rem;justify-content:center">
                <a href="login.php"    class="btn btn-primary">Iniciar sesión</a>
                <a href="register.php" class="btn btn-secondary">Registrarse</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- RECENT REVIEWS -->
        <div>
            <div class="section-header"><h2>Últimas reseñas</h2></div>
            <?php if (empty($recent)): ?>
                <div class="empty-state"><p>Aún no hay reseñas. ¡Sé el primero!</p></div>
            <?php else: ?>
            <div class="reviews-list">
                <?php foreach ($recent as $rev): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="review-user">
                            <img src="<?= getAvatarSrc($rev['avatar']) ?>" alt="" class="review-avatar"
                                 onerror="this.src='assets/img/default_avatar.png'">
                            <div>
                                <div class="review-username"><?= sanitize($rev['username']) ?></div>
                                <div style="font-size:.78rem;color:var(--muted)">
                                    sobre <a href="game_detail.php?id=<?= $rev['game_id'] ?>"><?= sanitize($rev['game_title']) ?></a>
                                    <span class="badge badge-<?= strtolower($rev['platform']) ?>" style="vertical-align:middle;margin-left:4px"><?= $rev['platform'] ?></span>
                                </div>
                            </div>
                        </div>
                        <div style="text-align:right">
                            <?= renderStars((float)$rev['rating']) ?>
                            <span class="review-date"><?= timeAgo($rev['created_at']) ?></span>
                        </div>
                    </div>
                    <?php if ($rev['comment']): ?>
                        <p class="review-text"><?= sanitize($rev['comment']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
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
