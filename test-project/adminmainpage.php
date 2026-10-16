<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="stylesheet" href="nav2.css"><title>pharmacy_test · Overview</title></head>
<body>
	<div class="sidenav">
		<h2>pharmacy_test</h2><a href="adminmainpage.php">Overview</a>
		<button class="dropdown-btn">Inventory<i class="down"></i></button><div class="dropdown-container"><a href="inventory-add.php">Add medicine</a><a href="inventory-view.php">Manage inventory</a></div>
		<button class="dropdown-btn">Suppliers<i class="down"></i></button><div class="dropdown-container"><a href="supplier-add.php">Add supplier</a><a href="supplier-view.php">Manage suppliers</a></div>
		<button class="dropdown-btn">Stock purchase<i class="down"></i></button><div class="dropdown-container"><a href="purchase-add.php">Record purchase</a><a href="purchase-view.php">Purchase history</a></div>
		<button class="dropdown-btn">People<i class="down"></i></button><div class="dropdown-container"><a href="employee-add.php">Add employee</a><a href="employee-view.php">Manage employees</a><a href="customer-add.php">Add customer</a><a href="customer-view.php">Manage customers</a></div>
		<button class="dropdown-btn">Sales<i class="down"></i></button><div class="dropdown-container"><a href="pos1.php">New sale</a><a href="sales-view.php">Invoices</a><a href="salesitems-view.php">Sold products</a></div>
		<button class="dropdown-btn">Reports<i class="down"></i></button><div class="dropdown-container"><a href="stockreport.php">Low stock</a><a href="expiryreport.php">Soon to expire</a><a href="salesreport.php">Transactions</a></div>
	</div>
	<div class="topnav"><a href="logout.php">Log out · Admin</a></div>
	<main class="dashboard">
		<div class="dash-intro"><div><p class="eyebrow">PHARMACY WORKSPACE</p><h1>A calmer way to manage care.</h1><p>Everything you need to keep your pharmacy running smoothly.</p></div><a class="primary-action" href="pos1.php"><span>＋</span> Start a new sale</a></div>
		<div class="dash-grid">
			<a class="dash-card" href="inventory-view.php"><span class="icon">✳</span><h3>Medicine inventory</h3><p>Review stock, update details and keep your shelves in order.</p></a>
			<a class="dash-card" href="purchase-view.php"><span class="icon">↗</span><h3>Stock purchases</h3><p>Track incoming stock and supplier purchases.</p></a>
			<a class="dash-card" href="customer-view.php"><span class="icon">♡</span><h3>Customers</h3><p>Find customer records and keep service personal.</p></a>
			<a class="dash-card" href="employee-view.php"><span class="icon">♧</span><h3>Team</h3><p>Manage employee details and pharmacy access.</p></a>
			<a class="dash-card" href="sales-view.php"><span class="icon">▤</span><h3>Sales & invoices</h3><p>Review completed transactions and sold items.</p></a>
			<a class="dash-card" href="salesreport.php"><span class="icon">⌁</span><h3>Reports</h3><p>Check sales trends, stock levels and expiry dates.</p></a>
		</div>
		<h2 class="section-label">A little attention goes a long way</h2>
		<a class="attention" href="stockreport.php"><span class="icon">◉</span><span><strong>Keep an eye on stock and expiry dates</strong>Open your inventory alerts to spot items that need attention.</span><span style="margin-left:auto;color:#526f5d">→</span></a>
	</main>
	<script>document.querySelectorAll('.dropdown-btn').forEach(function(button){button.addEventListener('click',function(){this.classList.toggle('active');var panel=this.nextElementSibling;panel.style.display=panel.style.display==='block'?'none':'block';});});</script>
</body></html>
