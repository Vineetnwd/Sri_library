<?php
require_once "functions.php";
require_once "db.php";

if (user_role() == 'admin') {

    $cid = center_id();

    $center = mysqli_fetch_assoc(
        mysqli_query($conn,
            "SELECT expiry_date,status 
             FROM centers WHERE id='$cid'"
        )
    );

    if ($center['status'] != 'active') {
        echo "<h2>Center Disabled</h2>";
        exit;
    }

    if (date('Y-m-d') > $center['expiry_date']) {
        redirect("../subscription_expired.php");
    }
}
