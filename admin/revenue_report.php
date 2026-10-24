<?php
require_once "layout/header.php";
require_once "../system/db.php";

/* TOTAL COLLECTION PER CENTER */
$q = mysqli_query($conn,
    "SELECT 
        c.id,
        c.center_name,
        c.plan_name,
        c.expiry_date,
        c.status,
        IFNULL(SUM(p.amount),0) AS total_collection,
        COUNT(DISTINCT p.student_id) AS total_students,
        COUNT(p.id) AS total_transactions
     FROM centers c
     LEFT JOIN payments p ON p.center_id = c.id
     GROUP BY c.id
     ORDER BY total_collection DESC"
);

/* OVERALL STATS */
$overall = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT 
            IFNULL(SUM(amount),0) as total_revenue,
            COUNT(DISTINCT center_id) as paying_centers,
            COUNT(id) as total_transactions
         FROM payments")
);

$today_revenue = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT IFNULL(SUM(amount),0) as total
         FROM payments 
         WHERE payment_date = CURDATE()")
)['total'];

$monthly_revenue = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT IFNULL(SUM(amount),0) as total
         FROM payments 
         WHERE MONTH(payment_date) = MONTH(CURDATE())
         AND YEAR(payment_date) = YEAR(CURDATE())")
)['total'];
?>

<?php include "layout/sidebar.php"; ?>

<div class="fade-in">

<div class="page-header">
    <div>
        <h4><i class="fas fa-chart-bar me-2"></i> Revenue Report</h4>
        <small class="text-muted">Complete financial overview across all centers</small>
    </div>
    <div>
        <button class="btn btn-success" onclick="window.print()">
            <i class="fas fa-print me-2"></i> Print Report
        </button>
    </div>
</div>

<!-- Overview Cards -->
<div class="row g-4 mb-4">
    
    <div class="col-md-3">
        <div class="stat-card">
            <h6>Total Revenue</h6>
            <h3>₹<?= number_format($overall['total_revenue']) ?></h3>
            <i class="fas fa-rupee-sign icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card success">
            <h6>Today's Collection</h6>
            <h3>₹<?= number_format($today_revenue) ?></h3>
            <i class="fas fa-calendar-day icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card warning">
            <h6>Monthly Revenue</h6>
            <h3>₹<?= number_format($monthly_revenue) ?></h3>
            <i class="fas fa-calendar-alt icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card danger">
            <h6>Total Transactions</h6>
            <h3><?= number_format($overall['total_transactions']) ?></h3>
            <i class="fas fa-receipt icon"></i>
        </div>
    </div>

</div>

<!-- Revenue Table -->
<div class="table-container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">
            <i class="fas fa-building me-2"></i> Center-wise Revenue Breakdown
        </h5>
        <div>
            <input type="text" id="searchBox" class="form-control" placeholder="🔍 Search centers..." style="width: 250px;">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table" id="revenueTable">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Center Name</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Expiry Date</th>
                    <th>Students</th>
                    <th>Transactions</th>
                    <th>Total Collection</th>
                    <th style="width: 140px;">Action</th>
                </tr>
            </thead>

            <tbody>
            <?php 
            $i = 1; 
            $grand_total = 0;
            while($r=mysqli_fetch_assoc($q)){ 
                $grand_total += $r['total_collection'];
                $days_left = (strtotime($r['expiry_date']) - time()) / (60*60*24);
            ?>
            <tr>
                <td><strong><?= $i++ ?></strong></td>
                <td>
                    <strong style="color: #2d3748;"><?= $r['center_name'] ?></strong>
                </td>
                <td>
                    <span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <?= $r['plan_name'] ?>
                    </span>
                </td>
                <td>
                    <span class="badge bg-<?= $r['status']=='active'?'success':'danger' ?>">
                        <?= ucfirst($r['status']) ?>
                    </span>
                </td>
                <td>
                    <small><?= date('M d, Y', strtotime($r['expiry_date'])) ?></small>
                    <?php if($days_left <= 7 && $days_left > 0){ ?>
                        <br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Expiring soon</small>
                    <?php } ?>
                </td>
                <td>
                    <span class="badge bg-info"><?= $r['total_students'] ?></span>
                </td>
                <td>
                    <span class="badge bg-secondary"><?= $r['total_transactions'] ?></span>
                </td>
                <td>
                    <strong style="color: #11998e; font-size: 16px;">
                        ₹<?= number_format($r['total_collection'], 2) ?>
                    </strong>
                </td>
                <td>
                    <a href="revenue_center_view.php?id=<?= $r['id'] ?>"
                       class="btn btn-sm btn-primary">
                       <i class="fas fa-eye me-1"></i> Details
                    </a>
                </td>
            </tr>
            <?php } ?>
            </tbody>

            <tfoot style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <tr>
                    <th colspan="7" class="text-end" style="padding: 15px;">GRAND TOTAL</th>
                    <th style="font-size: 18px; padding: 15px;">
                        ₹<?= number_format($grand_total, 2) ?>
                    </th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

</div>

</div>

<script>
// Search functionality
document.getElementById('searchBox').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let table = document.getElementById('revenueTable');
    let tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length - 1; i++) {
        let td = tr[i].getElementsByTagName('td')[1];
        if (td) {
            let txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
});
</script>

<style>
@media print {
    .sidebar, .page-header button, .btn, .form-control { display: none !important; }
    body { background: white !important; }
    .table-container { box-shadow: none !important; }
}
</style>

<?php include "layout/footer.php"; ?>