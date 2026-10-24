<?php
require_once "../system/db.php";
require_once "../fpdf/fpdf.php";

$id = $_GET['payment_id'];

$p = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT p.*, s.name, s.registration_no
     FROM payments p
     JOIN students s ON s.id=p.student_id
     WHERE p.id='$id'"
));

$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Payment Receipt',0,1,'C');

$pdf->Ln(5);
$pdf->SetFont('Arial','',12);

$pdf->Cell(0,8,"Student: ".$p['name'],0,1);
$pdf->Cell(0,8,"Reg No: ".$p['registration_no'],0,1);
$pdf->Cell(0,8,"Amount: Rs ".$p['amount'],0,1);
$pdf->Cell(0,8,"Mode: ".$p['payment_mode'],0,1);
$pdf->Cell(0,8,"Date: ".$p['payment_date'],0,1);

$pdf->Ln(10);
$pdf->Cell(0,10,'Thank you for payment',0,1,'C');

$pdf->Output();
