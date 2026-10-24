<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

$q = mysqli_query($conn,
    "SELECT f.*, s.name, s.registration_no, s.advance_balance
     FROM student_fees f
     JOIN students s ON s.id=f.student_id
     WHERE f.center_id='$cid'
     ORDER BY f.id DESC"
);
?>

<?php include "layout/sidebar.php"; ?>

<h4>Student Fees</h4>

<div class="card shadow border-0" style="border-radius:12px;">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0">

<thead class="table-dark">
<tr>
    <th>Date</th>
    <th>Student</th>
    <th>Total</th>
    <th>Paid</th>
    <th>Due</th>
    <th>Advance</th>
    <th>Expiry</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php while($r=mysqli_fetch_assoc($q)){ 

$today = date('Y-m-d');

if($today > $r['end_date']){
    $badge = '<span class="badge bg-danger">Expired</span>';
}
elseif($today >= date('Y-m-d',strtotime('-3 days',strtotime($r['end_date'])))){
    $badge = '<span class="badge bg-warning text-dark">Expiring</span>';
}
else{
    $badge = '<span class="badge bg-success">Active</span>';
}
?>

<tr>
    <td><?= date('d-m-Y',strtotime($r['start_date'])) ?></td>
    <td><strong><?= $r['name'] ?></strong> <small class="text-muted">(<?= $r['registration_no'] ?>)</small></td>
    <td>₹ <?= $r['total_fee'] ?></td>
    <td>₹ <?= $r['paid_amount'] ?></td>
    <td class="text-danger fw-bold">₹ <?= $r['due_amount'] ?></td>
    <td class="text-success fw-bold">₹ <?= $r['advance_balance'] ?></td>
    <td><?= $badge ?></td>
    <td><a href="fee_renew.php?student_id=<?= $r['student_id'] ?>"
   class="btn btn-sm btn-success">
   <i class="fas fa-sync-alt me-1"></i> Renew
</a>
</td>
</tr>

<?php } ?>
</tbody>
</table>
</div>
</div>
</div>

<?php include "layout/footer.php"; ?>
