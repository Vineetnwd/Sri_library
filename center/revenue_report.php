<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

/* Date filters */
$from_date = $_GET['from'] ?? date('Y-m-01');
$to_date = $_GET['to'] ?? date('Y-m-d');

/* Revenue Statistics */
$total_revenue = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT COALESCE(SUM(amount), 0) total FROM payments
         WHERE center_id='$cid' 
         AND (status != 'cancelled' OR status IS NULL)
         AND DATE(payment_date) BETWEEN '$from_date' AND '$to_date'")
)['total'];

$pending_dues = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT COALESCE(SUM(due_amount), 0) total FROM student_fees
         WHERE center_id='$cid' 
         AND status='running'")
)['total'];

$total_students = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT COUNT(*) total FROM students
         WHERE center_id='$cid' AND status='active'")
)['total'];

$total_expenses = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT COALESCE(SUM(amount), 0) total FROM expenses
         WHERE center_id='$cid' 
         AND DATE(expense_date) BETWEEN '$from_date' AND '$to_date'")
)['total'];

$net_profit = $total_revenue - $total_expenses;

/* Monthly chart data */
$months = [];
$revenues = [];
$expenses_chart = [];

for($i=11; $i>=0; $i--){
    $month_str = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime($month_str.'-01'));
    $revenues[] = 0;
    $expenses_chart[] = 0;
}

$monthly_data = mysqli_query($conn,
    "SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(amount) as revenue
     FROM payments
     WHERE center_id='$cid' AND (status != 'cancelled' OR status IS NULL)
     AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY month"
);
while($m = mysqli_fetch_assoc($monthly_data)){
    $idx = array_search(date('M Y', strtotime($m['month'].'-01')), $months);
    if($idx !== false) $revenues[$idx] = $m['revenue'];
}

$monthly_exp_data = mysqli_query($conn,
    "SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as total
     FROM expenses
     WHERE center_id='$cid'
     AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY month"
);
while($m = mysqli_fetch_assoc($monthly_exp_data)){
    $idx = array_search(date('M Y', strtotime($m['month'].'-01')), $months);
    if($idx !== false) $expenses_chart[$idx] = $m['total'];
}

/* Payment method breakdown */
$payment_methods = mysqli_query($conn,
    "SELECT payment_mode, SUM(amount) as total
     FROM payments
     WHERE center_id='$cid' AND (status != 'cancelled' OR status IS NULL)
     AND DATE(payment_date) BETWEEN '$from_date' AND '$to_date'
     GROUP BY payment_mode"
);

/* Period Ledger */
$ledger = [];

// Payments
$p_q = mysqli_query($conn,
    "SELECT p.id, p.payment_date as trans_date, p.amount, p.payment_mode as method, 
            p.receipt_no as ref_no, s.name as detail, s.registration_no as sub_detail,
            'income' as trans_type
     FROM payments p
     JOIN students s ON s.id = p.student_id
     WHERE p.center_id='$cid'
     AND (p.status != 'cancelled' OR p.status IS NULL)
     AND DATE(p.payment_date) BETWEEN '$from_date' AND '$to_date'"
);
while($r = mysqli_fetch_assoc($p_q)){
    $ledger[] = $r;
}

// Expenses
$e_q = mysqli_query($conn,
    "SELECT e.id, e.expense_date as trans_date, e.amount, e.source as method,
            CONCAT('EXP-', e.id) as ref_no, c.name as detail, e.remark as sub_detail,
            'expense' as trans_type
     FROM expenses e
     JOIN expense_categories c ON c.id = e.category_id
     WHERE e.center_id='$cid'
     AND DATE(e.expense_date) BETWEEN '$from_date' AND '$to_date'"
);
while($r = mysqli_fetch_assoc($e_q)){
    $ledger[] = $r;
}

usort($ledger, function($a, $b) {
    return strtotime($b['trans_date']) - strtotime($a['trans_date']); // Descending by date
});
?>

<?php include "layout/sidebar.php"; ?>

<!-- DataTables CSS & Buttons -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">
            <i class="fas fa-chart-line me-2 text-primary"></i> Revenue Analytics
        </h4>
        <div class="d-flex gap-2" id="export-buttons-container"></div>
    </div>

    <!-- Date Filter -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius:12px;">
        <div class="card-body py-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-muted mb-1" style="font-size:11px;font-weight:600;text-transform:uppercase;">From Date</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="<?= $from_date ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted mb-1" style="font-size:11px;font-weight:600;text-transform:uppercase;">To Date</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="<?= $to_date ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary btn-sm px-3 fw-semibold"><i class="fas fa-filter me-1"></i> Apply Filter</button>
                    <a href="revenue_report.php" class="btn btn-light border btn-sm px-3 text-muted fw-semibold">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- STATS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                        <i class="fas fa-rupee-sign" style="font-size:14px;"></i>
                    </div>
                    <div class="d-flex flex-wrap align-items-baseline gap-2">
                        <span class="text-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;">Total Revenue:</span>
                        <span class="fw-bold fs-5 lh-1 text-success">₹ <?= number_format($total_revenue, 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                        <i class="fas fa-file-invoice-dollar" style="font-size:14px;"></i>
                    </div>
                    <div class="d-flex flex-wrap align-items-baseline gap-2">
                        <span class="text-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;">Total Expenses:</span>
                        <span class="fw-bold fs-5 lh-1 text-danger">₹ <?= number_format($total_expenses, 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                        <i class="fas fa-piggy-bank" style="font-size:14px;"></i>
                    </div>
                    <div class="d-flex flex-wrap align-items-baseline gap-2">
                        <span class="text-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;">Net Profit:</span>
                        <span class="fw-bold fs-5 lh-1 text-primary">₹ <?= number_format($net_profit, 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                        <i class="fas fa-exclamation-triangle" style="font-size:14px;"></i>
                    </div>
                    <div class="d-flex flex-wrap align-items-baseline gap-2">
                        <span class="text-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;">Pending Dues:</span>
                        <span class="fw-bold fs-5 lh-1">₹ <?= number_format($pending_dues, 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="row g-4 mb-4">
    
    <!-- Revenue Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="fas fa-chart-area me-2 text-primary"></i>Revenue & Expenses Trend (Last 12 Months)</span>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
        
        <!-- Period Ledger -->
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="fas fa-book me-2 text-info"></i>Transaction Ledger</span>
            </div>
            <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 w-100 datatable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 text-uppercase text-muted" style="font-size:11px;">Date</th>
                            <th class="text-uppercase text-muted" style="font-size:11px;">Ref No</th>
                            <th class="text-uppercase text-muted" style="font-size:11px;">Details</th>
                            <th class="text-uppercase text-muted text-center" style="font-size:11px;">Method</th>
                            <th class="text-uppercase text-muted text-end" style="font-size:11px;">Income (₹)</th>
                            <th class="text-uppercase text-muted text-end pe-4" style="font-size:11px;">Expense (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    foreach($ledger as $r){ 
                    ?>
                        <tr>
                            <td class="ps-4 text-muted" style="font-size:12px;" data-order="<?= $r['trans_date'] ?>">
                                <?= date('d M Y', strtotime($r['trans_date'])) ?>
                            </td>
                            <td>
                                <?php if(!empty($r['ref_no'])): ?>
                                    <span class="badge rounded-pill bg-light text-dark border" style="font-size:11px;"><?= htmlspecialchars($r['ref_no']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark" style="font-size:13px;"><?= htmlspecialchars($r['detail'] ?? '') ?></div>
                                <?php if($r['sub_detail']): ?>
                                <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($r['sub_detail'] ?? '') ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php
                                    $m = ucfirst($r['method'] ?? 'cash');
                                    $bg = $m == 'Cash' ? 'secondary' : (strtolower($m) == 'upi' ? 'info' : 'primary');
                                ?>
                                <span class="badge rounded-pill bg-<?= $bg ?> bg-opacity-10 text-<?= $bg ?> border border-<?= $bg ?>" style="font-size:10px;">
                                    <?= $m ?>
                                </span>
                            </td>
                            <td class="text-end text-success fw-bold" style="font-size:13px;">
                                <?= $r['trans_type'] == 'income' ? number_format($r['amount'], 0) : '-' ?>
                            </td>
                            <td class="text-end text-danger fw-bold pe-4" style="font-size:13px;">
                                <?= $r['trans_type'] == 'expense' ? number_format($r['amount'], 0) : '-' ?>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                    <tfoot class="bg-light fw-bold border-top">
                        <tr>
                            <td class="ps-4 text-start text-nowrap border-0 align-middle" style="font-size:13px;">
                                <span class="text-muted text-uppercase me-2" style="font-size:11px;">Net Profit:</span> 
                                <span class="text-primary fs-6">₹<?= number_format($net_profit, 0) ?></span>
                            </td>
                            <td class="border-0"></td>
                            <td class="border-0"></td>
                            <td class="border-0"></td>
                            <td class="text-end text-success border-0 align-middle" style="font-size:14px;">₹<?= number_format($total_revenue, 0) ?></td>
                            <td class="text-end text-danger pe-4 border-0 align-middle" style="font-size:14px;">₹<?= number_format($total_expenses, 0) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Payment Methods Breakdown -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="fas fa-chart-pie me-2 text-success"></i>Payment Methods</span>
            </div>
            <div class="card-body">
                <canvas id="paymentMethodChart"></canvas>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-header bg-white border-bottom py-3">
                <span class="fw-semibold"><i class="fas fa-info-circle me-2 text-secondary"></i>Period Summary</span>
            </div>
            <div class="card-body">
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size:12px;font-weight:600;text-transform:uppercase;">Date Range</span>
                    <span class="fw-semibold text-dark" style="font-size:13px;"><?= date('d M Y', strtotime($from_date)) ?> - <?= date('d M Y', strtotime($to_date)) ?></span>
                </div>
                
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size:12px;font-weight:600;text-transform:uppercase;">Total Collected</span>
                    <span class="text-success fw-bold fs-6">₹<?= number_format($total_revenue, 0) ?></span>
                </div>
                
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size:12px;font-weight:600;text-transform:uppercase;">Total Expenses</span>
                    <span class="text-danger fw-bold fs-6">₹<?= number_format($total_expenses, 0) ?></span>
                </div>
                
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size:12px;font-weight:600;text-transform:uppercase;">Net Profit</span>
                    <span class="text-primary fw-bold fs-6">₹<?= number_format($net_profit, 0) ?></span>
                </div>
                
                <hr class="text-muted">
                
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted" style="font-size:12px;font-weight:600;text-transform:uppercase;">Collection Rate</span>
                        <?php 
                        $total_expected = $total_revenue + $pending_dues;
                        $collection_rate = $total_expected > 0 ? ($total_revenue/$total_expected)*100 : 0;
                        ?>
                        <span class="fw-bold" style="font-size:12px;"><?= number_format($collection_rate, 1) ?>%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: <?= $collection_rate ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    var table = $('.datatable').DataTable({
        dom: "<'row p-3 pb-0'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3 pt-0'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, 'desc']],
        language: { search: "", searchPlaceholder: "Search payments..." },
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm fw-semibold shadow-sm',
                title: 'Revenue_Report_<?= date("Ymd") ?>',
                footer: true
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> Export PDF',
                className: 'btn btn-danger btn-sm fw-semibold shadow-sm',
                title: 'Revenue_Report',
                footer: true,
                customize: function (doc) {
                    doc.styles.tableHeader.alignment = 'left';
                    doc.content[1].table.widths = ['15%', '15%', '30%', '15%', '12%', '13%'];
                }
            }
        ]
    });
    table.buttons().container().appendTo('#export-buttons-container');
});

// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Revenue (₹)',
            data: <?= json_encode($revenues) ?>,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.4,
            fill: true
        },
        {
            label: 'Expenses (₹)',
            data: <?= json_encode($expenses_chart) ?>,
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Payment Method Chart
const methodCtx = document.getElementById('paymentMethodChart').getContext('2d');
new Chart(methodCtx, {
    type: 'doughnut',
    data: {
        labels: [
            <?php 
            mysqli_data_seek($payment_methods, 0);
            while($pm = mysqli_fetch_assoc($payment_methods)){
                echo "'".ucfirst($pm['payment_mode'])."',";
            }
            ?>
        ],
        datasets: [{
            data: [
                <?php 
                mysqli_data_seek($payment_methods, 0);
                while($pm = mysqli_fetch_assoc($payment_methods)){
                    echo $pm['total'].",";
                }
                ?>
            ],
            backgroundColor: [
                'rgb(54, 162, 235)',
                'rgb(255, 99, 132)',
                'rgb(255, 205, 86)',
                'rgb(75, 192, 192)',
                'rgb(153, 102, 255)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php include "layout/footer.php"; ?>