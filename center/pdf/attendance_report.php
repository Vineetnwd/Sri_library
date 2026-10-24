<?php
require_once "../../system/db.php";
require_once "../../system/fpdf/fpdf.php";
require_once "../../system/functions.php";

$cid = center_id();
$student_id = $_GET['student_id'];
$month = $_GET['month']; // YYYY-MM

$q = mysqli_query($conn,
    "SELECT * FROM attendance
     WHERE center_id='$cid'
     AND student_id='$student_id'
     AND attendance_date LIKE '$month%'"
);

$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetLineWidth(0.8);
$pdf->Rect(5,5,200,287);

$pdf->SetFont("Arial","B",14);
$pdf->Cell(0,10,"MONTHLY ATTENDANCE REPORT",0,1,"C");

$pdf->Ln(5);

$pdf->SetFont("Arial","",11);
$pdf->Cell(0,8,"Month: ".$month,0,1);

$pdf->Ln(5);

$pdf->Cell(60,8,"Date",1);
$pdf->Cell(60,8,"Status",1,1);

while($a=mysqli_fetch_assoc($q)){
    $pdf->Cell(60,8,$a['attendance_date'],1);
    $pdf->Cell(60,8,ucfirst($a['status']),1,1);
}

$pdf->Output();
