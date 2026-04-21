<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$pdo   = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']    ?? '');
    $platform = $_POST['platform']      ?? '';
    $dev      = trim($_POST['developer']?? '');
    $year     = (int)($_POST['year']    ?? 0);
    $genre    = trim($_POST['genre']    ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $validPlats = ['PS4','PS5','Xbox'];

    if ($title === '' || !in_array($platform, $validPlats)) {
        $error = 'El título y la plataforma son obligatorios.';
    } elseif ($year < 1970 || $year > 2030) {
        $error = 'Introduce un año válido.';
    } else {
        $imageName = 'default_cover.png';
        if (!empty($_FILES['image']['name'])) {
            $res = uploadImage($_FILES['image'], UPLOAD_DIR_GAMES, 'game');
            if ($res['success']) { $imageName = $res['filename']; }
            else { $error = $res['error']; }
        }
        if (!$error) {
            $pdo->prepare('INSERT INTO games (title, platform, developer, release_year, genre, description, image) VALUES (?,?,?,?,?,?,?)')
                ->execute([$title, $platform, $dev, $year, $genre, $desc, $imageName]);
            $success = "¡Juego \"$title\" añadido correctamente!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir juego — APW Juegos</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/games.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container" style="max-width:720px">
    <h1 class="page-title">Añadir nuevo juego</h1>
    <p class="page-subtitle">Comparte un juego con la comunidad</p>

    <?php if ($error):   ?><div class="alert alert-error"><?=   sanitize($error)   ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?> <a href="index.php">Ver catálogo →</a></div><?php endif; ?>

    <div class="add-game-card">
        <form method="POST" enctype="multipart/form-data" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label for="title">Nombre del juego *</label>
                    <input type="text" id="title" name="title" placeholder="Ej: Elden Ring" required
                           value="<?= sanitize($_POST['title'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="platform">Plataforma *</label>
                    <select id="platform" name="platform" required>
                        <option value="">Seleccionar…</option>
                        <?php foreach(['PS4','PS5','Xbox'] as $p): ?>
                            <option value="<?= $p ?>" <?= ($_POST['platform']??'')===$p?'selected':'' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="developer">Desarrollador</label>
                    <input type="text" id="developer" name="developer" placeholder="Ej: FromSoftware"
                           value="<?= sanitize($_POST['developer'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="year">Año de lanzamiento</label>
                    <input type="number" id="year" name="year" min="1970" max="2030"
                           placeholder="Ej: 2024" value="<?= sanitize($_POST['year'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="genre">Género</label>
                    <input type="text" id="genre" name="genre" placeholder="Ej: RPG, Acción, Aventura"
                           value="<?= sanitize($_POST['genre'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="image">Imagen de portada</label>
                    <input type="file" id="image" name="image" accept="image/*" onchange="previewCover(this)">
                    <img id="cover-preview" class="cover-preview" src="assets/img/default_cover.png"
                         alt="Portada" style="display:none; margin-top:.5rem">
                </div>
            </div>
            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea id="description" name="description" rows="4"
                          placeholder="Describe de qué trata el juego…"><?= sanitize($_POST['description'] ?? '') ?></textarea>
            </div>
            <div style="display:flex;gap:.75rem;margin-top:.5rem">
                <button type="submit" class="btn btn-primary">➕ Añadir juego</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script>
function previewCover(input) {
    const preview = document.getElementById('cover-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
