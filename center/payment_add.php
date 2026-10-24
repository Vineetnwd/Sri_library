<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();
$preselect_sid = (int)($_GET['student_id'] ?? 0);

/* =============================
   FETCH PLANS
============================= */
$plans = [];
$q_plans = mysqli_query($conn, "SELECT * FROM fee_plans WHERE center_id='$cid'");
while($p = mysqli_fetch_assoc($q_plans)){
    $plans[$p['id']] = $p;
}

/* =============================
   FETCH STUDENTS & CALCULATE DUES
============================= */
$students_data = [];
$q_students = mysqli_query($conn,
    "SELECT s.*, 
            (SELECT SUM(amount) FROM payments WHERE student_id=s.id AND (status != 'cancelled' OR status IS NULL)) as total_paid
     FROM students s
     WHERE s.center_id='$cid' AND s.status='active'
     ORDER BY s.name"
);

while($s = mysqli_fetch_assoc($q_students)){
    $pid = $s['plan_id'];
    $paid = (float)$s['total_paid'];
    $expected = 0;
    
    if($pid && isset($plans[$pid])){
        $plan = $plans[$pid];
        
        $join_date = new DateTime($s['joining_date']);
        $today = new DateTime('today');
        
        if ($today < $join_date) {
            $months_passed = 0;
        } else {
            $diff = $join_date->diff($today);
            $months_passed = ($diff->y * 12) + $diff->m;
        }
        
        $duration = (int)$plan['duration_month'];
        if ($duration < 1) $duration = 1;
        
        $cycles = floor($months_passed / $duration) + 1;
        $expected = $cycles * (float)$plan['amount'];
    }
    
    $due = $expected - $paid;
    
    $s['calculated_due'] = $due;
    $s['calculated_expected'] = $expected;
    $s['calculated_paid'] = $paid;
    
    $students_data[] = $s;
}


/* =============================
   ADD PAYMENT
============================= */
if(isset($_POST['pay'])){

    $sid  = (int)$_POST['student_id'];
    $amt  = (float)$_POST['amount'];
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $mode = clean($_POST['mode']);

    if($sid <= 0 || $amt <= 0){
        set_flash("danger","Invalid data");
        redirect("center/payment_add.php");
        exit;
    }

    /* Just insert into payments! No more student_fees table updates needed. */
    $res = mysqli_query($conn,
        "INSERT INTO payments
        (center_id,student_id,amount,payment_mode,payment_date,payment_type)
        VALUES
        ('$cid','$sid','$amt','$mode','$payment_date','fee')"
    );

    if(!$res){
        die("Insert Error: ".mysqli_error($conn));
    }

    $payment_id = mysqli_insert_id($conn);

    set_flash("success", "Payment added successfully");
    redirect("center/receipt.php?id=".$payment_id);
    exit;
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="fa fa-money-bill-wave me-2 text-success"></i> Add Payment
    </h4>
</div>

<div class="row justify-content-center">

<div class="col-lg-5">

<div class="card shadow border-0" style="border-radius:12px;">

    <div class="card-header bg-success text-white">
        Payment Details
    </div>

    <div class="card-body p-4">

        <form method="post">

            <div class="mb-3">
                <label>Select Student</label>
                <select name="student_id" id="student_id" class="form-select" required>
                    <option value="" data-due="" data-paid="" data-expected="">Choose student...</option>
                    <?php foreach($students_data as $s){ 
                        $due = $s['calculated_due'];
                        $paid = $s['calculated_paid'];
                        $expected = $s['calculated_expected'];
                        $selected = ($s['id'] == $preselect_sid) ? 'selected' : '';
                    ?>
                        <option value="<?= $s['id'] ?>" <?= $selected ?> data-due="<?= $due ?>" data-paid="<?= $paid ?>" data-expected="<?= $expected ?>">
                            <?= $s['name'] ?> <?= $due > 0 ? "(Due: ₹$due)" : "" ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <!-- PAYMENT SUMMARY -->
            <div id="payment_summary" class="card bg-light p-3 mb-3 border-0" style="display:none; border-radius: 8px;">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small fw-bold text-uppercase">Total Billed</span>
                    <strong id="summary_expected" class="text-dark">₹0</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted small fw-bold text-uppercase">Total Paid</span>
                    <strong id="summary_paid" class="text-success">₹0</strong>
                </div>
                <div class="d-flex justify-content-between mt-2 pt-2 border-top">
                    <span class="text-muted small fw-bold text-uppercase">Current Due</span>
                    <strong id="summary_due" class="text-danger">₹0</strong>
                </div>
            </div>

            <div class="mb-3">
                <label>Amount to Pay</label>
                <input type="number" name="amount" id="amount"
                       class="form-control"
                       required>
            </div>

            <div class="mb-3">
                <label>Mode</label>
                <select name="mode" class="form-select">
                    <option value="cash">Cash</option>
                    <option value="upi">UPI</option>
                    <option value="bank">Bank</option>
                </select>
            </div>

            <div class="mb-4">
                <label>Date</label>
                <input type="date"
                       name="payment_date"
                       value="<?= date('Y-m-d') ?>"
                       class="form-control"
                       required>
            </div>

            <button name="pay" class="btn btn-success w-100">
                Submit Payment
            </button>

        </form>

    </div>

</div>

</div>
</div>
</div>

<?php include "layout/footer.php"; ?>

<script>
document.getElementById('student_id').addEventListener('change', function() {
    let selectedOption = this.options[this.selectedIndex];
    
    if (this.value === '') {
        document.getElementById('payment_summary').style.display = 'none';
        document.getElementById('amount').value = '';
        return;
    }
    
    let due = parseFloat(selectedOption.getAttribute('data-due')) || 0;
    let paid = parseFloat(selectedOption.getAttribute('data-paid')) || 0;
    let expected = parseFloat(selectedOption.getAttribute('data-expected')) || 0;
    
    document.getElementById('payment_summary').style.display = 'block';
    
    document.getElementById('summary_expected').innerText = '₹' + expected.toLocaleString('en-IN');
    document.getElementById('summary_paid').innerText = '₹' + paid.toLocaleString('en-IN');
    
    let dueEl = document.getElementById('summary_due');
    if(due > 0) {
        dueEl.innerText = '₹' + due.toLocaleString('en-IN');
        dueEl.className = 'text-danger';
    } else if (due < 0) {
        dueEl.innerText = '₹' + Math.abs(due).toLocaleString('en-IN') + ' (Advance)';
        dueEl.className = 'text-success';
    } else {
        dueEl.innerText = '₹0';
        dueEl.className = 'text-success';
    }

    let amountInput = document.getElementById('amount');
    
    if (due > 0) {
        amountInput.value = due;
    } else {
        amountInput.value = '';
    }
});

// Auto-trigger on page load if a student is pre-selected via URL
document.addEventListener('DOMContentLoaded', function() {
    let select = document.getElementById('student_id');
    if (select.value !== '') {
        select.dispatchEvent(new Event('change'));
    }
});
</script>