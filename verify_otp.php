<?php
require_once "system/db.php";
require_once "system/functions.php";

$user_id = $_SESSION['reset_user'] ?? 0;

if(isset($_POST['verify'])){

    $otp = $_POST['otp'];

    $r = mysqli_fetch_assoc(
        mysqli_query($conn,
            "SELECT * FROM password_resets
             WHERE user_id='$user_id'
             AND otp='$otp'
             AND used='no'
             AND expires_at >= NOW()
             ORDER BY id DESC LIMIT 1")
    );

    if(!$r){
        set_flash("danger","Invalid or expired OTP");
    } else {
        mysqli_query($conn,
            "UPDATE password_resets SET used='yes' WHERE id='".$r['id']."'"
        );
        redirect("reset_password.php");
    }
}
?>

<form method="post" class="p-5" style="max-width:400px;margin:auto">
<h4>Verify OTP</h4>

<input type="text" name="otp" class="form-control mb-3"
 placeholder="Enter OTP">

<button name="verify" class="btn btn-success w-100">Verify</button>
</form>
