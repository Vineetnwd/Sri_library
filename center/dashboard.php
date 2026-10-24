<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* ===========================
   STATS
=========================== */
$total_students = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM students WHERE center_id='$cid'"))['c'];

$active_students = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM students WHERE center_id='$cid' AND status='active'"))['c'];

$total_seats = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) c FROM seats WHERE center_id='$cid'"))['c'];

$occupied_seats = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT seat_id) c FROM students WHERE center_id='$cid' AND status='active'"))['c'];

$today_collection = (float)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT IFNULL(SUM(amount),0) c FROM payments WHERE center_id='$cid' AND payment_date=CURDATE() AND (status != 'cancelled' OR status IS NULL)"))['c'];

$monthly_collection = (float)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT IFNULL(SUM(amount),0) c FROM payments WHERE center_id='$cid' AND DATE_FORMAT(payment_date,'%Y-%m')='".date('Y-m')."' AND (status != 'cancelled' OR status IS NULL)"))['c'];

$total_collection_all = (float)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT IFNULL(SUM(amount),0) c FROM payments WHERE center_id='$cid' AND (status != 'cancelled' OR status IS NULL)"))['c'];

/* Calculate total pending dues dynamically from fee_plans */
$plans_all = [];
$q_p = mysqli_query($conn, "SELECT * FROM fee_plans WHERE center_id='$cid'");
while($p = mysqli_fetch_assoc($q_p)) $plans_all[$p['id']] = $p;

$q_s = mysqli_query($conn,
    "SELECT s.*, (SELECT IFNULL(SUM(amount),0) FROM payments WHERE student_id=s.id AND (status != 'cancelled' OR status IS NULL)) as paid
     FROM students s WHERE s.center_id='$cid' AND s.status='active'");

$total_pending_dues = 0;
$defaulter_count = 0;

while($s = mysqli_fetch_assoc($q_s)){
    $pid = $s['plan_id'];
    if($pid && isset($plans_all[$pid])){
        $plan = $plans_all[$pid];
        $duration = max(1, (int)$plan['duration_month']);
        $join = new DateTime($s['joining_date']);
        $today = new DateTime('today');
        if($today < $join){ $months = 0; } else {
            $diff = $join->diff($today);
            $months = ($diff->y * 12) + $diff->m;
        }
        $cycles = floor($months / $duration) + 1;
        $expected = $cycles * (float)$plan['amount'];
        $due = $expected - (float)$s['paid'];
        if($due > 0){
            $total_pending_dues += $due;
            $defaulter_count++;
        }
    }
}

/* ===========================
   CHART DATA
=========================== */
$months_arr = [];
$revenue_arr = [];
$q_chart = mysqli_query($conn,
    "SELECT DATE_FORMAT(payment_date,'%b') AS mo, MONTH(payment_date) as mo_num, SUM(amount) total
     FROM payments
     WHERE center_id='$cid'
     AND YEAR(payment_date)=YEAR(CURDATE())
     AND (status != 'cancelled' OR status IS NULL)
     GROUP BY MONTH(payment_date), DATE_FORMAT(payment_date,'%b')
     ORDER BY MONTH(payment_date)");
while($r = mysqli_fetch_assoc($q_chart)){
    $months_arr[] = $r['mo'];
    $revenue_arr[] = (float)$r['total'];
}

/* ===========================
   RECENT STUDENTS
=========================== */
$recent_students = mysqli_query($conn,
    "SELECT * FROM students WHERE center_id='$cid' ORDER BY id DESC LIMIT 6");

/* ===========================
   RECENT PAYMENTS
=========================== */
$recent_payments = mysqli_query($conn,
    "SELECT p.*, s.name, s.registration_no 
     FROM payments p
     LEFT JOIN students s ON p.student_id = s.id
     WHERE p.center_id='$cid'
     AND (p.status != 'cancelled' OR p.status IS NULL)
     ORDER BY p.id DESC LIMIT 6");

$seat_rate = $total_seats > 0 ? round(($occupied_seats / $total_seats) * 100) : 0;
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Dashboard</h4>
        <small class="text-muted"><?= date('l, d F Y') ?></small>
    </div>
    <div class="d-flex gap-2">
        <a href="admission_qr.php" class="btn btn-outline-warning text-dark btn-sm fw-semibold">
            <i class="fas fa-qrcode me-1 text-warning"></i> Admission QR
        </a>
        <a href="student_add.php" class="btn btn-primary btn-sm fw-semibold">
            <i class="fas fa-user-plus me-1"></i> Add Student
        </a>
        <a href="payment_add.php" class="btn btn-success btn-sm fw-semibold">
            <i class="fas fa-rupee-sign me-1"></i> Add Payment
        </a>
    </div>
</div>

<?php
$dash_pending_cnt = 0;
$pq_dash = mysqli_query($conn, "SELECT COUNT(*) c FROM students WHERE center_id='$cid' AND status='pending'");
if ($pq_dash) {
    $dash_pending_cnt = (int)mysqli_fetch_assoc($pq_dash)['c'];
}
?>

<?php if ($dash_pending_cnt > 0): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between p-3 mb-4 rounded-3 gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-hourglass-half fa-lg text-warning"></i>
            <span class="text-dark">
                You have <strong><?= $dash_pending_cnt ?> pending online admission application(s)</strong> waiting for verification. Seats will not be occupied until approved.
            </span>
        </div>
        <a href="pending_admissions.php" class="btn btn-dark btn-sm fw-bold">
            <i class="fas fa-check-circle me-1 text-warning"></i> Review & Verify Now
        </a>
    </div>
<?php endif; ?>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width:46px;height:46px;flex-shrink:0;">
                    <i class="fas fa-users" style="font-size:17px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Students</div>
                    <div class="fw-bold" style="font-size:22px;line-height:1.1;"><?= $active_students ?></div>
                    <div style="font-size:11px;color:#9ca3af;"><?= $total_students ?> total</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width:46px;height:46px;flex-shrink:0;">
                    <i class="fas fa-rupee-sign" style="font-size:17px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Today's Collection</div>
                    <div class="fw-bold text-success" style="font-size:22px;line-height:1.1;">₹ <?= number_format($today_collection, 0) ?></div>
                    <div style="font-size:11px;color:#9ca3af;"><?= date('d M Y') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width:46px;height:46px;flex-shrink:0;">
                    <i class="fas fa-chart-line" style="font-size:17px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">This Month</div>
                    <div class="fw-bold" style="font-size:22px;line-height:1.1;">₹ <?= number_format($monthly_collection, 0) ?></div>
                    <div style="font-size:11px;color:#9ca3af;"><?= date('F Y') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width:46px;height:46px;flex-shrink:0;">
                    <i class="fas fa-exclamation-circle" style="font-size:17px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Pending Dues</div>
                    <div class="fw-bold text-danger" style="font-size:22px;line-height:1.1;">₹ <?= number_format($total_pending_dues, 0) ?></div>
                    <div style="font-size:11px;color:#9ca3af;"><?= $defaulter_count ?> defaulters</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECOND ROW: Seat Occupancy + Quick Links -->
<div class="row g-3 mb-4">
    <!-- Seat Occupancy Bar -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-semibold text-dark">Seat Occupancy</span>
                    <a href="seat_utilization.php" style="font-size:12px;" class="text-muted text-decoration-none">View Details →</a>
                </div>
                <div class="d-flex gap-4 mb-3">
                    <div>
                        <div class="fw-bold" style="font-size:26px;line-height:1;"><?= $occupied_seats ?></div>
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase;">Occupied</div>
                    </div>
                    <div>
                        <div class="fw-bold text-success" style="font-size:26px;line-height:1;"><?= $total_seats - $occupied_seats ?></div>
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase;">Free</div>
                    </div>
                    <div>
                        <div class="fw-bold text-primary" style="font-size:26px;line-height:1;"><?= $total_seats ?></div>
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase;">Total</div>
                    </div>
                </div>
                <div class="progress mb-2" style="height:10px;border-radius:99px;background:#f0f0f0;">
                    <div class="progress-bar <?= $seat_rate > 80 ? 'bg-danger' : ($seat_rate > 50 ? 'bg-warning' : 'bg-success') ?>"
                         style="width:<?= $seat_rate ?>%;border-radius:99px;"></div>
                </div>
                <div class="d-flex justify-content-between">
                    <small class="text-muted">0%</small>
                    <small class="fw-semibold <?= $seat_rate > 80 ? 'text-danger' : ($seat_rate > 50 ? 'text-warning' : 'text-success') ?>"><?= $seat_rate ?>% Full</small>
                    <small class="text-muted">100%</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body">
                <div class="fw-semibold text-dark mb-3">Quick Access</div>
                <div class="row g-2">
                    <?php
                    $links = [
                        ['href'=>'students.php','icon'=>'fa-users','label'=>'All Students','color'=>'primary'],
                        ['href'=>'admission_qr.php','icon'=>'fa-qrcode','label'=>'Admission QR','color'=>'warning'],
                        ['href'=>'pending_admissions.php','icon'=>'fa-hourglass-half','label'=>'Pending Admissions','color'=>'danger'],
                        ['href'=>'payment_add.php','icon'=>'fa-money-bill-wave','label'=>'Add Payment','color'=>'success'],
                        ['href'=>'fee_collection.php','icon'=>'fa-wallet','label'=>'Fee Collection','color'=>'info'],
                        ['href'=>'dues_report.php','icon'=>'fa-file-invoice-dollar','label'=>'Dues Report','color'=>'secondary'],
                    ];
                    foreach($links as $l){ ?>
                    <div class="col-4">
                        <a href="<?= $l['href'] ?>" class="d-flex flex-column align-items-center gap-1 text-decoration-none p-2 rounded-3 text-center"
                           style="background:#f9fafb;transition:background .15s;" 
                           onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='#f9fafb'">
                            <div class="rounded-circle bg-<?= $l['color'] ?> bg-opacity-10 text-<?= $l['color'] ?> d-flex align-items-center justify-content-center" style="width:34px;height:34px;">
                                <i class="fas <?= $l['icon'] ?>" style="font-size:13px;"></i>
                            </div>
                            <span style="font-size:10px;font-weight:600;color:#374151;line-height:1.2;"><?= $l['label'] ?></span>
                        </a>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CHART + RECENT PAYMENTS -->
<div class="row g-3 mb-4">
    <!-- Revenue Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-semibold text-dark">Revenue Trend <span class="text-muted fw-normal" style="font-size:12px;">(<?= date('Y') ?>)</span></span>
                    <a href="payment_history.php" style="font-size:12px;" class="text-muted text-decoration-none">View All →</a>
                </div>
                <div style="position:relative;height:260px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body p-0">
                <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark">Recent Payments</span>
                    <a href="payment_history.php" style="font-size:12px;" class="text-muted text-decoration-none">All →</a>
                </div>
                <?php while($pay = mysqli_fetch_assoc($recent_payments)){ ?>
                <div class="px-3 py-2 d-flex justify-content-between align-items-center border-top border-light">
                    <div style="min-width:0;flex:1;">
                        <div class="fw-semibold text-dark" style="font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= htmlspecialchars($pay['name'] ?? 'Unknown') ?>
                        </div>
                        <div class="text-muted" style="font-size:11px;"><?= date('d M', strtotime($pay['payment_date'])) ?></div>
                    </div>
                    <div class="text-success fw-bold text-end" style="font-size:13px;flex-shrink:0;margin-left:8px;">
                        ₹ <?= number_format($pay['amount'], 0) ?>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- RECENT STUDENTS -->
<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="fas fa-users me-2 text-primary"></i>Recent Students</span>
        <a href="students.php" class="btn btn-outline-primary btn-sm" style="font-size:12px;">View All</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 text-uppercase text-muted" style="font-size:11px;">Student</th>
                    <th class="text-uppercase text-muted" style="font-size:11px;">Mobile</th>
                    <th class="text-uppercase text-muted" style="font-size:11px;">Joining Date</th>
                    <th class="text-uppercase text-muted text-end pe-3" style="font-size:11px;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while($s = mysqli_fetch_assoc($recent_students)){ ?>
            <tr>
                <td class="ps-3">
                    <strong class="text-dark" style="font-size:13px;"><?= htmlspecialchars($s['name']) ?></strong><br>
                    <small class="text-muted"><?= $s['registration_no'] ?></small>
                </td>
                <td class="text-muted" style="font-size:13px;"><?= $s['mobile'] ?></td>
                <td class="text-muted" style="font-size:13px;"><?= date('d M Y', strtotime($s['joining_date'])) ?></td>
                <td class="text-end pe-3">
                    <?php if($s['status'] == 'active'){ ?>
                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success" style="font-size:10px;">Active</span>
                    <?php } else { ?>
                        <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary border" style="font-size:10px;">Left</span>
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</div><!-- container-fluid -->

<?php include "layout/footer.php"; ?>

<script>
const ctx = document.getElementById('revenueChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($months_arr) ?>,
        datasets: [{
            label: 'Revenue (₹)',
            data: <?= json_encode($revenue_arr) ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.15)',
            borderColor: '#3b82f6',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(17,24,39,0.95)',
                padding: 12,
                cornerRadius: 8,
                callbacks: {
                    label: ctx => '₹ ' + ctx.parsed.y.toLocaleString('en-IN')
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                ticks: {
                    color: '#9ca3af',
                    font: { size: 11 },
                    callback: v => '₹' + (v >= 1000 ? (v/1000)+'K' : v)
                }
            },
            x: {
                grid: { display: false, drawBorder: false },
                ticks: { color: '#9ca3af', font: { size: 11 } }
            }
        }
    }
});
</script>