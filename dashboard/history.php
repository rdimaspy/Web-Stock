<?php
// Validasi sesi, akses bisnis aktif, bahasa, & mata uang
require_once __DIR__ . '/../includes/auth_business.php';

$type = $_GET['type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = trim($_GET['search'] ?? '');

$allowedTypes = ['stock_in', 'stock_out', 'stock_audit'];
if (!in_array($type, $allowedTypes, true)) $type = '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) $date_from = '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) $date_to = '';

// Query riwayat transaksi sesuai filter pengguna
$sql = "SELECT id, product_name, product_code, transaction_type, quantity,
               stock_before, stock_after, notes, transaction_date, selling_price, total_amount
        FROM inventory_transactions
        WHERE business_id = ?
          AND (? = '' OR transaction_type = ?)
          AND (? = '' OR DATE(transaction_date) >= ?)
          AND (? = '' OR DATE(transaction_date) <= ?)
          AND (? = '' OR product_name LIKE CONCAT('%', ?, '%')
               OR product_code LIKE CONCAT('%', ?, '%')
               OR notes LIKE CONCAT('%', ?, '%'))
        ORDER BY transaction_date DESC, id DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param(
    $stmt, "issssssssss",
    $business_id,
    $type, $type,
    $date_from, $date_from,
    $date_to, $date_to,
    $search, $search, $search, $search
);
mysqli_stmt_execute($stmt);
$transactions = mysqli_stmt_get_result($stmt);
$total = mysqli_num_rows($transactions);

function historyTypeLabel(string $type): string {
    return match ($type) {
        'stock_in'    => __('transaction_stock_in'),
        'stock_out'   => __('transaction_stock_out'),
        'stock_audit' => __('transaction_stock_audit'),
        default       => $type,
    };
}

function historyBadgeClass(string $type): string {
    return match ($type) {
        'stock_in'    => 'badge-success',
        'stock_out'   => 'badge-danger',
        'stock_audit' => 'badge-warning',
        default       => 'badge-neutral',
    };
}

$currentPage = 'history.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('history_title'), ENT_QUOTES, 'UTF-8') ?> - StockIn</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=2">
    <script src="https://unpkg.com/lucide@latest" defer></script>
    <script src="../assets/js/app.js?v=2" defer></script>
</head>
<body>

<div class="dashboard-layout">
    <?php require __DIR__ . '/components/sidebar.php'; ?>

    <main class="dashboard-main">
        <?php require __DIR__ . '/components/header.php'; ?>

        <section class="page-section">
            <div class="page-header">
                <h1 class="page-title"><?= htmlspecialchars(__('history_title'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="page-desc"><?= htmlspecialchars(__('history_description'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <!-- Form Filter Riwayat Transaksi -->
            <form method="GET" class="filter-bar">
                <input type="hidden" name="business_id" value="<?= (int) $business_id ?>">

                <div style="flex:1; min-width:170px;">
                    <label class="form-label"><?= htmlspecialchars(__('history_search_placeholder'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input name="search" class="form-control" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="<?= htmlspecialchars(__('history_search_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div>
                    <label class="form-label"><?= htmlspecialchars(__('transaction_type_label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select name="type" class="form-control">
                        <option value=""><?= htmlspecialchars(__('all_transaction_types'), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php foreach ($allowedTypes as $opt): ?>
                            <option value="<?= $opt ?>" <?= $type === $opt ? 'selected' : '' ?>>
                                <?= htmlspecialchars(historyTypeLabel($opt), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label"><?= htmlspecialchars(__('date_from'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div>
                    <label class="form-label"><?= htmlspecialchars(__('date_to'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <button type="submit" class="btn btn-primary"><?= htmlspecialchars(__('apply_filter'), ENT_QUOTES, 'UTF-8') ?></button>
                <a href="history.php?business_id=<?= (int) $business_id ?>" class="btn btn-danger"><?= htmlspecialchars(__('reset_filter'), ENT_QUOTES, 'UTF-8') ?></a>
            </form>

            <!-- Tabel Riwayat Transaksi -->
            <div class="table-wrapper">
                <div class="table-top-bar">
                    <span><?= htmlspecialchars(__('transactions_found'), ENT_QUOTES, 'UTF-8') ?>: <?= number_format($total) ?></span>
                </div>

                <div class="table-scroll" style="height:45vh; min-height:300px;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php foreach (['transaction_date', 'transaction_type_label', 'product_name', 'quantity', 'selling_price', 'total_amount', 'stock_before', 'stock_after', 'notes'] as $key): ?>
                                    <th><?= htmlspecialchars(__($key), ENT_QUOTES, 'UTF-8') ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total === 0): ?>
                                <tr>
                                    <td colspan="9" class="empty-cell"><?= htmlspecialchars(__('no_transactions'), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = mysqli_fetch_assoc($transactions)):
                                    $isOut = $row['transaction_type'] === 'stock_out';
                                ?>
                                    <tr>
                                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['transaction_date'])), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <span class="badge badge-pill <?= historyBadgeClass($row['transaction_type']) ?>">
                                                <?= htmlspecialchars(historyTypeLabel($row['transaction_type']), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($row['product_name'] ?: __('product_unavailable'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= number_format((int) $row['quantity']) ?></td>
                                        <td><?= ($isOut && $row['selling_price'] > 0) ? htmlspecialchars(formatMoneyPHP($row['selling_price'], $business['currency']), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                        <td><?= ($isOut && $row['total_amount'] > 0) ? htmlspecialchars(formatMoneyPHP($row['total_amount'], $business['currency']), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                        <td><?= $row['stock_before'] === null ? '—' : number_format((int) $row['stock_before']) ?></td>
                                        <td><?= $row['stock_after'] === null ? '—' : number_format((int) $row['stock_after']) ?></td>
                                        <td class="cell-wrap" style="color:#81776D; font-size:13px; max-width:220px;">
                                            <?= !empty(trim($row['notes'] ?? '')) ? htmlspecialchars($row['notes'], ENT_QUOTES, 'UTF-8') : '<span style="color:#B8B0A6;">—</span>' ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
