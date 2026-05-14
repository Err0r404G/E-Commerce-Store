<?php
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");          // leave blank unless you set a MySQL password in XAMPP
define("DB_NAME", "ecommerce_store");
 
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
