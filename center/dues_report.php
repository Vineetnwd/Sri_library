<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

// Default filter to current month/year
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$selected_year  = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Fetch plans for easy lookup
$plans = [];
$q_plans = mysqli_query($conn, "SELECT * FROM fee_plans WHERE center_id='$cid'");
while($p = mysqli_fetch_assoc($q_plans)){
    $plans[$p['id']] = $p;
}

// Fetch all active students with total paid
$q = mysqli_query($conn,
    "SELECT s.*, 
            (SELECT SUM(amount) FROM payments WHERE student_id=s.id AND (status != 'cancelled' OR status IS NULL)) as total_paid
     FROM students s
     WHERE s.center_id='$cid' AND s.status='active'
     ORDER BY s.name"
);

$report_data = [];

while($s = mysqli_fetch_assoc($q)){
    $pid = $s['plan_id'];
    $paid = (float)$s['total_paid'];
    
    if($pid && isset($plans[$pid])){
        $plan = $plans[$pid];
        $duration = (int)$plan['duration_month'];
        if ($duration < 1) $duration = 1;
        
        $join_date = new DateTime($s['joining_date']);
        
        // We want to calculate the due AS OF the selected month and year
        // We evaluate up to the last day of the selected month
        $eval_date_str = sprintf("%04d-%02d-%02d", $selected_year, $selected_month, cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year));
        $eval_date = new DateTime($eval_date_str);
        
        if ($eval_date < $join_date) {
            // Joined AFTER the selected month, no due yet
            continue;
        }
        
        $diff = $join_date->diff($eval_date);
        $months_passed = ($diff->y * 12) + $diff->m;
        
        $cycles = floor($months_passed / $duration) + 1;
        $expected = $cycles * (float)$plan['amount'];
        $due = $expected - $paid;
        
        if ($due > 0) {
            // Determine the actual due date (anniversary in the current cycle)
            // If they are on cycle N, their due date is joining_date + ( (N-1) * duration ) months
            $cycle_start_months = ($cycles - 1) * $duration;
            $due_date_obj = clone $join_date;
            $due_date_obj->modify("+{$cycle_start_months} months");
            
            $s['due_date'] = $due_date_obj->format('d-m-Y');
            $s['due_amount'] = $due;
            $s['plan_name'] = $plan['plan_name'];
            $report_data[] = $s;
        }
    }
}
?>

<?php include "layout/sidebar.php"; ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<?php
$total_due = array_sum(array_column($report_data, 'due_amount'));
$total_students = count($report_data);
$month_label = date('F Y', mktime(0,0,0,$selected_month,1,$selected_year));
?>

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">
        <i class="fa fa-file-invoice-dollar me-2 text-danger"></i> Dues Report
    </h4>
    <span class="badge bg-light text-muted border" style="font-size:13px;"><?= $month_label ?></span>
</div>

<!-- FILTER -->
<div class="card shadow-sm border-0 mb-4" style="border-radius:12px;">
    <div class="card-body py-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold text-muted mb-1" style="font-size:11px;text-transform:uppercase;">Month</label>
                <select name="month" class="form-select form-select-sm">
                    <?php 
                    for($m=1; $m<=12; $m++){ 
                        $m_name = date("F", mktime(0, 0, 0, $m, 10));
                    ?>
                        <option value="<?= $m ?>" <?= $selected_month == $m ? 'selected' : '' ?>><?= $m_name ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold text-muted mb-1" style="font-size:11px;text-transform:uppercase;">Year</label>
                <select name="year" class="form-select form-select-sm">
                    <?php 
                    $current_year = date('Y');
                    for($y=$current_year-2; $y<=$current_year+1; $y++){ 
                    ?>
                        <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm fw-semibold">
                    <i class="fa fa-filter me-1"></i> Generate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SUMMARY STATS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-danger bg-opacity-10 p-2 text-danger d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                    <i class="fa fa-users" style="font-size:14px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Defaulters</div>
                    <div class="fw-bold fs-5 lh-1"><?= $total_students ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-2 text-warning d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                    <i class="fa fa-rupee-sign" style="font-size:14px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Total Dues</div>
                    <div class="fw-bold fs-5 lh-1">₹ <?= number_format($total_due, 0) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- REPORT TABLE -->
<div class="card shadow border-0" style="border-radius:12px;">
    <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold"><i class="fa fa-table me-2 text-danger"></i>Dues as of <?= $month_label ?></span>
    </div>
    <div class="card-body p-0">
        <table id="duesTable" class="table table-hover table-sm align-middle mb-0 w-100">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 text-uppercase text-muted" style="font-size:11px;">Student</th>
                    <th class="text-uppercase text-muted" style="font-size:11px;">Mobile</th>
                    <th class="text-uppercase text-muted" style="font-size:11px;">Plan</th>
                    <th class="text-uppercase text-muted" style="font-size:11px;">Due Date</th>
                    <th class="text-uppercase text-muted text-end" style="font-size:11px;">Due Amount</th>
                    <th class="text-uppercase text-muted text-end pe-3" style="font-size:11px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($report_data as $row){ ?>
                <tr>
                    <td class="ps-3">
                        <strong class="text-dark"><?= htmlspecialchars($row['name']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($row['registration_no']) ?></small>
                    </td>
                    <td class="text-muted" style="font-size:13px;"><?= htmlspecialchars($row['mobile']) ?></td>
                    <td>
                        <span class="badge rounded-pill bg-light text-dark border" style="font-size:10px;">
                            <?= htmlspecialchars($row['plan_name']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger px-2" style="font-size:10px;">
                            <?= $row['due_date'] ?>
                        </span>
                    </td>
                    <td class="text-end fw-bold text-danger" style="font-size:14px;">₹ <?= number_format($row['due_amount'], 0) ?></td>
                    <td class="text-end pe-3">
                        <a href="payment_add.php?student_id=<?= $row['id'] ?>" class="btn btn-sm btn-light border text-success fw-semibold" style="font-size:12px;">
                            <i class="fa fa-money-bill-wave me-1"></i>Pay Now
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

<!-- DataTables JS & Export Extensions -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#duesTable').DataTable({
        dom: "<'row p-3 pb-0'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-4 text-center'B><'col-sm-12 col-md-4'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3 pt-0'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        buttons: [
            {
                extend: 'excelHtml5',
                className: 'btn btn-success btn-sm',
                text: '<i class="fas fa-file-excel"></i> Excel',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
            },
            {
                extend: 'pdfHtml5',
                className: 'btn btn-danger btn-sm',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
            },
            {
                extend: 'print',
                className: 'btn btn-primary btn-sm',
                text: '<i class="fas fa-print"></i> Print',
                exportOptions: { columns: [0, 1, 2, 3, 4, 5] }
            }
        ],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[4, 'asc']], // Sort by due date
        language: {
            search: "",
            searchPlaceholder: "Search students..."
        }
    });
});
</script>
