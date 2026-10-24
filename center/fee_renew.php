<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();
$sid = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

/* =============================
   VALIDATION
============================= */
if($sid <= 0){
    set_flash("danger","Invalid student");
    redirect("students.php");
    exit;
}

/* =============================
   STUDENT
============================= */
$student = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM students
         WHERE id='$sid' AND center_id='$cid'")
);

if(!$student){
    set_flash("danger","Student not found");
    redirect("students.php");
    exit;
}

/* =============================
   PLANS
============================= */
$plans = mysqli_query($conn,
    "SELECT * FROM fee_plans WHERE center_id='$cid'"
);

/* =============================
   RENEW
============================= */
if(isset($_POST['renew'])){

    $plan_id = (int)$_POST['plan_id'];

    $plan = mysqli_fetch_assoc(
        mysqli_query($conn,
            "SELECT * FROM fee_plans WHERE id='$plan_id'")
    );

    if(!$plan){
        set_flash("danger","Invalid plan selected");
        redirect("fee_renew.php?student_id=".$sid);
        exit;
    }

    $total  = $plan['amount'];
    $months = $plan['duration_month'];

    $advance = $student['advance_balance'];

    $use  = min($advance, $total);
    $paid = $use;
    $due  = $total - $use;

    $new_advance = $advance - $use;

    $start = date('Y-m-d');
    $end   = date('Y-m-d', strtotime("+$months month"));

    /* INSERT */
    mysqli_query($conn,
        "INSERT INTO student_fees
        (center_id,student_id,plan_id,
         total_fee,paid_amount,due_amount,
         start_date,end_date,status)
        VALUES
        ('$cid','$sid','$plan_id',
         '$total','$paid','$due',
         '$start','$end','running')"
    );

    /* UPDATE ADVANCE */
    mysqli_query($conn,
        "UPDATE students SET
            advance_balance='$new_advance'
         WHERE id='$sid'"
    );

    set_flash("success","Subscription renewed successfully");
    redirect("student_fees.php");
    exit;
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold">
        <i class="fa fa-sync text-success me-2"></i> Renew Subscription
    </h4>
    <a href="students.php" class="btn btn-secondary">
        Back
    </a>
</div>

<div class="row justify-content-center">

<div class="col-lg-5">

<div class="card shadow border-0" style="border-radius:12px;">

<div class="card-body p-4">

<!-- STUDENT INFO -->
<div class="mb-3">
    <h5><?= $student['name'] ?></h5>
    <small class="text-muted">
        Reg: <?= $student['registration_no'] ?>
    </small>
</div>

<!-- ADVANCE -->
<div class="alert alert-info d-flex justify-content-between">
    <span>Advance Balance</span>
    <strong>₹ <?= number_format($student['advance_balance']) ?></strong>
</div>

<form method="post">

<!-- PLAN -->
<div class="mb-3">
    <label class="form-label fw-semibold">Select Plan</label>
    <select name="plan_id" id="plan_id" class="form-select" required>
        <option value="">Choose Plan</option>
        <?php while($p=mysqli_fetch_assoc($plans)){ ?>
            <option value="<?= $p['id'] ?>"
                data-amount="<?= $p['amount'] ?>"
                data-month="<?= $p['duration_month'] ?>">
                <?= $p['plan_name'] ?> — ₹<?= $p['amount'] ?>
            </option>
        <?php } ?>
    </select>
</div>

<!-- SUMMARY -->
<div class="card bg-light p-3 mb-3" id="summary" style="display:none;">
    <div class="d-flex justify-content-between">
        <span>Total Fee</span>
        <strong id="total"></strong>
    </div>
    <div class="d-flex justify-content-between">
        <span>Used from Advance</span>
        <strong id="used"></strong>
    </div>
    <div class="d-flex justify-content-between text-danger">
        <span>Remaining Due</span>
        <strong id="due"></strong>
    </div>
</div>

<button name="renew" class="btn btn-success w-100 py-2">
    <i class="fa fa-check me-2"></i> Renew Subscription
</button>

</form>

</div>
</div>

</div>
</div>

</div>

<?php include "layout/footer.php"; ?>

<script>
let advance = <?= $student['advance_balance'] ?>;

document.getElementById('plan_id').addEventListener('change', function(){

    let amt = parseFloat(this.selectedOptions[0].dataset.amount || 0);

    let use = Math.min(advance, amt);
    let due = amt - use;

    document.getElementById('summary').style.display = 'block';

    document.getElementById('total').innerText = '₹ ' + amt;
    document.getElementById('used').innerText  = '₹ ' + use;
    document.getElementById('due').innerText   = '₹ ' + due;
});
</script>