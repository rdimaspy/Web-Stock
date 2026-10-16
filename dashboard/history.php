<?php
// Validasi autentikasi user dan akses bisnis aktif
require_once __DIR__ . '/../includes/auth_business.php';

// Pengaturan bahasa
require_once __DIR__ . '/../includes/language.php';

// Formatter mata uang
require_once __DIR__ . '/../includes/currency.php';

// Ambil dan validasi filter riwayat transaksi
$type = $_GET['type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = trim($_GET['search'] ?? '');

$allowedTypes = ['stock_in', 'stock_out', 'stock_audit'];

if (!in_array($type, $allowedTypes, true)) {
    $type = '';
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $date_from = '';
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $date_to = '';
}

// Ambil transaksi sesuai filter dan bisnis aktif
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
    $stmt,
    "issssssssss",
    $business_id,
    $type, $type,
    $date_from, $date_from,
    $date_to, $date_to,
    $search, $search, $search, $search
);
mysqli_stmt_execute($stmt);
$transactions = mysqli_stmt_get_result($stmt);
$total = mysqli_num_rows($transactions);

// Ubah kode transaksi menjadi label sesuai bahasa aktif
function historyTypeLabel($type) {
    return match ($type) {
        'stock_in' => __('transaction_stock_in'),
        'stock_out' => __('transaction_stock_out'),
        'stock_audit' => __('transaction_stock_audit'),
        default => $type
    };
}

// Menentukan tampilan warna badge berdasarkan jenis transaksi
function historyTypeBadge($type) {
    return match ($type) {
        'stock_in' => [
            'background' => '#E8F5E9',
            'color' => '#2E7D32'
        ],
        'stock_out' => [
            'background' => '#FBECEC',
            'color' => '#B45F5F'
        ],
        'stock_audit' => [
            'background' => '#FFF4E5',
            'color' => '#B26A00'
        ],
        default => [
            'background' => '#F5EFE8',
            'color' => '#76553D'
        ]
    };

}
?>


<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(__('history_title'), ENT_QUOTES, 'UTF-8') ?> - Stock</title>
</head>

<body style="margin:0; min-height:100vh; background:#FAF7F2; font-family:Arial,sans-serif; color:#49382D;">

<!-- Menentukan halaman aktif untuk sidebar -->
<?php $currentPage = 'history.php';?>

<!-- Layout utama: -->
<div style="display:flex; height:100vh;">
    
    <?php require __DIR__ . '/components/sidebar.php';?>
    
    <!-- KONTEN UTAMA -->
    <main style="margin-left:230px; flex:1; min-width:0; min-height:100vh;">
        <?php require __DIR__ . '/components/header.php'; ?>

        <section style="padding:32px;">

            <!-- Judul halaman -->
            <div style="margin-bottom:25px;">
                <h1 style="margin:0; font-size:29px;"><?= htmlspecialchars(__('history_title'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p style="color:#81776D; margin-top:10px; line-height:1.6;"><?= htmlspecialchars(__('history_description'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <!-- Filter berdasarkan kata kunci, jenis, dan tanggal transaksi -->
            <form method="GET" style="display:flex; flex-wrap:wrap; align-items:end; gap:12px; background:white; border:1px solid #E9E1D7; border-radius:12px; padding:18px; margin-bottom:20px;">
                <input type="hidden" name="business_id" value="<?= (int) $business_id ?>">

                <div style="flex:1; min-width:170px;">
                    <label style="display:block; margin-bottom:7px; font-size:13px; font-weight:bold;"><?= htmlspecialchars(__('history_search_placeholder'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars(__('history_search_placeholder'), ENT_QUOTES, 'UTF-8') ?>" style="width:100%; box-sizing:border-box; padding:11px; border:1px solid #E9E1D7; border-radius:8px;">
                </div>

                <div>
                    <label style="display:block; margin-bottom:7px; font-size:13px; font-weight:bold;"><?= htmlspecialchars(__('transaction_type_label'), ENT_QUOTES, 'UTF-8') ?></label>
                    <select name="type" style="padding:11px; border:1px solid #E9E1D7; border-radius:8px; background:white;">
                        <option value=""><?= htmlspecialchars(__('all_transaction_types'), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php foreach ($allowedTypes as $option): ?>
                            <option value="<?= $option ?>" <?= $type === $option ? 'selected' : '' ?>>
                                <?= htmlspecialchars(historyTypeLabel($option), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display:block; margin-bottom:7px; font-size:13px; font-weight:bold;"><?= htmlspecialchars(__('date_from'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8') ?>" style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">
                </div>

                <div>
                    <label style="display:block; margin-bottom:7px; font-size:13px; font-weight:bold;"><?= htmlspecialchars(__('date_to'), ENT_QUOTES, 'UTF-8') ?></label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8') ?>" style="padding:10px; border:1px solid #E9E1D7; border-radius:8px;">
                </div>

                <button type="submit" style="padding:11px 16px; border:0; border-radius:8px; background:#76553D; color:white; cursor:pointer;">
                    <?= htmlspecialchars(__('apply_filter'), ENT_QUOTES, 'UTF-8') ?>
                </button>

                <a href="history.php?business_id=<?= (int) $business_id ?>" style="padding:11px 14px; border-radius:8px; background:#F5EFE8; color:#76553D; text-decoration:none;">
                    <?= htmlspecialchars(__('reset_filter'), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </form>

            <!-- Tabel riwayat transaksi -->
            <div style="background:white; border:1px solid #E9E1D7; border-radius:12px; overflow:hidden;">
                <div style="padding:18px 20px; border-bottom:1px solid #E9E1D7; font-weight:bold;">
                    <?= htmlspecialchars(__('transactions_found'), ENT_QUOTES, 'UTF-8') ?>: <?= number_format($total) ?>
                </div>

                <div style="height:40vh; min-height:300px; overflow:auto;">
                    <table style="width:100%; border-collapse:collapse; text-align:left; white-space:nowrap;">
                        <thead style="background:#F5EFE8;">
                            <tr>
                                <?php foreach (['transaction_date', 'transaction_type_label', 'product_name', 'quantity', 'selling_price', 'total_amount', 'stock_before', 'stock_after', 'notes'] as $key): ?>
                                    <th style="position:sticky; top:0; z-index:2; padding:14px 16px; font-size:13px; color:#76553D; border-bottom:1px solid #E9E1D7; background:#F5EFE8;">                                        
                                        <?= htmlspecialchars(__($key), ENT_QUOTES, 'UTF-8') ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total === 0): ?>
                                <tr>
                                    <td colspan="9" style="padding:35px; text-align:center; color:#81776D;">
                                        <?= htmlspecialchars(__('no_transactions'), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = mysqli_fetch_assoc($transactions)): ?>
                                    <tr>
                                        <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                            <?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['transaction_date'])), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <?php $badge = historyTypeBadge($row['transaction_type']);?>

                                        <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                            <span style=" display:inline-block; padding:6px 12px; border-radius:20px; font-size:12px;  font-weight:bold; background:<?= $badge['background'] ?>; color:<?= $badge['color'] ?>;">
                                                <?= htmlspecialchars(historyTypeLabel($row['transaction_type']), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                            <?= htmlspecialchars($row['product_name'] ?: __('product_unavailable'), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                            <?= number_format((int) $row['quantity']) ?>
                                        </td>
                                        <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                            <?php if (
                                                $row['transaction_type'] === 'stock_out'
                                                && $row['selling_price'] > 0
                                            ): ?>
                                                <?= htmlspecialchars(formatMoneyPHP($row['selling_price'], $business['currency']), ENT_QUOTES, 'UTF-8') ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                            <?php if (
                                                $row['transaction_type'] === 'stock_out'
                                                && $row['total_amount'] > 0
                                            ): ?>
                                                <?= htmlspecialchars(formatMoneyPHP($row['total_amount'], $business['currency']), ENT_QUOTES, 'UTF-8') ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                            <?= $row['stock_before'] === null ? '—' : number_format((int) $row['stock_before']) ?>
                                        </td>
                                        <td style="padding:14px 16px; border-bottom:1px solid #F0EBE5;">
                                            <?= $row['stock_after'] === null ? '—' : number_format((int) $row['stock_after']) ?>
                                        </td>
                                        <td style="padding:14px 16px; min-width:180px; max-width:280px; white-space:normal; border-bottom:1px solid #F0EBE5;">
                                            <?= htmlspecialchars($row['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?: '—' ?>
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


<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
</body>
</html>
