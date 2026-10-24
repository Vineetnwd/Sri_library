<?php
require_once "layout/header.php";
require_once "../system/db.php";

$center_id = $_GET['id'];

/* CENTER INFO */
$center = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT * FROM centers WHERE id='$center_id'")
);

if(!$center){
    set_flash("danger", "Center not found");
    redirect("revenue_report.php");
}

/* TODAY COLLECTION */
$today = date('Y-m-d');
$today_collection = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT IFNULL(SUM(amount),0) total
         FROM payments
         WHERE center_id='$center_id'
         AND payment_date='$today'")
)['total'];

/* MONTHLY COLLECTION */
$month = date('Y-m');
$monthly_collection = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT IFNULL(SUM(amount),0) total
         FROM payments
         WHERE center_id='$center_id'
         AND payment_date LIKE '$month%'")
)['total'];

/* TOTAL COLLECTION */
$total_collection = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT IFNULL(SUM(amount),0) total
         FROM payments
         WHERE center_id='$center_id'")
)['total'];

/* AVERAGE PAYMENT */
$avg_payment = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT IFNULL(AVG(amount),0) avg
         FROM payments
         WHERE center_id='$center_id'")
)['avg'];
?>

<?php include "layout/sidebar.php"; ?>

<div class="fade-in">

<div class="page-header">
    <div>
        <h4><i class="fas fa-building me-2"></i> <?= $center['center_name'] ?> - Revenue Details</h4>
        <small class="text-muted">
            <i class="fas fa-user me-1"></i> <?= $center['owner_name'] ?> | 
            <i class="fas fa-phone ms-2 me-1"></i> <?= $center['mobile'] ?>
        </small>
    </div>
    <div>
        <a href="revenue_report.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Back
        </a>
        <button class="btn btn-success ms-2" onclick="window.print()">
            <i class="fas fa-print me-2"></i> Print
        </button>
    </div>
</div>

<!-- Revenue Stats -->
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="stat-card">
            <h6>Today's Collection</h6>
            <h3>₹<?= number_format($today_collection, 2) ?></h3>
            <small style="opacity: 0.8;"><?= date('F d, Y') ?></small>
            <i class="fas fa-calendar-day icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card success">
            <h6>Monthly Collection</h6>
            <h3>₹<?= number_format($monthly_collection, 2) ?></h3>
            <small style="opacity: 0.8;"><?= date('F Y') ?></small>
            <i class="fas fa-calendar-alt icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card warning">
            <h6>Total Collection</h6>
            <h3>₹<?= number_format($total_collection, 2) ?></h3>
            <small style="opacity: 0.8;">All Time</small>
            <i class="fas fa-rupee-sign icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card danger">
            <h6>Average Payment</h6>
            <h3>₹<?= number_format($avg_payment, 2) ?></h3>
            <small style="opacity: 0.8;">Per Transaction</small>
            <i class="fas fa-chart-line icon"></i>
        </div>
    </div>

</div>

<!-- Payment Mode Distribution -->
<div class="row g-4 mb-4">
    <div class="col-md-12">
        <div class="card p-4">
            <h5 class="mb-4"><i class="fas fa-credit-card me-2"></i> Payment Mode Distribution</h5>
            
            <?php
            $modes = mysqli_query($conn,
                "SELECT 
                    payment_mode,
                    COUNT(*) as count,
                    SUM(amount) as total
                 FROM payments
                 WHERE center_id='$center_id'
                 GROUP BY payment_mode"
            );
            ?>

            <div class="row g-3">
                <?php while($mode = mysqli_fetch_assoc($modes)){ 
                    $percentage = ($mode['total'] / $total_collection) * 100;
                ?>
                <div class="col-md-4">
                    <div class="p-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 12px; color: white;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span style="font-weight: 600; text-transform: uppercase;">
                                <?= $mode['payment_mode'] ?>
                            </span>
                            <span class="badge bg-light text-dark"><?= $mode['count'] ?> txns</span>
                        </div>
                        <h4 class="mb-1">₹<?= number_format($mode['total']) ?></h4>
                        <div class="progress" style="height: 6px; background: rgba(255,255,255,0.3);">
                            <div class="progress-bar" style="width: <?= $percentage ?>%; background: white;"></div>
                        </div>
                        <small style="opacity: 0.9;"><?= number_format($percentage, 1) ?>% of total</small>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- Payment History -->
<div class="table-container">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">
            <i class="fas fa-history me-2"></i> Payment History
        </h5>
        <input type="text" id="searchPayments" class="form-control" placeholder="🔍 Search payments..." style="width: 250px;">
    </div>

    <div class="table-responsive">
        <table class="table" id="paymentTable">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Date & Time</th>
                    <th>Student Name</th>
                    <th>Amount</th>
                    <th>Payment Mode</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
            <?php
            $payments = mysqli_query($conn,
                "SELECT p.*, s.name, s.mobile
                 FROM payments p
                 LEFT JOIN students s ON s.id = p.student_id
                 WHERE p.center_id='$center_id'
                 ORDER BY p.id DESC"
            );

            $i = 1;
            while($p=mysqli_fetch_assoc($payments)){
            ?>
            <tr>
                <td><strong><?= $i++ ?></strong></td>
                <td>
                    <div>
                        <i class="far fa-calendar me-1"></i>
                        <?= date('M d, Y', strtotime($p['payment_date'])) ?>
                    </div>
                    <small class="text-muted">
                        <i class="far fa-clock me-1"></i>
                        <?= date('h:i A', strtotime($p['created_at'] ?? $p['payment_date'])) ?>
                    </small>
                </td>
                <td>
                    <strong><?= $p['name'] ?></strong>
                    <br><small class="text-muted"><i class="fas fa-phone me-1"></i> <?= $p['mobile'] ?></small>
                </td>
                <td>
                    <strong style="color: #11998e; font-size: 16px;">
                        ₹<?= number_format($p['amount'], 2) ?>
                    </strong>
                </td>
                <td>
                    <span class="badge" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-credit-card me-1"></i>
                        <?= strtoupper($p['payment_mode']) ?>
                    </span>
                </td>
                <td>
                    <span class="badge bg-success">
                        <i class="fas fa-check-circle me-1"></i> Completed
                    </span>
                </td>
            </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>

</div>

</div>

<script>
// Search functionality
document.getElementById('searchPayments').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let table = document.getElementById('paymentTable');
    let tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let td = tr[i].getElementsByTagName('td')[2];
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
    .sidebar, .page-header .btn, .form-control, input { display: none !important; }
    body { background: white !important; }
    .card, .table-container { box-shadow: none !important; page-break-inside: avoid; }
}
</style>

<?php include "layout/footer.php"; ?>