<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error   = '';
$success = isset($_GET['registered']) ? '¡Cuenta creada! Ahora inicia sesión.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Rellena todos los campos.';
    } else {
        $stmt = getDB()->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user['id'], $user['username']);
            header('Location: index.php');
            exit;
        }
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión — APW Juegos</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-page">
<?php include 'includes/navbar.php'; ?>
<div class="auth-body">
    <div class="auth-card">
        <div class="auth-logo">
            <h1>🎮 APW Juegos</h1>
            <p>Tu comunidad gamer</p>
        </div>
        <h2>Iniciar sesión</h2>
        <p class="subtitle">Bienvenido de nuevo</p>

        <?php if ($error):   ?><div class="alert alert-error"><?=   sanitize($error)   ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= sanitize($success) ?></div><?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" placeholder="Tu nombre de usuario" required
                       value="<?= sanitize($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" placeholder="Tu contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </form>
        <div class="auth-footer">¿No tienes cuenta? <a href="register.php">Regístrate gratis</a></div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
