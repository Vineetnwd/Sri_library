<?php
require_once "../system/functions.php";
require_once "../system/db.php";

$cid = center_id();

$student = $_POST['student_id'];
$plan_id = $_POST['plan_id'];

$p = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT * FROM fee_plans WHERE id='$plan_id'")
);

$total = $p['amount'];
$months = $p['duration_month'];

$start = date('Y-m-d');
$end   = date('Y-m-d', strtotime("+$months month"));

mysqli_query($conn,
    "INSERT INTO student_fees
    (center_id,student_id,plan_id,total_fee,paid_amount,due_amount,start_date,end_date)
    VALUES
    ('$cid','$student','$plan_id','$total','0','$total','$start','$end')"
);

set_flash("success","Fee assigned successfully");
redirect("center/dashboard.php");
