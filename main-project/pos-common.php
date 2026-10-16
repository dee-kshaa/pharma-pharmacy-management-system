<?php
mysqli_report(MYSQLI_REPORT_OFF);
require_once __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function pos_escape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function pos_flash(string $message): void
{
    $_SESSION['pos_flash'] = $message;
}

function pos_take_flash(): string
{
    $message = (string) ($_SESSION['pos_flash'] ?? '');
    unset($_SESSION['pos_flash']);
    return $message;
}

function pos_csrf_token(): string
{
    if (empty($_SESSION['pos_csrf'])) {
        $_SESSION['pos_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['pos_csrf'];
}

function pos_verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && isset($_SESSION['pos_csrf']) && hash_equals($_SESSION['pos_csrf'], $token);
}

function pos_employee_id(mysqli $conn, string $role): ?string
{
    $user = $_SESSION['user'] ?? null;
    if (!is_string($user) && !is_int($user)) {
        return null;
    }
    $user = (string) $user;

    if ($role === 'admin') {
        $stmt = $conn->prepare('SELECT ID FROM admin WHERE A_USERNAME = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $user);
        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }
        $stmt->bind_result($employeeId);
        $found = $stmt->fetch();
        $stmt->close();
        return $found ? (string) $employeeId : null;
    }

    if ($role !== 'pharmacist' || !ctype_digit($user)) {
        return null;
    }
    $stmt = $conn->prepare('SELECT E_ID FROM employee WHERE E_ID = ? LIMIT 1');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $user);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $stmt->bind_result($employeeId);
    $found = $stmt->fetch();
    $stmt->close();
    return $found ? (string) $employeeId : null;
}

function pos_require_role(string $role): string
{
    global $conn;
    $employeeId = pos_employee_id($conn, $role);
    if ($employeeId === null) {
        header('Location: ' . ($role === 'admin' ? 'mainpage.php' : 'mainpage1.php'));
        exit;
    }
    return $employeeId;
}

function pos_employee_name(mysqli $conn, string $role): string
{
    if ($role === 'admin') {
        return (string) ($_SESSION['user'] ?? 'Administrator');
    }
    $employeeId = (string) ($_SESSION['user'] ?? '');
    $stmt = $conn->prepare('SELECT E_FNAME FROM employee WHERE E_ID = ? LIMIT 1');
    if (!$stmt) {
        return 'Pharmacist';
    }
    $stmt->bind_param('s', $employeeId);
    if (!$stmt->execute()) {
        $stmt->close();
        return 'Pharmacist';
    }
    $stmt->bind_result($name);
    $found = $stmt->fetch();
    $stmt->close();
    return $found ? (string) $name : 'Pharmacist';
}

function pos_role_page(string $role, string $page): string
{
    return ($role === 'admin' ? '' : 'pharm-') . $page . '.php';
}

function pos_redirect(string $role, string $page, ?int $medicineId = null): void
{
    $url = pos_role_page($role, $page);
    if ($medicineId !== null && $medicineId > 0) {
        $url .= '?medicine_id=' . $medicineId;
    }
    header('Location: ' . $url);
    exit;
}

function pos_validate_id($value, int $max = 999999): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);
    return $id !== false && $id !== null && $id > 0 && $id <= $max ? $id : null;
}

function pos_customer_options(mysqli $conn, ?int $selectedId = null): string
{
    $result = $conn->query('SELECT C_ID, C_FNAME, C_LNAME FROM customer ORDER BY C_ID');
    if (!$result) {
        error_log('pharmacy_test customer dropdown query failed (' . $conn->errno . ').');
        return '<option value="">Customer list unavailable. Refresh and try again.</option>';
    }

    $options = '';
    while ($row = $result->fetch_assoc()) {
        $id = (string) $row['C_ID'];
        $name = trim((string) $row['C_FNAME'] . ' ' . (string) ($row['C_LNAME'] ?? ''));
        $selected = $selectedId !== null && (string) $selectedId === $id ? ' selected' : '';
        $options .= '<option value="' . pos_escape($id) . '"' . $selected . '>' .
            pos_escape($id . ' — ' . $name) . '</option>';
    }
    $result->free();

    return $options !== '' ? $options : '<option value="">No customers found</option>';
}

function pos_medicine_options(mysqli $conn, ?int $selectedId = null): string
{
    $result = $conn->query('SELECT MED_ID, MED_NAME FROM meds ORDER BY MED_NAME');
    if (!$result) {
        error_log('pharmacy_test medicine dropdown query failed (' . $conn->errno . ').');
        return '<option value="">Medicine list unavailable. Refresh and try again.</option>';
    }

    $options = '';
    while ($row = $result->fetch_assoc()) {
        $id = (string) $row['MED_ID'];
        $selected = $selectedId !== null && (string) $selectedId === $id ? ' selected' : '';
        $options .= '<option value="' . pos_escape($id) . '"' . $selected . '>' .
            pos_escape($row['MED_NAME']) . '</option>';
    }
    $result->free();

    return $options !== '' ? $options : '<option value="">No medicines found</option>';
}

function pos_get_medicine(mysqli $conn, int $medicineId): ?array
{
    if ($medicineId < 1 || $medicineId > 999999) {
        return null;
    }

    $stmt = $conn->prepare('SELECT MED_ID, MED_NAME, MED_QTY, CATEGORY, MED_PRICE, LOCATION_RACK FROM meds WHERE MED_ID = ? LIMIT 1');
    if (!$stmt) {
        error_log('pharmacy_test medicine lookup could not be prepared (' . $conn->errno . ').');
        return null;
    }
    $stmt->bind_param('i', $medicineId);
    if (!$stmt->execute()) {
        error_log('pharmacy_test medicine lookup failed (' . $stmt->errno . ').');
        $stmt->close();
        return null;
    }
    $stmt->bind_result($id, $name, $quantity, $category, $price, $location);
    $found = $stmt->fetch();
    $stmt->close();

    return $found ? [
        'MED_ID' => $id,
        'MED_NAME' => $name,
        'MED_QTY' => $quantity,
        'CATEGORY' => $category,
        'MED_PRICE' => $price,
        'LOCATION_RACK' => $location,
    ] : null;
}

function pos_has_unit_price(mysqli $conn): bool
{
    $result = $conn->query("SHOW COLUMNS FROM sales_items LIKE 'UNIT_PRICE'");
    if (!$result) {
        error_log('pharmacy_test could not inspect sales_items columns (' . $conn->errno . ').');
        return false;
    }
    $exists = $result->num_rows > 0;
    $result->free();
    return $exists;
}

function pos_sale_is_owned(mysqli $conn, int $saleId, string $employeeId, bool $lock = false): bool
{
    $sql = 'SELECT SALE_ID FROM sales WHERE SALE_ID = ? AND E_ID = ? LIMIT 1' . ($lock ? ' FOR UPDATE' : '');
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('is', $saleId, $employeeId);
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }
    $stmt->store_result();
    $owned = $stmt->num_rows === 1;
    $stmt->close();
    return $owned;
}

function pos_start_sale(mysqli $conn, string $role, string $employeeId): bool
{
    $customerId = pos_validate_id($_POST['customer_id'] ?? null);
    if ($customerId === null) {
        pos_flash('Select a valid customer before starting the sale.');
        return false;
    }
    if (!empty($_SESSION['pending_sale_id'])) {
        pos_flash('Finish your current sale before starting another.');
        return false;
    }
    $_SESSION['pos_customer_id'] = $customerId;

    if (!$conn->begin_transaction()) {
        error_log('pharmacy_test could not start sale creation transaction (' . $conn->errno . ').');
        pos_flash('The sale could not be started. Please try again.');
        return false;
    }
    try {
        $customer = $conn->prepare('SELECT C_ID FROM customer WHERE C_ID = ? LIMIT 1 FOR UPDATE');
        if (!$customer) {
            throw new RuntimeException('Could not prepare customer validation.');
        }
        $customer->bind_param('i', $customerId);
        if (!$customer->execute()) {
            throw new RuntimeException('Could not validate selected customer.');
        }
        $customer->store_result();
        $customerExists = $customer->num_rows === 1;
        $customer->close();
        if (!$customerExists) {
            throw new DomainException('That customer no longer exists. Choose a customer from the list.');
        }

        $insert = $conn->prepare('INSERT INTO sales (C_ID, E_ID) VALUES (?, ?)');
        if (!$insert) {
            throw new RuntimeException('Could not prepare sale creation.');
        }
        $insert->bind_param('is', $customerId, $employeeId);
        if (!$insert->execute()) {
            throw new RuntimeException('Could not create sale.');
        }
        $saleId = (int) $conn->insert_id;
        $insert->close();
        if ($saleId < 1 || !$conn->commit()) {
            throw new RuntimeException('Could not commit sale creation.');
        }
        $_SESSION['pending_sale_id'] = $saleId;
        $_SESSION['pos_customer_id'] = $customerId;
        unset($_SESSION['last_completed_sale_id']);
        return true;
    } catch (DomainException $error) {
        $conn->rollback();
        pos_flash($error->getMessage());
        return false;
    } catch (Throwable $error) {
        $conn->rollback();
        error_log('pharmacy_test sale creation failed (' . $conn->errno . ').');
        pos_flash('The sale could not be started. Please verify the customer and try again.');
        return false;
    }
}

function pos_add_item(mysqli $conn, string $employeeId): bool
{
    $saleId = pos_validate_id($_SESSION['pending_sale_id'] ?? null, PHP_INT_MAX);
    $medicineId = pos_validate_id($_POST['medicine_id'] ?? null);
    $quantity = filter_var($_POST['quantity'] ?? null, FILTER_VALIDATE_INT);
    if ($saleId === null || $medicineId === null || $quantity === false || $quantity === null || $quantity < 1) {
        pos_flash('Choose a medicine and enter a positive whole-number quantity.');
        return false;
    }

    $hasUnitPrice = pos_has_unit_price($conn);
    $transactionStarted = false;
    try {
        if (!$conn->begin_transaction()) {
            throw new RuntimeException('Could not start sale item transaction.');
        }
        $transactionStarted = true;

        if (!pos_sale_is_owned($conn, $saleId, $employeeId, true)) {
            throw new DomainException('This sale is no longer available. Start a new sale.');
        }

        $medicine = $conn->prepare('SELECT MED_QTY, MED_PRICE FROM meds WHERE MED_ID = ? FOR UPDATE');
        if (!$medicine) {
            throw new RuntimeException('Could not prepare medicine stock lookup.');
        }
        $medicine->bind_param('i', $medicineId);
        if (!$medicine->execute()) {
            throw new RuntimeException('Could not read current medicine stock.');
        }
        $medicine->bind_result($available, $unitPrice);
        $foundMedicine = $medicine->fetch();
        $medicine->close();
        if (!$foundMedicine) {
            throw new DomainException('That medicine is no longer in inventory. Select another medicine.');
        }
        if ((int) $available < $quantity) {
            throw new DomainException('The requested quantity exceeds available stock.');
        }

        $lineSql = $hasUnitPrice
            ? 'SELECT SALE_QTY, TOT_PRICE, UNIT_PRICE FROM sales_items WHERE SALE_ID = ? AND MED_ID = ? FOR UPDATE'
            : 'SELECT SALE_QTY, TOT_PRICE FROM sales_items WHERE SALE_ID = ? AND MED_ID = ? FOR UPDATE';
        $line = $conn->prepare($lineSql);
        if (!$line) {
            throw new RuntimeException('Could not prepare existing sale line lookup.');
        }
        $line->bind_param('ii', $saleId, $medicineId);
        if (!$line->execute()) {
            throw new RuntimeException('Could not read existing sale line.');
        }
        if ($hasUnitPrice) {
            $line->bind_result($oldQuantity, $oldTotal, $oldUnitPrice);
        } else {
            $line->bind_result($oldQuantity, $oldTotal);
        }
        $hasLine = $line->fetch();
        $line->close();

        $unitCents = $hasLine
            ? ($hasUnitPrice
                ? (int) round((float) $oldUnitPrice * 100)
                : (int) round(((float) $oldTotal * 100) / max(1, (int) $oldQuantity)))
            : (int) round((float) $unitPrice * 100);
        $newQuantity = (int) $quantity + ($hasLine ? (int) $oldQuantity : 0);
        $totalCents = $unitCents * $newQuantity;
        if ($totalCents > 99999999) {
            throw new DomainException('This line total exceeds the amount supported by the current database schema.');
        }

        if ($hasLine) {
            // Existing SALEINSERT fires only on INSERT; update stock once for an added quantity.
            $stock = $conn->prepare('UPDATE meds SET MED_QTY = MED_QTY - ? WHERE MED_ID = ? AND MED_QTY >= ?');
            if (!$stock) {
                throw new RuntimeException('Could not prepare stock adjustment.');
            }
            $stock->bind_param('iii', $quantity, $medicineId, $quantity);
            if (!$stock->execute() || $stock->affected_rows !== 1) {
                $stock->close();
                throw new DomainException('The medicine stock changed. Refresh the page and try again.');
            }
            $stock->close();

            $totalValue = number_format($totalCents / 100, 2, '.', '');
            $update = $conn->prepare('UPDATE sales_items SET SALE_QTY = ?, TOT_PRICE = ? WHERE SALE_ID = ? AND MED_ID = ?');
            if (!$update) {
                throw new RuntimeException('Could not prepare sale line update.');
            }
            $update->bind_param('isii', $newQuantity, $totalValue, $saleId, $medicineId);
            if (!$update->execute()) {
                throw new RuntimeException('Could not update sale line.');
            }
            $update->close();
        } else {
            $totalValue = number_format($totalCents / 100, 2, '.', '');
            if ($hasUnitPrice) {
                $unitValue = number_format($unitCents / 100, 2, '.', '');
                $insert = $conn->prepare('INSERT INTO sales_items (SALE_ID, MED_ID, SALE_QTY, TOT_PRICE, UNIT_PRICE) VALUES (?, ?, ?, ?, ?)');
                if (!$insert) {
                    throw new RuntimeException('Could not prepare sale item insert.');
                }
                $insert->bind_param('iiiss', $saleId, $medicineId, $quantity, $totalValue, $unitValue);
            } else {
                $insert = $conn->prepare('INSERT INTO sales_items (SALE_ID, MED_ID, SALE_QTY, TOT_PRICE) VALUES (?, ?, ?, ?)');
                if (!$insert) {
                    throw new RuntimeException('Could not prepare sale item insert.');
                }
                $insert->bind_param('iiis', $saleId, $medicineId, $quantity, $totalValue);
            }
            if (!$insert->execute()) {
                throw new RuntimeException('Could not insert sale item.');
            }
            $insert->close(); // SALEINSERT decrements meds.MED_QTY exactly once.
        }

        if (!$conn->commit()) {
            throw new RuntimeException('Could not commit sale item transaction.');
        }
        $transactionStarted = false;
        return true;
    } catch (DomainException $error) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        pos_flash($error->getMessage());
    } catch (Throwable $error) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        error_log('pharmacy_test sale item transaction failed (' . $conn->errno . ').');
        pos_flash('The medicine could not be added. Please try again.');
    }
    return false;
}

function pos_complete_sale(mysqli $conn, string $employeeId): bool
{
    $saleId = pos_validate_id($_SESSION['pending_sale_id'] ?? null, PHP_INT_MAX);
    if ($saleId === null) {
        pos_flash('There is no active sale to complete.');
        return false;
    }
    $started = false;
    try {
        if (!$conn->begin_transaction()) {
            throw new RuntimeException('Could not start completion transaction.');
        }
        $started = true;
        if (!pos_sale_is_owned($conn, $saleId, $employeeId, true)) {
            throw new DomainException('This sale is no longer available.');
        }

        $total = $conn->prepare('SELECT COALESCE(SUM(TOT_PRICE), 0) FROM sales_items WHERE SALE_ID = ?');
        if (!$total) {
            throw new RuntimeException('Could not prepare sale total query.');
        }
        $total->bind_param('i', $saleId);
        if (!$total->execute()) {
            throw new RuntimeException('Could not calculate the sale total.');
        }
        $total->bind_result($amount);
        $total->fetch();
        $total->close();
        if ((float) $amount <= 0) {
            throw new DomainException('Add at least one medicine before completing the sale.');
        }

        $finish = $conn->prepare('UPDATE sales SET S_DATE = CURRENT_DATE(), S_TIME = CURRENT_TIME(), TOTAL_AMT = ? WHERE SALE_ID = ? AND E_ID = ?');
        if (!$finish) {
            throw new RuntimeException('Could not prepare sale completion.');
        }
        $finish->bind_param('sis', $amount, $saleId, $employeeId);
        if (!$finish->execute()) {
            throw new RuntimeException('Could not complete the sale.');
        }
        $finish->close();
        if (!$conn->commit()) {
            throw new RuntimeException('Could not commit sale completion.');
        }
        $started = false;
        $_SESSION['last_completed_sale_id'] = $saleId;
        unset($_SESSION['pending_sale_id']);
        return true;
    } catch (DomainException $error) {
        if ($started) {
            $conn->rollback();
        }
        pos_flash($error->getMessage());
    } catch (Throwable $error) {
        if ($started) {
            $conn->rollback();
        }
        error_log('pharmacy_test sale completion failed (' . $conn->errno . ').');
        pos_flash('The sale could not be completed. Please try again.');
    }
    return false;
}

function pos_delete_item(mysqli $conn, string $employeeId): bool
{
    $saleId = pos_validate_id($_SESSION['pending_sale_id'] ?? null, PHP_INT_MAX);
    $medicineId = pos_validate_id($_POST['medicine_id'] ?? null);
    if ($saleId === null || $medicineId === null) {
        pos_flash('The sale item request was invalid.');
        return false;
    }

    $started = false;
    try {
        if (!$conn->begin_transaction()) {
            throw new RuntimeException('Could not start item removal transaction.');
        }
        $started = true;
        if (!pos_sale_is_owned($conn, $saleId, $employeeId, true)) {
            throw new DomainException('This sale is no longer available.');
        }
        $delete = $conn->prepare('DELETE FROM sales_items WHERE SALE_ID = ? AND MED_ID = ?');
        if (!$delete) {
            throw new RuntimeException('Could not prepare sale item removal.');
        }
        $delete->bind_param('ii', $saleId, $medicineId);
        if (!$delete->execute() || $delete->affected_rows !== 1) {
            throw new DomainException('The item could not be removed from this sale.');
        }
        $delete->close(); // SALEDELETE restores stock within this transaction.
        if (!$conn->commit()) {
            throw new RuntimeException('Could not commit item removal.');
        }
        $started = false;
        return true;
    } catch (DomainException $error) {
        if ($started) {
            $conn->rollback();
        }
        pos_flash($error->getMessage());
    } catch (Throwable $error) {
        if ($started) {
            $conn->rollback();
        }
        error_log('pharmacy_test sale item removal failed (' . $conn->errno . ').');
        pos_flash('The item could not be removed. Please try again.');
    }
    return false;
}

function pos_get_items(mysqli $conn, int $saleId): array
{
    $hasUnitPrice = pos_has_unit_price($conn);
    $unitPrice = $hasUnitPrice ? 'si.UNIT_PRICE' : '(si.TOT_PRICE / NULLIF(si.SALE_QTY, 0))';
    $stmt = $conn->prepare('SELECT si.MED_ID, m.MED_NAME, si.SALE_QTY, ' . $unitPrice . ' AS UNIT_PRICE, si.TOT_PRICE FROM sales_items si JOIN meds m ON m.MED_ID = si.MED_ID WHERE si.SALE_ID = ? ORDER BY m.MED_NAME');
    if (!$stmt) {
        error_log('pharmacy_test invoice lookup could not be prepared (' . $conn->errno . ').');
        return [];
    }
    $stmt->bind_param('i', $saleId);
    if (!$stmt->execute()) {
        error_log('pharmacy_test invoice lookup failed (' . $stmt->errno . ').');
        $stmt->close();
        return [];
    }
    $stmt->bind_result($medicineId, $name, $quantity, $price, $total);
    $items = [];
    while ($stmt->fetch()) {
        $items[] = [
            'MED_ID' => $medicineId,
            'MED_NAME' => $name,
            'SALE_QTY' => $quantity,
            'UNIT_PRICE' => $price,
            'TOT_PRICE' => $total,
        ];
    }
    $stmt->close();
    return $items;
}

function pos_sale_id_for_page(mysqli $conn, string $employeeId): ?int
{
    foreach (['pending_sale_id', 'last_completed_sale_id'] as $sessionKey) {
        $saleId = pos_validate_id($_SESSION[$sessionKey] ?? null, PHP_INT_MAX);
        if ($saleId !== null && pos_sale_is_owned($conn, $saleId, $employeeId)) {
            return $saleId;
        }
    }
    return null;
}

function pos_handle_pos1(string $role, string $employeeId): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    if (!pos_verify_csrf()) {
        pos_flash('Your form expired. Refresh the page and try again.');
        pos_redirect($role, 'pos1');
    }

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'start_sale') {
        pos_start_sale($GLOBALS['conn'], $role, $employeeId);
        pos_redirect($role, 'pos1');
    }
    if (empty($_SESSION['pending_sale_id'])) {
        pos_flash('Start a sale with a valid customer before adding medicine.');
        pos_redirect($role, 'pos1');
    }

    if ($action === 'search_medicine') {
        $medicineId = pos_validate_id($_POST['medicine_id'] ?? null);
        if ($medicineId === null) {
            pos_flash('Select a valid medicine from the list.');
            pos_redirect($role, 'pos1');
        }
        if (pos_get_medicine($GLOBALS['conn'], $medicineId) === null) {
            pos_flash('That medicine was not found. Select another medicine.');
            pos_redirect($role, 'pos1');
        }
        pos_redirect($role, 'pos1', $medicineId);
    }

    if ($action === 'add_item') {
        $medicineId = pos_validate_id($_POST['medicine_id'] ?? null);
        pos_add_item($GLOBALS['conn'], $employeeId);
        pos_redirect($role, 'pos1', $medicineId);
    }

    pos_flash('Select an available POS action.');
    pos_redirect($role, 'pos1');
}

function pos_handle_invoice_post(string $role, string $employeeId): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    if (!pos_verify_csrf()) {
        pos_flash('Your form expired. Refresh the page and try again.');
    } elseif ((string) ($_POST['action'] ?? '') === 'complete_sale') {
        pos_complete_sale($GLOBALS['conn'], $employeeId);
    } elseif ((string) ($_POST['action'] ?? '') === 'remove_item') {
        pos_delete_item($GLOBALS['conn'], $employeeId);
    } else {
        pos_flash('Select a valid invoice action.');
    }

    $saleId = pos_sale_id_for_page($GLOBALS['conn'], $employeeId);
    $url = pos_role_page($role, 'pos2');
    if ($saleId !== null) {
        $url .= '?sid=' . $saleId;
    }
    header('Location: ' . $url);
    exit;
}

function pos_render_navigation(string $role, string $employeeName): void
{
    $name = pos_escape($employeeName);
    if ($role === 'admin') {
        echo '<div class="sidenav"><h2 style="font-family:Arial;color:white;text-align:center">pharmacy_test</h2>' .
            '<a href="adminmainpage.php">Dashboard</a>' .
            '<button class="dropdown-btn">Inventory <i class="down"></i></button><div class="dropdown-container"><a href="inventory-add.php">Add New Medicine</a><a href="inventory-view.php">Manage Inventory</a></div>' .
            '<button class="dropdown-btn">Suppliers <i class="down"></i></button><div class="dropdown-container"><a href="supplier-add.php">Add New Supplier</a><a href="supplier-view.php">Manage Suppliers</a></div>' .
            '<button class="dropdown-btn">Stock Purchase <i class="down"></i></button><div class="dropdown-container"><a href="purchase-add.php">Add New Purchase</a><a href="purchase-view.php">Manage Purchases</a></div>' .
            '<button class="dropdown-btn">Employees <i class="down"></i></button><div class="dropdown-container"><a href="employee-add.php">Add New Employee</a><a href="employee-view.php">Manage Employees</a></div>' .
            '<button class="dropdown-btn">Customers <i class="down"></i></button><div class="dropdown-container"><a href="customer-add.php">Add New Customer</a><a href="customer-view.php">Manage Customers</a></div>' .
            '<a href="sales-view.php">View Sales Invoice Details</a><a href="salesitems-view.php">View Sold Products Details</a><a href="pos1.php">Add New Sale</a>' .
            '<button class="dropdown-btn">Reports <i class="down"></i></button><div class="dropdown-container"><a href="stockreport.php">Medicines - Low Stock</a><a href="expiryreport.php">Medicines - Soon to Expire</a><a href="salesreport.php">Transactions Reports</a></div></div>' .
            '<div class="topnav"><a href="logout.php">Logout (signed in as ' . $name . ')</a></div>';
    } else {
        echo '<div class="sidenav"><h2 style="font-family:Arial;color:white;text-align:center">pharmacy_test</h2>' .
            '<a href="pharmmainpage.php">Dashboard</a><a href="pharm-inventory.php">View Inventory</a><a href="pharm-pos1.php">Add New Sale</a>' .
            '<button class="dropdown-btn">Customers <i class="down"></i></button><div class="dropdown-container"><a href="pharm-customer.php">Add New Customer</a><a href="pharm-customer-view.php">View Customers</a></div></div>' .
            '<div class="topnav"><a href="logout1.php">Logout (signed in as ' . $name . ')</a></div>';
    }
    echo '<script>for (const button of document.getElementsByClassName("dropdown-btn")) { button.addEventListener("click", function () { this.classList.toggle("active"); const content = this.nextElementSibling; content.style.display = content.style.display === "block" ? "none" : "block"; }); }</script>';
}

function pos_create_customer(mysqli $conn): string
{
    if (!pos_verify_csrf()) {
        return 'Your form expired. Refresh the page and try again.';
    }

    $id = pos_validate_id($_POST['cid'] ?? null);
    $firstName = trim((string) ($_POST['cfname'] ?? ''));
    $lastName = trim((string) ($_POST['clname'] ?? ''));
    $age = filter_var($_POST['age'] ?? null, FILTER_VALIDATE_INT);
    $sex = trim((string) ($_POST['sex'] ?? ''));
    $phone = trim((string) ($_POST['phno'] ?? ''));
    $email = trim((string) ($_POST['emid'] ?? ''));

    $nameLength = static function (string $value): int {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    };
    if ($id === null || $firstName === '' || $nameLength($firstName) > 30 ||
        $nameLength($lastName) > 30 || $age === false || $age === null || $age < 0 || $age > 130 ||
        !in_array($sex, ['Female', 'Male', 'Others'], true) ||
        preg_match('/^[0-9]{9,10}$/', $phone) !== 1 ||
        ($email !== '' && (strlen($email) > 40 || filter_var($email, FILTER_VALIDATE_EMAIL) === false))) {
        return 'Check the customer ID, names, age, sex, phone (9–10 digits, no country code), and email.';
    }

    $emailValue = $email === '' ? null : $email;
    $stmt = $conn->prepare('INSERT INTO customer (C_ID, C_FNAME, C_LNAME, C_AGE, C_SEX, C_PHNO, C_MAIL) VALUES (?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        error_log('pharmacy_test customer insert could not be prepared (' . $conn->errno . ').');
        return 'The customer could not be added. Check the details and try again.';
    }
    $stmt->bind_param('sssisss', $id, $firstName, $lastName, $age, $sex, $phone, $emailValue);
    $ok = $stmt->execute();
    $errno = $stmt->errno;
    $stmt->close();
    if (!$ok) {
        error_log('pharmacy_test customer insert failed (' . $errno . ').');
        if ($errno === 1062) {
            return 'That customer ID, phone number, or email is already in use.';
        }
        return 'The customer could not be added. Check the details and try again.';
    }
    return 'Customer successfully added.';
}
