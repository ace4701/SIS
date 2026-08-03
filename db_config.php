<?php
// Parse the configuration file safely without exposing credentials in code
$config = parse_ini_file(__DIR__ . '/config.ini');

if ($config === false) {
    die("System Error: Configuration file missing or unreadable.");
}

$host = $config['db_host'];
$user = $config['db_user'];
$pass = $config['db_pass'];
$dbname = $config['db_name'];

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>