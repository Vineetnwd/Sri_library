<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

$students = mysqli_query($conn,
    "SELECT * FROM students WHERE center_id='$cid' AND status='active'"
);

$plans = mysqli_query($conn,
    "SELECT * FROM fee_plans WHERE center_id='$cid'"
);

if(isset($_POST['assign'])){

    $sid = $_POST['student_id'];
    $pid = $_POST['plan_id'];

    $plan = mysqli_fetch_assoc(
        mysqli_query($conn,"SELECT * FROM fee_plans WHERE id='$pid'")
    );

    $start = date('Y-m-d');
    $end   = date('Y-m-d', strtotime("+{$plan['duration_month']} month"));

    mysqli_query($conn,
        "INSERT INTO student_fees
        (center_id,student_id,plan_id,total_fee,paid_amount,due_amount,start_date,end_date)
        VALUES
        ('$cid','$sid','$pid',
         '{$plan['amount']}',0,'{$plan['amount']}',
         '$start','$end')"
    );

    set_flash("success","Fee assigned");
    redirect("center/student_fees.php");
}
?>

<?php include "layout/sidebar.php"; ?>

<h4>Assign Fee</h4>

<form method="post" style="max-width:500px">

<select name="student_id" class="form-control mb-3" required>
<option value="">Select Student</option>
<?php while($s=mysqli_fetch_assoc($students)){ ?>
<option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
<?php } ?>
</select>

<select name="plan_id" class="form-control mb-3" required>
<option value="">Select Plan</option>
<?php while($p=mysqli_fetch_assoc($plans)){ ?>
<option value="<?= $p['id'] ?>">
<?= $p['plan_name'] ?> – ₹<?= $p['amount'] ?>
</option>
<?php } ?>
</select>

<button name="assign" class="btn btn-success">Assign</button>

</form>

<?php include "layout/footer.php"; ?>
