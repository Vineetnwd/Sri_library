<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* =============================
   FETCH DATA
============================= */
$q = mysqli_query($conn,
    "SELECT s.id,s.name,s.mobile,
            f.total_fee,f.paid_amount,f.due_amount,
            f.start_date,f.end_date
     FROM student_fees f
     JOIN students s ON s.id=f.student_id
     WHERE f.center_id='$cid'
     AND f.status='running'
     ORDER BY f.end_date ASC"
);

if(!$q){
    die(mysqli_error($conn));
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold">
        <i class="fa fa-rupee-sign text-success me-2"></i>
        Student Dues & Subscription
    </h4>
</div>

<!-- TABLE -->
<div class="card shadow border-0" style="border-radius:12px;">
<div class="card-body p-0">

<table class="table table-hover mb-0">

<thead class="table-dark">
<tr>
    <th>Student</th>
    <th>Total</th>
    <th>Paid</th>
    <th>Due</th>
    <th>Remaining Days</th>
    <th>Status</th>
</tr>
</thead>

<tbody>

<?php if(mysqli_num_rows($q)>0){ ?>

<?php while($r=mysqli_fetch_assoc($q)){ 

    $today = date('Y-m-d');
    $end   = $r['end_date'];

    $days = (strtotime($end) - strtotime($today)) / (60*60*24);
    $days = (int)$days;

    /* STATUS */
    if($days < 0){
        $status = "<span class='badge bg-danger'>Expired</span>";
    } elseif($days <= 3){
        $status = "<span class='badge bg-warning text-dark'>Expiring</span>";
    } else {
        $status = "<span class='badge bg-success'>Active</span>";
    }
?>

<tr>

    <td>
        <strong><?= $r['name'] ?></strong><br>
        <small class="text-muted"><?= $r['mobile'] ?></small>
    </td>

    <td>₹ <?= number_format($r['total_fee']) ?></td>

    <td class="text-success">
        ₹ <?= number_format($r['paid_amount']) ?>
    </td>

    <td class="text-danger fw-bold">
        ₹ <?= number_format($r['due_amount']) ?>
    </td>

    <td>
        <?= $days < 0 
            ? "<span class='text-danger'>Expired ".abs($days)." days ago</span>"
            : $days." days left" ?>
    </td>

    <td><?= $status ?></td>

</tr>

<?php } ?>

<?php } else { ?>

<tr>
    <td colspan="6" class="text-center py-5 text-muted">
        No data found
    </td>
</tr>

<?php } ?>

</tbody>

</table>

</div>
</div>

</div>

<?php include "layout/footer.php"; ?>