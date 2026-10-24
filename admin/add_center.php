<?php
require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

if (isset($_POST['save'])) {
    function uploadFile($file, $folder = "../uploads/"){
    if(!empty($_FILES[$file]['name'])){

        $ext = strtolower(pathinfo($_FILES[$file]['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];

        if(!in_array($ext, $allowed)){
            die("Invalid file type");
        }

        $name = time().rand(1000,9999).".".$ext;

        move_uploaded_file(
            $_FILES[$file]['tmp_name'],
            $folder.$name
        );

        return $name;
    }
    return '';
}

    $id_front = uploadFile('id_front');
    $id_back  = uploadFile('id_back');
    $dir_sign = uploadFile('dir_sign');
    $center_name = clean($_POST['center_name']);
    $owner_name  = clean($_POST['owner_name']);
    $mobile      = clean($_POST['mobile']);
    $email       = clean($_POST['email']);
    $expiry = date('Y-m-d', strtotime("+7 days"));

    mysqli_query($conn,
        "INSERT INTO centers
        (center_name, owner_name, mobile, email, plan_name, expiry_date, status,
         id_front, id_back, dir_sign)
        VALUES
        ('$center_name','$owner_name','$mobile','$email','TRIAL','$expiry','active',
         '$id_front','$id_back','$dir_sign')"
    );

    $center_id = mysqli_insert_id($conn);
    $password = password_hash("123456", PASSWORD_DEFAULT);

    mysqli_query($conn,
        "INSERT INTO users
        (center_id, username, password, role, status, center_name)
        VALUES
        ('$center_id','$mobile','$password','admin','active','$center_name')"
    );

    set_flash("success", "Center created successfully. Default password: 123456");
    redirect("centers.php");
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="fade-in">

<div class="page-header">
    <h4><i class="fas fa-plus-circle me-2"></i> Add New Center</h4>
    <a href="centers.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i> Back
    </a>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto; padding: 40px;">
    
    <div class="text-center mb-4">
        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);">
            <i class="fas fa-building" style="font-size: 35px; color: white;"></i>
        </div>
        <h5 class="mb-1">Create New Center</h5>
        <p class="text-muted">Enter center details to create a new account</p>
    </div>

    <form method="post">
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label><i class="fas fa-building me-2 text-primary"></i> Center Name *</label>
                <input type="text" name="center_name" class="form-control" placeholder="Enter center name" required>
            </div>

            <div class="col-md-6 mb-3">
                <label><i class="fas fa-user me-2 text-primary"></i> Owner Name</label>
                <input type="text" name="owner_name" class="form-control" placeholder="Enter owner name">
            </div>

            <div class="col-md-6 mb-3">
                <label><i class="fas fa-phone me-2 text-primary"></i> Mobile (Username) *</label>
                <input type="text" name="mobile" class="form-control" placeholder="Enter mobile number" required>
                <small class="text-muted">This will be used as username</small>
            </div>

            <div class="col-md-6 mb-3">
                <label><i class="fas fa-envelope me-2 text-primary"></i> Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email address">
            </div>
            <div class="col-md-6 mb-3">
                <label><i class="fas fa-id-card me-2 text-primary"></i> ID Front</label>
                <input type="file" name="id_front" class="form-control">
            </div>
            
            <div class="col-md-6 mb-3">
                <label><i class="fas fa-id-card me-2 text-primary"></i> ID Back</label>
                <input type="file" name="id_back" class="form-control">
            </div>
            
            <div class="col-md-6 mb-3">
                <label><i class="fas fa-signature me-2 text-primary"></i> Director Signature</label>
                <input type="file" name="dir_sign" class="form-control">
            </div>
        </div>

        <div class="alert alert-info" role="alert" style="border-left: 4px solid #0dcaf0; border-radius: 10px;">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Default Settings:</strong> 7-day trial period | Default password: <code>123456</code>
        </div>

        <div class="d-grid gap-2 mt-4">
            <button name="save" class="btn btn-primary btn-lg">
                <i class="fas fa-save me-2"></i> Create Center
            </button>
        </div>

    </form>
</div>

</div>

<?php include "layout/footer.php"; ?>