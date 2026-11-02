<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/db.php';
$userId = require_login();

// Only the authenticated user's card is used; a card ID is never accepted from the URL.
$stmt = $conn->prepare('SELECT id, owner FROM cards WHERE user_id = ? ORDER BY id LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$card = $stmt->get_result()->fetch_assoc();
if (!$card) { http_response_code(404); exit('User card not found.'); }
$cardId = (int)$card['id'];

$search = trim((string)($_GET['q'] ?? ''));
$search = mb_substr($search, 0, 100);
$direction = (string)($_GET['direction'] ?? 'all');
if (!in_array($direction, ['all', 'incoming', 'outgoing'], true)) $direction = 'all';
$sort = (string)($_GET['sort'] ?? 'newest');
if (!in_array($sort, ['newest', 'oldest', 'amount_high', 'amount_low'], true)) $sort = 'newest';

function valid_date($value): string {
    if (!is_string($value)) return '';
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : '';
}
$from = valid_date($_GET['from'] ?? '');
$to = valid_date($_GET['to'] ?? '');

$where = ['card_id = ?'];
$types = 'i';
$params = [$cardId];
if ($search !== '') {
    $where[] = 'type LIKE ?';
    $types .= 's';
    // Treat user input as literal text, not as SQL LIKE wildcards.
    $params[] = '%' . addcslashes($search, '%_\\') . '%';
}
if ($direction === 'incoming') $where[] = 'amount > 0';
if ($direction === 'outgoing') $where[] = 'amount < 0';
if ($from !== '') { $where[] = 'created_at >= ?'; $types .= 's'; $params[] = $from . ' 00:00:00'; }
if ($to !== '') { $where[] = 'created_at < DATE_ADD(?, INTERVAL 1 DAY)'; $types .= 's'; $params[] = $to . ' 00:00:00'; }
$whereSql = implode(' AND ', $where);

$countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM transactions WHERE ' . $whereSql);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$total = (int)$countStmt->get_result()->fetch_assoc()['total'];

$perPage = 20;
$totalPages = max(1, (int)ceil($total / $perPage));
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$orderBy = match ($sort) {
    'oldest' => 'created_at ASC, id ASC',
    'amount_high' => 'ABS(amount) DESC, created_at DESC, id DESC',
    'amount_low' => 'ABS(amount) ASC, created_at DESC, id DESC',
    default => 'created_at DESC, id DESC',
};
$stmt = $conn->prepare('SELECT id, amount, type, created_at FROM transactions WHERE ' . $whereSql . ' ORDER BY ' . $orderBy . ' LIMIT ? OFFSET ?');
$queryParams = [...$params, $perPage, $offset];
$stmt->bind_param($types . 'ii', ...$queryParams);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function page_url(int $number): string {
    $query = $_GET;
    $query['page'] = $number;
    return 'history.php?' . http_build_query($query);
}
function transaction_title(string $type): string {
    return preg_replace(['/^Перевод на карту/u', '/^Перевод с карты/u'], ['Transfer to card', 'Transfer from card'], $type);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions — Sino Money</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <a class="brand" href="index.php"><span class="brand-mark">i</span><span>sino <span class="brand-light">money</span></span></a>
        <div class="side-label">WORKSPACE</div>
        <nav class="nav">
            <a href="index.php"><span>▦</span>Overview</a>
            <a href="transfer.php"><span>⇄</span>Transfers</a>
            <a class="active" href="history.php" aria-current="page"><span>◷</span>Transactions</a>
        </nav>
        <div class="sidebar-foot"><span class="status-dot"></span>Demo account<small>Virtual funds only</small></div>
    </aside>
    <main class="main">
        <header class="topbar">
            <span class="breadcrumb">My account <span class="slash">/</span> Transactions</span>
            <form method="post" action="logout.php" class="logout-form"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button type="submit" class="logout-link logout-button">Sign out</button></form>
            <span class="avatar"><?= h(mb_substr($card['owner'], 0, 1)) ?></span>
        </header>
        <section class="welcome">
            <div>
                <div class="eyebrow">ACCOUNT ACTIVITY</div>
                <h1>Transactions<span class="accent">.</span></h1>
                <p>Search and filter your virtual money transfers.</p>
            </div>
        </section>
        <section class="history-panel history-page-panel">
            <div class="history-head">
                <div><div class="section-label">HISTORY</div><h2>All transactions</h2></div>
                <span class="history-count"><?= $total ?> result<?= $total === 1 ? '' : 's' ?></span>
            </div>
            <form class="history-filters" method="get" action="history.php">
                <label class="filter-search">Search description
                    <input type="search" name="q" value="<?= h($search) ?>" placeholder="e.g. Transfer to card" maxlength="100">
                </label>
                <label>Type
                    <select name="direction">
                        <option value="all" <?= $direction === 'all' ? 'selected' : '' ?>>All transfers</option>
                        <option value="incoming" <?= $direction === 'incoming' ? 'selected' : '' ?>>Incoming</option>
                        <option value="outgoing" <?= $direction === 'outgoing' ? 'selected' : '' ?>>Outgoing</option>
                    </select>
                </label>
                <label>From
                    <input type="date" name="from" value="<?= h($from) ?>">
                </label>
                <label>To
                    <input type="date" name="to" value="<?= h($to) ?>">
                </label>
                <label>Sort by
                    <select name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
                        <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
                        <option value="amount_high" <?= $sort === 'amount_high' ? 'selected' : '' ?>>Amount: high to low</option>
                        <option value="amount_low" <?= $sort === 'amount_low' ? 'selected' : '' ?>>Amount: low to high</option>
                    </select>
                </label>
                <div class="filter-actions">
                    <button type="submit" class="filter-apply">Apply filters</button>
                    <a class="filter-reset" href="history.php">Reset</a>
                </div>
            </form>
            <?php if (!$transactions): ?>
                <div class="empty">No matching transactions. Try adjusting your filters.</div>
            <?php else: ?>
                <div class="transaction-list">
                <?php foreach ($transactions as $transaction): ?>
                    <div class="transaction">
                        <span class="transaction-icon"><?= (int)$transaction['amount'] < 0 ? '↗' : '↙' ?></span>
                        <div class="transaction-detail">
                            <strong><?= h(transaction_title((string)$transaction['type'])) ?></strong>
                            <small><?= h(date('d.m.Y · H:i', strtotime($transaction['created_at']))) ?></small>
                        </div>
                        <div class="transaction-sum <?= (int)$transaction['amount'] < 0 ? 'out' : 'in' ?>">
                            <?= (int)$transaction['amount'] > 0 ? '+' : '−' ?><?= rub(abs((int)$transaction['amount'])) ?> ₽
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($totalPages > 1): ?>
            <nav class="history-pagination" aria-label="Transaction pages">
                <?php if ($page > 1): ?><a href="<?= h(page_url($page - 1)) ?>">← Previous</a><?php endif; ?>
                <span>Page <?= $page ?> of <?= $totalPages ?></span>
                <?php if ($page < $totalPages): ?><a href="<?= h(page_url($page + 1)) ?>">Next →</a><?php endif; ?>
            </nav>
            <?php endif; ?>
        </section>
        <footer>sino Money · Demo project · Not a bank</footer>
    </main>
</div>
</body>
</html>
