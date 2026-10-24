<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<?php
require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$id = $_GET['id'] ?? 0;

/* fetch center */
$center = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM centers WHERE id='$id'")
);

if (!$center) {
    set_flash("danger", "Center not found");
    redirect("centers.php");
}
function uploadFile($file, $old = ''){
    if(!empty($_FILES[$file]['name'])){

        $ext = strtolower(pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];

        if(!in_array($ext, $allowed)){
            die("Invalid file type");
        }

        $name = time().rand(1000,9999).".".$ext;

        move_uploaded_file(
            $_FILES[$file]['tmp_name'],
            "../uploads/".$name
        );

        // delete old file
        if(!empty($old) && file_exists("../uploads/".$old)){
            unlink("../uploads/".$old);
        }

        return $name;
    }

    return $old; // keep old if not changed
}
/* update */
if (isset($_POST['update'])) {

    $center_name = clean($_POST['center_name']);
    $owner_name  = clean($_POST['owner_name']);
    $mobile      = clean($_POST['mobile']);
    $email       = clean($_POST['email']);
    $plan_name   = clean($_POST['plan_name']);
    $expiry_date = clean($_POST['expiry_date']);
    $status      = clean($_POST['status']);
    $id_front = uploadFile('id_front', $center['id_front']);
    $id_back  = uploadFile('id_back',  $center['id_back']);
    $dir_sign = uploadFile('dir_sign', $center['dir_sign']);
    $logo = uploadFile('logo', $center['logo']);

    mysqli_query($conn,
        "UPDATE centers SET
            center_name='$center_name',
            owner_name='$owner_name',
            mobile='$mobile',
            email='$email',
            plan_name='$plan_name',
            expiry_date='$expiry_date',
            status='$status',
            id_front='$id_front',
            id_back='$id_back',
            dir_sign='$dir_sign',
            logo='$logo'
         WHERE id='$id'"
    );

    set_flash("success", "Center updated successfully");
    redirect("admin/centers.php");
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="fade-in">

<div class="page-header">
    <div>
        <h4><i class="fas fa-edit me-2"></i> Edit Center</h4>
        <small class="text-muted">Update center information and settings</small>
    </div>
    <a href="centers.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i> Back to Centers
    </a>
</div>

<div class="row">
    
    <!-- Main Form -->
    <div class="col-lg-8">
        <div class="card p-4">
            
            <form method="post" enctype="multipart/form-data">

                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-info-circle me-2 text-primary"></i> Basic Information
                    </h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-building me-2 text-primary"></i> Center Name *</label>
                            <input type="text" name="center_name"
                                   value="<?= $center['center_name'] ?>"
                                   class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-user me-2 text-primary"></i> Owner Name</label>
                            <input type="text" name="owner_name"
                                   value="<?= $center['owner_name'] ?>"
                                   class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-phone me-2 text-primary"></i> Mobile</label>
                            <input type="text" name="mobile"
                                   value="<?= $center['mobile'] ?>"
                                   class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-envelope me-2 text-primary"></i> Email</label>
                            <input type="email" name="email"
                                   value="<?= $center['email'] ?>"
                                   class="form-control">
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-id-card me-2 text-primary"></i> Documents
                    </h5>
                
                    <div class="row">
                
                        <div class="col-md-6 mb-3">
                            <label>ID Front</label>
                            <input type="file" name="id_front" class="form-control">
                
                            <?php if(!empty($center['id_front'])){ ?>
                                <img src="../uploads/<?= $center['id_front'] ?>"
                                     style="height:80px;margin-top:8px;border-radius:6px;">
                            <?php } ?>
                        </div>
                
                        <div class="col-md-6 mb-3">
                            <label>ID Back</label>
                            <input type="file" name="id_back" class="form-control">
                
                            <?php if(!empty($center['id_back'])){ ?>
                                <img src="../uploads/<?= $center['id_back'] ?>"
                                     style="height:80px;margin-top:8px;border-radius:6px;">
                            <?php } ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Center Logo</label>
                            <input type="file" name="logo" class="form-control">
                        
                            <?php if(!empty($center['logo'])){ ?>
                                <img src="../uploads/<?= $center['logo'] ?>"
                                     style="height:70px;margin-top:8px;border-radius:6px;">
                            <?php } ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Director Signature</label>
                            <input type="file" name="dir_sign" class="form-control">
                
                            <?php if(!empty($center['dir_sign'])){ ?>
                                <img src="../uploads/<?= $center['dir_sign'] ?>"
                                     style="height:60px;margin-top:8px;">
                            <?php } ?>
                        </div>
                
                    </div>
                </div>
                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-crown me-2 text-warning"></i> Subscription Details
                    </h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-box me-2 text-warning"></i> Plan Name</label>
                            <select name="plan_name" class="form-control">
                                <option value="TRIAL" <?= $center['plan_name']=='TRIAL'?'selected':'' ?>>
                                    🎯 TRIAL (7 Days)
                                </option>
                                <option value="MONTHLY" <?= $center['plan_name']=='MONTHLY'?'selected':'' ?>>
                                    📅 MONTHLY
                                </option>
                                <option value="QUARTERLY" <?= $center['plan_name']=='QUARTERLY'?'selected':'' ?>>
                                    📊 QUARTERLY (3 Months)
                                </option>
                                <option value="YEARLY" <?= $center['plan_name']=='YEARLY'?'selected':'' ?>>
                                    ⭐ YEARLY (12 Months)
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-calendar-check me-2 text-warning"></i> Expiry Date</label>
                            <input type="date" name="expiry_date"
                                   value="<?= $center['expiry_date'] ?>"
                                   class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-toggle-on me-2 text-success"></i> Status</label>
                            <select name="status" class="form-control">
                                <option value="active" <?= $center['status']=='active'?'selected':'' ?>>
                                    ✅ Active
                                </option>
                                <option value="inactive" <?= $center['status']=='inactive'?'selected':'' ?>>
                                    ❌ Inactive
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button name="update" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i> Update Center
                    </button>
                    <a href="centers.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                </div>

            </form>

        </div>
    </div>

    <!-- Info Panel -->
    <div class="col-lg-4">
        
        <div class="card p-4 mb-3">
            <h6 class="border-bottom pb-2 mb-3">
                <i class="fas fa-chart-line me-2"></i> Center Stats
            </h6>
            
            <?php
            $stats = mysqli_fetch_assoc(
                mysqli_query($conn,
                    "SELECT 
                        (SELECT COUNT(*) FROM students WHERE center_id='$id') as total_students,
                        (SELECT IFNULL(SUM(amount),0) FROM payments WHERE center_id='$id') as total_revenue
                    ")
            );
            ?>

            <div class="mb-3 p-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; color: white;">
                <small style="opacity: 0.9;">Total Students</small>
                <h4 class="mb-0 mt-1"><?= $stats['total_students'] ?></h4>
            </div>

            <div class="p-3" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 10px; color: white;">
                <small style="opacity: 0.9;">Total Revenue</small>
                <h4 class="mb-0 mt-1">₹<?= number_format($stats['total_revenue']) ?></h4>
            </div>
        </div>

        <div class="card p-4">
            <h6 class="border-bottom pb-2 mb-3">
                <i class="fas fa-clock me-2"></i> Quick Actions
            </h6>
            
            <div class="d-grid gap-2">
                <a href="extend_plan.php?id=<?= $id ?>" class="btn btn-success">
                    <i class="fas fa-calendar-plus me-2"></i> Extend Plan
                </a>
                <a href="login_as_center.php?id=<?= $id ?>" class="btn btn-info">
                    <i class="fas fa-sign-in-alt me-2"></i> Login as Center
                </a>
                <a href="revenue_center_view.php?id=<?= $id ?>" class="btn btn-warning">
                    <i class="fas fa-rupee-sign me-2"></i> View Revenue
                </a>
            </div>
        </div>

        <div class="alert alert-info mt-3" style="border-left: 4px solid #0dcaf0; border-radius: 10px;">
            <small>
                <i class="fas fa-info-circle me-1"></i>
                <strong>Created:</strong> <?= date('M d, Y', strtotime($center['created_at'] ?? 'now')) ?>
            </small>
        </div>

    </div>

</div>

</div>

<?php include "layout/footer.php"; ?>