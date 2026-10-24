<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

if(isset($_POST['bulk_add'])){
    $prefix = clean($_POST['prefix']);
    $start = intval($_POST['start_num']);
    $end = intval($_POST['end_num']);
    
    if($end < $start){
        set_flash("danger", "Ending number must be greater than starting number");
        redirect("seat_add.php");
    }
    
    if(($end - $start) > 100){
        set_flash("danger", "You can add maximum 100 seats at once");
        redirect("seat_add.php");
    }
    
    $added = 0;
    $skipped = 0;
    
    for($i = $start; $i <= $end; $i++){
        $seat_no = $prefix . $i;
        
        /* Check if exists */
        $check = mysqli_num_rows(
            mysqli_query($conn,
                "SELECT * FROM seats WHERE center_id='$cid' AND seat_no='$seat_no'")
        );
        
        if($check == 0){
            mysqli_query($conn,
                "INSERT INTO seats (center_id, seat_no) VALUES ('$cid', '$seat_no')");
            $added++;
        } else {
            $skipped++;
        }
    }
    
    set_flash("success", "$added seats added successfully" . ($skipped > 0 ? " ($skipped skipped - already exist)" : ""));
    redirect("seats.php");
}

redirect("seat_add.php");
?>