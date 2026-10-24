<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();
$id = $_GET['id'] ?? '';

if($id){
    $id = intval($id);

    mysqli_query($conn,
        "DELETE FROM students WHERE id='$id' AND center_id='$cid'"
    );
}

// ✅ Redirect BEFORE any HTML output
header("Location: students.php");
exit;
