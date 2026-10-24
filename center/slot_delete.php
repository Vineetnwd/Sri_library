<?php
require_once "../system/functions.php";
require_once "../system/db.php";

$cid = center_id();
$id  = $_GET['id'];

/* check usage */
$check = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT COUNT(*) total FROM students
         WHERE slot_id='$id' AND status='active'")
);

if($check['total'] > 0){
    set_flash("danger","Slot is currently in use");
    redirect("slots.php");
}

mysqli_query($conn,
    "DELETE FROM slots WHERE id='$id' AND center_id='$cid'"
);

set_flash("success","Slot deleted");
redirect("slots.php");
