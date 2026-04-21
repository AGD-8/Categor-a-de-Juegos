<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$pdo   = getDB();
$user  = getCurrentUser();
$msgs  = [];

// ── Change username ──────────────────────────────────────────────────────────
if (isset($_POST['change_username'])) {
    $newName = trim($_POST['new_username'] ?? '');
    if (strlen($newName) < 3 || strlen($newName) > 50) {
        $msgs['username'] = ['type'=>'error','text'=>'El nombre debe tener 3–50 caracteres.'];
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $newName)) {
        $msgs['username'] = ['type'=>'error','text'=>'Solo letras, números y guiones bajos.'];
    } else {
        $chk = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $chk->execute([$newName, $user['id']]);
        if ($chk->fetch()) {
            $msgs['username'] = ['type'=>'error','text'=>'Ese nombre ya está en uso.'];
        } else {
            $pdo->prepare('UPDATE users SET username = ? WHERE id = ?')->execute([$newName, $user['id']]);
            $_SESSION['username'] = $newName;
            $msgs['username'] = ['type'=>'success','text'=>'Nombre de usuario actualizado.'];
            $user = getCurrentUser();
        }
    }
}

// ── Change password ──────────────────────────────────────────────────────────
if (isset($_POST['change_password'])) {
    $oldPass = $_POST['old_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $conPass = $_POST['confirm_password'] ?? '';

    $row = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $row->execute([$user['id']]);
    $dbHash = $row->fetchColumn();

    if (!password_verify($oldPass, $dbHash)) {
        $msgs['password'] = ['type'=>'error','text'=>'La contraseña actual es incorrecta.'];
    } elseif (strlen($newPass) < 6) {
        $msgs['password'] = ['type'=>'error','text'=>'La nueva contraseña debe tener al menos 6 caracteres.'];
    } elseif ($newPass !== $conPass) {
        $msgs['password'] = ['type'=>'error','text'=>'Las contraseñas nuevas no coinciden.'];
    } else {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($newPass, PASSWORD_BCRYPT), $user['id']]);
        $msgs['password'] = ['type'=>'success','text'=>'Contraseña actualizada correctamente.'];
    }
}

// ── Change avatar ─────────────────────────────────────────────────────────────
if (isset($_POST['change_avatar']) && isset($_FILES['avatar'])) {
    $result = uploadImage($_FILES['avatar'], UPLOAD_DIR_AVATARS, 'avatar');
    if ($result['success']) {
        $pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?')->execute([$result['filename'], $user['id']]);
        $msgs['avatar'] = ['type'=>'success','text'=>'Foto de perfil actualizada.'];
        $user = getCurrentUser();
    } else {
        $msgs['avatar'] = ['type'=>'error','text'=>$result['error']];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi perfil — APW Juegos</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container" style="max-width:750px">
    <div style="margin-bottom:1.5rem">
        <h1 class="page-title">Mi perfil</h1>
        <p class="page-subtitle">Gestiona tu cuenta y personaliza tu perfil</p>
    </div>

    <div class="profile-page-grid">

        <!-- Avatar -->
        <div class="profile-card">
            <h3>🖼️ Foto de perfil</h3>
            <?php if (isset($msgs['avatar'])): ?>
                <div class="alert alert-<?= $msgs['avatar']['type'] ?>"><?= sanitize($msgs['avatar']['text']) ?></div>
            <?php endif; ?>
            <div class="avatar-preview-wrap">
                <img src="<?= getAvatarSrc($user['avatar']) ?>" alt="Avatar" class="avatar-preview" id="avatar-preview"
                     onerror="this.src='assets/img/default_avatar.png'">
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="avatar">Seleccionar imagen (JPG, PNG, WEBP — máx. 5 MB)</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(this)">
                </div>
                <button type="submit" name="change_avatar" class="btn btn-primary">Guardar foto</button>
            </form>
        </div>

        <!-- Username -->
        <div class="profile-card">
            <h3>👤 Cambiar nombre de usuario</h3>
            <?php if (isset($msgs['username'])): ?>
                <div class="alert alert-<?= $msgs['username']['type'] ?>"><?= sanitize($msgs['username']['text']) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="new_username">Nuevo nombre de usuario</label>
                    <input type="text" id="new_username" name="new_username"
                           placeholder="<?= sanitize($user['username']) ?>" maxlength="50">
                </div>
                <button type="submit" name="change_username" class="btn btn-primary">Guardar nombre</button>
            </form>
        </div>

        <!-- Password -->
        <div class="profile-card">
            <h3>🔒 Cambiar contraseña</h3>
            <?php if (isset($msgs['password'])): ?>
                <div class="alert alert-<?= $msgs['password']['type'] ?>"><?= sanitize($msgs['password']['text']) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label for="old_password">Contraseña actual</label>
                    <input type="password" id="old_password" name="old_password" placeholder="Tu contraseña actual">
                </div>
                <div class="form-group">
                    <label for="new_password">Nueva contraseña</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Mínimo 6 caracteres">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmar nueva contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repite la nueva contraseña">
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">Cambiar contraseña</button>
            </form>
        </div>

    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('avatar-preview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
