<?php
require_once "layout/header.php";

$q = mysqli_query($conn,
    "SELECT * FROM attendance 
     WHERE student_id='".$student['id']."'
     ORDER BY attendance_date DESC"
);
?>

<?php include "layout/sidebar.php"; ?>

<h4>My Attendance</h4>

<table class="table table-bordered">
<tr>
    <th>Date</th>
    <th>Status</th>
</tr>

<?php while($a=mysqli_fetch_assoc($q)){ ?>
<tr>
    <td><?= $a['attendance_date'] ?></td>
    <td>
        <span class="badge bg-<?= $a['status']=='present'?'success':'danger' ?>">
            <?= ucfirst($a['status']) ?>
        </span>
    </td>
</tr>
<?php } ?>

</table>

<?php include "layout/footer.php"; ?>
