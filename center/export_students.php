<?php
ob_start(); // 🔥 prevent accidental output

session_start();
require_once "../system/db.php";

$cid = $_SESSION['center_id'] ?? 0;

/* FILTERS */
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where = "WHERE s.center_id='$cid'";

if($search){
    $where .= " AND (s.name LIKE '%$search%' 
                 OR s.registration_no LIKE '%$search%' 
                 OR s.mobile LIKE '%$search%')";
}

if($status_filter){
    $where .= " AND s.status='$status_filter'";
}

/* QUERY */
$q = mysqli_query($conn,"
    SELECT s.*, st.slot_name, se.seat_no
    FROM students s
    LEFT JOIN slots st ON st.id = s.slot_id
    LEFT JOIN seats se ON se.id = s.seat_id
    $where
");

/* 🔥 CLEAR OUTPUT BUFFER */
ob_clean();

/* HEADERS */
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=students_".date('Y-m-d').".xls");
header("Pragma: no-cache");
header("Expires: 0");

/* TABLE */
echo "<table border='1'>";

echo "<tr>
<th>Reg No</th>
<th>Name</th>
<th>Mobile</th>
<th>Email</th>
<th>Seat</th>
<th>Slot</th>
<th>Joining Date</th>
<th>Status</th>
<th>Address</th>
</tr>";

while($row = mysqli_fetch_assoc($q)){

    echo "<tr>
        <td>".htmlspecialchars($row['registration_no'] ?? '')."</td>
        <td>".htmlspecialchars($row['name'] ?? '')."</td>
        <td>".htmlspecialchars($row['mobile'] ?? '')."</td>
        <td>".htmlspecialchars($row['email'] ?? '')."</td>
        <td>".htmlspecialchars($row['seat_no'] ?? '')."</td>
        <td>".htmlspecialchars($row['slot_name'] ?? '')."</td>
        <td>".($row['joining_date'] ? date('d-m-Y', strtotime($row['joining_date'])) : '')."</td>
        <td>".htmlspecialchars($row['status'] ?? '')."</td>
        <td>".htmlspecialchars($row['address'] ?? '')."</td>
    </tr>";
}

echo "</table>";

exit;