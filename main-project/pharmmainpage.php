<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="stylesheet" href="nav2.css"><title>pharmacy_test · Pharmacist</title></head>
<body>
	<div class="sidenav"><h2>pharmacy_test</h2><a href="pharmmainpage.php">Overview</a><a href="pharm-inventory.php">Medicine inventory</a><a href="pharm-pos1.php">New sale</a><button class="dropdown-btn">Customers<i class="down"></i></button><div class="dropdown-container"><a href="pharm-customer.php">Add customer</a><a href="pharm-customer-view.php">View customers</a></div></div>
	<?php include "config.php"; session_start(); $sql="SELECT E_FNAME from EMPLOYEE WHERE E_ID='$_SESSION[user]'"; $result=$conn->query($sql); $row=$result->fetch_row(); $ename=$row ? $row[0] : 'Pharmacist'; ?>
	<div class="topnav"><a href="logout1.php">Log out · <?php echo htmlspecialchars($ename, ENT_QUOTES, 'UTF-8'); ?></a></div>
	<main class="dashboard"><div class="dash-intro"><div><p class="eyebrow">PHARMACIST WORKSPACE</p><h1>Good day, <?php echo htmlspecialchars($ename, ENT_QUOTES, 'UTF-8'); ?>.</h1><p>Quick access to the tools you use most.</p></div><a class="primary-action" href="pharm-pos1.php"><span>＋</span> Start a new sale</a></div>
	<div class="dash-grid"><a class="dash-card" href="pharm-pos1.php"><span class="icon">＋</span><h3>New sale</h3><p>Start a customer transaction and prepare the bill.</p></a><a class="dash-card" href="pharm-inventory.php"><span class="icon">✳</span><h3>Medicine inventory</h3><p>Check availability before dispensing products.</p></a><a class="dash-card" href="pharm-customer-view.php"><span class="icon">♡</span><h3>Customers</h3><p>Find a customer or create a new record.</p></a></div>
	<h2 class="section-label">A smooth counter experience</h2><div class="attention"><span class="icon">✧</span><span><strong>All your everyday actions, close at hand</strong>Use the left menu to move between sales, stock and customer records.</span></div></main>
	<script>document.querySelectorAll('.dropdown-btn').forEach(function(button){button.addEventListener('click',function(){this.classList.toggle('active');var panel=this.nextElementSibling;panel.style.display=panel.style.display==='block'?'none':'block';});});</script>
</body></html>
