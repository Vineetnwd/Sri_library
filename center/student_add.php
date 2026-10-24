<?php
require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* ===============================
   FETCH DATA
================================ */
$slots = mysqli_query($conn,
    "SELECT * FROM slots 
     WHERE center_id='$cid'
     AND status='active'
     ORDER BY start_time"
);

$plans = mysqli_query($conn,
    "SELECT * FROM fee_plans WHERE center_id='$cid'"
);

$current_slot = get_current_slot($cid);
$current_slot_id = $current_slot['id'] ?? '';
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<h4 class="mb-4">
    <i class="fa fa-user-plus me-2"></i> Add Student
</h4>

<form action="student_save.php" method="post" enctype="multipart/form-data">

<!-- ===============================
     SEAT & SLOT
================================ -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <i class="fa fa-chair me-2"></i> Seat & Slot Details
    </div>

    <div class="card-body">
        <div class="row">

            <div class="col-md-6 mb-3">
                <label class="form-label">Slot</label>
                <select name="slot_id" id="slot_id" class="form-select" required>
                    <option value="">Select Slot</option>
                    <?php while($sl=mysqli_fetch_assoc($slots)){ ?>
                        <option value="<?= $sl['id'] ?>"
                            <?= ($sl['id']==$current_slot_id)?'selected':'' ?>>
                            <?= $sl['slot_name'] ?>
                            (<?= $sl['start_time'] ?> – <?= $sl['end_time'] ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Seat</label>
                <select name="seat_id" id="seat_id" class="form-select" required>
                    <option value="">Select Slot First</option>
                </select>
            </div>

        </div>
    </div>
</div>

<!-- ===============================
     STUDENT INFO
================================ -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <i class="fa fa-user me-2"></i> Student Information
    </div>

    <div class="card-body">
        <div class="row">

            <div class="col-md-6 mb-3">
                <label class="form-label">Student Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Mobile</label>
                <input type="text" name="mobile" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option>Male</option>
                    <option>Female</option>
                </select>
            </div>
            <div class="col-md-12 mb-3">
                <label class="form-label">Address</label>
                <textarea name="address"
                          class="form-control"
                          rows="2"
                          placeholder="Enter full address"></textarea>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Joining Date</label>
                <input type="date" name="joining_date"
                       value="<?= date('Y-m-d') ?>"
                       class="form-control">
            </div>
            
            <div class="col-md-6 mb-3">
                <label class="form-label">Student Photo</label>
                <input type="file" name="photo" class="form-control">
            </div>

        </div>
    </div>
</div>

<!-- ===============================
     FEES
================================ -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <i class="fa fa-wallet me-2"></i> Fee Details
    </div>

    <div class="card-body">
        <div class="row">

            <div class="col-md-6 mb-3">
                <label class="form-label">Fee Plan</label>
                <select name="plan_id" id="plan_id" class="form-select" required>
                    <option value="">Select Plan</option>
                    <?php while($p=mysqli_fetch_assoc($plans)){ ?>
                        <option value="<?= $p['id'] ?>"
                            data-amount="<?= $p['amount'] ?>">
                            <?= $p['plan_name'] ?> — ₹<?= $p['amount'] ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Plan Rate</label>
                <input type="number" id="total_fee"
                       class="form-control bg-light"
                       readonly>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Initial Payment</label>
                <input type="number"
                       name="paid_amount"
                       id="paid_amount"
                       value="0"
                       class="form-control">
            </div>

        </div>
    </div>
</div>

<div class="text-end mb-5">
    <button class="btn btn-success btn-lg px-4">
        <i class="fa fa-save me-2"></i> Save Student
    </button>
</div>

</form>

</div>

<?php include "layout/footer.php"; ?>

<!-- ===============================
     JS SECTION
================================ -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
function loadSeats(slot_id)
{
    if(slot_id === ''){
        $('#seat_id').html('<option>Select slot first</option>');
        return;
    }

    $.get(
        "ajax_get_seats.php",
        { slot_id: slot_id },
        function(data){
            $('#seat_id').html('<option value="">Select Seat</option>' + data);
        }
    );
}

/* slot change */
$('#slot_id').change(function(){
    loadSeats($(this).val());
});

/* auto load current slot */
<?php if($current_slot_id){ ?>
    loadSeats('<?= $current_slot_id ?>');
<?php } ?>

/* fee calculation */
$('#plan_id').change(function(){
    let amt = $('option:selected',this).data('amount') || 0;
    $('#total_fee').val(amt);
    $('#paid_amount').val(0);
});
</script>
