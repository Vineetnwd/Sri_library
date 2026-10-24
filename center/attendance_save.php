<?php
require_once "../system/functions.php";
require_once "../system/db.php";

$cid = center_id();
$date = $_POST['date'];
$attendance = $_POST['att'];

/* Delete existing attendance for this date */
mysqli_query($conn,
    "DELETE FROM attendance 
     WHERE center_id='$cid' 
     AND attendance_date='$date'"
);

/* Insert new attendance */
foreach($attendance as $student_id => $status){
    mysqli_query($conn,
        "INSERT INTO attendance 
        (center_id, student_id, attendance_date, status)
        VALUES
        ('$cid', '$student_id', '$date', '$status')"
    );
}

set_flash("success", "Attendance marked successfully for " . date('d M Y', strtotime($date)));
redirect("center/attendance.php?date=$date");