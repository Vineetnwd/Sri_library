<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();
$id  = (int)($_GET['id'] ?? 0);

/* =============================
   FETCH STUDENT
============================= */
$res = mysqli_query($conn,
    "SELECT * FROM students
     WHERE id='$id' AND center_id='$cid'"
);

$student = mysqli_fetch_assoc($res);

if (!$student) {
    set_flash("danger","Student not found");
    redirect("center/students.php");
    exit;
}

/* =============================
   UPDATE STUDENT
============================= */
if (isset($_POST['update'])) {

    $name   = clean($_POST['name'] ?? '');
    $mobile = clean($_POST['mobile'] ?? '');
    $email  = clean($_POST['email'] ?? '');
    $gender = clean($_POST['gender'] ?? '');
    $seat   = (int)($_POST['seat_id'] ?? 0);
    $slot   = (int)($_POST['slot_id'] ?? 0);
    $status = clean($_POST['status'] ?? '');
    $joining_date = clean($_POST['joining_date'] ?? '');
    $address = clean($_POST['address'] ?? '');
    $plan_id = (int)($_POST['plan_id'] ?? 0);

    if(empty($joining_date)){
        $joining_date = date('Y-m-d');
    }

    /* PHOTO */
    $photo_name = $student['photo'];

    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){

        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];

        if(!in_array($ext, $allowed)){
            die("Invalid file type");
        }

        if($_FILES['photo']['size'] > 2*1024*1024){
            die("File too large (Max 2MB)");
        }

        $photo_name = time().rand(1000,9999).".".$ext;

        if(move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/".$photo_name)){
            
            if(!empty($student['photo']) && file_exists("../uploads/".$student['photo'])){
                unlink("../uploads/".$student['photo']);
            }

        } else {
            die("Photo upload failed");
        }
    }

    /* =============================
       SEAT CONFLICT CHECK
    ============================= */
    $check = mysqli_fetch_assoc(
        mysqli_query($conn,
            "SELECT COUNT(*) AS total FROM students
             WHERE center_id='$cid'
             AND seat_id='$seat'
             AND slot_id='$slot'
             AND status='active'
             AND id!='$id'")
    );

    if ($check['total'] > 0) {
        set_flash("danger","Seat already occupied in this slot");
        redirect("center/student_edit.php?id=".$id);
        exit;
    }

    /* =============================
       HISTORY
    ============================= */
    if ($student['seat_id'] != $seat || $student['slot_id'] != $slot) {

        mysqli_query($conn,
            "INSERT INTO seat_slot_history
            (center_id,student_id,
             old_seat_id,new_seat_id,
             old_slot_id,new_slot_id)
            VALUES
            ('$cid','$id',
             '{$student['seat_id']}','$seat',
             '{$student['slot_id']}','$slot')"
        );
    }

    /* =============================
       UPDATE QUERY
    ============================= */
    $query = "UPDATE students SET
        name='$name',
        mobile='$mobile',
        email='$email',
        gender='$gender',
        seat_id='$seat',
        slot_id='$slot',
        status='$status',
        joining_date='$joining_date',
        address='$address',
        photo='$photo_name',
        plan_id=" . ($plan_id > 0 ? "'$plan_id'" : "NULL") . "
     WHERE id='$id'";

    if(!mysqli_query($conn, $query)){
        die("Update Error: " . mysqli_error($conn));
    }

    set_flash("success","Student updated successfully");
    redirect("center/students.php");
    exit;
}

/* =============================
   FETCH SLOTS
============================= */
$slots = mysqli_query($conn,
    "SELECT * FROM slots
     WHERE center_id='$cid' AND status='active'
     ORDER BY start_time"
);

/* =============================
   FETCH PLANS
============================= */
$plans = mysqli_query($conn,
    "SELECT * FROM fee_plans WHERE center_id='$cid'"
);
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<h4 class="mb-4">
    <i class="fa fa-user-edit me-2"></i> Edit Student
</h4>

<form method="post" enctype="multipart/form-data">

<!-- SLOT -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        Seat & Slot
    </div>
    <div class="card-body row">

        <div class="col-md-6 mb-3">
            <label>Slot</label>
            <select name="slot_id" id="slot_id" class="form-select" required>
                <?php while($sl=mysqli_fetch_assoc($slots)){ ?>
                    <option value="<?= $sl['id'] ?>"
                        <?= $student['slot_id']==$sl['id']?'selected':'' ?>>
                        <?= $sl['slot_name'] ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label>Seat</label>
            <select name="seat_id" id="seat_id" class="form-select" required></select>
        </div>

    </div>
</div>

<!-- INFO -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        Student Info
    </div>
    <div class="card-body row">

        <div class="col-md-6 mb-3">
            <label>Name</label>
            <input type="text" name="name" value="<?= $student['name'] ?>" class="form-control" required>
        </div>

        <div class="col-md-6 mb-3">
            <label>Mobile</label>
            <input type="text" name="mobile" value="<?= $student['mobile'] ?>" class="form-control">
        </div>

        <div class="col-md-6 mb-3">
            <label>Email</label>
            <input type="email" name="email" value="<?= $student['email'] ?>" class="form-control">
        </div>

        <div class="col-md-6 mb-3">
            <label>Gender</label>
            <select name="gender" class="form-select">
                <option <?= $student['gender']=='Male'?'selected':'' ?>>Male</option>
                <option <?= $student['gender']=='Female'?'selected':'' ?>>Female</option>
            </select>
        </div>

        <div class="col-md-12 mb-3">
            <label>Address</label>
            <textarea name="address" class="form-control"><?= htmlspecialchars($student['address']) ?></textarea>
        </div>

        <div class="col-md-6 mb-3">
            <label>Joining Date</label>
            <input type="date" name="joining_date" value="<?= $student['joining_date'] ?>" class="form-control">
        </div>

        <div class="col-md-6 mb-3">
            <label>Photo</label>
            <input type="file" name="photo" class="form-control">
            <?php if(!empty($student['photo'])){ ?>
                <img src="../uploads/<?= $student['photo'] ?>" style="height:70px;margin-top:5px;">
            <?php } ?>
        </div>

        <div class="col-md-6 mb-3">
            <label>Status</label>
            <select name="status" class="form-select">
                <option value="active" <?= $student['status']=='active'?'selected':'' ?>>Active</option>
                <option value="pending" <?= $student['status']=='pending'?'selected':'' ?>>Pending Verification</option>
                <option value="left" <?= $student['status']=='left'?'selected':'' ?>>Left</option>
                <option value="rejected" <?= $student['status']=='rejected'?'selected':'' ?>>Rejected</option>
            </select>
        </div>

        <div class="col-md-12 mb-3">
            <label>Assigned Fee Plan</label>
            <select name="plan_id" class="form-select">
                <option value="">No Plan</option>
                <?php while($p=mysqli_fetch_assoc($plans)){ ?>
                    <option value="<?= $p['id'] ?>" <?= $student['plan_id']==$p['id']?'selected':'' ?>>
                        <?= $p['plan_name'] ?> — ₹<?= $p['amount'] ?>
                    </option>
                <?php } ?>
            </select>
        </div>

    </div>
</div>

<div class="text-end mb-5">
    <button name="update" class="btn btn-primary">Update</button>
    <a href="students.php" class="btn btn-secondary">Back</a>
</div>

</form>
</div>

<?php include "layout/footer.php"; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
function loadSeats(slot_id, current_seat){
    $.get("ajax_get_seats.php", {
        slot_id: slot_id,
        current_seat: current_seat
    }, function(data){
        $('#seat_id').html(data);
    });
}

$('#slot_id').change(function(){
    loadSeats($(this).val(), <?= $student['seat_id'] ?>);
});

loadSeats(<?= $student['slot_id'] ?>, <?= $student['seat_id'] ?>);
</script>