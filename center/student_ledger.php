<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    set_flash('danger', 'Invalid Student ID');
    redirect('center/students.php');
}

// Fetch student
$q_student = mysqli_query($conn, "SELECT s.*, p.plan_name, p.amount as plan_amount, p.duration_month as plan_duration FROM students s LEFT JOIN fee_plans p ON s.plan_id = p.id WHERE s.id='$id' AND s.center_id='$cid'");
$student = mysqli_fetch_assoc($q_student);
if (!$student) {
    set_flash('danger', 'Student not found');
    redirect('center/students.php');
}

// Fallback removed as query is fixed

// Ledger logic
$ledger = [];

// 1. Fee Assessments (Debits)
$plan_amount = (float)($student['plan_amount'] ?? 0);
$duration = (int)($student['plan_duration'] ?? 1);
if ($duration < 1) $duration = 1;

$join_date = new DateTime($student['joining_date']);
$current_date = new DateTime(date('Y-m-d'));

if ($plan_amount > 0) {
    $eval_date = clone $join_date;
    $cycle = 1;
    while ($eval_date <= $current_date) {
        $ledger[] = [
            'date' => $eval_date->format('Y-m-d 00:00:00'),
            'type' => 'debit',
            'amount' => $plan_amount,
            'description' => 'Fee Assessment - Cycle ' . $cycle,
            'ref' => '-'
        ];
        // Advance eval date by duration
        $eval_date->modify("+{$duration} months");
        $cycle++;
    }
}

// 2. Payments (Credits)
$q_payments = mysqli_query($conn, "SELECT * FROM payments WHERE student_id='$id' AND (status != 'cancelled' OR status IS NULL)");
while ($p = mysqli_fetch_assoc($q_payments)) {
    $ledger[] = [
        'date' => $p['payment_date'],
        'type' => 'credit',
        'amount' => (float)$p['amount'],
        'description' => 'Payment Received - ' . ucfirst($p['payment_mode']),
        'ref' => 'Receipt #' . $p['id'],
        'payment_id' => $p['id']
    ];
}

// 3. Sort Chronologically
usort($ledger, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']); // ASC
});

// Calculate running balance and totals
$total_debit = 0;
$total_credit = 0;
$balance = 0;

foreach ($ledger as $k => $row) {
    if ($row['type'] == 'debit') {
        $total_debit += $row['amount'];
        $balance += $row['amount'];
    } else {
        $total_credit += $row['amount'];
        $balance -= $row['amount'];
    }
    $ledger[$k]['balance'] = $balance;
}
?>

<?php include "layout/sidebar.php"; ?>

<!-- DataTables CSS & Buttons -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">
            <i class="fas fa-book me-2 text-primary"></i> Student Ledger
        </h4>
        <div class="d-flex gap-2">
            <div id="export-buttons-container"></div>
            <a href="students.php" class="btn btn-light border btn-sm fw-semibold shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Students
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Student Info -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-body">
                    <h5 class="fw-bold mb-3 text-dark"><?= htmlspecialchars($student['name']) ?></h5>
                    <div class="row g-3">
                        <div class="col-6 col-sm-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;">Reg No</div>
                            <div class="fw-semibold text-dark" style="font-size:14px;"><?= htmlspecialchars($student['registration_no'] ?: 'N/A') ?></div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;">Joining Date</div>
                            <div class="fw-semibold text-dark" style="font-size:14px;"><?= date('d M Y', strtotime($student['joining_date'])) ?></div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="text-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;">Fee Plan</div>
                            <div class="fw-semibold text-primary" style="font-size:14px;">
                                <?= htmlspecialchars($student['plan_name'] ?: 'None') ?>
                                <?= $student['plan_amount'] ? '(₹'.number_format($student['plan_amount']).')' : '' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Balance Summary -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 bg-primary bg-gradient text-white" style="border-radius:12px;">
                <div class="card-body d-flex flex-column justify-content-center align-items-center text-center">
                    <div style="font-size:12px;text-transform:uppercase;font-weight:600;opacity:0.9;">Total Pending Dues</div>
                    <div class="fw-bold display-5 mt-1">₹<?= number_format(max(0, $balance), 0) ?></div>
                    <?php if($balance < 0): ?>
                        <div class="mt-2 badge bg-white text-primary rounded-pill">Advance Payment: ₹<?= number_format(abs($balance), 0) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-header bg-white border-bottom py-3">
            <span class="fw-semibold"><i class="fas fa-list-alt me-2 text-info"></i>Transaction History</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 w-100 datatable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 text-uppercase text-muted" style="font-size:11px;">Date</th>
                        <th class="text-uppercase text-muted" style="font-size:11px;">Description</th>
                        <th class="text-uppercase text-muted" style="font-size:11px;">Ref No</th>
                        <th class="text-end text-uppercase text-muted" style="font-size:11px;">Charge (₹)</th>
                        <th class="text-end text-uppercase text-muted" style="font-size:11px;">Payment (₹)</th>
                        <th class="text-end text-uppercase text-muted pe-4" style="font-size:11px;">Balance (₹)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($ledger as $r){ ?>
                    <tr>
                        <td class="ps-4 text-muted" style="font-size:12px;" data-order="<?= $r['date'] ?>">
                            <?= date('d M Y', strtotime($r['date'])) ?>
                        </td>
                        <td>
                            <div class="fw-bold text-dark" style="font-size:13px;"><?= htmlspecialchars($r['description']) ?></div>
                        </td>
                        <td>
                            <?php if($r['ref'] != '-'): ?>
                                <span class="badge rounded-pill bg-light text-dark border" style="font-size:11px;"><?= htmlspecialchars($r['ref']) ?></span>
                                <?php if($r['type'] == 'credit' && !empty($r['payment_id'])): ?>
                                    <a href="payment_cancel.php?id=<?= $r['payment_id'] ?>" class="text-danger ms-2" onclick="return confirm('Cancel this payment?')" title="Cancel Payment">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-danger fw-bold" style="font-size:13px;">
                            <?= $r['type'] == 'debit' ? number_format($r['amount'], 0) : '-' ?>
                        </td>
                        <td class="text-end text-success fw-bold" style="font-size:13px;">
                            <?= $r['type'] == 'credit' ? number_format($r['amount'], 0) : '-' ?>
                        </td>
                        <td class="text-end text-primary fw-bold pe-4" style="font-size:13px;">
                            <?= number_format($r['balance'], 0) ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
                <tfoot class="bg-light fw-bold border-top">
                    <tr>
                        <td class="ps-4 border-0"></td>
                        <td class="border-0"></td>
                        <td class="text-end pe-4 text-nowrap border-0 align-middle" style="font-size:13px;">
                            <span class="text-muted text-uppercase me-2" style="font-size:11px;">Totals:</span> 
                        </td>
                        <td class="text-end text-danger border-0 align-middle" style="font-size:14px;">₹<?= number_format($total_debit, 0) ?></td>
                        <td class="text-end text-success border-0 align-middle" style="font-size:14px;">₹<?= number_format($total_credit, 0) ?></td>
                        <td class="text-end text-primary pe-4 border-0 align-middle" style="font-size:14px;">₹<?= number_format($balance, 0) ?></td>
                    </tr>
                </tfoot>
            </table>
            </div>
        </div>
    </div>
</div>

<?php include "layout/footer.php"; ?>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- DataTables Buttons -->
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('.datatable').DataTable({
        dom: "<'row p-3 pb-0'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3 pt-0'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        pageLength: 25,
        lengthMenu: [25, 50, 100, -1],
        order: [], // Keeps chronological order
        language: { search: "", searchPlaceholder: "Search transactions..." },
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm fw-semibold shadow-sm',
                title: 'Student_Ledger_<?= htmlspecialchars($student['registration_no'] ?: $id) ?>',
                footer: true
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> Export PDF',
                className: 'btn btn-danger btn-sm fw-semibold shadow-sm',
                title: 'Student Ledger - <?= htmlspecialchars($student['name']) ?>',
                footer: true,
                customize: function (doc) {
                    doc.styles.tableHeader.alignment = 'left';
                    doc.content[1].table.widths = ['15%', '30%', '15%', '13%', '12%', '15%'];
                }
            }
        ]
    });
    table.buttons().container().appendTo('#export-buttons-container');
});
</script>
