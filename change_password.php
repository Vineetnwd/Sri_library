<?php
require_once "layout/header.php";

if(isset($_POST['save'])){

    $old = $_POST['old'];
    $new = $_POST['new'];

    $user = mysqli_fetch_assoc(
        mysqli_query($conn,"SELECT * FROM users WHERE id='".$_SESSION['user_id']."'")
    );

    if(!password_verify($old, $user['password'])){
        set_flash("danger","Old password incorrect");
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);

        mysqli_query($conn,
            "UPDATE users SET password='$hash'
             WHERE id='".$_SESSION['user_id']."'"
        );

        set_flash("success","Password changed successfully");
    }
}
?>

<?php include "layout/sidebar.php"; ?>

<h4>Change Password</h4>

<form method="post" style="max-width:400px">

<input type="password" name="old" class="form-control mb-3"
 placeholder="Old Password" required>

<input type="password" name="new" class="form-control mb-3"
 placeholder="New Password" required>

<button class="btn btn-primary">Update Password</button>

</form>

<?php include "layout/footer.php"; ?>
