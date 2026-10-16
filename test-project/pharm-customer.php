<?php
require_once __DIR__ . '/pos-common.php';
$role = 'pharmacist';
$employeeId = pos_require_role($role);
$employeeName = pos_employee_name($conn, $role);
$customerMessage = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['add'])) {
    $customerMessage = pos_create_customer($conn);
}
?>
<!DOCTYPE html>
<html>

<link rel="stylesheet" type="text/css" href="table1.css">
<link rel="stylesheet" type="text/css" href="nav2.css">
<link rel="stylesheet" type="text/css" href="form4.css">

<style>
body {font-family:Arial;}
</style>

<body>

	<div class="sidenav">
			<h2 style="font-family:Arial; color:white; text-align:center;"> pharmacy_test </h2>
			<a href="pharmmainpage.php">Dashboard</a>
			
			<a href="pharm-inventory.php">View Inventory</a>
			<a href="pharm-pos1.php">Add New Sale</a>
			<button class="dropdown-btn">Customers
			<i class="down"></i>
			</button>
			<div class="dropdown-container">
				<a href="pharm-customer.php">Add New Customer</a>
				<a href="pharm-customer-view.php">View Customers</a>
			</div>
	</div>

	<div class="topnav">
		<a href="logout1.php">Logout (signed in as <?= pos_escape($employeeName) ?>)</a>
	</div>
	
	<center>
	<div class="head">
	<h2> ADD CUSTOMER DETAILS</h2>
	</div>
	</center>
	
	<br><br><br><br><br><br><br><br>
	
	<div class="one">
		<div class="row">
			<form action="pharm-customer.php" method="post">
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
