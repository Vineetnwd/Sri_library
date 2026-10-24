<?php
require_once "layout/header.php";
require_once "../system/db.php";

$total_centers = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT COUNT(*) total FROM centers")
)['total'];

$active_centers = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT COUNT(*) total FROM centers WHERE status='active'")
)['total'];

$expired_centers = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT COUNT(*) total FROM centers 
         WHERE expiry_date < CURDATE()")
)['total'];

$total_revenue = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT IFNULL(SUM(amount),0) total FROM payments")
)['total'];
?>

<?php include "layout/sidebar.php"; ?>

<div class="fade-in">
    
<div class="page-header">
    <div>
        <h4><i class="fas fa-tachometer-alt me-2"></i> Dashboard Overview</h4>
        <small style="color: #6c757d;">Welcome back! Here's your SaaS performance</small>
    </div>
    <div>
        <span class="badge bg-light text-dark">
            <i class="far fa-calendar me-1"></i> <?= date('F d, Y') ?>
        </span>
    </div>
</div>

<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="stat-card">
            <h6>Total Centers</h6>
            <h3><?= $total_centers ?></h3>
            <i class="fas fa-building icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card success">
            <h6>Active Centers</h6>
            <h3><?= $active_centers ?></h3>
            <i class="fas fa-check-circle icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card danger">
            <h6>Expired Centers</h6>
            <h3><?= $expired_centers ?></h3>
            <i class="fas fa-exclamation-triangle icon"></i>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card warning">
            <h6>Total Revenue</h6>
            <h3>₹<?= number_format($total_revenue) ?></h3>
            <i class="fas fa-rupee-sign icon"></i>
        </div>
    </div>

</div>

<!-- Recent Centers -->
<div class="table-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0"><i class="fas fa-clock me-2"></i> Recent Centers</h5>
        <a href="centers.php" class="btn btn-sm btn-primary">View All</a>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Center</th>
                <th>Owner</th>
                <th>Plan</th>
                <th>Expiry</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $recent = mysqli_query($conn,"SELECT * FROM centers ORDER BY id DESC LIMIT 5");
            while($c=mysqli_fetch_assoc($recent)){
            ?>
            <tr>
                <td><strong><?= $c['center_name'] ?></strong></td>
                <td><?= $c['owner_name'] ?></td>
                <td><span class="badge bg-info"><?= $c['plan_name'] ?></span></td>
                <td><?= date('M d, Y', strtotime($c['expiry_date'])) ?></td>
                <td>
                    <span class="badge bg-<?= $c['status']=='active'?'success':'danger' ?>">
                        <?= ucfirst($c['status']) ?>
                    </span>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</div>

<?php include "layout/footer.php"; ?>