<?php
require_once "layout/header.php";
require_once "../system/db.php";
$cid = center_id();

$students = mysqli_query($conn,
    "SELECT * FROM students WHERE center_id='$cid'"
);

$plans = mysqli_query($conn,
    "SELECT * FROM fee_plans WHERE center_id='$cid'"
);
?>

<?php include "layout/sidebar.php"; ?>

<h4>Assign Fee</h4>

<form action="student_fee_save.php" method="post">

<select name="student_id" class="form-control mb-3" required>
    <option value="">Select Student</option>
    <?php while($s=mysqli_fetch_assoc($students)){ ?>
        <option value="<?= $s['id'] ?>">
            <?= $s['name'] ?> (<?= $s['registration_no'] ?>)
        </option>
    <?php } ?>
</select>

<select name="plan_id" class="form-control mb-3" required>
    <option value="">Select Plan</option>
    <?php while($p=mysqli_fetch_assoc($plans)){ ?>
        <option value="<?= $p['id'] ?>">
            <?= $p['plan_name'] ?> - ₹<?= $p['amount'] ?>
        </option>
    <?php } ?>
</select>

<button class="btn btn-primary">Assign Fee</button>

</form>

<?php include "layout/footer.php"; ?>
