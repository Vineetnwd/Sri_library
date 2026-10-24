<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* =============================
   FETCH EXPIRING + DUE (SIMPLIFIED)
============================= */
$q = mysqli_query($conn,
"SELECT 
    s.id,
    s.name,
    s.mobile,
    f.end_date,
    f.total_fee,
    f.paid_amount,
    f.due_amount

FROM student_fees f

JOIN students s ON s.id = f.student_id

WHERE f.center_id='$cid'
AND f.status='running'

AND (
    f.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    OR f.due_amount > 0
)

ORDER BY f.end_date ASC
");

if(!$q){
    die("SQL Error: ".mysqli_error($conn));
}

$total = mysqli_num_rows($q);
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-warning">
        <i class="fa fa-exclamation-triangle me-2"></i> Expiring / Due Students
    </h4>

    <span class="badge bg-warning text-dark px-3 py-2">
        <?= $total ?> Students
    </span>
</div>

<!-- INFO -->
<div class="card mb-4 border-0 shadow-sm" style="border-left:5px solid #ffc107;">
    <div class="card-body">
        <i class="fa fa-info-circle text-warning me-2"></i>
        Showing students whose plans are expiring within 3 days OR have pending dues.
    </div>
</div>

<!-- TABLE -->
<div class="card shadow border-0" style="border-radius:12px;">
<div class="card-body p-0">

<table class="table table-hover mb-0">

<thead class="table-dark">
<tr>
    <th>Student</th>
    <th>Mobile</th>
    <th>Expiry Date</th>
    <th>Status</th>
    <th>Due</th>
</tr>
</thead>

<tbody>

<?php if($total > 0){ ?>

<?php while($r=mysqli_fetch_assoc($q)){ 

    $today = date('Y-m-d');
    $diff = floor((strtotime($r['end_date']) - strtotime($today)) / (60*60*24));

    $due = (float)$r['due_amount']; // ✅ DIRECT

    /* STATUS */
    $status = "";

    if($diff == 0){
        $status .= "<span class='badge bg-danger me-1'>Today</span>";
    } elseif($diff == 1){
        $status .= "<span class='badge bg-warning text-dark me-1'>Tomorrow</span>";
    } elseif($diff > 1){
        $status .= "<span class='badge bg-info text-dark me-1'>{$diff} Days Left</span>";
    }

    if($due > 0){
        $status .= "<span class='badge bg-danger'>Due ₹{$due}</span>";
    }
?>

<tr>

<td>
    <strong><?= htmlspecialchars($r['name']) ?></strong>
</td>

<td><?= htmlspecialchars($r['mobile']) ?></td>

<td class="text-warning fw-semibold">
    <?= date('d M Y', strtotime($r['end_date'])) ?>
</td>

<td><?= $status ?></td>

<td class="text-danger fw-bold">
    <?= $due > 0 ? "₹ ".$due : "-" ?>
</td>

</tr>

<?php } ?>

<?php } else { ?>

<tr>
    <td colspan="5" class="text-center py-4 text-muted">
        🎉 No students expiring or with dues
    </td>
</tr>

<?php } ?>

</tbody>
</table>

</div>
</div>

</div>

<?php include "layout/footer.php"; ?>