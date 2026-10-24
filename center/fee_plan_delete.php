<?php
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();
$id  = $_GET['id'];

/* check usage */
$used = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT COUNT(*) total
         FROM student_fees
         WHERE plan_id='$id'")
);

if($used['total'] > 0){
    set_flash("danger","This plan is already assigned to students");
    redirect("fee_plans.php");
}

mysqli_query($conn,
    "DELETE FROM fee_plans
     WHERE id='$id' AND center_id='$cid'"
);

set_flash("success","Fee plan deleted");
redirect("center/fee_plans.php");
