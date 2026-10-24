<?php
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
         AND status='completed'
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

$expired_count = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT COUNT(*) total FROM student_fees
         WHERE center_id='$cid' 
         AND status='running'
         AND end_date < CURDATE()")
)['total'];

/* Monthly revenue chart data */
$monthly_data = mysqli_query($conn,
    "SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        SUM(amount) as revenue
     FROM payments
     WHERE center_id='$cid' AND status='completed'
     AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
     GROUP BY month
     ORDER BY month ASC"
);

$months = [];
$revenues = [];
while($m = mysqli_fetch_assoc($monthly_data)){
    $months[] = date('M Y', strtotime($m['month'].'-01'));
    $revenues[] = $m['revenue'];
}

/* Payment method breakdown */
$payment_methods = mysqli_query($conn,
    "SELECT payment_method, SUM(amount) as total
     FROM payments
     WHERE center_id='$cid' AND status='completed'
     AND DATE(payment_date) BETWEEN '$from_date' AND '$to_date'
     GROUP BY payment_method"
);

/* Recent payments */
$recent = mysqli_query($conn,
    "SELECT p.*, s.name, s.registration_no
     FROM payments p
     JOIN students s ON s.id = p.student_id
     WHERE p.center_id='$cid'
     AND DATE(p.payment_date) BETWEEN '$from_date' AND '$to_date'
     ORDER BY p.payment_date DESC
     LIMIT 10"
);
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-chart-line me-2"></i>Revenue Reports & Analytics
    </h1>
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fas fa-print me-2"></i>Print Report
    </button>
</div>

<!-- Date Filter -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold">From Date</label>
                <input type="date" name="from" class="form-control" 
                       value="<?= $from_date ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">To Date</label>
                <input type="date" name="to" class="form-control" 
                       value="<?= $to_date ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i>Apply Filter
                </button>
                <a href="reports.php" class="btn btn-secondary">
                    <i class="fas fa-redo me-1"></i>Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-left-success shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Revenue
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ₹<?= number_format($total_revenue, 2) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-danger shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                            Pending Dues
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            ₹<?= number_format($pending_dues, 2) ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-primary shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Active Students
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $total_students ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-left-warning shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Expired Plans
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= $expired_count ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-times fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    
    <!-- Revenue Chart -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-chart-area me-2"></i>Monthly Revenue Trend (Last 12 Months)
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
        
        <!-- Recent Payments -->
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <i class="fas fa-history me-2"></i>Recent Payments
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Receipt</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        if(mysqli_num_rows($recent) == 0){
                            echo "<tr><td colspan='5' class='text-center py-4'>No payments found</td></tr>";
                        }
                        while($r = mysqli_fetch_assoc($recent)){ 
                        ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($r['payment_date'])) ?></td>
                                <td>
                                    <span class="badge bg-dark"><?= $r['receipt_no'] ?></span>
                                </td>
                                <td>
                                    <strong><?= $r['name'] ?></strong>
                                    <br><small class="text-muted"><?= $r['registration_no'] ?></small>
                                </td>
                                <td>
                                    <strong class="text-success">₹<?= number_format($r['amount'], 2) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= ucfirst($r['payment_method']) ?></span>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Methods Breakdown -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white">
                <i class="fas fa-chart-pie me-2"></i>Payment Methods
            </div>
            <div class="card-body">
                <canvas id="paymentMethodChart"></canvas>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <i class="fas fa-info-circle me-2"></i>Period Summary
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="small text-muted">Date Range</label>
                    <h6><?= date('d M Y', strtotime($from_date)) ?> to <?= date('d M Y', strtotime($to_date)) ?></h6>
                </div>
                
                <div class="mb-3">
                    <label class="small text-muted">Total Collected</label>
                    <h5 class="text-success">₹<?= number_format($total_revenue, 2) ?></h5>
                </div>
                
                <div class="mb-3">
                    <label class="small text-muted">Average per Student</label>
                    <h6>₹<?= $total_students > 0 ? number_format($total_revenue/$total_students, 2) : '0.00' ?></h6>
                </div>
                
                <hr>
                
                <div class="mb-2">
                    <label class="small text-muted">Collection Rate</label>
                    <div class="progress" style="height: 20px;">
                        <?php 
                        $total_expected = $total_revenue + $pending_dues;
                        $collection_rate = $total_expected > 0 ? ($total_revenue/$total_expected)*100 : 0;
                        ?>
                        <div class="progress-bar bg-success" style="width: <?= $collection_rate ?>%">
                            <?= number_format($collection_rate, 1) ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
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
                echo "'".ucfirst($pm['payment_method'])."',";
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