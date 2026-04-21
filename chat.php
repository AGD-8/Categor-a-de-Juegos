<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$pdo    = getDB();
$gameId = (int)($_GET['game_id'] ?? 0);

if (!$gameId) { header('Location: index.php'); exit; }

$game = $pdo->prepare('SELECT * FROM games WHERE id = ?');
$game->execute([$gameId]); $game = $game->fetch();
if (!$game) { header('Location: index.php'); exit; }

// Post message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $msg = trim($_POST['message'] ?? '');
    if ($msg !== '') {
        $pdo->prepare('INSERT INTO chat_messages (game_id, user_id, message) VALUES (?,?,?)')
            ->execute([$gameId, $_SESSION['user_id'], $msg]);
        header("Location: chat.php?game_id=$gameId");
        exit;
    }
}

$messages = $pdo->prepare(
    'SELECT m.*, u.username, u.avatar FROM chat_messages m
     JOIN users u ON m.user_id = u.id
     WHERE m.game_id = ?
     ORDER BY m.created_at ASC'
);
$messages->execute([$gameId]); $messages = $messages->fetchAll();
$myId = $_SESSION['user_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat — <?= sanitize($game['title']) ?> — APW Juegos</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/chat.css">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container" style="padding-top:1rem;padding-bottom:0">
    <div class="chat-layout">

        <!-- SIDEBAR -->
        <aside class="chat-sidebar">
            <div class="chat-game-info">
                <img src="<?= getGameImageSrc($game['image']) ?>" alt="<?= sanitize($game['title']) ?>"
                     class="chat-game-cover" onerror="this.src='assets/img/default_cover.png'">
                <h2 class="chat-game-title"><?= sanitize($game['title']) ?></h2>
                <p class="chat-game-platform">
                    <span class="badge badge-<?= strtolower($game['platform']) ?>"><?= $game['platform'] ?></span>
                    &nbsp;<?= $game['release_year'] ?>
                </p>
            </div>
            <h3>Estadísticas</h3>
            <?php $rating = getAverageRating($gameId); ?>
            <div style="margin-bottom:.8rem">
                <?= renderStars((float)$rating['avg_rating']) ?>
                <div style="font-size:.82rem;color:var(--muted);margin-top:.3rem">
                    <?= number_format($rating['avg_rating'],1) ?>/5 · <?= $rating['total'] ?> reseñas
                </div>
            </div>
            <div style="font-size:.83rem;color:var(--muted);margin-bottom:1.2rem">
                💬 <?= count($messages) ?> mensajes en este chat
            </div>
            <a href="game_detail.php?id=<?= $gameId ?>" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;margin-bottom:.5rem">Ver detalles del juego</a>
            <a href="index.php" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">← Catálogo</a>
        </aside>

        <!-- MAIN CHAT -->
        <main class="chat-main">
            <div class="chat-header">
                <h2><span class="online-dot"></span> Chat — <?= sanitize($game['title']) ?></h2>
                <span style="font-size:.82rem;color:var(--muted)">Opiniones, estrategias y recomendaciones</span>
            </div>

            <div class="chat-messages" id="chat-messages">
                <?php if (empty($messages)): ?>
                    <div class="chat-empty">🎮 Sé el primero en comentar sobre <?= sanitize($game['title']) ?></div>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                    <?php $isOwn = ($m['user_id'] == $myId); ?>
                    <div class="chat-msg <?= $isOwn ? 'own' : '' ?>">
                        <img src="<?= getAvatarSrc($m['avatar']) ?>" alt="Avatar" class="chat-msg-avatar"
                             onerror="this.src='assets/img/default_avatar.png'">
                        <div class="chat-msg-body">
                            <div class="chat-msg-meta">
                                <span class="chat-msg-user"><?= sanitize($m['username']) ?></span>
                                <span class="chat-msg-time"><?= timeAgo($m['created_at']) ?></span>
                            </div>
                            <div class="chat-msg-bubble"><?= sanitize($m['message']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (isLoggedIn()): ?>
            <form method="POST" class="chat-form" id="chat-form">
                <textarea name="message" id="chat-input" placeholder="Escribe un mensaje… (Enter para enviar)"
                          rows="1" maxlength="1000"></textarea>
                <button type="submit" class="chat-send-btn" title="Enviar">➤</button>
            </form>
            <?php else: ?>
            <div class="chat-guest-prompt">
                <a href="login.php">Inicia sesión</a> o <a href="register.php">regístrate</a> para participar en el chat.
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
<script>
// Auto-scroll to bottom
const chatMessages = document.getElementById('chat-messages');
if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;

// Send on Enter (Shift+Enter for new line)
const chatInput = document.getElementById('chat-input');
if (chatInput) {
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chat-form').submit();
        }
    });
    // Auto-resize
    chatInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
}
</script>
</body>
</html>
