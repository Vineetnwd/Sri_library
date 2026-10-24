<?php
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();
$id  = (int)$_GET['id'];

/* fetch payment */
$p = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM payments
         WHERE id='$id' AND center_id='$cid'")
);

if(!$p || $p['status']=='cancelled'){
    set_flash("danger","Invalid payment or already cancelled");
    redirect("center/reports.php");
    exit;
}

/* update payment */
mysqli_query($conn,
    "UPDATE payments SET status='cancelled'
     WHERE id='$id'"
);

/* reverse fee (if fee_id exists) */
if (!empty($p['fee_id'])) {
    mysqli_query($conn,
        "UPDATE student_fees SET
            paid_amount = paid_amount - '{$p['amount']}',
            due_amount  = due_amount  + '{$p['amount']}'
         WHERE id='{$p['fee_id']}'"
    );
}

set_flash("success","Payment cancelled successfully");
$referer = $_SERVER['HTTP_REFERER'] ?? "center/payment_history.php";
if (strpos($referer, 'console/') !== false) {
    $referer = substr($referer, strpos($referer, 'console/') + 8);
} else {
    $referer = "center/payment_history.php";
}
redirect($referer);
