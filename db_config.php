<?php
$host = "localhost";
$user = "root";
$pass = ""; // Default password for Laragon is usually blank
$dbname = "sis_db";

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>