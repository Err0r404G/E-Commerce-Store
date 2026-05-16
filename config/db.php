<?php
define("DB_HOST", "127.0.0.1");
define("DB_USER", "root");
define("DB_PASS", "");          // leave blank unless you set a MySQL password in XAMPP
define("DB_NAME", "ecommerce_store");
define("DB_PORT", 3307);
 
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
 
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
