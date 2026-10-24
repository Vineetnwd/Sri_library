<?php
require_once "../system/db.php";

/* select center */
$center_id = $_GET['center'] ?? 1;

/* current slot */
$slot_id = $_GET['slot'] ?? 1;

/* all seats */
$seats = mysqli_query($conn,
    "SELECT * FROM seats WHERE center_id='$center_id'"
);

/* occupied seats in this slot */
$occupied = [];

$q = mysqli_query($conn,
    "SELECT seat_id FROM students
     WHERE center_id='$center_id'
     AND slot_id='$slot_id'
     AND status='active'"
);

while($r=mysqli_fetch_assoc($q)){
    $occupied[] = $r['seat_id'];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Seat Availability</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background:#111; color:white; }
.seat {
    width:120px;
    height:70px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
    font-size:18px;
}
.available { background:#16a34a; }
.occupied { background:#dc2626; }
</style>

<meta http-equiv="refresh" content="10">
</head>

<body>

<div class="container text-center mt-4">

<h2>Seat Availability</h2>
<p class="text-secondary">Auto refresh every 10 seconds</p>

<div class="row g-3 justify-content-center mt-4">

<?php while($s=mysqli_fetch_assoc($seats)){ ?>

<div class="col-auto">
    <div class="seat <?= in_array($s['id'],$occupied)?'occupied':'available' ?>">
        <?= $s['seat_no'] ?>
    </div>
</div>

<?php } ?>

</div>

</div>

</body>
</html>
