<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

/* FILTERS */
$month = $_GET['month'] ?? date('Y-m');
$slot_id = $_GET['slot_id'] ?? '';

$start_date = date('Y-m-01', strtotime($month));
$end_date   = date('Y-m-t', strtotime($month));
$total_days = date('t', strtotime($month));

/* SLOT LIST */
$slots = mysqli_query($conn,"SELECT id, slot_name FROM slots WHERE center_id='$cid'");

/* STUDENT QUERY */
$where_student = "WHERE center_id='$cid'";
if(!empty($slot_id)){
    $where_student .= " AND slot_id='$slot_id'";
}

$students_q = mysqli_query($conn,"
    SELECT id, name, registration_no 
    FROM students 
    $where_student
");

/* ATTENDANCE DATA */
$attendance = [];
$res = mysqli_query($conn,"
    SELECT * FROM attendance 
    WHERE center_id='$cid'
    AND attendance_date BETWEEN '$start_date' AND '$end_date'
");

while($row = mysqli_fetch_assoc($res)){
    $day = date('j', strtotime($row['attendance_date']));
    $attendance[$row['student_id']][$day] = $row['status'];
}

/* SLOT NAME */
$slot_name = "All Slots";
if($slot_id){
    $s = mysqli_fetch_assoc(mysqli_query($conn,"SELECT slot_name FROM slots WHERE id='$slot_id'"));
    $slot_name = $s['slot_name'] ?? 'Unknown';
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold">
        📅 Monthly Attendance
    </h4>
    <button onclick="window.print()" class="btn btn-success">
        <i class="fas fa-print me-1"></i> Print
    </button>
</div>

<!-- FILTER CARD -->
<div class="card shadow-sm mb-3">
    <div class="card-body">

        <form method="get" class="row g-2 align-items-end">

            <div class="col-md-3">
                <label class="form-label small fw-bold">Month</label>
                <input type="month" name="month" value="<?= $month ?>" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">Slot</label>
                <select name="slot_id" class="form-select">
                    <option value="">All Slots</option>
                    <?php while($sl = mysqli_fetch_assoc($slots)){ ?>
                        <option value="<?= $sl['id'] ?>" <?= $slot_id==$sl['id']?'selected':'' ?>>
                            <?= $sl['slot_name'] ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="col-md-3">
                <button class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Load Report
                </button>
            </div>

            <div class="col-md-3">
                <a href="attendance_monthly.php" class="btn btn-secondary w-100">
                    Reset
                </a>
            </div>

        </form>

    </div>
</div>

<!-- SUMMARY -->
<div class="alert alert-info d-flex justify-content-between">
    <div>
        <b>Month:</b> <?= date('F Y', strtotime($month)) ?>
    </div>
    <div>
        <b>Slot:</b> <?= $slot_name ?>
    </div>
</div>

<!-- TABLE -->
<div class="card shadow">
<div class="card-body p-0">

<div class="table-responsive">

<table class="table table-bordered table-sm text-center align-middle">

<thead class="table-dark sticky-top">
<tr>
    <th>Reg No</th>
    <th>Name</th>

    <?php for($d=1; $d <= $total_days; $d++){ ?>
        <th><?= $d ?></th>
    <?php } ?>

    <th class="bg-success">P</th>
    <th class="bg-danger">A</th>
    <th class="bg-primary">%</th>
</tr>
</thead>

<tbody>

<?php 
if(mysqli_num_rows($students_q) == 0){
    echo "<tr><td colspan='".($total_days+5)."' class='text-center py-4'>No students found</td></tr>";
}

while($s = mysqli_fetch_assoc($students_q)){ 

    $present = 0;
    $absent  = 0;
?>

<tr>

<td><span class="badge bg-dark"><?= $s['registration_no'] ?></span></td>
<td class="text-start"><?= $s['name'] ?></td>

<?php 
for($d=1; $d <= $total_days; $d++){

    $status = $attendance[$s['id']][$d] ?? '';

    if($status == 'present') $present++;
    if($status == 'absent') $absent++;

    $class = '';
    $text  = '-';

    if($status == 'present'){
        $class = 'bg-success text-white';
        $text = 'P';
    }
    elseif($status == 'absent'){
        $class = 'bg-danger text-white';
        $text = 'A';
    }

    echo "<td class='$class'>$text</td>";
}
?>

<td class="fw-bold text-success"><?= $present ?></td>
<td class="fw-bold text-danger"><?= $absent ?></td>

<?php 
$total = $present + $absent;
$percent = $total ? round(($present/$total)*100) : 0;
?>

<td class="fw-bold text-primary"><?= $percent ?>%</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>
</div>
</div>

</div>

<style>

/* Sticky header */
.sticky-top{
    position: sticky;
    top: 0;
    z-index: 2;
}

/* Better spacing */
.table td, .table th{
    padding:5px;
    font-size:12px;
}

/* Hover effect */
tbody tr:hover{
    background:#f5f5f5;
}

/* PRINT */
@media print{
    .btn, form, .sidebar, .navbar{
        display:none !important;
    }

    .table{
        font-size:10px;
    }

    @page{
        size: A4 landscape;
        margin:5mm;
    }
}

</style>

<?php include "layout/footer.php"; ?>