<?php
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();
$slot_id = $_GET['slot_id'];
$current_seat = $_GET['current_seat'] ?? 0;

/* ---------------------------------
   get selected slot time
--------------------------------- */
$slot = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT start_time, end_time
         FROM slots
         WHERE id='$slot_id'
         AND center_id='$cid'")
);

$start = $slot['start_time'];
$end   = $slot['end_time'];

/* ---------------------------------
   find occupied seats (time overlap)
--------------------------------- */
$occupied = [];

$q = mysqli_query($conn,
    "SELECT s.seat_id, sl.start_time, sl.end_time
     FROM students s
     JOIN slots sl ON sl.id = s.slot_id
     WHERE s.center_id='$cid'
     AND s.status='active'
     AND s.seat_id != '$current_seat'
     AND (
            sl.start_time < '$end'
        AND sl.end_time   > '$start'
         )"
);

while($r = mysqli_fetch_assoc($q)){
    $occupied[] = $r['seat_id'];
}

/* ---------------------------------
   fetch all seats
--------------------------------- */
$seats = mysqli_query($conn,
    "SELECT * FROM seats
     WHERE center_id='$cid'
     ORDER BY seat_no"
);

// echo '<option value="">Select Seat</option>';

while($s = mysqli_fetch_assoc($seats)){

    if(!in_array($s['id'], $occupied)){

        $selected = ($s['id'] == $current_seat) ? 'selected' : '';

        echo "<option value='{$s['id']}' $selected>
                {$s['seat_no']}
              </option>";
    }
}
