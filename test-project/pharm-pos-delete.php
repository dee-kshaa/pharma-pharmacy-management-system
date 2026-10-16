<?php
require_once __DIR__ . '/pos-common.php';
$role = 'pharmacist';
$employeeId = pos_require_role($role);
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && pos_verify_csrf()) {
    pos_delete_item($conn, $employeeId);
} else {
    pos_flash('The item removal request was invalid.');
}
$saleId = pos_validate_id($_SESSION['pending_sale_id'] ?? null, PHP_INT_MAX);
$url = pos_role_page($role, 'pos2');
if ($saleId !== null) {
    $url .= '?sid=' . $saleId;
}
header('Location: ' . $url);
exit;
