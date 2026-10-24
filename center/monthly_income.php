<?php
ini_set('display_errors',1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* =============================
   CURRENT MONTH
============================= */
$current_month = date('Y-m');

$current = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT IFNULL(SUM(amount),0) as total 
         FROM payments
         WHERE center_id='$cid'
         AND DATE_FORMAT(payment_date,'%Y-%m')='$current_month'")
);

/* =============================
   MONTHLY DATA
============================= */
$res = mysqli_query($conn,
    "SELECT 
        DATE_FORMAT(payment_date,'%Y-%m') as month,
        SUM(amount) as total
     FROM payments
     WHERE center_id='$cid'
     GROUP BY month
     ORDER BY month ASC"
);

$data = [];
$labels = [];

while($row = mysqli_fetch_assoc($res)){
    $labels[] = date('M Y', strtotime($row['month'].'-01'));
    $data[]   = (int)$row['total'];
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold">
        <i class="fa fa-chart-line text-success me-2"></i> Monthly Income Dashboard
    </h4>
</div>

<!-- SUMMARY CARDS -->
<div class="row mb-4">

<div class="col-md-4">
    <div class="card text-white shadow" style="background:linear-gradient(135deg,#11998e,#38ef7d); border-radius:12px;">
        <div class="card-body">
            <small>Current Month</small>
            <h3 class="mb-0 mt-2">₹ <?= number_format($current['total']) ?></h3>
            <small><?= date('F Y') ?></small>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card text-white shadow" style="background:linear-gradient(135deg,#667eea,#764ba2); border-radius:12px;">
        <div class="card-body">
            <small>Total Months</small>
            <h3 class="mb-0 mt-2"><?= count($data) ?></h3>
            <small>Active months</small>
        </div>
    </div>
</div>

<div class="col-md-4">
    <div class="card text-white shadow" style="background:linear-gradient(135deg,#f7971e,#ffd200); border-radius:12px;">
        <div class="card-body">
            <small>Average Income</small>
            <h3 class="mb-0 mt-2">
                ₹ <?= count($data) ? number_format(array_sum($data)/count($data)) : 0 ?>
            </h3>
            <small>Per month</small>
        </div>
    </div>
</div>

</div>

<!-- CHART -->
<div class="card shadow mb-4 border-0" style="border-radius:12px;">
    <div class="card-body">
        <h6 class="mb-3 fw-semibold">Income Trend</h6>
        <canvas id="incomeChart" height="90"></canvas>
    </div>
</div>

<!-- TABLE -->
<div class="card shadow border-0" style="border-radius:12px;">
<div class="card-body p-0">

<table class="table table-hover mb-0">

<thead class="table-dark">
<tr>
    <th>Month</th>
    <th class="text-end">Income</th>
</tr>
</thead>

<tbody>

<?php foreach($labels as $i => $label){ ?>
<tr>
    <td><?= $label ?></td>
    <td class="text-end">
        <strong class="text-success">₹ <?= number_format($data[$i]) ?></strong>
    </td>
</tr>
<?php } ?>

<?php if(empty($labels)){ ?>
<tr>
    <td colspan="2" class="text-center text-muted py-4">
        No income data found
    </td>
</tr>
<?php } ?>

</tbody>

</table>

</div>
</div>

</div>

<?php include "layout/footer.php"; ?>

<!-- CHART JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const labels = <?= json_encode($labels) ?>;
const data = <?= json_encode($data) ?>;

new Chart(document.getElementById('incomeChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Income',
            data: data,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        plugins: {
            legend: { display: false }
        }
    }
});
</script>