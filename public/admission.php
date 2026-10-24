<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "../system/db.php";
require_once "../system/functions.php";

$cid = (int)($_GET['center'] ?? ($_POST['center_id'] ?? 0));

/* -------------------------------------------
   Ensure students table status column allows 'pending'
------------------------------------------- */
@mysqli_query($conn, "ALTER TABLE students MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");

/* -------------------------------------------
   Fetch Center Details
------------------------------------------- */
$center = null;
if ($cid > 0) {
    $c_res = mysqli_query($conn, "SELECT * FROM centers WHERE id='$cid' AND status='active'");
    if ($c_res && mysqli_num_rows($c_res) > 0) {
        $center = mysqli_fetch_assoc($c_res);
    }
}

$success_data = null;
$error_msg = null;

/* -------------------------------------------
   Handle Admission Form Submission
------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_admission'])) {
    if (!$center) {
        $error_msg = "Invalid or inactive center.";
    } else {
        $name         = clean($_POST['name'] ?? '');
        $mobile       = clean($_POST['mobile'] ?? '');
        $email        = clean($_POST['email'] ?? '');
        $gender       = clean($_POST['gender'] ?? 'Male');
        $address      = clean($_POST['address'] ?? '');
        $joining_date = clean($_POST['joining_date'] ?? date('Y-m-d'));
        $slot_id      = (int)($_POST['slot_id'] ?? 0);
        $seat_id      = (int)($_POST['seat_id'] ?? 0);
        $plan_id      = (int)($_POST['plan_id'] ?? 0);

        // Validation
        if (empty($name)) {
            $error_msg = "Please enter your full name.";
        } elseif (empty($mobile) || !preg_match('/^[0-9]{10}$/', $mobile)) {
            $error_msg = "Please enter a valid 10-digit mobile number.";
        } elseif ($slot_id <= 0) {
            $error_msg = "Please select your preferred study slot.";
        } elseif (empty($_POST['declaration'])) {
            $error_msg = "Please accept the rules and discipline declaration before submitting.";
        } else {
            // Check existing student with same mobile
            $dup_check = mysqli_query($conn, 
                "SELECT id, status, registration_no FROM students 
                 WHERE center_id='$cid' AND mobile='$mobile' AND status IN ('active', 'pending') 
                 ORDER BY id DESC LIMIT 1"
            );

            if ($dup_check && mysqli_num_rows($dup_check) > 0) {
                $dup = mysqli_fetch_assoc($dup_check);
                if ($dup['status'] === 'active') {
                    $error_msg = "A student with this mobile number is already active (Reg No: {$dup['registration_no']}). Please contact the library desk.";
                } else {
                    $error_msg = "An admission application with this mobile is already pending verification (Ref: {$dup['registration_no']}).";
                }
            } else {
                // Photo Upload (Optional)
                $photo_name = null;
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    if (in_array($ext, $allowed) && $_FILES['photo']['size'] <= 3 * 1024 * 1024) {
                        $photo_name = "online_" . time() . "_" . rand(1000, 9999) . "." . $ext;
                        if (!is_dir("../uploads")) {
                            @mkdir("../uploads", 0777, true);
                        }
                        move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/" . $photo_name);
                    }
                }

                $reg = "STU" . date('ymd') . rand(100, 999);

                $seat_sql = ($seat_id > 0) ? "'$seat_id'" : "NULL";
                $plan_sql = ($plan_id > 0) ? "'$plan_id'" : "NULL";
                $photo_sql = $photo_name ? "'$photo_name'" : "NULL";

                // Save student with status = 'pending' so seat is NOT yet occupied!
                $ins_query = "INSERT INTO students 
                    (center_id, registration_no, name, mobile, email, gender, joining_date, address, photo, seat_id, slot_id, status, plan_id)
                    VALUES 
                    ('$cid', '$reg', '$name', '$mobile', '$email', '$gender', '$joining_date', '$address', $photo_sql, $seat_sql, '$slot_id', 'pending', $plan_sql)";

                if (mysqli_query($conn, $ins_query)) {
                    $new_student_id = mysqli_insert_id($conn);

                    // Fetch slot name
                    $sl_q = mysqli_query($conn, "SELECT slot_name, start_time, end_time FROM slots WHERE id='$slot_id'");
                    $sl_row = mysqli_fetch_assoc($sl_q);

                    // Fetch seat name if chosen
                    $seat_no = "Auto-Assign / At Desk";
                    if ($seat_id > 0) {
                        $se_q = mysqli_query($conn, "SELECT seat_no FROM seats WHERE id='$seat_id'");
                        if ($se_row = mysqli_fetch_assoc($se_q)) {
                            $seat_no = $se_row['seat_no'];
                        }
                    }

                    // Fetch plan name
                    $plan_name = "To be confirmed at desk";
                    if ($plan_id > 0) {
                        $pl_q = mysqli_query($conn, "SELECT plan_name, amount FROM fee_plans WHERE id='$plan_id'");
                        if ($pl_row = mysqli_fetch_assoc($pl_q)) {
                            $plan_name = $pl_row['plan_name'] . " (₹" . number_format($pl_row['amount'], 0) . ")";
                        }
                    }

                    $success_data = [
                        'id'              => $new_student_id,
                        'registration_no' => $reg,
                        'name'            => $name,
                        'mobile'          => $mobile,
                        'slot'            => ($sl_row['slot_name'] ?? '') . " (" . ($sl_row['start_time'] ?? '') . " - " . ($sl_row['end_time'] ?? '') . ")",
                        'seat'            => $seat_no,
                        'plan'            => $plan_name,
                        'joining_date'    => $joining_date,
                    ];
                } else {
                    $error_msg = "Database Error: " . mysqli_error($conn);
                }
            }
        }
    }
}

/* -------------------------------------------
   Fetch Slots & Plans for Form
------------------------------------------- */
$slots = [];
$plans = [];
if ($center) {
    $sq = mysqli_query($conn, "SELECT * FROM slots WHERE center_id='$cid' AND status='active' ORDER BY start_time ASC");
    if ($sq) {
        while ($s = mysqli_fetch_assoc($sq)) {
            $slots[] = $s;
        }
    }

    $pq = mysqli_query($conn, "SELECT * FROM fee_plans WHERE center_id='$cid' ORDER BY amount ASC");
    if ($pq) {
        while ($p = mysqli_fetch_assoc($pq)) {
            $plans[] = $p;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= $center ? htmlspecialchars($center['center_name']) . " - Online Admission" : "Library Admission" ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4338ca;
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            --accent: #f59e0b;
            --success: #10b981;
            --dark: #0f172a;
            --card-border: rgba(226, 232, 240, 0.9);
        }

        *, *::before, *::after {
            box-sizing: border-box;
        }

        html, body {
            overflow-x: hidden;
            width: 100%;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding-bottom: 30px;
        }

        /* ── HEADER ────────────────────────────────────── */
        .admission-header {
            background: var(--primary-gradient);
            color: white;
            padding: 36px 16px 65px;
            position: relative;
            text-align: center;
            box-shadow: 0 10px 25px -5px rgba(67, 56, 202, 0.3);
        }

        .center-logo-badge {
            width: 74px;
            height: 74px;
            border-radius: 18px;
            background: white;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.15);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            overflow: hidden;
            border: 3px solid white;
        }

        .center-logo-badge img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .center-heading-title {
            font-size: 1.65rem;
            font-weight: 800;
            margin-bottom: 4px;
            word-break: break-word;
            line-height: 1.25;
        }

        .center-heading-subtitle {
            font-size: 0.92rem;
            opacity: 0.85;
            word-break: break-word;
            max-width: 520px;
            margin: 0 auto;
            line-height: 1.4;
        }

        /* ── CARD CONTAINER ────────────────────────────── */
        .admission-container {
            max-width: 650px;
            margin: -36px auto 0;
            padding: 0 16px;
            width: 100%;
            box-sizing: border-box;
            position: relative;
            z-index: 10;
        }

        .main-card {
            background: #ffffff;
            border-radius: 22px;
            border: 1px solid var(--card-border);
            box-shadow: 0 15px 35px -5px rgba(0, 0, 0, 0.08), 0 5px 15px -3px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            padding: 2.25rem 2rem;
            position: relative;
        }

        /* ── FORM ELEMENTS (iOS 16px rule for zoom prevention) ── */
        .form-label {
            font-weight: 600;
            font-size: 0.88rem;
            color: #334155;
            margin-bottom: 5px;
            display: block;
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 11px 14px;
            border: 1.5px solid #cbd5e1;
            font-size: 16px; /* Prevents auto-zoom on mobile iOS Safari */
            min-height: 48px;
            transition: all 0.2s ease;
            width: 100%;
        }

        .form-control:focus, .form-select:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
        }

        .input-group-text {
            border-radius: 10px;
            border: 1.5px solid #cbd5e1;
            padding: 0 14px;
            font-size: 15px;
            background: #f8fafc;
        }

        .btn-submit {
            background: var(--primary-gradient);
            border: none;
            color: white;
            font-weight: 700;
            padding: 14px 20px;
            border-radius: 12px;
            font-size: 1.05rem;
            min-height: 50px;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.35);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .btn-submit:hover:not(:disabled), .btn-submit:active:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 14px 20px -3px rgba(79, 70, 229, 0.45);
            color: white;
        }

        .btn-submit:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            box-shadow: none;
            transform: none;
            filter: grayscale(30%);
        }

        .declaration-box {
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            padding: 14px 16px;
            transition: all 0.2s ease;
        }

        .declaration-box.is-checked {
            background: #eef2ff;
            border-color: #6366f1;
        }

        .declaration-box .form-check-input {
            width: 1.25em;
            height: 1.25em;
            cursor: pointer;
            border-color: #94a3b8;
        }

        .declaration-box .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .notice-banner {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 12px;
            padding: 12px 14px;
            color: #92400e;
            font-size: 0.85rem;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .notice-banner i {
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* ── SUCCESS RECEIPT ──────────────────────────── */
        .success-receipt {
            background: #ffffff;
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 20px 16px;
            margin: 18px 0;
        }

        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.92rem;
            gap: 10px;
        }

        .receipt-row:last-child {
            border-bottom: none;
        }

        .receipt-label {
            color: #64748b;
            font-size: 0.86rem;
            flex-shrink: 0;
        }

        .receipt-value {
            font-weight: 700;
            color: #0f172a;
            text-align: right;
            word-break: break-word;
            font-size: 0.92rem;
        }

        /* ── RESPONSIVE BREAKPOINTS ───────────────────── */
        @media (max-width: 768px) {
            .admission-header {
                padding: 28px 14px 50px;
                border-radius: 0 0 20px 20px;
            }
            .center-heading-title {
                font-size: 1.45rem;
            }
            .center-heading-subtitle {
                font-size: 0.85rem;
            }
            .admission-container {
                margin-top: -26px;
                padding: 0 12px;
                position: relative;
                z-index: 10;
            }
            .main-card {
                padding: 1.6rem 1.25rem;
                border-radius: 18px;
            }
        }

        @media (max-width: 480px) {
            .admission-header {
                padding: 24px 12px 46px;
                border-radius: 0 0 18px 18px;
            }
            .center-logo-badge {
                width: 62px;
                height: 62px;
                border-radius: 14px;
                margin-bottom: 8px;
            }
            .center-heading-title {
                font-size: 1.3rem;
            }
            .center-heading-subtitle {
                font-size: 0.82rem;
            }
            .admission-container {
                margin-top: -22px;
                padding: 0 10px;
                position: relative;
                z-index: 10;
            }
            .main-card {
                padding: 1.4rem 1.1rem;
                border-radius: 16px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            }
            .receipt-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 2px;
                padding: 8px 0;
            }
            .receipt-value {
                text-align: left;
                width: 100%;
            }
            .btn-submit {
                font-size: 1rem;
                padding: 12px 16px;
                min-height: 48px;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .admission-header, .btn-no-print {
                display: none !important;
            }
            .admission-container {
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .main-card {
                border: none;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>

<?php if (!$center): ?>
    <!-- Invalid Center Error Screen -->
    <div class="admission-header">
        <h3 class="fw-bold mb-1">Library Admission Portal</h3>
    </div>
    <div class="admission-container">
        <div class="main-card p-4 text-center">
            <div class="text-danger mb-3">
                <i class="fas fa-exclamation-circle fa-3x"></i>
            </div>
            <h4 class="fw-bold text-dark">Center Not Available</h4>
            <p class="text-muted small mb-3">
                The admission link you followed is invalid or the center is currently inactive.
                Please check with the library reception to obtain a valid QR code or link.
            </p>
            <a href="../login.php" class="btn btn-outline-primary px-4">
                <i class="fas fa-home me-2"></i> Portal Login
            </a>
        </div>
    </div>
<?php elseif ($success_data): ?>
    <!-- SUCCESS / PENDING VERIFICATION SCREEN -->
    <div class="admission-header">
        <div class="center-logo-badge">
            <?php if (!empty($center['logo']) && file_exists("../uploads/" . $center['logo'])): ?>
                <img src="../uploads/<?= htmlspecialchars($center['logo']) ?>" alt="Logo">
            <?php else: ?>
                <i class="fas fa-book-reader fa-2x text-primary"></i>
            <?php endif; ?>
        </div>
        <h1 class="center-heading-title"><?= htmlspecialchars($center['center_name']) ?></h1>
        <p class="center-heading-subtitle">
            <?= htmlspecialchars($center['address'] ?? 'Library Self Study Center') ?>
        </p>
    </div>

    <div class="admission-container">
        <div class="main-card">
            <div class="text-center mb-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle mb-2" style="width: 68px; height: 68px;">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Application Submitted!</h4>
                <div class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mt-1 d-inline-flex align-items-center" style="font-size: 12px;">
                    <i class="fas fa-hourglass-half me-1"></i> Verification Pending
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size: 0.9rem;">
                    Thank you, <strong><?= htmlspecialchars($success_data['name']) ?></strong>! Your admission application has been registered.
                </p>
            </div>

            <!-- Notice about seat confirmation -->
            <div class="alert alert-info border-0 rounded-3 p-3 d-flex align-items-center gap-3 mb-3">
                <i class="fas fa-shield-alt fa-2x text-primary flex-shrink-0"></i>
                <div style="font-size: 0.85rem; line-height: 1.4;">
                    <strong>Seat Confirmation Notice:</strong> Your requested seat will be officially confirmed & occupied once verified by the center administration.
                </div>
            </div>

            <!-- Receipt Summary -->
            <div class="success-receipt">
                <div class="text-center mb-3">
                    <span class="text-uppercase text-muted fw-bold" style="font-size: 10.5px; letter-spacing: 1px;">Application Reference</span>
                    <div class="h4 fw-bold text-primary mb-0"><?= htmlspecialchars($success_data['registration_no']) ?></div>
                </div>

                <div class="receipt-row">
                    <span class="receipt-label">Student Name</span>
                    <span class="receipt-value"><?= htmlspecialchars($success_data['name']) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Registered Mobile</span>
                    <span class="receipt-value"><?= htmlspecialchars($success_data['mobile']) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Preferred Slot</span>
                    <span class="receipt-value"><?= htmlspecialchars($success_data['slot']) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Requested Seat</span>
                    <span class="receipt-value"><?= htmlspecialchars($success_data['seat']) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Fee Plan</span>
                    <span class="receipt-value"><?= htmlspecialchars($success_data['plan']) ?></span>
                </div>
                <div class="receipt-row">
                    <span class="receipt-label">Joining Date</span>
                    <span class="receipt-value"><?= date('d M Y', strtotime($success_data['joining_date'])) ?></span>
                </div>
            </div>

            <div class="d-flex flex-column flex-sm-row gap-2 mt-3 btn-no-print">
                <button onclick="window.print()" class="btn btn-outline-secondary flex-grow-1 py-2 fw-semibold" style="min-height: 46px;">
                    <i class="fas fa-print me-2"></i> Print / Save Slip
                </button>
                <?php if (!empty($center['mobile'])): ?>
                    <a href="https://wa.me/91<?= preg_replace('/[^0-9]/', '', $center['mobile']) ?>?text=<?= urlencode("Hello, I have submitted my admission application (Ref: {$success_data['registration_no']}) for {$center['center_name']}. Please verify my seat.") ?>" 
                       target="_blank" 
                       class="btn btn-success flex-grow-1 py-2 fw-semibold d-flex align-items-center justify-content-center"
                       style="min-height: 46px;">
                        <i class="fab fa-whatsapp me-2"></i> Contact on WhatsApp
                    </a>
                <?php endif; ?>
            </div>

            <div class="text-center mt-3 btn-no-print">
                <a href="admission.php?center=<?= $cid ?>" class="text-muted text-decoration-none small">
                    <i class="fas fa-arrow-left me-1"></i> Submit Another Admission
                </a>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ADMISSION APPLICATION FORM -->
    <div class="admission-header">
        <div class="center-logo-badge">
            <?php if (!empty($center['logo']) && file_exists("../uploads/" . $center['logo'])): ?>
                <img src="../uploads/<?= htmlspecialchars($center['logo']) ?>" alt="Logo">
            <?php else: ?>
                <i class="fas fa-book-reader fa-2x text-primary"></i>
            <?php endif; ?>
        </div>
        <h1 class="center-heading-title"><?= htmlspecialchars($center['center_name']) ?></h1>
        <p class="center-heading-subtitle">
            <?= htmlspecialchars($center['address'] ?? 'Library Self Study Center') ?>
        </p>
    </div>

    <div class="admission-container">
        <div class="main-card">

            <div class="text-center mb-3">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-2 d-inline-flex align-items-center" style="font-size: 12px; letter-spacing: 0.3px;">
                    <i class="fas fa-user-plus me-1"></i> Student Self-Admission
                </span>
                <h4 class="fw-bold text-dark mb-1" style="font-size: 1.35rem;">Enter Your Details</h4>
                <p class="text-muted small mb-0">Fill out the form below to register your library seat</p>
            </div>

            <div class="notice-banner">
                <i class="fas fa-info-circle fa-lg text-warning"></i>
                <div>
                    <strong>Verification Process:</strong> Once submitted, the library administration will review your details to approve and officially occupy your seat.
                </div>
            </div>

            <?php if ($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-3 p-3 mb-3" role="alert">
                    <i class="fas fa-exclamation-circle me-1"></i> <?= htmlspecialchars($error_msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" id="admissionForm">
                <input type="hidden" name="center_id" value="<?= $cid ?>">
                <input type="hidden" name="submit_admission" value="1">

                <div class="row g-3">
                    <!-- Student Full Name -->
                    <div class="col-12">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" name="name" class="form-control" placeholder="Enter your full name" 
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Mobile Number -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">+91</span>
                            <input type="tel" name="mobile" class="form-control" placeholder="10-digit mobile number" 
                                   maxlength="10" pattern="[0-9]{10}"
                                   value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Email Address -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Gender & Joining Date -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Joining Date</label>
                        <input type="date" name="joining_date" class="form-control" 
                               value="<?= htmlspecialchars($_POST['joining_date'] ?? date('Y-m-d')) ?>" required>
                    </div>

                    <!-- Preferred Slot -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Select Slot <span class="text-danger">*</span></label>
                        <select name="slot_id" id="slot_id" class="form-select" required>
                            <option value="">-- Choose Slot --</option>
                            <?php foreach ($slots as $sl): ?>
                                <option value="<?= $sl['id'] ?>" <?= ((int)($_POST['slot_id'] ?? 0) === (int)$sl['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sl['slot_name']) ?> (<?= $sl['start_time'] ?> - <?= $sl['end_time'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Seat Selection (Dynamic) -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Preferred Seat</label>
                        <select name="seat_id" id="seat_id" class="form-select">
                            <option value="">-- Select Slot First --</option>
                        </select>
                        <div id="seat_loading" class="small text-primary mt-1" style="display:none;">
                            <i class="fas fa-spinner fa-spin me-1"></i> Checking seat availability...
                        </div>
                    </div>

                    <!-- Fee Plan -->
                    <div class="col-12">
                        <label class="form-label">Fee Plan</label>
                        <select name="plan_id" class="form-select">
                            <option value="">-- Select Fee Plan (Can be finalized at desk) --</option>
                            <?php foreach ($plans as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ((int)($_POST['plan_id'] ?? 0) === (int)$p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['plan_name']) ?> — ₹<?= number_format($p['amount'], 0) ?> (<?= (int)$p['duration_month'] ?> Month<?= ((int)$p['duration_month'] > 1 ? 's' : '') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Address -->
                    <div class="col-12">
                        <label class="form-label">Address / Area <span class="text-muted fw-normal">(Optional)</span></label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Local address or hostel/PG name"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>

                    <!-- Student Photo -->
                    <div class="col-12">
                        <label class="form-label">Passport Photo / Selfie <span class="text-muted fw-normal">(Optional, for ID card)</span></label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <small class="text-muted" style="font-size: 11px;">JPG or PNG (Max 3MB)</small>
                    </div>

                    <!-- Declaration / Rules Agreement -->
                    <div class="col-12 mt-3">
                        <div class="declaration-box <?= isset($_POST['declaration']) ? 'is-checked' : '' ?>" id="declarationBox">
                            <div class="form-check d-flex align-items-start gap-2 mb-0 ps-0">
                                <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" name="declaration" id="declaration" value="1" required <?= isset($_POST['declaration']) ? 'checked' : '' ?>>
                                <label class="form-check-label text-dark small user-select-none" for="declaration" style="font-size: 0.88rem; line-height: 1.55; cursor: pointer;">
                                    I declare that I will follow all the rules, discipline, and instructions of Sri Library and maintain a peaceful study environment. I will use the library facilities responsibly, protect its property, and switch off AC, fans and lights when leaving. I understand that violation of the rules may result in disciplinary action, suspension or cancellation of my membership. <span class="text-danger">*</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-12 mt-4">
                        <button type="submit" id="submitBtn" class="btn btn-submit" disabled>
                            <i class="fas fa-paper-plane me-2"></i> Submit Admission Application
                        </button>
                    </div>

                </div>
            </form>

            <?php if (!empty($center['mobile'])): ?>
                <div class="text-center mt-4 pt-3 border-top">
                    <span class="text-muted small">Need assistance? Call reception:</span>
                    <a href="tel:<?= htmlspecialchars($center['mobile']) ?>" class="fw-bold text-dark text-decoration-none ms-1">
                        <i class="fas fa-phone-alt text-primary me-1"></i> <?= htmlspecialchars($center['mobile']) ?>
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
<?php endif; ?>

<!-- jQuery for Dynamic Seat Loading -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function(){
    // Declaration checkbox logic to enable/disable submit button
    function updateDeclarationState() {
        var isChecked = $('#declaration').is(':checked');
        $('#submitBtn').prop('disabled', !isChecked);
        if (isChecked) {
            $('#declarationBox').addClass('is-checked');
        } else {
            $('#declarationBox').removeClass('is-checked');
        }
    }

    $('#declaration').on('change', updateDeclarationState);
    updateDeclarationState();
    function loadAvailableSeats(slotId, preselectedSeat = '') {
        if (!slotId) {
            $('#seat_id').html('<option value="">-- Select Slot First --</option>');
            return;
        }

        $('#seat_loading').show();
        $.get('ajax_get_seats.php', {
            center: '<?= $cid ?>',
            slot_id: slotId
        }, function(data){
            $('#seat_loading').hide();
            $('#seat_id').html(data);
            if (preselectedSeat) {
                $('#seat_id').val(preselectedSeat);
            }
        }).fail(function(){
            $('#seat_loading').hide();
            $('#seat_id').html('<option value="">-- Any Available Seat --</option>');
        });
    }

    $('#slot_id').on('change', function(){
        loadAvailableSeats($(this).val());
    });

    // If slot was previously selected (e.g. on validation error)
    var initialSlot = $('#slot_id').val();
    var initialSeat = '<?= (int)($_POST['seat_id'] ?? 0) ?>';
    if (initialSlot) {
        loadAvailableSeats(initialSlot, initialSeat);
    }
});
</script>

</body>
</html>
