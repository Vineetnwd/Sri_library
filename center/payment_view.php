<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* =============================
   VALIDATE FEE ID
============================= */
$fee_id = isset($_GET['fee_id']) ? (int)$_GET['fee_id'] : 0;

if ($fee_id <= 0) {
    set_flash("danger", "Invalid Fee ID");
    // redirect("fees.php");
    exit;
}

/* =============================
   FETCH FEE
============================= */
$fee = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM student_fees
         WHERE student_id='$fee_id'")
);

if (!$fee) {
    set_flash("danger", "Fee record not found");
    // redirect("fees.php");
    exit;
}

/* =============================
   FETCH PAYMENTS
============================= */
$payments = mysqli_query($conn,
    "SELECT * FROM payments
     WHERE fee_id='$fee_id'
     ORDER BY id DESC"
);
?>

<?php include "layout/sidebar.php";
?>

<div class="container-fluid">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <i class="fa fa-receipt me-2 text-primary"></i> Payment History
    </h4>

    <a href="fees.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left me-1"></i> Back
    </a>
</div>

<!-- SUMMARY CARD -->
<div class="card mb-4 shadow-sm border-0" style="border-radius:10px;">
    <div class="card-body d-flex justify-content-between">

        <div>
            <small class="text-muted">Total Fee</small>
            <h5 class="mb-0">₹ <?= number_format($fee['total_fee']) ?></h5>
        </div>

        <div>
            <small class="text-muted">Paid</small>
            <h5 class="mb-0 text-success">₹ <?= number_format($fee['paid_amount']) ?></h5>
        </div>

        <div>
            <small class="text-muted">Due</small>
            <h5 class="mb-0 text-danger">₹ <?= number_format($fee['due_amount']) ?></h5>
        </div>

    </div>
</div>

<!-- TABLE -->
<div class="card shadow border-0" style="border-radius:10px;">
<div class="card-body p-0">

<table class="table table-hover mb-0">

<thead class="table-dark">
<tr>
    <th>Date</th>
    <th>Amount</th>
    <th>Mode</th>
    <th>Status</th>
    <th class="text-end">Action</th>
</tr>
</thead>

<tbody>

<?php if(mysqli_num_rows($payments) > 0){ ?>

    <?php while($p=mysqli_fetch_assoc($payments)){ ?>
    <tr>

        <td><?= date('d M Y', strtotime($p['payment_date'])) ?></td>

        <td><strong>₹ <?= number_format($p['amount']) ?></strong></td>

        <td>
            <span class="badge bg-info text-dark">
                <?= strtoupper($p['payment_mode']) ?>
            </span>
        </td>

        <td>
            <?php if($p['status']=='cancelled'){ ?>
                <span class="badge bg-danger">Cancelled</span>
            <?php } else { ?>
                <span class="badge bg-success">Paid</span>
            <?php } ?>
        </td>

        <td class="text-end">
            <?php if($p['status']=='paid'){ ?>
                <a href="payment_cancel.php?id=<?= $p['id'] ?>"
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Cancel this payment?')">
                   <i class="fa fa-times"></i>
                </a>
            <?php } ?>
        </td>

    </tr>
    <?php } ?>

<?php } else { ?>

<tr>
    <td colspan="5" class="text-center text-muted py-4">
        No payments found
    </td>
</tr>

<?php } ?>

</tbody>
</table>

</div>
</div>

</div>

<?php include "layout/footer.php"; ?>