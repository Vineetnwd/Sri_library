<?php
require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

// Fetch all fee plans for easy lookup
$plans = [];
$q_plans = mysqli_query($conn, "SELECT * FROM fee_plans WHERE center_id='$cid'");
while($p = mysqli_fetch_assoc($q_plans)){
    $plans[$p['id']] = $p;
}

// Fetch all students with their total payments
$q = mysqli_query($conn,
    "SELECT s.*, 
            (SELECT SUM(amount) FROM payments WHERE student_id=s.id AND (status != 'cancelled' OR status IS NULL)) as total_paid
     FROM students s
     WHERE s.center_id='$cid' AND s.status='active'
     ORDER BY s.name ASC"
);

$rows = [];
$total_expected = 0;
$total_paid_sum = 0;
$total_due_sum = 0;
$cleared_count = 0;
$due_count = 0;

while($r = mysqli_fetch_assoc($q)){
    $pid = $r['plan_id'];
    $has_plan = isset($plans[$pid]);
    $paid = (float)$r['total_paid'];
    $expected = 0;
    $cycles = 0;
    $duration = 1;
    $due = 0;

    if ($has_plan) {
        $plan = $plans[$pid];
        $duration = (int)$plan['duration_month'];
        if ($duration < 1) $duration = 1;

        $join_date = new DateTime($r['joining_date']);
        $today = new DateTime('today');

        if ($today < $join_date) {
            $months_passed = 0;
        } else {
            $diff = $join_date->diff($today);
            $months_passed = ($diff->y * 12) + $diff->m;
        }

        $cycles = floor($months_passed / $duration) + 1;
        $expected = $cycles * (float)$plan['amount'];
        $due = $expected - $paid;

        $total_expected += $expected;
        $total_paid_sum += $paid;
        if ($due > 0) {
            $total_due_sum += $due;
            $due_count++;
        } else {
            $cleared_count++;
        }

        $r['_plan_name'] = $plan['plan_name'];
        $r['_plan_rate'] = $plan['amount'];
    } else {
        $r['_plan_name'] = '';
        $r['_plan_rate'] = 0;
    }

    $r['_has_plan'] = $has_plan;
    $r['_expected'] = $expected;
    $r['_paid'] = $paid;
    $r['_due'] = $due;
    $r['_cycles'] = $cycles;
    $r['_duration'] = $duration;
    $rows[] = $r;
}
?>

<?php include "layout/sidebar.php"; ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">
        <i class="fa fa-wallet me-2 text-success"></i> Fee Collection
    </h4>
</div>

<!-- STATS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                    <i class="fa fa-rupee-sign" style="font-size:14px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Expected</div>
                    <div class="fw-bold fs-6 lh-1">₹ <?= number_format($total_expected, 0) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                    <i class="fa fa-check-circle" style="font-size:14px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Collected</div>
                    <div class="fw-bold fs-6 lh-1 text-success">₹ <?= number_format($total_paid_sum, 0) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                    <i class="fa fa-exclamation-circle" style="font-size:14px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Pending Dues</div>
                    <div class="fw-bold fs-6 lh-1 text-danger">₹ <?= number_format($total_due_sum, 0) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                    <i class="fa fa-users" style="font-size:14px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Defaulters</div>
                    <div class="fw-bold fs-6 lh-1"><?= $due_count ?> / <?= count($rows) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TABLE -->
<div class="card shadow border-0" style="border-radius:12px;">
    <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold"><i class="fa fa-table me-2 text-success"></i>Student Fee Status</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table id="feeTable" class="table table-hover table-sm align-middle mb-0 w-100">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 text-uppercase text-muted" style="font-size:11px;">Student</th>
                    <th class="text-uppercase text-muted" style="font-size:11px;">Joining Date</th>
                    <th class="text-uppercase text-muted" style="font-size:11px;">Plan</th>
                    <th class="text-uppercase text-muted text-center" style="font-size:11px;">Cycles</th>
                    <th class="text-uppercase text-muted text-end" style="font-size:11px;">Expected</th>
                    <th class="text-uppercase text-muted text-end" style="font-size:11px;">Paid</th>
                    <th class="text-uppercase text-muted text-end" style="font-size:11px;">Due</th>
                    <th class="text-uppercase text-muted text-end pe-3" style="font-size:11px;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($rows as $r){ ?>
            <tr>
                <td class="ps-3">
                    <strong class="text-dark"><?= htmlspecialchars($r['name']) ?></strong><br>
                    <small class="text-muted"><?= $r['registration_no'] ?></small>
                </td>
                <td class="text-muted" style="font-size:12px;">
                    <?= date('d M Y', strtotime($r['joining_date'])) ?>
                </td>
                <td>
                    <?php if($r['_has_plan']){ ?>
                        <span class="badge rounded-pill bg-light text-dark border" style="font-size:10px;">
                            <?= htmlspecialchars($r['_plan_name']) ?> &middot; ₹<?= number_format($r['_plan_rate'], 0) ?>
                        </span>
                    <?php } else { ?>
                        <span class="text-muted fst-italic" style="font-size:12px;">No Plan</span>
                    <?php } ?>
                </td>
                <td class="text-center" style="font-size:12px;">
                    <?php if($r['_has_plan']){ ?>
                        <span class="text-muted"><?= $r['_cycles'] ?></span>
                    <?php } else { ?>
                        <span class="text-muted">—</span>
                    <?php } ?>
                </td>
                <td class="text-end" style="font-size:12px;">
                    <?= $r['_has_plan'] ? '₹ '.number_format($r['_expected'], 0) : '—' ?>
                </td>
                <td class="text-end text-success fw-semibold" style="font-size:12px;">
                    ₹ <?= number_format($r['_paid'], 0) ?>
                </td>
                <td class="text-end fw-bold" style="font-size:13px;">
                    <?php if(!$r['_has_plan']){ ?>
                        <span class="text-muted">—</span>
                    <?php } elseif($r['_due'] > 0){ ?>
                        <span class="text-danger">₹ <?= number_format($r['_due'], 0) ?></span>
                    <?php } else { ?>
                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success" style="font-size:10px;">Cleared</span>
                    <?php } ?>
                </td>
                <td class="text-end pe-3">
                    <?php if($r['_has_plan']){ ?>
                        <a href="payment_add.php?student_id=<?= $r['id'] ?>"
                           class="btn btn-sm btn-light border fw-semibold <?= $r['_due'] > 0 ? 'text-danger' : 'text-success' ?>"
                           style="font-size:12px;">
                            <i class="fas fa-rupee-sign me-1"></i>Pay
                        </a>
                    <?php } else { ?>
                        <a href="student_edit.php?id=<?= $r['id'] ?>"
                           class="btn btn-sm btn-light border text-warning fw-semibold"
                           style="font-size:12px;">
                            <i class="fas fa-link me-1"></i>Assign Plan
                        </a>
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

</div>

<?php include "layout/footer.php"; ?>

<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#feeTable').DataTable({
        dom: "<'row p-3 pb-0'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3 pt-0'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[6, 'desc']], // Sort by Due (highest first)
        language: {
            search: "",
            searchPlaceholder: "Search students..."
        }
    });
});
</script>
