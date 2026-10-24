<?php
require_once "../system/functions.php";
require_once "../system/db.php";

$cid = center_id();
$id  = $_GET['id'];

/* ensure no student uses seat */
$check = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT COUNT(*) total FROM students
         WHERE seat_id='$id' AND status='active'")
);

if($check['total'] > 0){
    set_flash("danger","Seat is currently occupied");
    redirect("seats.php");
}

mysqli_query($conn,
    "DELETE FROM seats WHERE id='$id' AND center_id='$cid'"
);

set_flash("success","Seat deleted");
redirect("seats.php");
