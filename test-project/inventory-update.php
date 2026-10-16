<?php
mysqli_report(MYSQLI_REPORT_OFF);

function inventory_update_escape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

require_once __DIR__ . '/config.php';

$updateError = '';
$medicine = null;
$form = [];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST' && isset($_POST['update'])) {
    $idInput = $_POST['medid'] ?? '';
    $name = trim((string) ($_POST['medname'] ?? ''));
    $qtyInput = $_POST['qty'] ?? '';
    $category = trim((string) ($_POST['cat'] ?? ''));
    $priceInput = trim((string) ($_POST['sp'] ?? ''));
    $location = trim((string) ($_POST['loc'] ?? ''));

    $id = filter_var($idInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999999]]);
    $qty = filter_var($qtyInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $validPrice = preg_match('/^\d{1,4}(?:\.\d{1,2})?$/', $priceInput) === 1;
    $price = $validPrice ? (float) $priceInput : false;

    $form = ['MED_ID' => $idInput, 'MED_NAME' => $name, 'MED_QTY' => $qtyInput, 'CATEGORY' => $category, 'MED_PRICE' => $priceInput, 'LOCATION_RACK' => $location];
    if ($id === false || $qty === false || !$validPrice || $price > 9999.99 || $name === '' || strlen($name) > 50 || strlen($category) > 20 || strlen($location) > 30) {
        $updateError = 'Enter a valid medicine ID, name, non-negative quantity, category, price, and location.';
    } else {
        try {
            $stmt = $conn->prepare('UPDATE meds SET MED_NAME = ?, MED_QTY = ?, CATEGORY = ?, MED_PRICE = ?, LOCATION_RACK = ? WHERE MED_ID = ?');
            if (!$stmt) {
                throw new RuntimeException('Could not prepare medicine update.');
            }
            $stmt->bind_param('sisdsi', $name, $qty, $category, $price, $location, $id);
            if (!$stmt->execute()) {
                error_log('pharmacy_test medicine update failed (' . $stmt->errno . ').');
                $updateError = 'The medicine could not be updated. Check the values and try again.';
            } elseif ($stmt->affected_rows >= 0) {
                $stmt->close();
                header('Location: inventory-view.php');
                exit;
            }
            $stmt->close();
        } catch (Throwable $error) {
            error_log('pharmacy_test medicine update failed (' . get_class($error) . ').');
            $updateError = 'The medicine could not be updated. Check the values and try again.';
        }
    }
}

$idSource = $method === 'POST' ? ($_POST['medid'] ?? null) : ($_GET['id'] ?? null);
$id = filter_var($idSource, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999999]]);
if ($id === false || $id === null) {
    header('Location: inventory-view.php');
    exit;
}

try {
    $stmt = $conn->prepare('SELECT MED_ID, MED_NAME, MED_QTY, CATEGORY, MED_PRICE, LOCATION_RACK FROM meds WHERE MED_ID = ?');
    if (!$stmt) {
        throw new RuntimeException('Could not prepare medicine selection.');
    }
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        error_log('pharmacy_test medicine lookup failed (' . $stmt->errno . ').');
        http_response_code(503);
        exit('Medicine details are temporarily unavailable. Please try again.');
    }
    $stmt->bind_result($medId, $medName, $medQty, $medCategory, $medPrice, $medLocation);
    if ($stmt->fetch()) {
        $medicine = ['MED_ID' => $medId, 'MED_NAME' => $medName, 'MED_QTY' => $medQty, 'CATEGORY' => $medCategory, 'MED_PRICE' => $medPrice, 'LOCATION_RACK' => $medLocation];
    }
    $stmt->close();
} catch (Throwable $error) {
    error_log('pharmacy_test medicine lookup failed (' . get_class($error) . ').');
    http_response_code(503);
    exit('Medicine details are temporarily unavailable. Please try again.');
}

if ($medicine === null) {
    header('Location: inventory-view.php');
    exit;
}
if (!$form) {
    $form = $medicine;
}
?>

<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="nav2.css">
<link rel="stylesheet" type="text/css" href="form4.css">
<title>
Medicines
</title>
</head>

<body>

	<div class="sidenav">
		<h2 style="font-family:Arial; color:white; text-align:center;"> pharmacy_test </h2>
		<a href="adminmainpage.php">Dashboard</a>
		<button class="dropdown-btn">Inventory
		<i class="down"></i>
		</button>
		<div class="dropdown-container">
			<a href="inventory-add.php">Add New Medicine</a>
			<a href="inventory-view.php">Manage Inventory</a>
		</div>
		<button class="dropdown-btn">Suppliers
		<i class="down"></i>
		</button>
		<div class="dropdown-container">
			<a href="supplier-add.php">Add New Supplier</a>
			<a href="supplier-view.php">Manage Suppliers</a>
		</div>
		<button class="dropdown-btn">Stock Purchase
		<i class="down"></i>
		</button>
		<div class="dropdown-container">
			<a href="purchase-add.php">Add New Purchase</a>
			<a href="purchase-view.php">Manage Purchases</a>
		</div>
		<button class="dropdown-btn">Employees
		<i class="down"></i>
		</button>
		<div class="dropdown-container">
			<a href="employee-add.php">Add New Employee</a>
			<a href="employee-view.php">Manage Employees</a>
		</div>
		<button class="dropdown-btn">Customers
		<i class="down"></i>
		</button>
		<div class="dropdown-container">
			<a href="customer-add.php">Add New Customer</a>
			<a href="customer-view.php">Manage Customers</a>
		</div>
		<a href="sales-view.php">View Sales Invoice Details</a>
		<a href="salesitems-view.php">View Sold Products Details</a>
		<a href="pos1.php">Add New Sale</a>
		<button class="dropdown-btn">Reports
		<i class="down"></i>
		</button>
		<div class="dropdown-container">
			<a href="stockreport.php">Medicines - Low Stock</a>
			<a href="expiryreport.php">Medicines - Soon to Expire</a>
			<a href="salesreport.php">Transactions Reports</a>
		</div>	
	</div>

	<div class="topnav">
		<a href="logout.php">Logout</a>
	</div>
	
	<center>
	<div class="head">
	<h2> UPDATE MEDICINE DETAILS</h2>
	</div>
	</center>

	<div class="one">
		<div class="row">
			<form action="inventory-update.php" method="post">
				<div class="column">
				<p>
					<label for="medid">Medicine ID:</label><br>
					<input type="number" name="medid" value="<?= inventory_update_escape($medicine['MED_ID']) ?>" readonly>
				</p>
				<p>
					<label for="medname">Medicine Name:</label><br>
					<input type="text" name="medname" value="<?= inventory_update_escape($form['MED_NAME']) ?>" required maxlength="50">
				</p>
				<p>
					<label for="qty">Quantity:</label><br>
					<input type="number" name="qty" value="<?= inventory_update_escape($form['MED_QTY']) ?>" required min="0">
				</p>
				<p>
					<label for="cat">Category:</label><br>
					<input type="text" name="cat" value="<?= inventory_update_escape($form['CATEGORY']) ?>" maxlength="20">
				</p>
				</div>
				
				<div class="column">
				<p>
					<label for="sp">Price: </label><br>
					<input type="number" step="0.01" name="sp" value="<?= inventory_update_escape($form['MED_PRICE']) ?>" required min="0" max="9999.99" step="0.01">
				</p>
				<p>
					<label for="loc">Location:</label><br>
					<input type="text" name="loc" value="<?= inventory_update_escape($form['LOCATION_RACK']) ?>" maxlength="30">
				</p>
				</div>
		
				<?php if ($updateError !== ''): ?><p role="alert" style="color:red;"><?= inventory_update_escape($updateError) ?></p><?php endif; ?>
				<input type="submit" name="update" value="Update">
				</form>
				

		</div>
	</div>
	
</body>

<script>
	
		var dropdown = document.getElementsByClassName("dropdown-btn");
		var i;

			for (i = 0; i < dropdown.length; i++) {
			  dropdown[i].addEventListener("click", function() {
			  this.classList.toggle("active");
			  var dropdownContent = this.nextElementSibling;
			  if (dropdownContent.style.display === "block") {
			  dropdownContent.style.display = "none";
			  } else {
			  dropdownContent.style.display = "block";
			  }
			  });
			}
			
</script>
	
</html>