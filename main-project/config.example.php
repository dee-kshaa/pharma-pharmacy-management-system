<?php
// Copy this file to config.php and set credentials for your local MySQL instance.
$conn = mysqli_connect('localhost', 'root', 'CHANGE_ME', 'pharmacy_main');
if (!$conn) {
    error_log('Database connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    exit('Database unavailable. Check the local configuration.');
}
?>
