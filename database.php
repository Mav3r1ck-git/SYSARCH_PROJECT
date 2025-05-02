<?php

$hostName = "localhost";
$dbUser = "root";
$dbPassword = "";
$dbName = "sysarch_project";

$conn = mysqli_connect($hostName, $dbUser, $dbPassword, $dbName);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Add sitin_count column to users table
$alterTableQuery = "ALTER TABLE users ADD COLUMN IF NOT EXISTS sitin_count INT DEFAULT 0";
$conn->query($alterTableQuery);

?>