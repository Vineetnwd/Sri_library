<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

/* Get or create settings */
$settings = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM settings WHERE center_id='$cid'")
);

if(!$settings){
    mysqli_query($conn,
        "INSERT INTO settings (center_id) VALUES ('$cid')");
    $settings = mysqli_fetch_assoc(
        mysqli_query($conn,
            "SELECT * FROM settings WHERE center_id='$cid'")
    );
}

/* Save settings */
if(isset($_POST['save_settings'])){
    $sms_enabled = isset($_POST['sms_enabled']) ? 1 : 0;
    $sms_api_key = clean($_POST['sms_api_key']);
    $sms_sender_id = clean($_POST['sms_sender_id']);
    
    $wa_enabled = isset($_POST['wa_enabled']) ? 1 : 0;
    $wa_token = clean($_POST['wa_token']);
    $wa_number = clean($_POST['wa_number']);
    
    $late_fee_enabled = isset($_POST['late_fee_enabled']) ? 1 : 0;
    $late_fee_amount = clean($_POST['late_fee_amount']);
    $late_fee_days = clean($_POST['late_fee_days']);
    
    $auto_backup = isset($_POST['auto_backup']) ? 1 : 0;
    $backup_time = clean($_POST['backup_time']);
    
    mysqli_query($conn,
        "UPDATE settings SET
            sms_enabled='$sms_enabled',
            sms_api_key='$sms_api_key',
            sms_sender_id='$sms_sender_id',
            wa_enabled='$wa_enabled',
            wa_token='$wa_token',
            wa_number='$wa_number',
            late_fee_enabled='$late_fee_enabled',
            late_fee_amount='$late_fee_amount',
            late_fee_days='$late_fee_days',
            auto_backup='$auto_backup',
            backup_time='$backup_time'
         WHERE center_id='$cid'"
    );
    
    set_flash("success", "Settings saved successfully");
    redirect("settings.php");
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-cog me-2"></i>Settings & Configuration
    </h1>
</div>

<form method="post">
    
    <div class="row g-4">
        
        <!-- SMS Settings -->
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-sms me-2"></i>SMS Configuration
                </div>
                <div class="card-body">
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" 
                               name="sms_enabled" id="sms_enabled"
                               <?= $settings['sms_enabled']?'checked':'' ?>>
                        <label class="form-check-label fw-bold" for="sms_enabled">
                            Enable SMS Notifications
                        </label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">SMS API Key</label>
                        <input type="text" name="sms_api_key" class="form-control"
                               value="<?= $settings['sms_api_key'] ?>"
                               placeholder="Enter your SMS API key">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sender ID</label>
                        <input type="text" name="sms_sender_id" class="form-control"
                               value="<?= $settings['sms_sender_id'] ?>"
                               placeholder="e.g., TXTLCL">
                    </div>
                    
                </div>
            </div>
        </div>
        
        <!-- WhatsApp Settings -->
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <i class="fab fa-whatsapp me-2"></i>WhatsApp Configuration
                </div>
                <div class="card-body">
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" 
                               name="wa_enabled" id="wa_enabled"
                               <?= $settings['wa_enabled']?'checked':'' ?>>
                        <label class="form-check-label fw-bold" for="wa_enabled">
                            Enable WhatsApp Integration
                        </label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">WhatsApp Business API Token</label>
                        <input type="text" name="wa_token" class="form-control"
                               value="<?= $settings['wa_token'] ?>"
                               placeholder="Enter API token">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Business Phone Number</label>
                        <input type="text" name="wa_number" class="form-control"
                               value="<?= $settings['wa_number'] ?>"
                               placeholder="e.g., 919876543210">
                    </div>
                    
                </div>
            </div>
        </div>
        
        <!-- Late Fee Settings -->
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-coins me-2"></i>Late Fee Configuration
                </div>
                <div class="card-body">
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" 
                               name="late_fee_enabled" id="late_fee_enabled"
                               <?= $settings['late_fee_enabled']?'checked':'' ?>>
                        <label class="form-check-label fw-bold" for="late_fee_enabled">
                            Enable Automatic Late Fee
                        </label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Late Fee Amount (₹)</label>
                        <input type="number" name="late_fee_amount" class="form-control"
                               value="<?= $settings['late_fee_amount'] ?>"
                               placeholder="e.g., 100">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Grace Period (Days)</label>
                        <input type="number" name="late_fee_days" class="form-control"
                               value="<?= $settings['late_fee_days'] ?>"
                               placeholder="e.g., 5">
                        <small class="text-muted">
                            Late fee will be applied after this many days from due date
                        </small>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <!-- Backup Settings -->
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-database me-2"></i>Backup Configuration
                </div>
                <div class="card-body">
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" 
                               name="auto_backup" id="auto_backup"
                               <?= $settings['auto_backup']?'checked':'' ?>>
                        <label class="form-check-label fw-bold" for="auto_backup">
                            Enable Automatic Daily Backup
                        </label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Backup Time</label>
                        <input type="time" name="backup_time" class="form-control"
                               value="<?= $settings['backup_time'] ?>">
                    </div>
                    
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Backups will be stored in the system and can be downloaded from the admin panel
                    </div>
                    
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Save Button -->
    <div class="card shadow mt-4">
        <div class="card-body text-center">
            <button name="save_settings" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-save me-2"></i>Save All Settings
            </button>
        </div>
    </div>
    
</form>

</div>

<?php include "layout/footer.php"; ?>