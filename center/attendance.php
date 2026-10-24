<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();
$date = $_GET['date'] ?? date('Y-m-d');

/* Get current slot */
$current_slot = get_current_slot($cid);
$slot_filter = $_GET['slot'] ?? ($current_slot['id'] ?? '');

/* Get all slots */
$slots = mysqli_query($conn,
    "SELECT * FROM slots WHERE center_id='$cid' AND status='active'"
);

/* Get students */
$where = "WHERE s.center_id='$cid' AND s.status='active'";
if($slot_filter){
    $where .= " AND s.slot_id='$slot_filter'";
}

$students = mysqli_query($conn,
    "SELECT s.*, sl.slot_name 
     FROM students s
     LEFT JOIN slots sl ON sl.id = s.slot_id
     $where
     ORDER BY s.name"
);

/* Check if attendance already marked */
$att_check = mysqli_query($conn,
    "SELECT DISTINCT student_id FROM attendance
     WHERE center_id='$cid' AND attendance_date='$date'"
);

$marked_students = [];
while($a = mysqli_fetch_assoc($att_check)){
    $marked_students[] = $a['student_id'];
}

$already_marked = count($marked_students) > 0;
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-calendar-check me-2"></i>Daily Attendance
    </h1>
</div>

<!-- Date & Slot Filter -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">
                    <i class="far fa-calendar me-1"></i>Select Date
                </label>
                <input type="date" name="date" class="form-control" 
                       value="<?= $date ?>" max="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-bold">
                    <i class="fas fa-clock me-1"></i>Filter by Slot
                </label>
                <select name="slot" class="form-select">
                    <option value="">All Slots</option>
                    <?php 
                    mysqli_data_seek($slots, 0);
                    while($sl = mysqli_fetch_assoc($slots)){ 
                    ?>
                        <option value="<?= $sl['id'] ?>" 
                                <?= $slot_filter==$sl['id']?'selected':'' ?>>
                            <?= $sl['slot_name'] ?> 
                            (<?= date('h:i A', strtotime($sl['start_time'])) ?> - 
                             <?= date('h:i A', strtotime($sl['end_time'])) ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <button class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i>Apply Filter
                </button>
                <a href="attendance.php" class="btn btn-secondary">
                    <i class="fas fa-redo me-1"></i>Reset
                </a>
            </div>
        </form>
    </div>
</div>

<?php if($already_marked){ ?>
<div class="alert alert-warning">
    <i class="fas fa-info-circle me-2"></i>
    Attendance for <strong><?= date('d M Y', strtotime($date)) ?></strong> 
    has already been marked. You can view or modify it below.
</div>
<?php } ?>

<!-- Attendance Form -->
<form action="attendance_save.php" method="post">
    <input type="hidden" name="date" value="<?= $date ?>">
    
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span>
                <i class="fas fa-users me-2"></i>
                Mark Attendance - <?= date('d M Y', strtotime($date)) ?>
            </span>
            <span class="badge bg-light text-dark">
                Total: <?= mysqli_num_rows($students) ?> Students
            </span>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="50">#</th>
                            <th>Reg No</th>
                            <th>Student Name</th>
                            <th>Slot</th>
                            <th width="150" class="text-center">
                                <i class="fas fa-check-circle text-success me-2"></i>Present
                            </th>
                            <th width="150" class="text-center">
                                <i class="fas fa-times-circle text-danger me-2"></i>Absent
                            </th>
                        </tr>
                    </thead>
                    
                    <tbody>
                    <?php 
                    if(mysqli_num_rows($students) == 0){
                        echo "<tr><td colspan='6' class='text-center py-4 text-muted'>
                              <i class='fas fa-inbox fa-3x mb-3 d-block'></i>
                              No students found</td></tr>";
                    }
                    
                    $i=1;
                    while($s = mysqli_fetch_assoc($students)){ 
                        /* Check existing attendance */
                        $existing = mysqli_fetch_assoc(
                            mysqli_query($conn,
                                "SELECT status FROM attendance
                                 WHERE student_id='{$s['id']}' 
                                 AND attendance_date='$date'")
                        );
                        
                        $is_present = $existing ? $existing['status']=='present' : true;
                    ?>
                        <tr>
                            <td><strong><?= $i++ ?></strong></td>
                            <td>
                                <span class="badge bg-dark"><?= $s['registration_no'] ?></span>
                            </td>
                            <td>
                                <strong><?= $s['name'] ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $s['slot_name'] ?></span>
                            </td>
                            <td class="text-center">
                                <input type="radio" 
                                       name="att[<?= $s['id'] ?>]" 
                                       value="present" 
                                       class="form-check-input" 
                                       style="width:20px; height:20px;"
                                       <?= $is_present?'checked':'' ?>>
                            </td>
                            <td class="text-center">
                                <input type="radio" 
                                       name="att[<?= $s['id'] ?>]" 
                                       value="absent" 
                                       class="form-check-input" 
                                       style="width:20px; height:20px;"
                                       <?= !$is_present?'checked':'' ?>>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if(mysqli_num_rows($students) > 0){ ?>
        <div class="card-footer text-end">
            <button class="btn btn-success btn-lg px-5">
                <i class="fas fa-save me-2"></i>Save Attendance
            </button>
        </div>
        <?php } ?>
    </div>
</form>

</div>

<?php include "layout/footer.php"; ?>