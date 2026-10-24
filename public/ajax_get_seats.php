<?php
require_once "../system/db.php";
require_once "../system/functions.php";

header('Content-Type: text/html; charset=utf-8');

$cid = (int)($_GET['center'] ?? 0);
$slot_id = (int)($_GET['slot_id'] ?? 0);

if (!$cid || !$slot_id) {
    echo '<option value="">Select Slot First</option>';
    exit;
}

/* ---------------------------------
   Get selected slot time
--------------------------------- */
$slot_q = mysqli_query($conn,
    "SELECT start_time, end_time
     FROM slots
     WHERE id='$slot_id'
     AND center_id='$cid'"
);

$slot = mysqli_fetch_assoc($slot_q);
if (!$slot) {
    echo '<option value="">Invalid Slot</option>';
    exit;
}

$start = $slot['start_time'];
$end   = $slot['end_time'];

/* ---------------------------------
   Find occupied seats (active students with time overlap)
--------------------------------- */
$occupied = [];

$q = mysqli_query($conn,
    "SELECT s.seat_id
     FROM students s
     JOIN slots sl ON sl.id = s.slot_id
     WHERE s.center_id='$cid'
     AND s.status='active'
     AND s.seat_id IS NOT NULL
     AND s.seat_id > 0
     AND (
            sl.start_time < '$end'
        AND sl.end_time   > '$start'
         )"
);

if ($q) {
    while($r = mysqli_fetch_assoc($q)){
        $occupied[] = (int)$r['seat_id'];
    }
}

/* ---------------------------------
   Fetch all seats in this center
--------------------------------- */
$seats_q = mysqli_query($conn,
    "SELECT * FROM seats
     WHERE center_id='$cid'
     ORDER BY LENGTH(seat_no), seat_no"
);

echo '<option value="">-- Choose a Seat (Optional / Auto-Assign) --</option>';
$available_count = 0;

if ($seats_q) {
    while($s = mysqli_fetch_assoc($seats_q)){
        if(!in_array((int)$s['id'], $occupied)){
            $available_count++;
            echo "<option value='{$s['id']}'>Seat {$s['seat_no']} (Available)</option>";
        }
    }
}

if ($available_count === 0) {
    echo '<option value="" disabled>No vacant seats in this slot</option>';
}
