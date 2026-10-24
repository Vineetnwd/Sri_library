<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

$slots = mysqli_query($conn,
    "SELECT * FROM slots 
     WHERE center_id='$cid'
     ORDER BY start_time ASC"
);
?>

<?php include "layout/sidebar.php"; ?>

<h4 class="mb-3">Time Slots</h4>

<a href="slot_add.php" class="btn btn-primary mb-3">
    + Add Slot
</a>

<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
    <th>#</th>
    <th>Slot Name</th>
    <th>Time</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>

<tbody>
<?php $i=1; while($s=mysqli_fetch_assoc($slots)){ ?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= $s['slot_name'] ?></td>
    <td>
        <?= date("h:i A", strtotime($s['start_time'])) ?>
        -
        <?= date("h:i A", strtotime($s['end_time'])) ?>
    </td>
    <td>
        <span class="badge bg-<?= $s['status']=='active'?'success':'secondary' ?>">
            <?= ucfirst($s['status']) ?>
        </span>
    </td>
    <td>
        <a href="slot_edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-warning">Edit</a>

        <a href="slot_delete.php?id=<?= $s['id'] ?>"
           onclick="return confirm('Delete this slot?')"
           class="btn btn-sm btn-danger">
           Delete
        </a>
    </td>
</tr>
<?php } ?>
</tbody>
</table>

<?php include "layout/footer.php"; ?>
