<?php
require_once __DIR__ . '/pos-common.php';
$role = 'pharmacist';
$employeeId = pos_require_role($role);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    pos_handle_pos1($role, $employeeId);
}

$rawMedicineId = $_GET['medicine_id'] ?? null;
$medicineId = pos_validate_id($rawMedicineId);
if (array_key_exists('medicine_id', $_GET) && $medicineId === null) {
    pos_flash('Select a valid medicine from the inventory list.');
}
$medicine = $medicineId !== null ? pos_get_medicine($conn, $medicineId) : null;
if ($medicineId !== null && $medicine === null) {
    pos_flash('That medicine was not found. Select another medicine.');
}
$pendingSaleId = pos_validate_id($_SESSION['pending_sale_id'] ?? null, PHP_INT_MAX);
$selectedCustomerId = pos_validate_id($_SESSION['pos_customer_id'] ?? null);
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
<link rel="stylesheet" href="form3.css">
<title>pharmacy_test — Point of Sale</title>
<style>
.pos-content section{margin:22px 0;padding:18px;border:1px solid #ddd}
.pos-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}
.pos-grid label{display:block;font-weight:600}.pos-grid input,.pos-grid select{width:100%;box-sizing:border-box;padding:8px;margin:4px 0 12px}
.pos-message{padding:10px;background:#fff3cd;color:#664d03}.pos-readonly{background:#f2f2f2}
.pos-button{padding:9px 14px;cursor:pointer}
</style>
</head>
<body>
<?php pos_render_navigation($role, $employeeName); ?>
<main class="pos-content">
<h1>POINT OF SALE</h1>
<?php if ($flash !== ''): ?><p class="pos-message" role="alert"><?= pos_escape($flash) ?></p><?php endif; ?>

<?php if ($pendingSaleId === null): ?>
<section>
<h2>Select customer</h2>
<form method="post" action="pharm-pos1.php">
<input type="hidden" name="csrf_token" value="<?= pos_escape($csrfToken) ?>">
<input type="hidden" name="action" value="start_sale">
<label for="customer_id">Customer</label>
<select id="customer_id" name="customer_id" required>
<option value="">Select a customer</option>
<?= pos_customer_options($conn, $selectedCustomerId) ?>
</select>
<button class="pos-button" type="submit">Add to Proceed</button>
</form>
</section>
<?php else: ?>
<section>
<h2>Sale #<?= pos_escape($pendingSaleId) ?></h2>
<p>Customer ID: <?= pos_escape($selectedCustomerId) ?></p>
<p><a class="button1 view-btn" href="pharm-pos2.php?sid=<?= pos_escape($pendingSaleId) ?>">View Order / Complete Sale</a></p>

<h3>Select medicine</h3>
<form method="post" action="pharm-pos1.php">
<input type="hidden" name="csrf_token" value="<?= pos_escape($csrfToken) ?>">
<input type="hidden" name="action" value="search_medicine">
<label for="medicine_id">Medicine</label>
<select id="medicine_id" name="medicine_id" required>
<option value="">Select a medicine</option>
<?= pos_medicine_options($conn, $medicineId) ?>
</select>
<button class="pos-button" type="submit">Search</button>
</form>

<?php if ($medicine !== null): ?>
<form method="post" action="pharm-pos1.php" class="pos-grid">
<input type="hidden" name="csrf_token" value="<?= pos_escape($csrfToken) ?>">
<input type="hidden" name="action" value="add_item">
<input type="hidden" name="medicine_id" value="<?= pos_escape($medicine['MED_ID']) ?>">
<label>Medicine ID<input class="pos-readonly" value="<?= pos_escape($medicine['MED_ID']) ?>" readonly></label>
<label>Medicine Name<input class="pos-readonly" value="<?= pos_escape($medicine['MED_NAME']) ?>" readonly></label>
<label>Category<input class="pos-readonly" value="<?= pos_escape($medicine['CATEGORY']) ?>" readonly></label>
<label>Location<input class="pos-readonly" value="<?= pos_escape($medicine['LOCATION_RACK']) ?>" readonly></label>
<label>Quantity Available<input class="pos-readonly" type="number" value="<?= pos_escape($medicine['MED_QTY']) ?>" readonly></label>
<label>Price of One Unit<input class="pos-readonly" value="<?= pos_escape(number_format((float) $medicine['MED_PRICE'], 2, '.', '')) ?>" readonly></label>
<label>Quantity Required<input type="number" name="quantity" min="1" max="<?= pos_escape($medicine['MED_QTY']) ?>" step="1" required></label>
<div><button class="pos-button" type="submit">Add Medicine</button></div>
</form>
<?php endif; ?>
</section>
<?php endif; ?>
</main>
</body>
</html>
