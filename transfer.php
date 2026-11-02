<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
$userId = require_login();
$stmt = $conn->prepare('SELECT id FROM cards WHERE user_id = ? ORDER BY id LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$ownCard = $stmt->get_result()->fetch_assoc();
if (!$ownCard) { http_response_code(404); exit('User card not found.'); }
$fromId = (int)$ownCard['id'];
$error = '';
if (empty($_SESSION['transfer_nonce'])) {
    $_SESSION['transfer_nonce'] = bin2hex(random_bytes(24));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toCard = trim((string)($_POST['to_card'] ?? ''));
    $amountInput = (string)($_POST['amount'] ?? '');
    $amount = filter_var($amountInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 150000]]);
    if (!csrf_valid()) {
        $error = 'Your session has expired. Refresh the page.';
    } elseif (!isset($_POST['transfer_nonce']) || !is_string($_POST['transfer_nonce']) || !hash_equals($_SESSION['transfer_nonce'], $_POST['transfer_nonce'])) {
        $error = 'This transfer form has already been used. Refresh the page.';
    } elseif (!preg_match('/^[0-9]{16}$/D', $toCard)) {
        $error = 'Enter a 16-digit card number.';
    } elseif ($amount === false) {
        $error = 'Enter a whole-number amount between 1 and 150,000 RUB.';
    } else {
        try {
            $conn->begin_transaction();
            $stmt = $conn->prepare('SELECT id, card_number, balance FROM cards WHERE id = ? FOR UPDATE');
            $stmt->bind_param('i', $fromId);
            $stmt->execute();
            $sender = $stmt->get_result()->fetch_assoc();
            $stmt = $conn->prepare('SELECT id, card_number FROM cards WHERE card_number = ? FOR UPDATE');
            $stmt->bind_param('s', $toCard);
            $stmt->execute();
            $recipient = $stmt->get_result()->fetch_assoc();
            if (!$sender) { throw new RuntimeException('Sender card not found.'); }
            if (!$recipient) { throw new RuntimeException('Recipient card not found.'); }
            if ((int)$recipient['id'] === $fromId) { throw new RuntimeException('You cannot transfer money to the same card.'); }
            if ((int)$sender['balance'] < $amount) { throw new RuntimeException('Insufficient funds.'); }
            $recipientId = (int)$recipient['id'];
            $stmt = $conn->prepare('UPDATE cards SET balance = balance - ? WHERE id = ? AND balance >= ?');
            $stmt->bind_param('iii', $amount, $fromId, $amount);
            $stmt->execute();
            if ($stmt->affected_rows !== 1) { throw new RuntimeException('Insufficient funds.'); }
            $stmt = $conn->prepare('UPDATE cards SET balance = balance + ? WHERE id = ?');
            $stmt->bind_param('ii', $amount, $recipientId);
            $stmt->execute();
            $stmt = $conn->prepare('INSERT INTO transactions (card_id, amount, type, created_at) VALUES (?, ?, ?, NOW())');
            $debit = -$amount;
            $debitText = 'Transfer to card •••• ' . substr($toCard, -4);
            $stmt->bind_param('iis', $fromId, $debit, $debitText);
            $stmt->execute();
            $creditText = 'Transfer from card •••• ' . substr($sender['card_number'], -4);
            $stmt->bind_param('iis', $recipientId, $amount, $creditText);
            $stmt->execute();
            $conn->commit();
            $_SESSION['transfer_nonce'] = bin2hex(random_bytes(24));
            header('Location: index.php?transfer=success', true, 303);
            exit;
        } catch (RuntimeException $e) {
            $conn->rollback();
            $error = $e->getMessage();
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $error = 'Transfer failed. Please try again.';
        }
    }
}
$stmt = $conn->prepare('SELECT owner, card_number, balance FROM cards WHERE id = ?');
$stmt->bind_param('i', $fromId);
$stmt->execute();
$card = $stmt->get_result()->fetch_assoc();
if (!$card) { http_response_code(404); exit('Card not found.'); }
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>
            Transfer — Sino Money
        </title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="layout">
            <aside class="sidebar">
                <a class="brand" href="index.php">
                <span class="brand-mark">
                i
                </span>
                <span>
                sino
                <span class="brand-light">
                money
                </span>
                </span>
                </a>
                <div class="side-label">
                    WORKSPACE
                </div>
                <nav class="nav">
                    <a href="index.php">
                    <span>
                    ▦
                    </span>
                    Overview
                    </a>
                    <a class="active" href="transfer.php">
                    <span>
                    ⇄
                    </span>
                    Transfers
                    </a>
                    <a href="index.php#history">
                    <span>
                    ◷
                    </span>
                    Transactions
                    </a>
                </nav>
                <div class="sidebar-foot">
                    <span class="status-dot">
                    </span>
                    Demo mode
                    <small>
                    Virtual funds only
                    </small>
                </div>
            </aside>
            <main class="main">
                <header class="topbar">
                    <span class="breadcrumb">
                    My account
                    <span class="slash">
                    /
                    </span>
                    Transfers
                    </span>
                    <form method="post" action="logout.php" class="logout-form"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button type="submit" class="logout-link logout-button">Sign out</button></form>
                    <span class="avatar">
                    <?= h(mb_substr($card['owner'], 0, 1)) ?>
                    </span>
                </header>
                <section class="welcome">
                    <div>
                        <div class="eyebrow">
                            PAYMENTS & TRANSFERS
                        </div>
                        <h1>
                            New transfer
                            <span class="accent">
                            .
                            </span>
                        </h1>
                        <p>
                            Send virtual funds to another user.
                        </p>
                    </div>
                </section>
                <div class="transfer-layout">
                    <section class="form-panel">
                        <div class="section-label">
                            TRANSFER DETAILS
                        </div>
                        <h2>
                            Recipient details
                        </h2>
                        <?php if ($error): ?>
                        <div class="error" role="alert">
                            <?= h($error) ?>
                        </div>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                            <input type="hidden" name="transfer_nonce" value="<?= h($_SESSION['transfer_nonce']) ?>">
                            <label for="to_card">
                            Recipient card number
                            </label>
                            <input id="to_card" name="to_card" type="text" inputmode="numeric" autocomplete="off" pattern="[0-9]{16}" maxlength="16" placeholder="0000 0000 0000 0000" value="<?= h($_POST['to_card'] ?? '') ?>
                            " required>
                            <div class="hint">
                                16 digits, no spaces
                            </div>
                            <label for="amount">
                            Transfer amount, RUB
                            </label>
                            <input id="amount" name="amount" type="number" min="1" max="150000" step="1" placeholder="e.g. 500" value="<?= h($_POST['amount'] ?? '') ?>
                            " required>
                            <div class="hint">
                                Maximum 150,000 RUB per transfer
                            </div>
                            <button class="primary-btn" type="submit">
                            Transfer funds
                            <span>
                            →
                            </span>
                            </button>
                        </form>
                    </section>
                    <aside class="transfer-summary">
                        <div class="section-label">
                            FROM ACCOUNT
                        </div>
                        <div class="mini-card flashlight-card">
                            <div class="mini-card-top">
                                sino money
                                <span>
                                ●●
                                </span>
                            </div>
                            <div class="card-details" data-card-details>
                                <div class="mini-card-number" data-masked="•••• •••• •••• <?= h(substr($card['card_number'], -4)) ?>" data-full="<?= h($card['card_number']) ?>">•••• •••• •••• <?= h(substr($card['card_number'], -4)) ?></div>
                                <div class="card-detail-actions">
                                    <button type="button" class="card-reveal" aria-pressed="false" aria-label="Show full card number">Show details</button>
                                    <button type="button" class="card-copy" hidden>Copy number</button>
                                </div>
                            </div>
                            <div class="mini-card-owner">
                                <?= h($card['owner']) ?>
                            </div>
                        </div>
                        <div class="summary-row">
                            <span>
                            Available balance
                            </span>
                            <strong>
                            <?= rub($card['balance']) ?>
                            ₽
                            </strong>
                        </div>
                        <div class="summary-row">
                            <span>
                            Fee
                            </span>
                            <strong>
                            0 ₽
                            </strong>
                        </div>
                        <p class="summary-disclaimer">
                            This is a demo application. Transfers work only between virtual cards in the local database.
                        </p>
                    </aside>
                </div>
                <footer>
                    sino Money · Demo project · Not a bank
                </footer>
            </main>
        </div>
        <script src="card-ui.js" defer></script>
    </body>
</html>
