<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
    redirect("admin/centers.php");
}

/* calculate days remaining */
$expiry_date = strtotime($center['expiry_date']);
$current_date = time();
$days_remaining = ceil(($expiry_date - $current_date) / (60 * 60 * 24));

/* extend plan */
if (isset($_POST['extend'])) {

    $plan_name = clean($_POST['plan_name']);
    $extend_days = intval($_POST['extend_days']);
    
    // Calculate new expiry date
    $current_expiry = $center['expiry_date'];
    $new_expiry = date('Y-m-d', strtotime($current_expiry . " + $extend_days days"));

    mysqli_query($conn,
        "UPDATE centers SET
            plan_name='$plan_name',
            expiry_date='$new_expiry',
            status='active'
         WHERE id='$id'"
    );

    set_flash("success", "Plan extended successfully! New expiry: $new_expiry");
    redirect("admin/centers.php");
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="fade-in">

<div class="page-header">
    <div>
        <h4><i class="fas fa-calendar-plus me-2"></i> Extend Plan</h4>
        <small class="text-muted">Extend subscription for <?= $center['center_name'] ?></small>
    </div>
    <a href="centers.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i> Back to Centers
    </a>
</div>

<div class="row">
    
    <!-- Extend Form -->
    <div class="col-lg-8">
        <div class="card p-4">
            
            <div class="alert" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 12px;">
                <h5 class="mb-2">
                    <i class="fas fa-building me-2"></i> <?= $center['center_name'] ?>
                </h5>
                <div class="row">
                    <div class="col-md-6">
                        <small style="opacity: 0.9;">Owner:</small>
                        <div><strong><?= $center['owner_name'] ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <small style="opacity: 0.9;">Contact:</small>
                        <div><strong><?= $center['mobile'] ?></strong></div>
                    </div>
                </div>
            </div>

            <form method="post">

                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-info-circle me-2 text-primary"></i> Current Plan Status
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 12px; color: white;">
                                <small style="opacity: 0.9;">Current Plan</small>
                                <h5 class="mb-0 mt-1"><?= $center['plan_name'] ?></h5>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 12px; color: white;">
                                <small style="opacity: 0.9;">Expiry Date</small>
                                <h6 class="mb-0 mt-1"><?= date('M d, Y', strtotime($center['expiry_date'])) ?></h6>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3" style="background: linear-gradient(135deg, <?= $days_remaining < 0 ? '#fa709a 0%, #fee140 100%' : '#11998e 0%, #38ef7d 100%' ?>); border-radius: 12px; color: white;">
                                <small style="opacity: 0.9;">Days Remaining</small>
                                <h5 class="mb-0 mt-1">
                                    <?= $days_remaining < 0 ? 'EXPIRED' : $days_remaining . ' days' ?>
                                </h5>
                            </div>
                        </div>
                    </div>

                    <?php if($days_remaining < 0){ ?>
                    <div class="alert alert-danger mt-3" style="border-left: 4px solid #dc3545;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This plan has expired <?= abs($days_remaining) ?> days ago!
                    </div>
                    <?php } elseif($days_remaining <= 7){ ?>
                    <div class="alert alert-warning mt-3" style="border-left: 4px solid #ffc107;">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Notice:</strong> This plan is expiring soon. Consider extending it now.
                    </div>
                    <?php } ?>
                </div>

                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-crown me-2 text-warning"></i> Extension Options
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-box me-2 text-primary"></i> Select New Plan
                            </label>
                            <select name="plan_name" id="planSelect" class="form-control form-control-lg" required>
                                <option value="">-- Choose Plan --</option>
                                <option value="TRIAL" data-days="7">🎯 TRIAL (7 Days)</option>
                                <option value="MONTHLY" data-days="30">📅 MONTHLY (30 Days)</option>
                                <option value="QUARTERLY" data-days="90">📊 QUARTERLY (90 Days)</option>
                                <option value="YEARLY" data-days="365">⭐ YEARLY (365 Days)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt me-2 text-primary"></i> Extension Days
                            </label>
                            <input type="number" name="extend_days" id="extendDays" 
                                   class="form-control form-control-lg" 
                                   placeholder="Enter days" 
                                   min="1" 
                                   required>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i> Will be added to current expiry date
                            </small>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="border-bottom pb-2 mb-3">
                        <i class="fas fa-calendar-check me-2 text-success"></i> Preview New Expiry
                    </h5>

                    <div class="p-4 text-center" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 12px; color: white;">
                        <small style="opacity: 0.9;">New Expiry Date</small>
                        <h3 class="mb-0 mt-2" id="newExpiryPreview">Select a plan to preview</h3>
                        <small id="totalDaysPreview" style="opacity: 0.9;"></small>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button name="extend" class="btn btn-primary btn-lg">
                        <i class="fas fa-check-circle me-2"></i> Extend Plan Now
                    </button>
                    <a href="centers.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times me-2"></i> Cancel
                    </a>
                </div>

            </form>

        </div>
    </div>

    <!-- Quick Actions & Info -->
    <div class="col-lg-4">
        
        <!-- Quick Extension Presets -->
        <div class="card p-4 mb-3">
            <h6 class="border-bottom pb-2 mb-3">
                <i class="fas fa-bolt me-2"></i> Quick Extensions
            </h6>
            
            <div class="d-grid gap-2">
                <button class="btn btn-outline-primary" onclick="quickExtend('TRIAL', 7)">
                    <i class="fas fa-rocket me-2"></i> Trial (7 Days)
                </button>
                <button class="btn btn-outline-primary" onclick="quickExtend('MONTHLY', 30)">
                    <i class="fas fa-calendar me-2"></i> Monthly (30 Days)
                </button>
                <button class="btn btn-outline-primary" onclick="quickExtend('QUARTERLY', 90)">
                    <i class="fas fa-chart-line me-2"></i> Quarterly (90 Days)
                </button>
                <button class="btn btn-outline-primary" onclick="quickExtend('YEARLY', 365)">
                    <i class="fas fa-star me-2"></i> Yearly (365 Days)
                </button>
            </div>
        </div>

        <!-- Center Stats -->
        <div class="card p-4 mb-3">
            <h6 class="border-bottom pb-2 mb-3">
                <i class="fas fa-chart-bar me-2"></i> Center Statistics
            </h6>
            
            <?php
            $stats = mysqli_fetch_assoc(
                mysqli_query($conn,
                    "SELECT 
                        (SELECT COUNT(*) FROM students WHERE center_id='$id') as total_students,
                        (SELECT IFNULL(SUM(amount),0) FROM payments WHERE center_id='$id') as total_revenue,
                        (SELECT COUNT(*) FROM payments WHERE center_id='$id') as total_payments
                    ")
            );
            ?>

            <div class="mb-2">
                <small class="text-muted">Total Students</small>
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= $stats['total_students'] ?></h5>
                    <i class="fas fa-users text-primary"></i>
                </div>
            </div>
            <hr>
            <div class="mb-2">
                <small class="text-muted">Total Revenue</small>
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">₹<?= number_format($stats['total_revenue']) ?></h5>
                    <i class="fas fa-rupee-sign text-success"></i>
                </div>
            </div>
            <hr>
            <div class="mb-0">
                <small class="text-muted">Total Payments</small>
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= $stats['total_payments'] ?></h5>
                    <i class="fas fa-receipt text-warning"></i>
                </div>
            </div>
        </div>

        <!-- Plan Info -->
        <div class="alert alert-info" style="border-left: 4px solid #0dcaf0; border-radius: 10px;">
            <h6><i class="fas fa-lightbulb me-2"></i> Pricing Guide</h6>
            <small>
                • <strong>Trial:</strong> ₹0 (7 days)<br>
                • <strong>Monthly:</strong> ₹999/month<br>
                • <strong>Quarterly:</strong> ₹2,499 (3 months)<br>
                • <strong>Yearly:</strong> ₹8,999 (12 months)
            </small>
        </div>

    </div>

</div>

</div>

<script>
// Auto-fill days based on plan selection
document.getElementById('planSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const days = selectedOption.getAttribute('data-days');
    
    if(days) {
        document.getElementById('extendDays').value = days;
        updatePreview();
    }
});

// Update preview when days change
document.getElementById('extendDays').addEventListener('input', updatePreview);

function updatePreview() {
    const days = parseInt(document.getElementById('extendDays').value) || 0;
    const currentExpiry = new Date('<?= $center['expiry_date'] ?>');
    
    if(days > 0) {
        const newExpiry = new Date(currentExpiry);
        newExpiry.setDate(newExpiry.getDate() + days);
        
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('newExpiryPreview').textContent = 
            newExpiry.toLocaleDateString('en-US', options);
        
        document.getElementById('totalDaysPreview').textContent = 
            `(${days} days extension)`;
    }
}

// Quick extend function
function quickExtend(plan, days) {
    document.getElementById('planSelect').value = plan;
    document.getElementById('extendDays').value = days;
    updatePreview();
}
</script>

<?php include "layout/footer.php"; ?>