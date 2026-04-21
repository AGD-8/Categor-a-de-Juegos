<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$pdo      = getDB();
$platform = $_GET['platform'] ?? 'all';
$search   = trim($_GET['search'] ?? '');

$query      = 'SELECT g.*, COALESCE(AVG(r.rating),0) as avg_rating, COUNT(r.id) as review_count FROM games g LEFT JOIN reviews r ON g.id = r.game_id';
$conditions = []; $params = [];

if ($platform !== 'all') { $conditions[] = 'g.platform = ?'; $params[] = $platform; }
if ($search !== '') {
    $conditions[] = '(g.title LIKE ? OR g.developer LIKE ? OR g.genre LIKE ?)';
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($conditions) $query .= ' WHERE ' . implode(' AND ', $conditions);
$query .= ' GROUP BY g.id ORDER BY g.created_at DESC';

$stmt = $pdo->prepare($query); $stmt->execute($params);
$games = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APW Juegos — Descubre, Valora y Comenta Videojuegos</title>
    <meta name="description" content="Plataforma de videojuegos para PS4, PS5 y Xbox. Valora, comenta y descubre los mejores juegos.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/games.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<section class="hero">
    <h1>🎮 APW Juegos</h1>
    <p>Descubre, valora y comenta los mejores juegos de PS4, PS5 y Xbox</p>
    <form method="GET" action="index.php" class="hero-search">
        <input type="text" name="search" placeholder="Buscar juego, desarrollador…"
               value="<?= sanitize($search) ?>" id="search-input" autocomplete="off">
        <?php if ($platform !== 'all'): ?>
            <input type="hidden" name="platform" value="<?= sanitize($platform) ?>">
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>
</section>

<div class="container">
    <div class="filter-bar">
        <button class="filter-btn <?= $platform==='all'  ? 'active':'' ?>" onclick="filterPlat('all')">Todos</button>
        <button class="filter-btn <?= $platform==='PS4'  ? 'active':'' ?>" onclick="filterPlat('PS4')">PS4</button>
        <button class="filter-btn <?= $platform==='PS5'  ? 'active':'' ?>" onclick="filterPlat('PS5')">PS5</button>
        <button class="filter-btn <?= $platform==='Xbox' ? 'active':'' ?>" onclick="filterPlat('Xbox')">Xbox</button>
        <?php if (isLoggedIn()): ?>
            <a href="add_game.php" class="btn btn-primary btn-sm" style="margin-left:auto">+ Añadir Juego</a>
        <?php endif; ?>
    </div>

    <?php if (empty($games)): ?>
        <div class="empty-state">
            <p>🎮 No se encontraron juegos.
            <?php if (isLoggedIn()): ?><a href="add_game.php">¡Añade el primero!</a><?php endif; ?></p>
        </div>
    <?php else: ?>
    <div class="games-grid" id="games-grid">
        <?php foreach ($games as $g): ?>
        <div class="game-card" data-platform="<?= $g['platform'] ?>">
            <a href="game_detail.php?id=<?= $g['id'] ?>" class="game-card-img-link">
                <div class="game-card-img-wrap">
                    <img src="<?= getGameImageSrc($g['image']) ?>"
                         alt="<?= sanitize($g['title']) ?>"
                         class="game-card-img"
                         onerror="this.src='assets/img/default_cover.png'">
                    <div class="game-card-img-badge">
                        <span class="badge badge-<?= strtolower($g['platform']) ?>"><?= $g['platform'] ?></span>
                    </div>
                </div>
            </a>
            <div class="game-card-body">
                <div class="game-card-meta">
                    <span class="badge badge-<?= strtolower($g['platform']) ?>"><?= $g['platform'] ?></span>
                    <span class="game-year"><?= $g['release_year'] ?></span>
                </div>
                <h3 class="game-card-title">
                    <a href="game_detail.php?id=<?= $g['id'] ?>"><?= sanitize($g['title']) ?></a>
                </h3>
                <p class="game-card-dev">👾 <?= sanitize($g['developer']) ?></p>
                <div class="game-card-rating">
                    <?php $avg = round($g['avg_rating']); for ($i=1;$i<=5;$i++): ?>
                        <span class="star <?= $i<=$avg?'filled':'' ?>">★</span>
                    <?php endfor; ?>
                    <span class="rating-count">(<?= $g['review_count'] ?>)</span>
                </div>
                <div class="game-card-actions">
                    <a href="game_detail.php?id=<?= $g['id'] ?>" class="btn btn-secondary btn-sm">Ver más</a>
                    <a href="chat.php?game_id=<?= $g['id'] ?>" class="btn btn-primary btn-sm">💬 Comentar</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
<script>
function filterPlat(p) {
    const url = new URL(window.location.href);
    p === 'all' ? url.searchParams.delete('platform') : url.searchParams.set('platform', p);
    window.location.href = url.toString();
}
</script>
</body>
</html>
