<?php
require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

// Delete Expense
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM expenses WHERE id='$id' AND center_id='$cid'");
    set_flash('success', 'Expense deleted successfully!');
    redirect('center/expenses.php');
}

// Filters
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

$where = "WHERE e.center_id='$cid'";
if($from_date){
    $where .= " AND e.expense_date >= '$from_date'";
}
if($to_date){
    $where .= " AND e.expense_date <= '$to_date'";
}

// Fetch Expenses Data
$query = "
    SELECT e.*, c.name as category_name
    FROM expenses e
    LEFT JOIN expense_categories c ON e.category_id = c.id
    $where
    ORDER BY e.expense_date DESC, e.id DESC
";
$q = mysqli_query($conn, $query);

$expenses = [];
$total_filtered = 0;

while($r = mysqli_fetch_assoc($q)){
    $expenses[] = $r;
    $total_filtered += (float)$r['amount'];
}

// Global Stats
$this_month_str = date('Y-m');
$q_stats = mysqli_query($conn, "SELECT SUM(amount) as all_time, SUM(CASE WHEN expense_date LIKE '$this_month_str%' THEN amount ELSE 0 END) as this_month FROM expenses WHERE center_id='$cid'");
$stats = mysqli_fetch_assoc($q_stats);
$total_all_time = (float)$stats['all_time'];
$total_this_month = (float)$stats['this_month'];
?>

<?php include "layout/sidebar.php"; ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div class="container-fluid">
    <?= get_flash() ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">
            <i class="fa fa-file-invoice-dollar me-2 text-danger"></i> Expenses
        </h4>
        <a href="expense_add.php" class="btn btn-primary btn-sm fw-semibold">
            <i class="fas fa-plus me-1"></i> Record Expense
        </a>
    </div>

    <!-- FILTER -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius:12px;">
        <div class="card-body py-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-muted mb-1" style="font-size:11px;font-weight:600;text-transform:uppercase;">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($from_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted mb-1" style="font-size:11px;font-weight:600;text-transform:uppercase;">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($to_date) ?>">
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary btn-sm px-3 fw-semibold"><i class="fas fa-filter me-1"></i> Filter</button>
                    <?php if($from_date || $to_date){ ?>
                        <a href="expenses.php" class="btn btn-light border btn-sm px-3 text-muted fw-semibold">Clear</a>
                    <?php } ?>
                </div>
                <?php if($from_date || $to_date){ ?>
                <div class="col-md-2 text-end border-start">
                    <div class="text-muted" style="font-size:11px;font-weight:600;text-transform:uppercase;">Filtered Total</div>
                    <div class="fw-bold fs-5 text-danger">₹ <?= number_format($total_filtered, 0) ?></div>
                </div>
                <?php } ?>
            </form>
        </div>
    </div>

    <!-- STATS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                        <i class="fa fa-calendar-alt" style="font-size:14px;"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">This Month</div>
                        <div class="fw-bold fs-5 lh-1 text-danger">₹ <?= number_format($total_this_month, 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                        <i class="fa fa-chart-bar" style="font-size:14px;"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">All Time Spent</div>
                        <div class="fw-bold fs-5 lh-1">₹ <?= number_format($total_all_time, 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0;">
                        <i class="fa fa-tags" style="font-size:14px;"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Expense Categories</div>
                        <a href="expense_categories.php" class="btn btn-link btn-sm p-0 fw-bold text-decoration-none" style="font-size:14px;">Manage Categories →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLE -->
    <div class="card shadow border-0" style="border-radius:12px;">
        <div class="card-header bg-white border-bottom py-3">
            <span class="fw-semibold"><i class="fa fa-list me-2 text-danger"></i>Expense History</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
            <table id="expenseTable" class="table table-hover table-sm align-middle mb-0 w-100">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 text-uppercase text-muted" style="font-size:11px;">Date</th>
                        <th class="text-uppercase text-muted" style="font-size:11px;">Category</th>
                        <th class="text-uppercase text-muted" style="font-size:11px;">Remark</th>
                        <th class="text-uppercase text-muted" style="font-size:11px;">Source</th>
                        <th class="text-uppercase text-muted text-end" style="font-size:11px;">Amount</th>
                        <th class="text-uppercase text-muted text-end pe-4" style="font-size:11px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($expenses as $e){ 
                    $src = $e['source'] ?: 'Cash';
                    $src_color = 'secondary';
                    if($src == 'Bank') $src_color = 'primary';
                    if($src == 'UPI') $src_color = 'info';
                ?>
                <tr>
                    <td class="ps-4 text-muted" style="font-size:12px;" data-order="<?= $e['expense_date'] ?>">
                        <?= date('d M Y', strtotime($e['expense_date'])) ?>
                    </td>
                    <td>
                        <span class="badge rounded-pill bg-light text-dark border" style="font-size:11px;">
                            <?= htmlspecialchars($e['category_name'] ?? 'Unknown') ?>
                        </span>
                    </td>
                    <td>
                        <span class="text-dark" style="font-size:13px;"><?= htmlspecialchars($e['remark']) ?: '<i class="text-muted">No remark</i>' ?></span>
                    </td>
                    <td>
                        <span class="badge rounded-pill bg-<?= $src_color ?> bg-opacity-10 text-<?= $src_color ?> border border-<?= $src_color ?>" style="font-size:10px;">
                            <?= htmlspecialchars($src) ?>
                        </span>
                    </td>
                    <td class="text-end fw-bold text-danger" style="font-size:14px;">
                        ₹ <?= number_format($e['amount'], 0) ?>
                    </td>
                    <td class="text-end pe-4">
                        <a href="expense_edit.php?id=<?= $e['id'] ?>" class="btn btn-sm btn-light border text-primary fw-semibold" style="font-size:12px;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="?delete=<?= $e['id'] ?>" class="btn btn-sm btn-light border text-danger fw-semibold ms-1" style="font-size:12px;" onclick="return confirm('Delete this expense?');">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
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
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    $('#expenseTable').DataTable({
        dom: "<'row p-3 pb-0'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-4 text-center'B><'col-sm-12 col-md-4'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3 pt-0'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel me-1"></i> Excel',
                className: 'btn btn-success btn-sm fw-semibold',
                title: 'Expenses Report',
                exportOptions: { columns: [0, 1, 2, 3, 4] }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf me-1"></i> PDF',
                className: 'btn btn-danger btn-sm fw-semibold',
                title: 'Expenses Report',
                exportOptions: { columns: [0, 1, 2, 3, 4] },
                customize: function (doc) {
                    doc.pageMargins = [20, 30, 20, 30];
                    doc.styles.title = { color: '#2c3e50', fontSize: 18, bold: true, alignment: 'center', margin: [0, 0, 0, 15] };
                    doc.defaultStyle.fontSize = 9; 
                    doc.defaultStyle.color = '#333';
                    var objLayout = {};
                    objLayout['hLineWidth'] = function(i) { return 0.5; };
                    objLayout['vLineWidth'] = function(i) { return 0.5; };
                    objLayout['hLineColor'] = function(i) { return '#e0e0e0'; };
                    objLayout['vLineColor'] = function(i) { return '#e0e0e0'; };
                    objLayout['paddingLeft'] = function(i) { return 5; };
                    objLayout['paddingRight'] = function(i) { return 5; };
                    objLayout['paddingTop'] = function(i) { return 5; };
                    objLayout['paddingBottom'] = function(i) { return 5; };
                    objLayout['fillColor'] = function (rowIndex) { return (rowIndex === 0) ? '#343a40' : (rowIndex % 2 === 0) ? '#f9f9f9' : null; };
                    doc.content[1].layout = objLayout;
                    doc.styles.tableHeader = { color: '#ffffff', fontSize: 10, bold: true, alignment: 'left', margin: [0, 2, 0, 2] };
                    doc.content[1].table.widths = ['15%', '20%', '35%', '15%', '15%'];
                    doc['footer'] = (function(page, pages) {
                        return {
                            columns: [
                                { alignment: 'left', text: 'SelfStudy CRM - Generated on ' + new Date().toLocaleString(), fontSize: 8, margin: [20, 10], color: '#666' },
                                { alignment: 'right', text: 'Page ' + page.toString() + ' of ' + pages.toString(), fontSize: 8, margin: [20, 10], color: '#666' }
                            ]
                        }
                    });
                }
            }
        ],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, 'desc']], // Sort by Date by default
        language: {
            search: "",
            searchPlaceholder: "Search expenses..."
        },
        columnDefs: [
            { orderable: false, targets: [5] } // Disable sorting on Action column
        ]
    });
});
</script>
