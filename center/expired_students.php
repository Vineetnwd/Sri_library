<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* =============================
   FETCH EXPIRED STUDENTS
============================= */
$q = mysqli_query($conn,
    "SELECT s.id,s.name,s.mobile,f.end_date
     FROM student_fees f
     JOIN students s ON s.id=f.student_id
     WHERE f.center_id='$cid'
     AND f.status='running'
     AND f.end_date < CURDATE()
     ORDER BY f.end_date DESC"
);

if(!$q){
    die("SQL Error: ".mysqli_error($conn));
}

$total = mysqli_num_rows($q);
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-danger">
        <i class="fa fa-times-circle me-2"></i> Expired Students
    </h4>

    <span class="badge bg-danger px-3 py-2">
        <?= $total ?> Students
    </span>
</div>

<!-- ALERT -->
<div class="card mb-4 shadow-sm border-0" style="border-left:5px solid #dc3545;">
    <div class="card-body">
        <i class="fa fa-exclamation-circle text-danger me-2"></i>
        These students have expired subscriptions. Please renew immediately.
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
    <th>Expired On</th>
    <th>Status</th>
    <th class="text-end">Action</th>
</tr>
</thead>

<tbody>

<?php if($total > 0){ ?>

    <?php while($r=mysqli_fetch_assoc($q)){ 

        $days = floor((time() - strtotime($r['end_date'])) / (60*60*24));
    ?>

    <tr>

        <td>
            <strong><?= $r['name'] ?></strong>
        </td>

        <td><?= $r['mobile'] ?></td>

        <td class="text-danger fw-semibold">
            <?= date('d M Y', strtotime($r['end_date'])) ?>
        </td>

        <td>
            <span class="badge bg-danger">
                <?= $days ?> days overdue
            </span>
        </td>

        <td class="text-end">
            <a href="fee_renew.php?student_id=<?= $r['id'] ?>"
               class="btn btn-sm btn-success">
               <i class="fa fa-sync me-1"></i> Renew
            </a>
        </td>

    </tr>

    <?php } ?>

<?php } else { ?>

<tr>
    <td colspan="5" class="text-center py-5 text-muted">
        🎉 No expired students
    </td>
</tr>

<?php } ?>

</tbody>

</table>

</div>
</div>

</div>

<?php include "layout/footer.php"; ?>