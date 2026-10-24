<?php
require_once "../system/db.php";
require_once "../system/functions.php";

$student_id = $_SESSION['user_id'];

$student = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM students WHERE id='$student_id'")
);

$fee = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM student_fees
         WHERE student_id='$student_id'
         AND status='running'")
);
?>

<h3>Welcome <?= $student['name'] ?></h3>

<div class="row">

<div class="col-md-4">
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h6>Total Fee</h6>
            <h3>₹<?= $fee['total_fee'] ?></h3>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h6>Paid</h6>
            <h3 class="text-success">₹<?= $fee['paid_amount'] ?></h3>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <h6>Due</h6>
            <h3 class="text-danger">₹<?= $fee['due_amount'] ?></h3>
        </div>
    </div>
</div>

</div>
