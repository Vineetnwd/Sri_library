<?php
require '../vendor/autoload.php';
require_once "../system/db.php";
require_once "../system/functions.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$cid = center_id();

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

/* FETCH DATA */
$q = mysqli_query($conn,
    "SELECT p.*, s.name, s.registration_no
     FROM payments p
     JOIN students s ON s.id = p.student_id
     WHERE p.center_id='$cid'
     AND (p.status != 'cancelled' OR p.status IS NULL)
     AND DATE(p.payment_date) BETWEEN '$from' AND '$to'
     ORDER BY p.payment_date DESC"
);

/* CREATE EXCEL */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Revenue Report');

/* HEADER */
$headers = ['Date','Receipt','Student','Reg No','Amount','Mode','Type'];

$col = 'A';
foreach($headers as $h){
    $sheet->setCellValue($col.'1', $h);
    $col++;
}

/* STYLE HEADER */
$sheet->getStyle('A1:G1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '28A745']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER
    ]
]);

/* DATA */
$row = 2;

while($r = mysqli_fetch_assoc($q)){

    $sheet->setCellValue('A'.$row, date('d-m-Y', strtotime($r['payment_date'])));
    $sheet->setCellValue('B'.$row, $r['receipt_no']);
    $sheet->setCellValue('C'.$row, $r['name']);
    $sheet->setCellValue('D'.$row, $r['registration_no']);
    $sheet->setCellValue('E'.$row, $r['amount']);
    $sheet->setCellValue('F'.$row, ucfirst($r['payment_mode']));
    $sheet->setCellValue('G'.$row, ucfirst($r['payment_type']));

    $row++;
}

/* AUTO WIDTH */
foreach(range('A','G') as $col){
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

/* DOWNLOAD */
$filename = "Revenue_Report_".date('Ymd').".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;