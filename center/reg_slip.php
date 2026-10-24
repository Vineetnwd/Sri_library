<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

if (isset($_GET['all'])) {
    $q = mysqli_query($conn, "
        SELECT s.*, f.plan_name, f.amount, f.duration_month
        FROM students s
        LEFT JOIN fee_plans f ON s.slot_id = f.id
        WHERE s.center_id = '$cid'
    ");
} else {
    if (!isset($_POST['student_id']) || empty($_POST['student_id'])) {
        die("No students selected");
    }
    $ids     = array_map('intval', $_POST['student_id']);
    $id_list = implode(",", $ids);
    $q = mysqli_query($conn, "
        SELECT s.*, f.plan_name, f.amount, f.duration_month
        FROM students s
        LEFT JOIN fee_plans f ON s.slot_id = f.id
        WHERE s.id IN ($id_list)
        AND s.center_id = '$cid'
    ");
}

$c_info = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM centers WHERE id='$cid'")
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registration Slips</title>
<style>

/* ── RESET ────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 12px;
    background: #eef2f7;
    color: #1a1a2e;
}

/* ── SCREEN PAGE ──────────────────────────────── */
.page {
    width: 210mm;
    min-height: 297mm;
    margin: 12mm auto;
    padding: 9mm;
    background: #fff;
    display: flex;
    flex-direction: column;
    gap: 7mm;
    box-shadow: 0 4px 24px rgba(0,0,0,0.13);
    border-radius: 6px;
}

/* ── SLIP WRAPPER ─────────────────────────────── */
.slip-wrapper {
    width: 100%;
    height: 133mm;
    flex-shrink: 0;
}

/* ── SLIP CARD ────────────────────────────────── */
.slip {
    width: 100%;
    height: 100%;
    border: 1.5px solid #c8d3e8;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: #fff;
}

/* ── HEADER BAND ──────────────────────────────── */
.slip-header {
    background: linear-gradient(135deg, #1a237e 0%, #283593 60%, #3949ab 100%);
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 7px 10px;
    flex-shrink: 0;
}

.slip-header .logo-wrap img {
    height: 38px;
    width: auto;
    display: block;
    border-radius: 3px;
    background: #fff;
    padding: 2px;
}

.slip-header .logo-placeholder {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    font-weight: bold;
    color: #fff;
    flex-shrink: 0;
}

.slip-header .center-info {
    flex: 1;
}

.slip-header .center-name {
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.4px;
    line-height: 1.2;
}

.slip-header .slip-title {
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    opacity: 0.85;
    margin-top: 1px;
}

.slip-header .reg-badge {
    background: rgba(255,255,255,0.18);
    border: 1px solid rgba(255,255,255,0.4);
    border-radius: 4px;
    padding: 4px 10px;
    text-align: center;
    flex-shrink: 0;
}

.slip-header .reg-label {
    font-size: 8.5px;
    letter-spacing: 1px;
    text-transform: uppercase;
    opacity: 0.8;
    display: block;
}

.slip-header .reg-value {
    font-size: 12px;
    font-weight: 700;
    color: #ffd54f;
    display: block;
    letter-spacing: 0.5px;
}

/* ── BODY ─────────────────────────────────────── */
.slip-body {
    display: flex;
    flex: 1;
    min-height: 0;
}

/* ── PHOTO COLUMN ─────────────────────────────── */
.photo-col {
    width: 120px;
    flex-shrink: 0;
    background: #f4f6fb;
    border-right: 1px solid #e0e7f0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-between;
    padding: 8px 6px;
}

.photo-col img.photo {
    width: 120px;
    height: 150px;
    object-fit: cover;
    border: 2px solid #3949ab;
    border-radius: 4px;
    display: block;
}

.photo-col .qr-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
}

.photo-col .qr-wrap img {
    width: 58px;
    height: 58px;
    display: block;
    border: 1px solid #c8d3e8;
    border-radius: 3px;
    padding: 2px;
    background: #fff;
}

.photo-col .qr-label {
    font-size: 7.5px;
    color: #7986cb;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    text-align: center;
}

/* ── INFO + FEE COLUMNS ───────────────────────── */
.info-col {
    flex: 1;
    display: flex;
    min-width: 0;
}

/* Student details */
.detail-section {
    flex: 1.1;
    padding: 8px 8px 8px 10px;
    border-right: 1px solid #e0e7f0;
    display: flex;
    flex-direction: column;
    gap: 0;
}

.section-label {
    font-size: 12px;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #7986cb;
    font-weight: 700;
    margin-bottom: 5px;
    padding-bottom: 3px;
    border-bottom: 1.5px solid #e8eaf6;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 11px;
}

.info-table tr td {
    padding: 2px 3px;
    vertical-align: top;
    line-height: 1.4;
}

.info-table .lbl {
    font-weight: 600;
    color: #455a8a;
    white-space: nowrap;
    width: 52px;
    font-size: 15px;
}

.info-table .sep {
    color: #9fa8da;
    width: 8px;
    text-align: center;
}

.info-table .val {
    color: #1a1a2e;
}

/* Fee section */
.fee-section {
    width: 160px;
    flex-shrink: 0;
    padding: 8px;
    display: flex;
    flex-direction: column;
}

.fee-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 10.5px;
}

.fee-table tr td {
    padding: 2.5px 4px;
    line-height: 1.3;
    vertical-align: middle;
}

.fee-table .lbl {
    font-weight: 600;
    color: #455a8a;
    font-size: 14px;
    white-space: nowrap;
}

.fee-table .val {
    color: #1a1a2e;
    text-align: right;
}

.fee-table tr:not(:last-child) {
    border-bottom: 0.5px solid #e8eaf6;
}

.fee-table .paid-row .val {
    color: #2e7d32;
    font-weight: 600;
}

.fee-table .due-row td {
    background: #fff3e0;
    color: #e65100;
    font-weight: 700;
}

.fee-table .due-row .lbl {
    color: #bf360c;
}

/* ── FOOTER STRIP ─────────────────────────────── */
.slip-footer {
    background: #f4f6fb;
    border-top: 1px solid #e0e7f0;
    padding: 4px 10px;
    padding-top:25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}

.slip-footer .footer-left {
    font-size: 15px;
    color: #7986cb;
    letter-spacing: 0.3px;
}

.slip-footer .footer-right {
    font-size: 15px;
    color: #999;
    border-top: 1px solid #bbb;
    padding-top: 2px;
    min-width: 90px;
    text-align: center;
}
td{
   font-size:15px; 
}
/* ── SCREEN DIVIDER ───────────────────────────── */
.page-divider {
    border: none;
    border-top: 1.5px dashed #c5cae9;
    margin: 0;
}

/* ═══ PRINT ═══════════════════════════════════ */
@media print {
    html, body {
        width: 210mm;
        background: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .no-print { display: none !important; }

    .page {
        width: 210mm;
        height: 297mm;
        margin: 0;
        padding: 8mm;
        box-shadow: none;
        border-radius: 0;
        gap: 6mm;
        page-break-after: always;
        page-break-inside: avoid;
    }

    .slip-wrapper {
        height: 133mm;
        page-break-inside: avoid;
    }

    .page-divider { display: none; }

    @page {
        size: A4 portrait;
        margin: 0;
    }
}
</style>
</head>
<body>

<?php
$rows  = [];
while ($row = mysqli_fetch_assoc($q)) {
    $rows[] = $row;
}

$total         = count($rows);
$index         = 0;
$avatar_letter = strtoupper(mb_substr($c_info['center_name'], 0, 1));

while ($index < $total) {
    echo '<div class="page">';

    for ($slot = 0; $slot < 2; $slot++) {

        if ($index >= $total) {
            echo '<div class="slip-wrapper"></div>';
            $index++;
            continue;
        }

        $row = $rows[$index++];
        $due = $row['amount'] - $row['advance_balance'];

        $logo_html = !empty($c_info['logo'])
            ? '<div class="logo-wrap"><img src="../uploads/' . htmlspecialchars($c_info['logo']) . '" alt="logo"></div>'
            : '<div class="logo-placeholder">' . $avatar_letter . '</div>';

        echo '
        <div class="slip-wrapper">
          <div class="slip">

            <!-- HEADER BAND -->
            <div class="slip-header">
              ' . $logo_html . '
              <div class="center-info">
                <div class="center-name">' . htmlspecialchars($c_info['center_name']) . '</div>
                <div class="slip-title">Registration Slip</div>
              </div>
              <div class="reg-badge">
                <span class="reg-label">Reg No</span>
                <span class="reg-value">' . htmlspecialchars($row['registration_no']) . '</span>
              </div>
            </div>

            <!-- BODY -->
            <div class="slip-body">

              <!-- PHOTO + QR -->
              <div class="photo-col">
                <img class="photo"
                     src="../uploads/' . htmlspecialchars($row['photo']) . '"
                     alt="Student Photo">
                <div class="qr-wrap">
                  <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . urlencode($row['registration_no']) . '"
                       alt="QR Code">
                  <span class="qr-label">Scan ID</span>
                </div>
              </div>

              <!-- STUDENT DETAILS + FEE -->
              <div class="info-col">

                <div class="detail-section">
                  <div class="section-label">Student Details</div>
                  <table class="info-table">
                    <tr>
                      <td class="lbl">Name</td>
                      <td class="sep">:</td>
                      <td class="val"><strong>' . htmlspecialchars($row['name']) . '</strong></td>
                    </tr>
                    <tr>
                      <td class="lbl">Mobile</td>
                      <td class="sep">:</td>
                      <td class="val">' . htmlspecialchars($row['mobile']) . '</td>
                    </tr>
                    <tr>
                      <td class="lbl">Gender</td>
                      <td class="sep">:</td>
                      <td class="val">' . htmlspecialchars($row['gender']) . '</td>
                    </tr>
                    <tr>
                      <td class="lbl">Joining</td>
                      <td class="sep">:</td>
                      <td class="val">' . date('d M Y', strtotime($row['joining_date'])) . '</td>
                    </tr>
                    <tr>
                      <td class="lbl">Address</td>
                      <td class="sep">:</td>
                      <td class="val">' . htmlspecialchars($row['address']) . '</td>
                    </tr>
                  </table>
                </div>

                <div class="fee-section">
                  <div class="section-label">Fee Details</div>
                  <table class="fee-table">
                    <tr>
                      <td class="lbl">Plan</td>
                      <td class="val">' . htmlspecialchars($row['plan_name']) . '</td>
                    </tr>
                    <tr>
                      <td class="lbl">Duration</td>
                      <td class="val">' . (int)$row['duration_month'] . ' mo.</td>
                    </tr>
                    <tr>
                      <td class="lbl">Total</td>
                      <td class="val">&#8377;' . number_format($row['amount'], 0) . '</td>
                    </tr>
                    <tr class="paid-row">
                      <td class="lbl">Paid</td>
                      <td class="val">&#8377;' . number_format($row['advance_balance'], 0) . '</td>
                    </tr>
                    <tr class="due-row">
                      <td class="lbl">Due</td>
                      <td class="val">&#8377;' . number_format($due, 0) . '</td>
                    </tr>
                  </table>
                </div>

              </div><!-- /info-col -->
            </div><!-- /slip-body -->

            <!-- FOOTER STRIP -->
            <div class="slip-footer">
              <span class="footer-left">Issued: ' . date('d M Y') . '</span>';
              ?>
              <?php if(!empty($c_info['dir_sign'])){ ?>
                <!--<img src="../uploads/<?= htmlspecialchars($c_info['dir_sign']) ?>"-->
                    <!--class="footer-right"-->
                    <!--style="height:50px;object-fit:contain;background-color:#000;align:right">-->
            <?php } else { ?>
                <!--<div style="height:50px;"></div>-->
            <?php }
              echo '<span class="footer-right">Authorised Signatory</span>
            </div>

          </div><!-- /slip -->
        </div><!-- /slip-wrapper -->
        ';

        if ($slot === 0 && $index < $total) {
            echo '<hr class="page-divider">';
        }
    }

    echo '</div><!-- /page -->';
}
?>

<script>
window.addEventListener('load', function () {
    window.print();
});
</script>

</body>
</html>