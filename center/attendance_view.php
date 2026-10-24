<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

/* =============================
   VALIDATION
============================= */
if($student_id <= 0){
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
         WHERE id='$student_id' AND center_id='$cid'")
);

if(!$student){
    set_flash("danger","Student not found");
    redirect("students.php");
    exit;
}

/* =============================
   FILTER
============================= */
$month = $_GET['month'] ?? date('Y-m');

/* =============================
   ATTENDANCE DATA
============================= */
$q = mysqli_query($conn,
    "SELECT * FROM attendance 
     WHERE center_id='$cid' 
     AND student_id='$student_id'
     AND DATE_FORMAT(attendance_date, '%Y-%m') = '$month'
     ORDER BY attendance_date DESC"
);

$total_days = mysqli_num_rows($q);

/* PRESENT COUNT */
$present = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT COUNT(*) total FROM attendance
         WHERE center_id='$cid'
         AND student_id='$student_id'
         AND DATE_FORMAT(attendance_date, '%Y-%m') = '$month'
         AND status='present'")
)['total'];

$absent = $total_days - $present;
$percentage = $total_days > 0 ? round(($present/$total_days)*100, 1) : 0;
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold">
        <i class="fas fa-calendar-check text-success me-2"></i>
        Attendance History
    </h4>
    <a href="students.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<!-- STUDENT CARD -->
<div class="card shadow-sm border-0 mb-4" style="border-radius:12px;">
    <div class="card-body d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-1"><?= $student['name'] ?></h5>
            <small class="text-muted">
                Reg: <?= $student['registration_no'] ?> | 
                <?= $student['mobile'] ?>
            </small>
        </div>

        <form method="get" class="d-flex gap-2">
            <input type="hidden" name="student_id" value="<?= $student_id ?>">
            <input type="month" name="month" value="<?= $month ?>" class="form-control">
            <button class="btn btn-primary">
                <i class="fas fa-filter"></i>
            </button>
        </form>

    </div>
</div>

<!-- SUMMARY CARDS -->
<div class="row g-4 mb-4">

<div class="col-md-3">
    <div class="card text-white shadow" style="background:linear-gradient(135deg,#667eea,#764ba2);border-radius:12px;">
        <div class="card-body">
            <small>Total Days</small>
            <h3><?= $total_days ?></h3>
        </div>
    </div>
</div>

<div class="col-md-3">
    <div class="card text-white shadow" style="background:linear-gradient(135deg,#11998e,#38ef7d);border-radius:12px;">
        <div class="card-body">
            <small>Present</small>
            <h3><?= $present ?></h3>
        </div>
    </div>
</div>

<div class="col-md-3">
    <div class="card text-white shadow" style="background:linear-gradient(135deg,#ff416c,#ff4b2b);border-radius:12px;">
        <div class="card-body">
            <small>Absent</small>
            <h3><?= $absent ?></h3>
        </div>
    </div>
</div>

<div class="col-md-3">
    <div class="card shadow border-0" style="border-radius:12px;">
        <div class="card-body text-center">
            <small>Attendance %</small>
            <h3 class="text-primary"><?= $percentage ?>%</h3>

            <!-- PROGRESS BAR -->
            <div class="progress mt-2" style="height:8px;">
                <div class="progress-bar bg-success" 
                     style="width: <?= $percentage ?>%">
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<!-- TABLE -->
<div class="card shadow border-0" style="border-radius:12px;">
<div class="card-header bg-dark text-white">
    <?= date('F Y', strtotime($month.'-01')) ?> Attendance
</div>

<div class="card-body p-0">

<table class="table table-hover mb-0">

<thead class="table-light">
<tr>
    <th>#</th>
    <th>Date</th>
    <th>Day</th>
    <th>Status</th>
</tr>
</thead>

<tbody>

<?php if($total_days > 0){ 
$i=1;
while($a = mysqli_fetch_assoc($q)){ ?>
<tr>

    <td><?= $i++ ?></td>

    <td><?= date('d M Y', strtotime($a['attendance_date'])) ?></td>

    <td><?= date('l', strtotime($a['attendance_date'])) ?></td>

    <td>
        <?php if($a['status']=='present'){ ?>
            <span class="badge bg-success">Present</span>
        <?php } else { ?>
            <span class="badge bg-danger">Absent</span>
        <?php } ?>
    </td>

</tr>
<?php } } else { ?>

<tr>
    <td colspan="4" class="text-center py-5 text-muted">
        <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
        No attendance found
    </td>
</tr>

<?php } ?>

</tbody>

</table>

</div>
</div>

</div>

<?php include "layout/footer.php"; ?>