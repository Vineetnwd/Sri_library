<?php
require_once "../system/auth.php";
require_once "../system/db.php";
require_super();

$center_id = $_GET['id'];

/* fetch center admin user */
$admin = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM users 
         WHERE center_id='$center_id' 
         AND role='admin'
         LIMIT 1")
);

if (!$admin) {
    set_flash("danger", "Center admin not found");
    redirect("centers.php");
}

/* store super admin session */
$_SESSION['super_admin_id'] = $_SESSION['user_id'];
$_SESSION['super_role']     = $_SESSION['role'];

/* impersonation flag */
$_SESSION['impersonate'] = true;

/* switch login */
$_SESSION['user_id']   = $admin['id'];
$_SESSION['center_id'] = $admin['center_id'];
$_SESSION['role']      = 'admin';

/* redirect */
redirect("./center/dashboard.php");
