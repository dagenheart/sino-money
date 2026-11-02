<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    if (!csrf_valid()) $error = 'Your session has expired. Refresh the page.';
    elseif (mb_strlen($name) < 2 || mb_strlen($name) > 50) $error = 'Name must contain 2 to 50 characters.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $error = 'Enter a valid email address.';
    elseif (strlen($password) < 10 || strlen($password) > 256) $error = 'Password must contain 10 to 256 characters.';
    else {
        try {
            $conn->begin_transaction();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $name, $email, $hash);
            $stmt->execute();
            $userId = $conn->insert_id;
            // Random, unique demo card number; never use for real payment cards.
            $cardNumber = '';
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $candidate = '9' . (string)random_int(100000000, 999999999) . (string)random_int(100000, 999999);
                $stmt = $conn->prepare('SELECT id FROM cards WHERE card_number = ?');
                $stmt->bind_param('s', $candidate);
                $stmt->execute();
                if (!$stmt->get_result()->fetch_assoc()) { $cardNumber = $candidate; break; }
            }
            if ($cardNumber === '') throw new RuntimeException('Could not create a virtual card.');
            $initialBalance = 10000;
            $stmt = $conn->prepare("INSERT INTO cards (user_id, owner, card_number, pin, balance) VALUES (?, ?, ?, '', ?)");
            $stmt->bind_param('issi', $userId, $name, $cardNumber, $initialBalance);
            $stmt->execute();
            $conn->commit();
            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            header('Location: index.php', true, 303); exit;
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $error = $e->getCode() === 1062 ? 'This email address is already registered.' : 'Could not create the account.';
        } catch (RuntimeException $e) {
            $conn->rollback();
            $error = 'Could not create a virtual card.';
        }
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign up — Sino Money</title><link rel="stylesheet" href="style.css"></head><body class="auth-body">
<main class="auth-card"><div class="auth-brand">sino <span>money</span></div><div class="section-label">NEW ACCOUNT</div><h1>Create an account</h1><p>Create a demo account with 10,000 virtual RUB.</p>
<?php if ($error): ?><div class="error" role="alert"><?= h($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><label for="name">Your name</label><input id="name" name="name" maxlength="50" required value="<?= h($_POST['name'] ?? '') ?>"><label for="email">Email address</label><input id="email" type="email" name="email" autocomplete="email" required value="<?= h($_POST['email'] ?? '') ?>"><label for="password">Password (at least 10 characters)</label><input id="password" type="password" name="password" minlength="10" autocomplete="new-password" required><button class="primary-btn" type="submit">Create account →</button></form>
<p>Already have an account? <a href="login.php">Sign in</a></p><small>Virtual funds only · Demo project</small></main></body></html>
