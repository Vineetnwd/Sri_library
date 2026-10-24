<?php
require_once "../../system/db.php";
require_once "../../system/fpdf/fpdf.php";
require_once "../../system/functions.php";

$cid = center_id();
$payment_id = $_GET['id'];

$p = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT p.*, s.name, s.registration_no
         FROM payments p
         JOIN students s ON s.id = p.student_id
         WHERE p.id='$payment_id' AND p.center_id='$cid'"
    )
);

$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetLineWidth(0.8);
$pdf->Rect(5,5,200,287);

$pdf->SetFont("Arial","B",16);
$pdf->Cell(0,10,"SELF STUDY CENTER PAYMENT RECEIPT",0,1,"C");

$pdf->Ln(5);

$pdf->SetFont("Arial","",11);
$pdf->Cell(100,8,"Receipt No: ".$p['id'],0,0);
$pdf->Cell(0,8,"Date: ".$p['payment_date'],0,1);

$pdf->Ln(5);

$pdf->Cell(100,8,"Student Name: ".$p['name'],0,0);
$pdf->Cell(0,8,"Reg No: ".$p['registration_no'],0,1);

$pdf->Ln(5);

$pdf->Cell(100,8,"Payment Mode: ".$p['payment_mode'],0,0);
$pdf->Cell(0,8,"Amount: Rs ".$p['amount'],0,1);

$pdf->Ln(20);

$pdf->Cell(0,8,"Authorized Signature",0,1,"R");

$pdf->Output();
