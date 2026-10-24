<?php
$host = "localhost";
$user = "u443617320_lms";
$pass = "@Lms_2001";
$db   = "u443617320_lms";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}
