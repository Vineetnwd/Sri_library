<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

if(isset($_POST['save'])){
    mysqli_query($conn,
        "INSERT INTO fee_plans
        (center_id,plan_name,duration_month,amount)
        VALUES
        ('$cid','$_POST[name]','$_POST[months]','$_POST[amount]')"
    );

    set_flash("success","Fee plan added");
    redirect("center/fee_plans.php");
}
?>

<?php include "layout/sidebar.php"; ?>

<h4>Add Fee Plan</h4>

<form method="post" style="max-width:400px">

<input name="name" class="form-control mb-3" placeholder="Plan Name" required>
<input name="months" type="number" class="form-control mb-3" placeholder="Months" required>
<input name="amount" type="number" class="form-control mb-3" placeholder="Amount" required>

<button name="save" class="btn btn-success">Save</button>

</form>

<?php include "layout/footer.php"; ?>
