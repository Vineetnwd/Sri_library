<?php
session_start();
require_once "../system/functions.php";

// Check if this is an admin return session
if (!isset($_SESSION['admin_return']) || !isset($_SESSION['admin_id_backup'])) {
    set_flash("danger", "Invalid session");
    redirect("../admin/index.php");
}

// Clear center session
unset($_SESSION['center_id']);
unset($_SESSION['center_name']);

// Restore admin session
$_SESSION['admin_id'] = $_SESSION['admin_id_backup'];
unset($_SESSION['admin_id_backup']);
unset($_SESSION['admin_return']);

set_flash("success", "Returned to admin panel");
redirect("../admin/centers.php");
?>