<?php
require_once "layout/header.php";
require_once "../system/db.php";
$cid = center_id();

if(isset($_POST['save'])){
    mysqli_query($conn,
        "REPLACE INTO settings
         (center_id,wa_token,wa_phone)
         VALUES
         ('$cid','$_POST[token]','$_POST[phone]')"
    );
    set_flash("success","WhatsApp settings saved");
}
?>

<?php include "layout/sidebar.php"; ?>

<h4>WhatsApp Settings</h4>

<form method="post">

<input type="text" name="token" class="form-control mb-3"
 placeholder="WhatsApp API Token">

<input type="text" name="phone" class="form-control mb-3"
 placeholder="Phone Number ID">

<button class="btn btn-success">Save</button>

</form>

<?php include "layout/footer.php"; ?>
