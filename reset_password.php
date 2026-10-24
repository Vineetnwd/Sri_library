<?php
require_once "system/db.php";
require_once "system/functions.php";

$user_id = $_SESSION['reset_user'];

if(isset($_POST['reset'])){

    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    mysqli_query($conn,
        "UPDATE users SET password='$pass' WHERE id='$user_id'"
    );

    unset($_SESSION['reset_user']);

    set_flash("success","Password reset successfully");
    redirect("login.php");
}
?>

<form method="post" class="p-5" style="max-width:400px;margin:auto">
<h4>Reset Password</h4>

<input type="password" name="password" class="form-control mb-3"
 placeholder="New Password">

<button name="reset" class="btn btn-primary w-100">Update</button>
</form>
