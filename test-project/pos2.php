<?php
require_once __DIR__ . '/pos-common.php';
$role = 'admin';
$employeeId = pos_require_role($role);
pos_handle_invoice_post($role, $employeeId);

$saleId = pos_validate_id($_GET['sid'] ?? null, PHP_INT_MAX);
$pendingId = pos_validate_id($_SESSION['pending_sale_id'] ?? null, PHP_INT_MAX);
$completedId = pos_validate_id($_SESSION['last_completed_sale_id'] ?? null, PHP_INT_MAX);
if ($saleId === null || !in_array($saleId, array_filter([$pendingId, $completedId]), true) ||
    !pos_sale_is_owned($conn, $saleId, $employeeId)) {
    pos_flash('This invoice is not available in your session.');
    pos_redirect($role, 'pos1');
}
$items = pos_get_items($conn, $saleId);
$isPending = $pendingId === $saleId;
$totalCents = 0;
foreach ($items as $item) {
    $totalCents += (int) round((float) $item['TOT_PRICE'] * 100);
}
$flash = pos_take_flash();
$csrfToken = pos_csrf_token();
$employeeName = pos_employee_name($conn, $role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="nav2.css">
<link rel="stylesheet" href="pos.css">
<link rel="stylesheet" href="table1.css">
<title>pharmacy_test — Sales Invoice</title>
<style>
.pos-message{padding:10px;background:#fff3cd;color:#664d03}
.pos-table{width:100%;border-collapse:collapse}.pos-table th,.pos-table td{border:1px solid #ddd;padding:9px;text-align:left}
.pos-actions{display:flex;gap:14px;align-items:center;margin-top:20px;flex-wrap:wrap}
.pos-actions form{margin:0}
</style>
</head>
<body>
<?php pos_render_navigation($role, $employeeName); ?>
<main class="pos-content">
<h1>SALES INVOICE</h1>
<p>Invoice #<?= pos_escape($saleId) ?> — <?= $isPending ? 'In progress' : 'Completed' ?></p>
<?php if ($flash !== ''): ?><p class="pos-message" role="alert"><?= pos_escape($flash) ?></p><?php endif; ?>
<table class="pos-table">
<thead><tr><th>Medicine ID</th><th>Medicine</th><th>Quantity</th><th>Price per unit</th><th>Total price</th><?php if ($isPending): ?><th>Action</th><?php endif; ?></tr></thead>
<tbody>
<?php if (!$items): ?>
<tr><td colspan="<?= $isPending ? '6' : '5' ?>">No medicines have been added yet.</td></tr>
<?php else: foreach ($items as $item): ?>
<tr>
<td><?= pos_escape($item['MED_ID']) ?></td>
<td><?= pos_escape($item['MED_NAME']) ?></td>
<td><?= pos_escape($item['SALE_QTY']) ?></td>
<td><?= pos_escape(number_format((float) $item['UNIT_PRICE'], 2, '.', '')) ?></td>
<td><?= pos_escape(number_format(((int) round((float) $item['TOT_PRICE'] * 100)) / 100, 2, '.', '')) ?></td>
<?php if ($isPending): ?>
<td><form method="post" action="pos2.php?sid=<?= pos_escape($saleId) ?>">
<input type="hidden" name="csrf_token" value="<?= pos_escape($csrfToken) ?>">
<input type="hidden" name="action" value="remove_item">
<input type="hidden" name="medicine_id" value="<?= pos_escape($item['MED_ID']) ?>">
<button type="submit">Remove</button></form></td>
<?php endif; ?>
</tr>
<?php endforeach; endif; ?>
</tbody>
<tfoot><tr><th colspan="4">Total</th><th colspan="<?= $isPending ? '2' : '1' ?>"><?= pos_escape(number_format($totalCents / 100, 2, '.', '')) ?></th></tr></tfoot>
</table>
<div class="pos-actions">
<?php if ($isPending): ?>
<a href="pos1.php">Continue sale</a>
<?php if ($items): ?>
<form method="post" action="pos2.php?sid=<?= pos_escape($saleId) ?>">
<input type="hidden" name="csrf_token" value="<?= pos_escape($csrfToken) ?>">
<input type="hidden" name="action" value="complete_sale">
<button type="submit">Complete Order</button></form>
<?php endif; ?>
<?php else: ?><a href="pos1.php">Start another sale</a><?php endif; ?>
</div>
</main>
</body>
</html>
