<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
$userId = require_login();
$stmt = $conn->prepare('SELECT id, owner, card_number, balance FROM cards WHERE user_id = ? ORDER BY id LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$card = $stmt->get_result()->fetch_assoc();
if (!$card) { http_response_code(404); exit('User card not found.'); }
$cardId = (int)$card['id'];
$stmt = $conn->prepare('SELECT amount, type, created_at FROM transactions WHERE card_id = ? ORDER BY created_at DESC, id DESC LIMIT 6');
$stmt->bind_param('i', $cardId);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overview — Sino Money</title>
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
                    <a class="active" href="index.php">
                    <span>
                    ▦
                    </span>
                    Overview
                    </a>
                    <a href="transfer.php">
                    <span>
                    ⇄
                    </span>
                    Transfers
                    </a>
                    <a href="history.php">
                    <span>
                    ◷
                    </span>
                    Transactions
                    </a>
                </nav>
                <div class="sidebar-foot">
                    <span class="status-dot">
                    </span>
                    Demo account
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
                    Overview
                    </span>
                    <form method="post" action="logout.php" class="logout-form"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button type="submit" class="logout-link logout-button">Sign out</button></form>
                    <span class="avatar">
                    <?= h(mb_substr($card['owner'], 0, 1)) ?>
                    </span>
                </header>
                <section class="welcome">
                    <div>
                        <div class="eyebrow">
                            YOUR FINANCIAL OVERVIEW
                        </div>
                        <h1>
                            Welcome
                            <span class="accent">
                            .
                            </span>
                        </h1>
                        <p>
                            Manage your virtual funds in one place.
                        </p>
                    </div>
                    <div class="date-pill">
                        <?= h(date('d.m.Y')) ?>
                    </div>
                </section>
                <div class="dashboard">
                    <section class="balance-panel flashlight-card">
                        <div class="panel-heading">
                            <span>
                            Total balance
                            </span>
                            <span class="tiny-pill">
                            ● Active
                            </span>
                        </div>
                        <div class="balance">
                            <?= rub($card['balance']) ?>
                            <span>
                            ₽
                            </span>
                        </div>
                        <div class="balance-note">
                            Available on your card
                        </div>
                        <div class="divider-light">
                        </div>
                        <div class="balance-footer">
                            <div>
                                <span class="micro">
                                CARDHOLDER
                                </span>
                                <strong>
                                <?= h($card['owner']) ?>
                                </strong>
                            </div>
                            <div class="card-details" data-card-details>
                                <span class="card-number" data-masked="•••• •••• •••• <?= h(substr($card['card_number'], -4)) ?>" data-full="<?= h($card['card_number']) ?>">•••• •••• •••• <?= h(substr($card['card_number'], -4)) ?></span>
                                <div class="card-detail-actions">
                                    <button type="button" class="card-reveal" aria-pressed="false" aria-label="Show full card number">Show details</button>
                                    <button type="button" class="card-copy" hidden>Copy number</button>
                                </div>
                            </div>
                        </div>
                        <div class="circle-art circle-one">
                        </div>
                        <div class="circle-art circle-two">
                        </div>
                    </section>
                    <section class="quick-panel">
                        <div class="section-label">
                            QUICK ACTIONS
                        </div>
                        <h2>
                            What would you like to do?
                        </h2>
                        <a class="action-tile" href="transfer.php">
                        <span class="tile-icon">
                        ↗
                        </span>
                        <span>
                        <strong>
                        Transfer funds
                        </strong>
                        <small>
                        To another demo card
                        </small>
                        </span>
                        <span class="arrow">
                        →
                        </span>
                        </a>
                        <a class="action-tile" href="history.php">
                        <span class="tile-icon alt">
                        ◷
                        </span>
                        <span>
                        <strong>
                        Transaction history
                        </strong>
                        <small>
                        Recent account activity
                        </small>
                        </span>
                        <span class="arrow">
                        →
                        </span>
                        </a>
                    </section>
                </div>
                <section class="history-panel" id="history">
                    <div class="history-head">
                        <div>
                            <div class="section-label">
                                ACTIVITY
                            </div>
                            <h2>
                                Recent transactions
                            </h2>
                        </div>
                        <a class="history-view-all" href="history.php">View all transactions →</a>
                    </div>
                    <?php if (!$transactions): ?>
                    <div class="empty">
                        No transactions yet. Make your first demo transfer.
                    </div>
                    <?php else: ?>
                    <div class="transaction-list">
                        <?php foreach ($transactions as $transaction): ?>
                        <div class="transaction">
                            <span class="transaction-icon">
                            <?= (int)$transaction['amount'] < 0 ? '↗' : '↙' ?>
                            </span>
                            <div class="transaction-detail">
                                <strong>
                                <?= h(preg_replace(['/^Перевод на карту/u', '/^Перевод с карты/u'], ['Transfer to card', 'Transfer from card'], $transaction['type'])) ?>
                                </strong>
                                <small>
                                <?= h(date('d.m.Y · H:i', strtotime($transaction['created_at']))) ?>
                                </small>
                            </div>
                            <div class="transaction-sum <?= (int)$transaction['amount'] < 0 ? 'out' : 'in' ?>">
                                <?= (int)$transaction['amount'] > 0 ? '+' : '−' ?>
                                <?= rub(abs((int)$transaction['amount'])) ?>
                                ₽
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </section>
                <footer>
                    sino Money · Demo project · Not a bank
                </footer>
            </main>
        </div>
    <script src="card-ui.js" defer></script>
</body>
</html>
