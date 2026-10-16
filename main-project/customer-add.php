<?php
require_once __DIR__ . '/pos-common.php';
$role = 'admin';
$employeeId = pos_require_role($role);
$employeeName = pos_employee_name($conn, $role);
$customerMessage = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['add'])) {
    $customerMessage = pos_create_customer($conn);
}
?>
<!DOCTYPE html>
<html>

<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="nav2.css">
<link rel="stylesheet" type="text/css" href="form4.css">
<title>
Customers
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
		<a href="logout.php">Logout (<?= pos_escape($employeeName) ?>)</a>
	</div>
	
	<center>
	<div class="head">
	<h2> ADD CUSTOMER DETAILS</h2>
	</div>
	</center>

	<br><br><br><br><br><br><br><br>
	
	<div class="one">
		<div class="row">
			<form action="customer-add.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= pos_escape(pos_csrf_token()) ?>">
				<div class="column">
					<p>
						<label for="cid">Customer ID:</label><br>
						<input type="number" name="cid" min="1" max="999999" required>
					</p>
					<p>
						<label for="cfname">First Name:</label><br>
						<input type="text" name="cfname" maxlength="30" required>
					</p>
					<p>
						<label for="clname">Last Name:</label><br>
						<input type="text" name="clname" maxlength="30">
					</p>
					<p>
						<label for="age">Age:</label><br>
						<input type="number" name="age" min="0" max="130" required>
					</p>
					
					<p>
						<label for="sex">Sex: </label><br>
						<select id="sex" name="sex">
								<option value="" selected disabled>Select</option>
								<option>Female</option>
								<option>Male</option>
								<option>Others</option>
						</select>
					</p>
					
				</div>
				<div class="column">
					
					<p>
						<label for="phno">Phone Number: </label><br>
						<input type="text" name="phno" inputmode="numeric" pattern="[0-9]{9,10}" minlength="9" maxlength="10" required>
						<small>Enter 9–10 digits without a country code.</small>
					</p>
					<p>
						<label for="emid">Email ID:</label><br>
						<input type="email" name="emid" maxlength="40">
					</p>
				</div>
				
			
			<input type="submit" name="add" value="Add Customer">
			</form>
		<br>
		
		
			<?php if ($customerMessage !== ''): ?>
            <p role="status" style="color:<?= str_starts_with($customerMessage, 'Customer successfully') ? 'green' : 'red' ?>;">
                <?= pos_escape($customerMessage) ?>
            </p>
        <?php endif; ?>
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
