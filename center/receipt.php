<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once "../system/db.php";
require_once "../system/functions.php";
$cid = center_id();
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
  die("Invalid ID");
}

// $res = mysqli_query($conn,
//     "SELECT p.*, s.name, s.registration_no,
//             c.center_name, c.mobile as center_mobile, c.logo
//      FROM payments p
//      LEFT JOIN students s ON s.id=p.student_id
//      LEFT JOIN centers c ON c.id=p.center_id
//      WHERE p.id='$id' AND p.center_id='$cid'"
// );
$res = mysqli_query(
  $conn,
  "SELECT p.*, s.name, s.registration_no, s.mobile as student_mobile, s.joining_date,
            c.center_name, c.mobile as center_mobile, c.logo,c.address, c.dir_sign,
            fp.amount as plan_amount, fp.duration_month
     FROM payments p
     LEFT JOIN students s ON s.id=p.student_id
     LEFT JOIN centers c ON c.id=p.center_id
     LEFT JOIN fee_plans fp ON fp.id=s.plan_id
     WHERE p.id='$id'"
);
if (!$res) {
  die("SQL Error: " . mysqli_error($conn));
}
$payment = mysqli_fetch_assoc($res);
if (!$payment) {
  die("Invalid receipt");
}

$paid = (float)$payment['amount'];
$total = $paid;
$due = 0;

$s_date = '';
$e_date = '';

if (strtolower($payment['payment_type']) == 'fee' && !empty($payment['joining_date'])) {
    $student_id = $payment['student_id'];
    $payment_id = $payment['id'];
    
    $q_prior = mysqli_query($conn, "SELECT SUM(amount) as prior_total FROM payments WHERE student_id='$student_id' AND id < '$payment_id' AND payment_type='fee' AND (status != 'cancelled' OR status IS NULL)");
    $prior_row = mysqli_fetch_assoc($q_prior);
    $prior_paid = (float)($prior_row['prior_total'] ?? 0);
    
    $this_paid = $paid;
    $plan_amount = (float)($payment['plan_amount'] ?? 0);
    $dur = (int)($payment['duration_month'] ?? 1);
    if ($dur < 1) $dur = 1;
    
    if ($plan_amount > 0) {
        $start_cycle_index = floor($prior_paid / $plan_amount);
        $end_cycle_index = ceil(($prior_paid + $this_paid) / $plan_amount) - 1;
        if ($end_cycle_index < $start_cycle_index) $end_cycle_index = $start_cycle_index;
        
        $cycles_covered = $end_cycle_index - $start_cycle_index + 1;
        $billed_for_cycles = $cycles_covered * $plan_amount;
        $prev_paid_for_cycles = $prior_paid - ($start_cycle_index * $plan_amount);
        
        $total = $billed_for_cycles - $prev_paid_for_cycles;
        $due = $total - $paid;
        if ($due < 0) $due = 0;
        
        try {
            $join_date = new DateTime($payment['joining_date']);
            
            $start_date_obj = clone $join_date;
            $start_months = $start_cycle_index * $dur;
            $start_date_obj->modify("+$start_months months");
            
            $end_date_obj = clone $join_date;
            $end_months = ($end_cycle_index + 1) * $dur;
            $end_date_obj->modify("+$end_months months")->modify("-1 day");
            
            $s_date = $start_date_obj->format('Y-m-d');
            $e_date = $end_date_obj->format('Y-m-d');
        } catch (Exception $e) {}
    }
}

// Fallback if unable to compute
if (empty($s_date)) {
    $s_date = $payment['payment_date'] ?? date('Y-m-d');
    $dur = (int)($payment['duration_month'] ?? 1);
    if ($dur < 1) $dur = 1;
    $e_date = date('Y-m-d', strtotime($s_date . " + $dur months - 1 day"));
}

/* Build receipt URL and WhatsApp link */
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$receipt_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?id=' . $id;
$student_mobile = preg_replace('/[^0-9]/', '', $payment['student_mobile'] ?? '');
// Ensure Indian number has country code
if (strlen($student_mobile) === 10)
  $student_mobile = '91' . $student_mobile;
$wa_msg = urlencode(
  "Hello " . $payment['name'] . ",\n" .
  "Your payment receipt #" . $id . " of \u20b9" . number_format($payment['amount'], 2) . " has been generated.\n" .
  "View/Download: " . $receipt_url
);
$wa_link = "https://wa.me/{$student_mobile}?text={$wa_msg}";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Receipt #<?= $payment['id'] ?></title>
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap"
    rel="stylesheet">
  <style>
    :root {
      --ink: #0f0f0f;
      --ink-light: #555;
      --accent: #1a3c5e;
      --accent2: #c8a96e;
      --rule: #d4d0c8;
      --bg-tint: #f7f5f0;
      --white: #ffffff;
      --danger: #b94040;

      /* Half A4 = 210mm × 148.5mm  →  use mm for print */
      --page-w: 210mm;
      --page-h: 148.5mm;
    }

    /**, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }*/

    /*body {*/
    /*  font-family: 'DM Sans', sans-serif;*/
    /*  background: #e8e4dc;*/
    /*  min-height: 100vh;*/
    /*  display: flex;*/
    /*  flex-direction: column;*/
    /*  align-items: center;*/
    /*  justify-content: flex-start;*/
    /*  padding: 24px 16px 40px;*/
    /*}*/

    /* ─── RECEIPT CARD ─────────────────────────────────────── */
    .receipt {
      width: var(--page-w);
      min-height: var(--page-h);
      background: var(--white);
      position: relative;
      overflow: hidden;
      box-shadow: 0 8px 40px rgba(0, 0, 0, .18), 0 2px 8px rgba(0, 0, 0, .08);
    }

    /* Decorative top band */
    .receipt::before {
      content: '';
      display: block;
      height: 6px;
      background: linear-gradient(90deg, var(--accent) 0%, var(--accent2) 100%);
    }

    /* ─── HEADER ────────────────────────────────────────────── */
    .header {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 14px 18px 10px;
      border-bottom: 1.5px solid var(--rule);
    }

    .logo-wrap {
      flex-shrink: 0;
      width: 52px;
      height: 52px;
      border-radius: 6px;
      overflow: hidden;
      border: 1px solid var(--rule);
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg-tint);
    }

    .logo-wrap img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .logo-placeholder {
      font-family: 'Playfair Display', serif;
      font-size: 22px;
      font-weight: 700;
      color: var(--accent);
      letter-spacing: -1px;
    }

    .center-info {
      flex: 1;
    }

    .center-name {
      font-family: 'Playfair Display', serif;
      font-size: 17px;
      font-weight: 700;
      color: var(--accent);
      line-height: 1.2;
      letter-spacing: .2px;
    }

    .center-contact,
    .center-address {
      font-size: 12px;
      color: var(--ink);
      margin-top: 4px;
      letter-spacing: .2px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .receipt-badge {
      text-align: right;
      flex-shrink: 0;
    }

    .receipt-label {
      font-size: 9px;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--ink-light);
      font-weight: 500;
    }

    .receipt-no {
      font-family: 'Playfair Display', serif;
      font-size: 15px;
      font-weight: 600;
      color: var(--accent);
    }

    .receipt-date {
      font-size: 10px;
      color: var(--ink-light);
      margin-top: 2px;
    }

    /* ─── STUDENT ROW ───────────────────────────────────────── */
    .student-row {
      display: flex;
      align-items: stretch;
      background: var(--bg-tint);
      border-bottom: 1px solid var(--rule);
    }

    .student-col {
      flex: 1;
      padding: 10px 18px;
    }

    .student-col+.student-col {
      border-left: 1px solid var(--rule);
    }

    .field-label {
      font-size: 8.5px;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--ink-light);
      font-weight: 500;
      margin-bottom: 2px;
    }

    .field-value {
      font-size: 13px;
      font-weight: 600;
      color: var(--ink);
      letter-spacing: .1px;
    }

    /* ─── DETAILS GRID ──────────────────────────────────────── */
    .details {
      display: grid;
      grid-template-columns: 1fr 1fr;
      border-bottom: 1px solid var(--rule);
    }

    .detail-cell {
      padding: 8px 18px;
      border-right: 1px solid var(--rule);
      border-bottom: 1px solid var(--rule);
    }

    .detail-cell:nth-child(2n) {
      border-right: none;
    }

    .detail-cell:nth-last-child(-n+2) {
      border-bottom: none;
    }

    /* ─── AMOUNTS ───────────────────────────────────────────── */
    .amounts {
      display: flex;
      align-items: stretch;
      border-bottom: 1px solid var(--rule);
    }

    .amount-block {
      flex: 1;
      padding: 12px 18px;
      text-align: center;
      position: relative;
    }

    .amount-block+.amount-block::before {
      content: '';
      position: absolute;
      left: 0;
      top: 16%;
      bottom: 16%;
      width: 1px;
      background: var(--rule);
    }

    .amount-block.total-block {
      background: var(--accent);
    }

    .amount-block.total-block .field-label {
      color: rgba(255, 255, 255, .65);
    }

    .amount-block.total-block .amount-value {
      color: var(--white);
    }

    .amount-block.paid-block {
      background: var(--bg-tint);
    }

    .amount-block.due-block {
      background: #fdf5f5;
    }

    .amount-value {
      /*font-family: 'Playfair Display', serif;*/
      font-size: 20px;
      font-weight: 700;
      color: var(--ink);
      letter-spacing: 1.5px;
      line-height: 1.1;
    }

    .amount-value.red {
      color: var(--danger);
    }

    .amount-tag {
      display: inline-block;
      margin-top: 3px;
      font-size: 8.5px;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: var(--accent2);
      font-weight: 600;
    }

    .amount-block.total-block .amount-tag {
      color: rgba(255, 255, 255, .7);
    }

    /* ─── WORDS ROW ─────────────────────────────────────────── */
    .words-row {
      padding: 7px 18px;
      font-size: 10.5px;
      color: var(--ink-light);
      border-bottom: 1px solid var(--rule);
      background: #fafaf8;
    }

    .words-row span {
      color: var(--ink);
      font-weight: 500;
    }

    /* ─── INSTRUCTIONS ──────────────────────────────────────── */
    .instructions {
      display: flex;
      gap: 0;
      padding: 8px 18px;
      border-bottom: 1px solid var(--rule);
      background: var(--bg-tint);
    }

    .inst-title {
      font-size: 9px;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      font-weight: 600;
      color: var(--accent);
      margin-bottom: 4px;
    }

    .inst-list {
      list-style: none;
      display: flex;
      flex-wrap: wrap;
      gap: 3px 16px;
    }

    .inst-list li {
      font-size: 10px;
      color: var(--ink-light);
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .inst-list li::before {
      content: '›';
      color: var(--accent2);
      font-size: 12px;
      line-height: 1;
      font-weight: 700;
    }

    /* ─── FOOTER ────────────────────────────────────────────── */
    .footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 8px 18px;
      margin-top: 10px;
    }

    .issued-by {
      font-size: 10px;
      color: var(--ink-light);
    }

    .issued-by span {
      color: var(--ink);
      font-weight: 500;
    }

    .sig-block {
      text-align: right;
    }

    .sig-line {
      width: 90px;
      border-bottom: 1px solid var(--ink);
      margin-bottom: 3px;
      margin-left: auto;
    }

    .sig-label {
      font-size: 9px;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: var(--ink-light);
    }

    /* Watermark "PAID" */
    .watermark {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-30deg);
      font-family: 'Playfair Display', serif;
      font-size: 90px;
      font-weight: 700;
      color: rgba(26, 60, 94, .04);
      pointer-events: none;
      user-select: none;
      white-space: nowrap;
      letter-spacing: 8px;
    }

    /* ─── PRINT BUTTON ──────────────────────────────────────── */
    .print-btn-wrap {
      margin-top: 16px;
      display: flex;
      gap: 10px;
    }

    .btn {
      padding: 10px 28px;
      border: none;
      border-radius: 4px;
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      letter-spacing: .4px;
      transition: opacity .2s;
    }

    .btn:hover {
      opacity: .85;
    }

    .btn-print {
      background: var(--accent);
      color: #fff;
    }

    .btn-wa {
      background: #25D366;
      color: #fff;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
    }

    .btn-wa:hover {
      opacity: .85;
      color: #fff;
    }

    .btn-cancel {
      background: #dc2626;
      color: #fff;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
    }

    .btn-cancel:hover {
      opacity: .85;
      color: #fff;
    }

    .watermark {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%) rotate(-30deg);
      font-size: 80px;
      font-weight: 800;
      color: rgba(220, 38, 38, 0.1);
      z-index: 100;
      pointer-events: none;
      letter-spacing: 10px;
      text-transform: uppercase;
    }

    /* ─── PRINT STYLES ──────────────────────────────────────── */
    @media print {
      @page {
        size: A4 portrait;
        /* landscape = half-A4 portrait in one half */
        margin: 0;
      }

      body {
        background: none;
        padding: 0;
        display: block;
      }

      .print-btn-wrap {
        display: none;
      }

      .receipt {
        width: var(--page-w);
        min-height: var(--page-h);
        box-shadow: none;
        page-break-after: avoid;
      }
    }
  </style>
</head>

<body>
  <?php if(isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
  <div class="receipt">
    <?php if (isset($payment['status']) && $payment['status'] == 'cancelled'): ?>
      <div class="watermark">CANCELLED</div>
    <?php else: ?>
      <div class="watermark">PAID</div>
    <?php endif; ?>

    <!-- HEADER -->
    <div class="header">
      <div class="center-info">
        <?php if (!empty($payment['logo'])): ?>
          <img src="../uploads/<?= $payment['logo'] ?>" alt="Logo" style="width: auto; height: 54px; margin-bottom: 8px;">
        <?php else: ?>
          <div class="center-name"><?= $payment['center_name'] ?></div>
        <?php endif; ?>
        <div class="center-contact">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
          <?= $payment['center_mobile'] ?>
        </div>
        <div class="center-address">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
          <?= $payment['address'] ?>
        </div>
      </div>
      <div class="receipt-badge">
        <div class="receipt-label">Receipt</div>
        <div class="receipt-no">#<?= $payment['id'] ?>-PAID</div>
        <div class="receipt-date"><?= date('d M Y, h:i A', strtotime($payment['payment_date'])) ?></div>
      </div>
    </div>

    <!-- STUDENT -->
    <div class="student-row">
      <div class="student-col">
        <div class="field-label">Student Name</div>
        <div class="field-value"><?= $payment['name'] ?></div>
      </div>
      <div class="student-col">
        <div class="field-label">Registration No.</div>
        <div class="field-value"><?= $payment['registration_no'] ?></div>
      </div>
    </div>

    <!-- DETAILS GRID -->
    <div class="details">
      <div class="detail-cell">
        <div class="field-label">Payment Type</div>
        <div class="field-value"><?= ucwords(strtolower($payment['payment_type'])) ?></div>
      </div>

      <div class="detail-cell">
        <div class="field-label">Payment Mode</div>
        <div class="field-value"><?= ucwords(strtolower($payment['payment_mode'])) ?></div>
      </div>

      <div class="detail-cell">
        <div class="field-label">Fee Tenure</div>
        <div class="field-value">
          <?= !empty($s_date) ? date('d M Y', strtotime($s_date)) : '-' ?> →
          <?= !empty($e_date) ? date('d M Y', strtotime($e_date)) : '-' ?>
        </div>
      </div>
    </div>

    <!-- AMOUNTS -->
    <div class="amounts">
      <div class="amount-block total-block">
        <div class="field-label">Total Amount</div>
        <div class="amount-value">₹<?= number_format($total, 2) ?></div>
        <div class="amount-tag">Billed</div>
      </div>
      <div class="amount-block paid-block">
        <div class="field-label">Paid Amount</div>
        <div class="amount-value">₹<?= number_format($paid, 2) ?></div>
        <div class="amount-tag">Received</div>
      </div>
      <div class="amount-block due-block">
        <div class="field-label">Current Dues</div>
        <div class="amount-value <?= $due > 0 ? 'red' : '' ?>">₹<?= number_format($due, 2) ?></div>
        <div class="amount-tag"><?= $due > 0 ? 'Outstanding' : 'Nil' ?></div>
      </div>
    </div>

    <!-- AMOUNT IN WORDS -->
    <div class="words-row">
      Amount in Words: <span>Rupees <?= number_format($paid, 0) ?> Only</span>
    </div>

    <!-- INSTRUCTIONS -->
    <div class="instructions">
      <div style="flex:1">
        <div class="inst-title">Instructions</div>
        <ul class="inst-list">
          <li>Pay before 10th of every month</li>
          <li>Keep your mobile number updated</li>
          <li>This is a system-generated receipt</li>
        </ul>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
      <div class="issued-by">Issued by: <span>Admin</span></div>
      <div class="sig-block">
        <?php if (!empty($payment['dir_sign'])): ?>
          <img src="../uploads/<?= $payment['dir_sign'] ?>" alt="Signature"
            style="max-height: 40px; margin-bottom: 4px; display: block; margin-left: auto;">
        <?php endif; ?>
        <div class="sig-line"></div>
        <div class="sig-label">Authorised Signatory</div>
      </div>
    </div>
  </div>

  <div class="print-btn-wrap">
    <button class="btn btn-print" onclick="window.print()">&#9113;&nbsp; Print Receipt</button>
    <?php if (!empty($student_mobile)) { ?>
      <a href="<?= $wa_link ?>" target="_blank" rel="noopener" class="btn btn-wa">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="white"
          style="margin-right:6px;vertical-align:-2px;">
          <path
            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
        </svg>
        Send on WhatsApp
      </a>
    <?php } ?>
  </div>
  </div>

</body>

</html>