<?php
require_once "layout/header.php";

$fee = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM student_fees 
         WHERE student_id='".$student['id']."' 
         AND status='running'")
);

?>

<?php include "layout/sidebar.php"; ?>

<h4>Welcome, <?= $student['name'] ?></h4>

<div class="row g-4">

<div class="col-md-4">
    <div class="card p-3">
        <h6>Total Fee</h6>
        <h3>₹ <?= $fee['total_fee'] ?? 0 ?></h3>
    </div>
</div>

<div class="col-md-4">
    <div class="card p-3">
        <h6>Paid</h6>
        <h3>₹ <?= $fee['paid_amount'] ?? 0 ?></h3>
    </div>
</div>

<div class="col-md-4">
    <div class="card p-3">
        <h6>Due</h6>
        <h3 class="text-danger">₹ <?= $fee['due_amount'] ?? 0 ?></h3>
    </div>
</div>

</div>

<?php include "layout/footer.php"; ?>
