<?php
require_once "../system/db.php";
require_once "../system/fpdf/fpdf.php";
require_once "../system/functions.php";

$cid = center_id();

/* Search functionality */
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where = "WHERE s.center_id='$cid'";

if($search){
    $where .= " AND (s.name LIKE '%$search%' OR s.registration_no LIKE '%$search%' OR s.mobile LIKE '%$search%')";
}

if($status_filter){
    $where .= " AND s.status='$status_filter'";
}

$q = mysqli_query($conn,
    "SELECT s.*, st.slot_name, se.seat_no
     FROM students s
     LEFT JOIN slots st ON st.id = s.slot_id
     LEFT JOIN seats se ON se.id = s.seat_id
     $where
     ORDER BY s.id DESC"
);

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, 'All Students Report', 0, 1, 'C');
        $this->Ln(5);
        
        $this->SetFillColor(240, 240, 240);
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(12, 8, 'Sr.No.', 1, 0, 'C', true);
        $this->Cell(22, 8, 'Reg No', 1, 0, 'C', true);
        $this->Cell(52, 8, 'Student Details', 1, 0, 'L', true);
        $this->Cell(25, 8, 'Contact', 1, 0, 'C', true);
        $this->Cell(15, 8, 'Seat', 1, 0, 'C', true);
        $this->Cell(30, 8, 'Slot', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Joining', 1, 0, 'C', true);
        $this->Cell(14, 8, 'Status', 1, 1, 'C', true);
    }
}

$pdf = new PDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);

$i = 1;
while($row = mysqli_fetch_assoc($q)){
    $name = substr($row['name'], 0, 30);
    $seat = $row['seat_no'] ?: 'N/A';
    $slot = $row['slot_name'] ?: 'N/A';
    if(strlen($slot) > 15) {
        $slot = substr($slot, 0, 15) . '...';
    }
    $joining = date('d M Y', strtotime($row['joining_date']));
    $status = ucfirst($row['status']);
    
    $pdf->Cell(12, 8, $i++, 1, 0, 'C');
    $pdf->Cell(22, 8, $row['registration_no'], 1, 0, 'C');
    $pdf->Cell(52, 8, $name, 1, 0, 'L');
    $pdf->Cell(25, 8, $row['mobile'], 1, 0, 'C');
    $pdf->Cell(15, 8, $seat, 1, 0, 'C');
    $pdf->Cell(30, 8, $slot, 1, 0, 'C');
    $pdf->Cell(20, 8, $joining, 1, 0, 'C');
    $pdf->Cell(14, 8, $status, 1, 1, 'C');
}

$pdf->Output('I', 'Students_Report.pdf');
?>
