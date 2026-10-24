<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
session_start();

require_once "../system/db.php";

/* CENTER */
$cid = $_SESSION['center_id'] ?? 0;

/* FILTERS */
$student_id = $_GET['student_id'] ?? '';
$from_date  = $_GET['from_date'] ?? '';
$to_date    = $_GET['to_date'] ?? '';

$where = "WHERE p.center_id='$cid'";

/* APPLY FILTERS */
if(!empty($student_id)){
    $where .= " AND p.student_id='$student_id'";
}

if(!empty($from_date) && !empty($to_date)){
    $where .= " AND DATE(p.payment_date) BETWEEN '$from_date' AND '$to_date'";
}

/* QUERY (ASC for ledger flow) */
$q = mysqli_query($conn,"
    SELECT p.*, s.name, s.registration_no
    FROM payments p
    LEFT JOIN students s ON s.id = p.student_id
    $where
    ORDER BY p.payment_date ASC, p.id ASC
");

/* CLEAR OUTPUT */
ob_clean();

/* EXCEL HEADERS */
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=payment_history_".date('Y-m-d').".xls");
header("Pragma: no-cache");
header("Expires: 0");

/* TABLE START */
echo "<table border='1'>";

/* HEADER */
echo "<tr>
<th>Date</th>
<th>Reg No</th>
<th>Student Name</th>
<th>Particular</th>
<th>Debit</th>
<th>Credit</th>
<th>Balance</th>
<th>Mode</th>
<th>Remark</th>
</tr>";

$balance = 0;
$total_credit = 0;
$total_debit  = 0;

/* LOOP */
while($row = mysqli_fetch_assoc($q)){

    $amount = (float)($row['amount'] ?? 0);

    /* DEFINE TYPE */
    $type = strtolower($row['payment_type'] ?? 'fee');

    $debit = 0;
    $credit = 0;

    // CREDIT → Fee Received
    if($type == 'fee' || $type == 'payment'){
        $credit = $amount;
        $balance += $amount;
        $total_credit += $amount;
    }
    // DEBIT → Refund / Expense
    else{
        $debit = $amount;
        $balance -= $amount;
        $total_debit += $amount;
    }

    echo "<tr>
        <td>".(!empty($row['payment_date']) ? date('d-m-Y', strtotime($row['payment_date'])) : '')."</td>
        <td>".htmlspecialchars($row['registration_no'] ?? '')."</td>
        <td>".htmlspecialchars($row['name'] ?? '')."</td>
        <td>".htmlspecialchars($row['payment_type'] ?? '')."</td>
        <td>".($debit ? $debit : '')."</td>
        <td>".($credit ? $credit : '')."</td>
        <td>".$balance."</td>
        <td>".htmlspecialchars($row['payment_mode'] ?? '')."</td>
        <td>".htmlspecialchars($row['remark'] ?? '')."</td>
    </tr>";
}

/* TOTAL ROW */
echo "<tr>
    <td colspan='4'><b>Total</b></td>
    <td><b>$total_debit</b></td>
    <td><b>$total_credit</b></td>
    <td><b>$balance</b></td>
    <td colspan='2'></td>
</tr>";

echo "</table>";

exit;