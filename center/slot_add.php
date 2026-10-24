<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

if(isset($_POST['save'])){

    $name  = trim($_POST['slot_name']);
    $start = $_POST['start_time'];
    $end   = $_POST['end_time'];

    if(strtotime($start) >= strtotime($end)){
        set_flash("danger","End time must be greater than start time");
        redirect("slot_add.php");
    }

    mysqli_query($conn,
        "INSERT INTO slots 
        (center_id,slot_name,start_time,end_time,status)
        VALUES
        ('$cid','$name','$start','$end','active')"
    );

    set_flash("success","Slot added successfully");
    redirect("center/slots.php");
}
?>

<?php include "layout/sidebar.php"; ?>

<h4>Add Slot</h4>

<form method="post" style="max-width:450px">

    <input type="text" name="slot_name"
           class="form-control mb-3"
           placeholder="Slot Name (Morning)"
           required>

    <label>Start Time</label>
    <input type="time" name="start_time"
           class="form-control mb-3"
           required>

    <label>End Time</label>
    <input type="time" name="end_time"
           class="form-control mb-3"
           required>

    <button name="save" class="btn btn-primary">
        Save Slot
    </button>

</form>

<?php include "layout/footer.php"; ?>
