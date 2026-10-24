<?php
require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* fetch plans with student count */
$plans = mysqli_query($conn,
    "SELECT fp.*, 
            COUNT(s.id) as student_count
     FROM fee_plans fp
     LEFT JOIN students s ON s.plan_id = fp.id AND s.status = 'active'
     WHERE fp.center_id='$cid'
     GROUP BY fp.id
     ORDER BY fp.amount ASC"
);

$plans_data = [];
while($p = mysqli_fetch_assoc($plans)){
    $plans_data[] = $p;
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">
        <i class="fa fa-layer-group me-2 text-primary"></i> Fee Plans
    </h4>
    <a href="fee_plan_add.php" class="btn btn-primary btn-sm fw-semibold">
        <i class="fas fa-plus me-1"></i> Add Plan
    </a>
</div>

<?php if(empty($plans_data)){ ?>
    <div class="card border-0 shadow-sm text-center py-5" style="border-radius:12px;">
        <i class="fa fa-layer-group fa-3x text-muted opacity-25 mb-3"></i>
        <p class="text-muted">No fee plans created yet.</p>
        <a href="fee_plan_add.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Create Your First Plan
        </a>
    </div>
<?php } else { ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                    <i class="fa fa-layer-group" style="font-size:14px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Total Plans</div>
                    <div class="fw-bold fs-5 lh-1"><?= count($plans_data) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                    <i class="fa fa-users" style="font-size:14px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Enrolled Students</div>
                    <div class="fw-bold fs-5 lh-1"><?= array_sum(array_column($plans_data, 'student_count')) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow border-0" style="border-radius:12px;">
    <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold"><i class="fa fa-table me-2 text-primary"></i>All Plans</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 text-uppercase text-muted" style="font-size:11px;">Plan Name</th>
                    <th class="text-uppercase text-muted" style="font-size:11px;">Duration</th>
                    <th class="text-uppercase text-muted text-end" style="font-size:11px;">Amount</th>
                    <th class="text-uppercase text-muted text-center" style="font-size:11px;">Students</th>
                    <th class="text-uppercase text-muted text-end pe-3" style="font-size:11px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($plans_data as $p){ ?>
                <tr>
                    <td class="ps-3">
                        <strong class="text-dark"><?= htmlspecialchars($p['plan_name']) ?></strong>
                    </td>
                    <td>
                        <span class="badge rounded-pill bg-light text-dark border" style="font-size:11px;">
                            <?= $p['duration_month'] ?> <?= $p['duration_month'] == 1 ? 'Month' : 'Months' ?>
                        </span>
                    </td>
                    <td class="text-end fw-bold text-success" style="font-size:14px;">
                        ₹ <?= number_format($p['amount'], 0) ?>
                    </td>
                    <td class="text-center">
                        <?php if($p['student_count'] > 0){ ?>
                            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary" style="font-size:11px;">
                                <?= $p['student_count'] ?>
                            </span>
                        <?php } else { ?>
                            <span class="text-muted" style="font-size:12px;">—</span>
                        <?php } ?>
                    </td>
                    <td class="text-end pe-3">
                        <a href="fee_plan_edit.php?id=<?= $p['id'] ?>"
                           class="btn btn-sm btn-light border text-primary fw-semibold" style="font-size:12px;">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <?php if($p['student_count'] == 0){ ?>
                            <a href="fee_plan_delete.php?id=<?= $p['id'] ?>"
                               onclick="return confirm('Delete plan: <?= addslashes($p['plan_name']) ?>?')"
                               class="btn btn-sm btn-light border text-danger fw-semibold ms-1" style="font-size:12px;">
                                <i class="fas fa-trash me-1"></i>Delete
                            </a>
                        <?php } else { ?>
                            <button class="btn btn-sm btn-light border text-muted ms-1" disabled title="<?= $p['student_count'] ?> student(s) on this plan" style="font-size:12px;">
                                <i class="fas fa-lock me-1"></i>In Use
                            </button>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php } ?>

</div>

<?php include "layout/footer.php"; ?>
