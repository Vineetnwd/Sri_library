<?php
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();
$seat_id = (int)($_GET['id'] ?? 0);

if(!$seat_id){ http_response_code(400); echo json_encode(['error'=>'Invalid seat']); exit; }

/* Seat Info */
$seat = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM seats WHERE id='$seat_id' AND center_id='$cid'"));

if(!$seat){ http_response_code(404); echo json_encode(['error'=>'Seat not found']); exit; }

/* All students on this seat */
$students = [];
$q = mysqli_query($conn,
    "SELECT st.id, st.name, st.registration_no, st.mobile, st.joining_date, st.status,
            sl.slot_name, sl.start_time, sl.end_time,
            fp.plan_name, fp.amount as plan_amount,
            (SELECT IFNULL(SUM(amount),0) FROM payments WHERE student_id=st.id AND (status != 'cancelled' OR status IS NULL)) as total_paid
     FROM students st
     LEFT JOIN slots sl ON st.slot_id = sl.id
     LEFT JOIN fee_plans fp ON st.plan_id = fp.id
     WHERE st.seat_id='$seat_id' AND st.center_id='$cid' AND st.status='active'
     ORDER BY sl.start_time"
);

while($r = mysqli_fetch_assoc($q)){
    $students[] = $r;
}

header('Content-Type: application/json');
echo json_encode([
    'seat'     => $seat,
    'students' => $students
]);
