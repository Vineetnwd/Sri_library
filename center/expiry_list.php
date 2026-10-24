<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

$q = mysqli_query($conn,
    "SELECT s.name, f.end_date
     FROM student_fees f
     JOIN students s ON s.id=f.student_id
     WHERE f.center_id='$cid'
     AND f.status='running'
     AND f.end_date < CURDATE()"
);
?>

<?php include "layout/sidebar.php"; ?>

<h4>Expired Subscriptions</h4>

<table class="table table-bordered">

<tr class="table-dark">
    <th>Student</th>
    <th>Expiry Date</th>
</tr>

<?php while($r=mysqli_fetch_assoc($q)){ ?>
<tr>
    <td><?= $r['name'] ?></td>
    <td class="text-danger"><?= $r['end_date'] ?></td>
</tr>
<?php } ?>

</table>

<?php include "layout/footer.php"; ?>
