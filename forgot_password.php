<?php
require_once "system/db.php";
require_once "system/functions.php";

if(isset($_POST['send'])){

    $username = clean($_POST['username']);

    $user = mysqli_fetch_assoc(
        mysqli_query($conn,
            "SELECT * FROM users WHERE username='$username' AND role='student'")
    );

    if(!$user){
        set_flash("danger","Student not found");
    } else {

        $otp = rand(100000,999999);
        $expire = date('Y-m-d H:i:s', strtotime("+5 minutes"));

        mysqli_query($conn,
            "INSERT INTO password_resets
            (user_id,otp,expires_at)
            VALUES
            ('".$user['id']."','$otp','$expire')"
        );

        // later send OTP via WhatsApp/SMS
        // send_whatsapp($mobile, "Your OTP is $otp");

        $_SESSION['reset_user'] = $user['id'];

        redirect("verify_otp.php");
    }
}
?>

<form method="post" class="p-5" style="max-width:400px;margin:auto">
<h4>Forgot Password</h4>

<input type="text" name="username" class="form-control mb-3"
 placeholder="Registration No" required>

<button name="send" class="btn btn-primary w-100">Send OTP</button>
</form>
