<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if (!csrf_valid()) {
        $error = 'Your session has expired. Refresh the page.';
    } else {
        $stmt = $conn->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            header('Location: index.php', true, 303); exit;
        }
        $error = 'Invalid email or password.';
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in — Sino Money</title><link rel="stylesheet" href="style.css"></head><body class="auth-body">
<main class="auth-card"><div class="auth-brand">sino <span>money</span></div><div class="section-label">MY ACCOUNT</div><h1>Sign in</h1><p>Manage your virtual funds.</p>
<?php if ($error): ?><div class="error" role="alert"><?= h($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><label for="email">Email address</label><input id="email" type="email" name="email" autocomplete="email" required value="<?= h($_POST['email'] ?? '') ?>"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" required><button class="primary-btn" type="submit">Sign in →</button></form>
<p>New here? <a href="register.php">Create an account</a></p><small>Virtual funds only · Demo project</small></main></body></html>
