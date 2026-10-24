<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

/* Add Seat */
if(isset($_POST['add_seat'])){
    $seat_no = clean($_POST['seat_no']);
    
    /* Check duplicate */
    $check = mysqli_num_rows(
        mysqli_query($conn,
            "SELECT * FROM seats WHERE center_id='$cid' AND seat_no='$seat_no'")
    );
    
    if($check > 0){
        set_flash("danger", "Seat number already exists");
    } else {
        mysqli_query($conn,
            "INSERT INTO seats (center_id, seat_no) VALUES ('$cid', '$seat_no')");
        
        set_flash("success", "Seat added successfully");
        redirect("seats.php");
    }
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-plus-circle me-2"></i>Add New Seat
    </h1>
    <a href="seats.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Seats
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-chair me-2"></i>Seat Information
            </div>
            <div class="card-body">
                
                <form method="post">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Seat Number *</label>
                        <input type="text" name="seat_no" class="form-control form-control-lg"
                               required placeholder="e.g., A1, B2, 101">
                        <small class="text-muted">
                            Enter a unique identifier for this seat
                        </small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Tip:</strong> Use a consistent naming pattern like A1, A2, A3 
                        or 101, 102, 103 for better organization
                    </div>
                    
                    <button name="add_seat" class="btn btn-success btn-lg w-100 mt-3">
                        <i class="fas fa-save me-2"></i>Add Seat
                    </button>
                    
                </form>
                
            </div>
        </div>
        
        <!-- Quick Add Multiple Seats -->
        <div class="card shadow mt-4">
            <div class="card-header bg-info text-white">
                <i class="fas fa-layer-group me-2"></i>Quick Add Multiple Seats
            </div>
            <div class="card-body">
                <form method="post" action="seat_bulk_add.php">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Prefix</label>
                            <input type="text" name="prefix" class="form-control" 
                                   placeholder="e.g., A" maxlength="5">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Starting Number</label>
                            <input type="number" name="start_num" class="form-control" 
                                   value="1" min="1" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ending Number</label>
                            <input type="number" name="end_num" class="form-control" 
                                   value="10" min="1" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">&nbsp;</label>
                            <button name="bulk_add" class="btn btn-info w-100">
                                <i class="fas fa-plus me-2"></i>Create Seats
                            </button>
                        </div>
                    </div>
                    
                    <small class="text-muted d-block mt-2">
                        Example: Prefix "A" with range 1-10 will create A1, A2, A3...A10
                    </small>
                    
                </form>
            </div>
        </div>
    </div>
</div>

</div>

<?php include "layout/footer.php"; ?>