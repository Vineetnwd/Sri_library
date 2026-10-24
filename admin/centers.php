<?php
require_once "layout/header.php";
require_once "../system/db.php";

$q = mysqli_query($conn,"SELECT * FROM centers ORDER BY id DESC");
?>

<?php include "layout/sidebar.php"; ?>

<div class="fade-in">

<div class="page-header">
    <h4><i class="fas fa-building me-2"></i> Manage Centers</h4>
    <a href="add_center.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Add New Center
    </a>
</div>

<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th style="width: 60px;">#</th>
                <th>Center Name</th>
                <th>Owner</th>
                <th>Contact</th>
                <th>Plan</th>
                <th>Expiry Date</th>
                <th>Status</th>
                <th style="width: 260px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($c=mysqli_fetch_assoc($q)){ 
                $days_left = (strtotime($c['expiry_date']) - time()) / (60*60*24);
            ?>
            <tr>
                <td><strong><?= $c['id'] ?></strong></td>
                <td>
                    <strong style="color: #2d3748;"><?= $c['center_name'] ?></strong>
                </td>
                <td><?= $c['owner_name'] ?></td>
                <td>
                    <i class="fas fa-phone text-muted me-1"></i> <?= $c['mobile'] ?>
                </td>
                <td>
                    <span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <?= $c['plan_name'] ?>
                    </span>
                </td>
                <td>
                    <?= date('M d, Y', strtotime($c['expiry_date'])) ?>
                    <?php if($days_left <= 7 && $days_left > 0){ ?>
                        <br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> <?= round($days_left) ?> days left</small>
                    <?php } elseif($days_left <= 0){ ?>
                        <br><small class="text-danger"><i class="fas fa-times-circle"></i> Expired</small>
                    <?php } ?>
                </td>
                <td>
                    <span class="badge bg-<?= $c['status']=='active'?'success':'danger' ?>">
                        <i class="fas fa-<?= $c['status']=='active'?'check':'times' ?> me-1"></i>
                        <?= ucfirst($c['status']) ?>
                    </span>
                </td>
                <td>
                    <a href="edit_center.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="extend_plan.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-success" title="Extend Plan">
                        <i class="fas fa-calendar-plus"></i>
                    </a>
                    <a href="login_as_center.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-info" title="Login As">
                        <i class="fas fa-sign-in-alt"></i>
                    </a>
                </td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</div>

<?php include "layout/footer.php"; ?>