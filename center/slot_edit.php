<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();
$id  = $_GET['id'];

$slot = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM slots WHERE id='$id' AND center_id='$cid'")
);

if(!$slot){
    set_flash("danger","Slot not found");
    redirect("slots.php");
}

if(isset($_POST['update'])){

    $name  = trim($_POST['slot_name']);
    $start = $_POST['start_time'];
    $end   = $_POST['end_time'];
    $status= $_POST['status'];

    if(strtotime($start) >= strtotime($end)){
        set_flash("danger","Invalid time range");
        redirect("center/slot_edit.php?id=".$id);
    }

    mysqli_query($conn,
        "UPDATE slots SET
            slot_name='$name',
            start_time='$start',
            end_time='$end',
            status='$status'
         WHERE id='$id'"
    );

    set_flash("success","Slot updated successfully");
    redirect("center/slots.php");
}
?>

<?php include "layout/sidebar.php"; ?>

<h4>Edit Slot</h4>

<form method="post" style="max-width:450px">

    <input type="text" name="slot_name"
           value="<?= $slot['slot_name'] ?>"
           class="form-control mb-3"
           required>

    <label>Start Time</label>
    <input type="time" name="start_time"
           value="<?= $slot['start_time'] ?>"
           class="form-control mb-3">

    <label>End Time</label>
    <input type="time" name="end_time"
           value="<?= $slot['end_time'] ?>"
           class="form-control mb-3">

    <label>Status</label>
    <select name="status" class="form-control mb-3">
        <option value="active"   <?= $slot['status']=='active'?'selected':'' ?>>Active</option>
        <option value="inactive" <?= $slot['status']=='inactive'?'selected':'' ?>>Inactive</option>
    </select>

    <button name="update" class="btn btn-primary">
        Update Slot
    </button>

</form>

<?php include "layout/footer.php"; ?>
