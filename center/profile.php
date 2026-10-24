<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "layout/header.php";
require_once "../system/db.php";

$user_id = $_SESSION['user_id'];

$user = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM users WHERE id='$user_id'")
);

/* Update Profile */
if(isset($_POST['update_profile'])){
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $mobile = clean($_POST['mobile']);
    $address = clean($_POST['address']);
    
    mysqli_query($conn,
        "UPDATE users SET
            name='$name',
            email='$email',
            mobile='$mobile',
            address='$address'
         WHERE id='$user_id'"
    );
    
    set_flash("success", "Profile updated successfully");
}

/* Change Password */
if(isset($_POST['change_password'])){
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    
    if($new != $confirm){
        set_flash("danger", "New passwords do not match");
        redirect("profile.php");
    }
    
    if(strlen($new) < 6){
        set_flash("danger", "Password must be at least 6 characters");
        redirect("profile.php");
    }
    
    /* Verify current password */
    if(password_verify($current, $user['password'])){
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        
        mysqli_query($conn,
            "UPDATE users SET password='$new_hash' WHERE id='$user_id'");
        
        set_flash("success", "Password changed successfully");
        redirect("profile.php");
    } else {
        set_flash("danger", "Current password is incorrect");
        redirect("profile.php");
    }
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-user-circle me-2"></i>My Profile
    </h1>
</div>

<div class="row g-4">
    
    <!-- Profile Information -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-user me-2"></i>Profile Information
            </div>
            <div class="card-body">
                
                <form method="post">
                    
                    <div class="row g-3">
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Full Name *</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= $user['name'] ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email Address *</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= $user['email'] ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mobile Number *</label>
                            <input type="text" name="mobile" class="form-control"
                                   value="<?= $user['mobile'] ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Center/Library Name</label>
                            <input type="text" class="form-control" 
                                   value="<?= $user['center_name'] ?>" disabled>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?= $user['address'] ?></textarea>
                        </div>
                        
                    </div>
                    
                    <button name="update_profile" class="btn btn-success btn-lg mt-4">
                        <i class="fas fa-save me-2"></i>Update Profile
                    </button>
                    
                </form>
                
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <i class="fas fa-lock me-2"></i>Change Password
            </div>
            <div class="card-body">
                
                <form method="post">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Current Password *</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">New Password *</label>
                        <input type="password" name="new_password" class="form-control" 
                               required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Confirm New Password *</label>
                        <input type="password" name="confirm_password" class="form-control" 
                               required minlength="6">
                    </div>
                    
                    <button name="change_password" class="btn btn-warning btn-lg">
                        <i class="fas fa-key me-2"></i>Change Password
                    </button>
                    
                </form>
                
            </div>
        </div>
    </div>
    
    <!-- Account Details -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <i class="fas fa-id-card me-2"></i>Account Details
            </div>
            <div class="card-body">
                
                <div class="text-center mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                         style="width: 100px; height: 100px; font-size: 48px;">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">User ID</td>
                        <td><strong>#<?= $user['id'] ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Username</td>
                        <td><strong><?= $user['username'] ?></strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Role</td>
                        <td>
                            <span class="badge bg-primary">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td>
                            <span class="badge bg-success">Active</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Joined</td>
                        <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                    </tr>
                </table>
                
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <i class="fas fa-link me-2"></i>Quick Links
            </div>
            <div class="card-body">
                
                <a href="dashboard.php" class="btn btn-outline-primary btn-sm w-100 mb-2">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
                
                <a href="settings.php" class="btn btn-outline-info btn-sm w-100 mb-2">
                    <i class="fas fa-cog me-2"></i>Settings
                </a>
                
                <a href="help.php" class="btn btn-outline-success btn-sm w-100 mb-2">
                    <i class="fas fa-question-circle me-2"></i>Help & Support
                </a>
                
                <a href="logout.php" class="btn btn-outline-danger btn-sm w-100"
                   onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
                
            </div>
        </div>
    </div>
    
</div>

</div>

<?php include "layout/footer.php"; ?>