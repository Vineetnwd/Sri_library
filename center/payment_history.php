<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* =============================
   FILTER INPUTS
============================= */
$sid   = $_GET['sid'] ?? '';
$type  = $_GET['type'] ?? '';
$mode  = $_GET['mode'] ?? '';
$from  = $_GET['from'] ?? '';
$to    = $_GET['to'] ?? '';

/* =============================
   BUILD QUERY
============================= */
$where = "WHERE p.center_id='$cid'";

if(!empty($sid)){
    $where .= " AND p.student_id='".(int)$sid."'";
}

if(!empty($type)){
    $where .= " AND p.payment_type='$type'";
}

if(!empty($mode)){
    $where .= " AND p.payment_mode='$mode'";
}

if(!empty($from)){
    $where .= " AND p.payment_date >= '$from'";
}

if(!empty($to)){
    $where .= " AND p.payment_date <= '$to'";
}

$query = "SELECT p.*, s.name, s.registration_no 
          FROM payments p 
          LEFT JOIN students s ON p.student_id = s.id 
          $where ORDER BY p.id DESC";

$q = mysqli_query($conn, $query);

if(!$q){
    die("SQL Error: ".mysqli_error($conn));
}

/* =============================
   TOTAL
============================= */
$total = 0;
while($row = mysqli_fetch_assoc($q)){
    $total += $row['amount'];
}
mysqli_data_seek($q, 0);

/* =============================
   STUDENTS LIST
============================= */
$students = mysqli_query($conn,
    "SELECT id,name FROM students WHERE center_id='$cid'"
);
?>

<?php include "layout/sidebar.php"; ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

<div class="container-fluid">

<h4 class="mb-4">
    <i class="fa fa-filter me-2 text-primary"></i> Payment History
</h4>

<!-- =============================
     FILTER FORM
============================= -->
<div class="card mb-4 shadow-sm border-0" style="border-radius:12px;">
<div class="card-body">

<form method="get" class="row g-3 align-items-end">

<div class="col-md-3">
    <label>Student</label>
    <select name="sid" class="form-select">
        <option value="">All Students</option>
        <?php while($s=mysqli_fetch_assoc($students)){ ?>
            <option value="<?= $s['id'] ?>" <?= ($sid==$s['id'])?'selected':'' ?>>
                <?= $s['name'] ?>
            </option>
        <?php } ?>
    </select>
</div>

<div class="col-md-2">
    <label>Type</label>
    <select name="type" class="form-select">
        <option value="">All</option>
        <option value="fee" <?= $type=='fee'?'selected':'' ?>>Fee</option>
        <option value="advance" <?= $type=='advance'?'selected':'' ?>>Advance</option>
    </select>
</div>

<div class="col-md-2">
    <label>Mode</label>
    <select name="mode" class="form-select">
        <option value="">All</option>
        <option value="cash" <?= $mode=='cash'?'selected':'' ?>>Cash</option>
        <option value="upi" <?= $mode=='upi'?'selected':'' ?>>UPI</option>
        <option value="bank" <?= $mode=='bank'?'selected':'' ?>>Bank</option>
    </select>
</div>

<div class="col-md-2">
    <label>From</label>
    <input type="date" name="from" value="<?= $from ?>" class="form-control">
</div>

<div class="col-md-2">
    <label>To</label>
    <input type="date" name="to" value="<?= $to ?>" class="form-control">
</div>

<div class="col-md-1 d-flex align-items-end">
    <button class="btn btn-primary w-100">
        <i class="fa fa-search"></i>
    </button>
</div>

</form>
</div>
</div>

<!-- =============================
     SUMMARY
============================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="card shadow-sm border-0 bg-success text-white px-4 py-2" style="border-radius:12px;">
        <small class="text-white-50 text-uppercase fw-bold">Total Collected</small>
        <h4 class="mb-0 fw-bold">₹ <?= number_format($total) ?></h4>
    </div>
    
    <a href="export_payment_history.php?student_id=<?= $sid ?>&from_date=<?= $from ?>&to_date=<?= $to ?>" 
       class="btn btn-outline-success fw-bold" style="border-radius:8px;">
       <i class="fas fa-file-excel me-2"></i>Export Ledger
    </a>
</div>

<!-- =============================
     TABLE
============================= -->
<div class="card shadow border-0" style="border-radius:12px;">
<div class="card-body p-0">
<div class="table-responsive">
<table id="historyTable" class="table table-hover table-sm align-middle mb-0 w-100">

<thead class="table-light text-muted">
<tr>
    <th class="text-uppercase" style="font-size:12px;">Date</th>
    <th class="text-uppercase" style="font-size:12px;">Student</th>
    <th class="text-uppercase text-end" style="font-size:12px;">Amount</th>
    <th class="text-uppercase text-center" style="font-size:12px;">Mode</th>
    <th class="text-uppercase text-center" style="font-size:12px;">Type</th>
    <th class="text-uppercase text-end" style="font-size:12px;">Actions</th>
</tr>
</thead>

<tbody>

<?php if(mysqli_num_rows($q) > 0){ ?>
    <?php while($p=mysqli_fetch_assoc($q)){ 
        $date_str = (!empty($p['payment_date']) && $p['payment_date'] != '0000-00-00') ? date('d M Y', strtotime($p['payment_date'])) : '-';
    ?>
    <tr>

        <td class="text-muted fw-semibold"><?= $date_str ?></td>
        
        <td>
            <?php if(!empty($p['name'])){ ?>
                <strong class="text-dark"><?= htmlspecialchars($p['name']) ?></strong><br>
                <small class="text-muted"><?= htmlspecialchars($p['registration_no'] ?? '-') ?></small>
            <?php } else { ?>
                <span class="text-muted fst-italic">Deleted Student</span>
            <?php } ?>
        </td>

        <td class="text-end text-success fw-bold fs-6">₹ <?= number_format($p['amount']) ?></td>

        <td class="text-center">
            <span class="badge rounded-pill bg-light text-dark border px-3 py-2">
                <?= strtoupper($p['payment_mode']) ?>
            </span>
        </td>

        <td class="text-center">
            <?php if($p['payment_type']=='fee'){ ?>
                <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success px-3 py-2">
                    FEE
                </span>
            <?php } else { ?>
                <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2">
                    ADVANCE
                </span>
            <?php } ?>
        </td>
        <td class="text-end">
            <a href="receipt.php?id=<?= $p['id'] ?>" 
               target="_blank"
               class="btn btn-sm btn-light border text-primary fw-semibold" style="border-radius:6px;">
               <i class="fa fa-print"></i>
            </a>
            <?php if (!isset($p['status']) || $p['status'] != 'cancelled'): ?>
                <a href="payment_cancel.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Cancel payment?')" title="Cancel Payment" style="border-radius:6px;">
                   <i class="fa fa-times"></i>
                </a>
            <?php else: ?>
                <span class="badge bg-danger text-white ms-1" style="font-size:10px;">CANCELLED</span>
            <?php endif; ?>
        </td>

    </tr>
    <?php } ?>
<?php } ?>

</tbody>

</table>
</div>
</div>
</div>

</div>

<?php include "layout/footer.php"; ?>

<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#historyTable').DataTable({
        dom: "<'row p-3 pb-0'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3 pt-0'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, 'desc']], // Sort by date descending
        language: {
            search: "",
            searchPlaceholder: "Search payments..."
        }
    });
});
</script>