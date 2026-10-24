<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();
$year = date('Y');

/* =============================
   FETCH MONTHLY COLLECTION
============================= */
$data = [];

$q = mysqli_query($conn,
    "SELECT 
        MONTH(payment_date) month,
        SUM(amount) total
     FROM payments
     WHERE center_id='$cid'
     AND (status != 'cancelled' OR status IS NULL)
     AND YEAR(payment_date)='$year'
     GROUP BY MONTH(payment_date)
");

while($r = mysqli_fetch_assoc($q)){
    $data[(int)$r['month']] = $r['total'];
}

/* FETCH MONTHLY EXPENSES */
$q_exp = mysqli_query($conn,
    "SELECT 
        MONTH(expense_date) month,
        SUM(amount) total
     FROM expenses
     WHERE center_id='$cid'
     AND YEAR(expense_date)='$year'
     GROUP BY MONTH(expense_date)
");
$exp_data = [];
while($r = mysqli_fetch_assoc($q_exp)){
    $exp_data[(int)$r['month']] = $r['total'];
}

/* prepare 12 months */
$months = [];
$amounts = [];
$exp_amounts = [];

for($i=1; $i<=12; $i++){
    $months[] = date('M', mktime(0,0,0,$i,1));
    $amounts[] = $data[$i] ?? 0;
    $exp_amounts[] = $exp_data[$i] ?? 0;
}
?>

<?php include "layout/sidebar.php"; ?>

<!-- DataTables CSS & Buttons -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">
        <i class="fa fa-chart-line me-2 text-primary"></i> Monthly Revenue & Expenses - <?= $year ?>
    </h4>
    <div class="d-flex gap-2" id="export-buttons-container"></div>
</div>

<div class="card shadow-sm border-0" style="border-radius:12px;">
    <div class="card-body">
        <canvas id="revenueChart" height="100"></canvas>
    </div>
</div>

<div class="card shadow-sm border-0 mt-4" style="border-radius:12px;">
    <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold"><i class="fas fa-table me-2 text-info"></i>Monthly Data</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 w-100 datatable">
            <thead class="table-light">
                <tr>
                    <th class="ps-4 text-uppercase text-muted" style="font-size:11px;">Month</th>
                    <th class="text-end text-uppercase text-muted" style="font-size:11px;">Revenue</th>
                    <th class="text-end text-uppercase text-muted pe-4" style="font-size:11px;">Expenses</th>
                </tr>
            </thead>
            <tbody>
                <?php for($i=0; $i<12; $i++){ ?>
                <tr>
                    <td class="ps-4 fw-bold text-dark" style="font-size:13px;"><?= $months[$i] . ' ' . $year ?></td>
                    <td class="text-end text-success fw-bold" style="font-size:13px;">₹ <?= number_format($amounts[$i], 0) ?></td>
                    <td class="text-end text-danger fw-bold pe-4" style="font-size:13px;">₹ <?= number_format($exp_amounts[$i], 0) ?></td>
                </tr>
                <?php } ?>
            </tbody>
            <?php
            $yearly_revenue = array_sum($amounts);
            $yearly_expenses = array_sum($exp_amounts);
            $yearly_profit = $yearly_revenue - $yearly_expenses;
            ?>
            <tfoot class="bg-light fw-bold border-top">
                <tr>
                    <td class="ps-4 text-start text-nowrap border-0 align-middle" style="font-size:13px;">
                        <span class="text-muted text-uppercase me-2" style="font-size:11px;">Net Profit:</span> 
                        <span class="text-primary fs-6">₹<?= number_format($yearly_profit, 0) ?></span>
                    </td>
                    <td class="text-end text-success border-0 align-middle" style="font-size:14px;">₹<?= number_format($yearly_revenue, 0) ?></td>
                    <td class="text-end text-danger pe-4 border-0 align-middle" style="font-size:14px;">₹<?= number_format($yearly_expenses, 0) ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    var table = $('.datatable').DataTable({
        dom: "<'row p-3 pb-0'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row p-3 pt-0'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        pageLength: 12,
        lengthMenu: [12, 24],
        order: [], // no default sorting
        language: { search: "", searchPlaceholder: "Search months..." },
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="fas fa-file-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm fw-semibold shadow-sm',
                title: 'Monthly_Revenue_<?= $year ?>',
                footer: true
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="fas fa-file-pdf"></i> Export PDF',
                className: 'btn btn-danger btn-sm fw-semibold shadow-sm',
                title: 'Monthly_Revenue_<?= $year ?>',
                footer: true,
                customize: function (doc) {
                    doc.styles.tableHeader.alignment = 'left';
                    doc.content[1].table.widths = ['40%', '30%', '30%'];
                }
            }
        ]
    });
    table.buttons().container().appendTo('#export-buttons-container');
});

const ctx = document.getElementById('revenueChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Monthly Revenue (₹)',
            data: <?= json_encode($amounts) ?>,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            fill: true,
            tension: 0.3,
            borderWidth: 3
        },
        {
            label: 'Monthly Expenses (₹)',
            data: <?= json_encode($exp_amounts) ?>,
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            fill: true,
            tension: 0.3,
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
