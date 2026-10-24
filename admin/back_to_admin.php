<?php
require_once "../system/functions.php";

if (!isset($_SESSION['impersonate'])) {
    redirect("dashboard.php");
}

/* restore admin session */
$_SESSION['user_id'] = $_SESSION['super_admin_id'];
$_SESSION['role']    = $_SESSION['super_role'];

unset($_SESSION['center_id']);
unset($_SESSION['impersonate']);
unset($_SESSION['super_admin_id']);
unset($_SESSION['super_role']);

redirect("admin/dashboard.php");
