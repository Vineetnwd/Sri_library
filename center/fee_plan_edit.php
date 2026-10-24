<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();
$id  = $_GET['id'] ?? 0;

$plan = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM fee_plans
         WHERE id='$id' AND center_id='$cid'")
);

if(!$plan){
    set_flash("danger","Plan not found");
    redirect("center/fee_plans.php");
}

if(isset($_POST['update'])){

    $name    = $_POST['plan_name'];
    $months  = $_POST['duration_month'];
    $amount  = $_POST['amount'];

    mysqli_query($conn,
        "UPDATE fee_plans SET
         plan_name='$name',
         duration_month='$months',
         amount='$amount'
         WHERE id='$id'"
    );

    set_flash("success","Fee plan updated");
    redirect("center/fee_plans.php");
}
?>

<?php include "layout/sidebar.php"; ?>

<h4>Edit Fee Plan</h4>

<form method="post" style="max-width:400px">

<input name="plan_name"
       value="<?= $plan['plan_name'] ?>"
       class="form-control mb-3"
       required>

<input type="number" name="duration_month"
       value="<?= $plan['duration_month'] ?>"
       class="form-control mb-3"
       required>

<input type="number" name="amount"
       value="<?= $plan['amount'] ?>"
       class="form-control mb-3"
       required>

<button name="update" class="btn btn-primary">
    Update
</button>

<a href="fee_plans.php" class="btn btn-secondary ms-2">
    Back
</a>

</form>

<?php include "layout/footer.php"; ?>
