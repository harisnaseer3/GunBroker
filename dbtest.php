<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'gunbroker';

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die('DB ERROR: ' . mysqli_connect_error());
}
echo "DB connected OK";
?>
