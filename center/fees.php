<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

/* fetch fee records */
$q = mysqli_query($conn,
    "SELECT sf.*, s.name, s.registration_no
     FROM student_fees sf
     LEFT JOIN students s ON s.id = sf.student_id
     WHERE sf.center_id='$cid'
     ORDER BY sf.id DESC"
);
?>

<?php include "layout/sidebar.php"; ?>

<h4>Student Fees</h4>

<!--<a href="fee_assign.php" class="btn btn-primary mb-3">-->
<!--    Assign Fee-->
<!--</a>-->

<table class="table table-bordered table-striped">

<tr class="table-dark">
    <th>Student</th>
    <th>Total</th>
    <th>Paid</th>
    <th>Due</th>
    <th>Period</th>
    <th>Action</th>
</tr>

<?php if(mysqli_num_rows($q)==0){ ?>
<tr>
    <td colspan="5" class="text-center text-muted">
        No fee records found
    </td>
</tr>
<?php } ?>

<?php while($r=mysqli_fetch_assoc($q)){ ?>
<tr>
    <td><?= $r['name'] ?> (<?= $r['registration_no'] ?>)</td>
    <td>₹ <?= $r['total_fee'] ?></td>
    <td>₹ <?= $r['paid_amount'] ?></td>
    <td class="text-danger">₹ <?= $r['due_amount'] ?></td>
    <td><?= $r['start_date'] ?> → <?= $r['end_date'] ?></td>
    <td>
        <a href="payment_view.php?fee_id=<?= $r['id'] ?>"
           class="btn btn-sm btn-primary">
           View Payments
        </a>
    </td>
</tr>
<?php } ?>

</table>

<?php include "layout/footer.php"; ?>
