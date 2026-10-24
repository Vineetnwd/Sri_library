<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();
$id = intval($_GET['id'] ?? 0);

$seat = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM seats WHERE id='$id' AND center_id='$cid'")
);

if(!$seat){
    set_flash("danger", "Seat not found");
    redirect("seats.php");
}

/* Check if seat is occupied */
$student = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT s.*, sl.slot_name FROM students s
         LEFT JOIN slots sl ON sl.id = s.slot_id
         WHERE s.seat_id='$id' AND s.center_id='$cid' AND s.status='active'")
);

/* Update Seat */
if(isset($_POST['update_seat'])){
    $seat_no = clean($_POST['seat_no']);
    
    /* Check duplicate (excluding current) */
    $check = mysqli_num_rows(
        mysqli_query($conn,
            "SELECT * FROM seats WHERE center_id='$cid' 
             AND seat_no='$seat_no' AND id!='$id'")
    );
    
    if($check > 0){
        set_flash("danger", "Seat number already exists");
    } else {
        mysqli_query($conn,
            "UPDATE seats SET seat_no='$seat_no' WHERE id='$id'");
        
        set_flash("success", "Seat updated successfully");
        redirect("seats.php");
    }
}

/* Release Seat */
if(isset($_POST['release_seat'])){
    if($student){
        mysqli_query($conn,
            "UPDATE students SET seat_id=NULL WHERE id='{$student['id']}'");
        
        set_flash("success", "Seat released successfully");
        redirect("seat_edit.php?id=$id");
    }
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-edit me-2"></i>Edit Seat
    </h1>
    <a href="seats.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Seats
    </a>
</div>

<div class="row">
    
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-chair me-2"></i>Seat Details
            </div>
            <div class="card-body">
                
                <form method="post">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Seat Number *</label>
                        <input type="text" name="seat_no" class="form-control form-control-lg"
                               value="<?= $seat['seat_no'] ?>" required>
                    </div>
                    
                    <button name="update_seat" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-save me-2"></i>Update Seat
                    </button>
                    
                </form>
                
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-header <?= $student ? 'bg-danger' : 'bg-success' ?> text-white">
                <i class="fas fa-info-circle me-2"></i>Occupancy Status
            </div>
            <div class="card-body">
                
                <?php if($student){ ?>
                    
                    <div class="alert alert-danger">
                        <i class="fas fa-user me-2"></i>
                        <strong>OCCUPIED</strong>
                    </div>
                    
                    <table class="table table-bordered">
                        <tr>
                            <th width="150">Student Name</th>
                            <td><strong><?= $student['name'] ?></strong></td>
                        </tr>
                        <tr>
                            <th>Reg. Number</th>
                            <td><?= $student['registration_no'] ?></td>
                        </tr>
                        <tr>
                            <th>Mobile</th>
                            <td><?= $student['mobile'] ?></td>
                        </tr>
                        <tr>
                            <th>Slot</th>
                            <td>
                                <span class="badge bg-info"><?= $student['slot_name'] ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Joined Date</th>
                            <td><?= date('d M Y', strtotime($student['join_date'])) ?></td>
                        </tr>
                    </table>
                    
                    <form method="post" onsubmit="return confirm('Are you sure you want to release this seat?')">
                        <button name="release_seat" class="btn btn-warning w-100">
                            <i class="fas fa-unlock me-2"></i>Release This Seat
                        </button>
                    </form>
                    
                    <div class="mt-3">
                        <a href="student_edit.php?id=<?= $student['id'] ?>" class="btn btn-info w-100">
                            <i class="fas fa-user-edit me-2"></i>Edit Student Details
                        </a>
                    </div>
                    
                <?php } else { ?>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>AVAILABLE</strong>
                    </div>
                    
                    <p class="text-center text-muted py-4">
                        <i class="fas fa-chair fa-4x mb-3 d-block"></i>
                        This seat is currently not assigned to any student
                    </p>
                    
                    <a href="student_add.php?seat_id=<?= $id ?>" class="btn btn-primary w-100">
                        <i class="fas fa-user-plus me-2"></i>Assign to New Student
                    </a>
                    
                <?php } ?>
                
            </div>
        </div>
    </div>
    
</div>

</div>

<?php include "layout/footer.php"; ?>