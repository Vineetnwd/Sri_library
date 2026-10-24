<?php
require_once "../../system/db.php";
require_once "../../system/fpdf/fpdf.php";
require_once "../../system/functions.php";

$cid = center_id();
$fee_id = $_GET['fee_id'];

$f = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT sf.*, s.name, s.registration_no, fp.plan_name
         FROM student_fees sf
         JOIN students s ON s.id = sf.student_id
         JOIN fee_plans fp ON fp.id = sf.plan_id
         WHERE sf.id='$fee_id' AND sf.center_id='$cid'"
    )
);

$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetLineWidth(0.8);
$pdf->Rect(5,5,200,287);

$pdf->SetFont("Arial","B",16);
$pdf->Cell(0,10,"FEE INVOICE",0,1,"C");

$pdf->Ln(5);

$pdf->SetFont("Arial","",11);
$pdf->Cell(100,8,"Student: ".$f['name'],0,0);
$pdf->Cell(0,8,"Reg No: ".$f['registration_no'],0,1);

$pdf->Ln(5);

$pdf->Cell(100,8,"Plan: ".$f['plan_name'],0,0);
$pdf->Cell(0,8,"Duration: ".$f['start_date']." to ".$f['end_date'],0,1);

$pdf->Ln(5);

$pdf->Cell(100,8,"Total Fee: Rs ".$f['total_fee'],0,0);
$pdf->Cell(0,8,"Paid: Rs ".$f['paid_amount'],0,1);

$pdf->Ln(5);

$pdf->Cell(100,8,"Due Amount: Rs ".$f['due_amount'],0,1);

$pdf->Ln(20);
$pdf->Cell(0,8,"Authorized Signature",0,1,"R");

$pdf->Output();
