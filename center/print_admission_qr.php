<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "../system/auth.php";
require_center();
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* Fetch Center Info */
$c_info = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM centers WHERE id='$cid'")
);

if (!$c_info) {
    die("Center not found.");
}

/* Public Admission URL */
$admission_url = BASE_URL . "public/admission.php?center=" . $cid;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>A4 Admission Poster - <?= htmlspecialchars($c_info['center_name']) ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ── PAGE RESET & SIZING ──────────────────────── */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #e2e8f0;
            color: #0f172a;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Screen Toolbar */
        .print-toolbar {
            width: 210mm;
            margin: 15px auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #1e293b;
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }

        .btn-print {
            background: #f59e0b;
            color: #0f172a;
            border: none;
            font-weight: 800;
            font-size: 15px;
            padding: 9px 24px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print:hover {
            background: #d97706;
            color: white;
        }

        .btn-back {
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .btn-back:hover {
            color: white;
        }

        /* ── A4 PAGE CONTAINER ───────────────────────── */
        .a4-page {
            width: 210mm;
            height: 297mm;
            margin: 15px auto 30px;
            padding: 8mm;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border: 2px solid #0f172a;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        /* Inner Frame */
        .a4-inner-frame {
            border: 2px solid #cbd5e1;
            border-radius: 14px;
            padding: 10px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
        }

        /* ── 1. HEADER ────────────────────────────────── */
        .poster-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            color: white;
            padding: 22px 26px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .header-logo {
            width: 86px;
            height: 86px;
            border-radius: 16px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            padding: 5px;
            border: 3px solid #f59e0b;
        }

        .header-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .header-text {
            flex: 1;
        }

        .center-name {
            font-size: 32px;
            font-weight: 900;
            letter-spacing: -0.5px;
            line-height: 1.15;
            color: #ffffff;
            text-transform: uppercase;
        }

        .center-tagline {
            font-size: 12.5px;
            font-weight: 700;
            color: #f59e0b;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .center-address {
            font-size: 13px;
            color: #cbd5e1;
            margin-top: 5px;
            line-height: 1.35;
        }

        /* ── 2. HERO ADMISSION BANNER ─────────────────── */
        .admission-banner {
            background: #f8fafc;
            border: 2px dashed #4338ca;
            border-radius: 12px;
            padding: 14px 20px;
            text-align: center;
        }

        .admission-badge {
            display: inline-block;
            background: #f59e0b;
            color: #0f172a;
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            padding: 4px 18px;
            border-radius: 20px;
        }

        .admission-title {
            font-size: 25px;
            font-weight: 900;
            color: #1e1b4b;
            margin-top: 6px;
            letter-spacing: -0.3px;
        }

        .admission-desc {
            font-size: 13px;
            color: #475569;
            font-weight: 500;
            margin-top: 2px;
        }

        /* ── 3. MAIN BODY: QR & STEPS (2 COLUMNS) ────────── */
        .poster-body {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 16px;
            align-items: stretch;
            flex-grow: 1;
            margin: 6px 0;
        }

        /* Left: Large QR Display Card */
        .qr-hero-card {
            background: #ffffff;
            border: 2px solid #1e1b4b;
            border-radius: 14px;
            padding: 16px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }

        .qr-frame {
            width: 255px;
            height: 255px;
            padding: 12px;
            background: white;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .qr-frame canvas, .qr-frame img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        /* QR Corner Markers */
        .qr-corner {
            position: absolute;
            width: 24px;
            height: 24px;
            border-color: #1e1b4b;
            border-style: solid;
        }
        .qr-corner.top-left { top: -2px; left: -2px; border-width: 4.5px 0 0 4.5px; border-top-left-radius: 10px; }
        .qr-corner.top-right { top: -2px; right: -2px; border-width: 4.5px 4.5px 0 0; border-top-right-radius: 10px; }
        .qr-corner.bottom-left { bottom: -2px; left: -2px; border-width: 0 0 4.5px 4.5px; border-bottom-left-radius: 10px; }
        .qr-corner.bottom-right { bottom: -2px; right: -2px; border-width: 0 4.5px 4.5px 0; border-bottom-right-radius: 10px; }

        .qr-scan-prompt {
            margin-top: 14px;
            font-size: 14px;
            font-weight: 800;
            color: #1e1b4b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qr-url-pill {
            font-size: 10px;
            color: #64748b;
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 6px;
            margin-top: 6px;
            word-break: break-all;
            font-family: monospace;
        }

        /* Right: 3 Steps Guide & Verification Notice */
        .steps-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .section-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .step-block {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .step-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #1e1b4b;
            color: white;
            font-size: 14px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .step-info-title {
            font-size: 14.5px;
            font-weight: 800;
            color: #0f172a;
        }

        .step-info-desc {
            font-size: 12px;
            color: #475569;
            line-height: 1.35;
            margin-top: 2px;
        }

        .verification-callout {
            background: #fffbeb;
            border: 1.5px solid #fde68a;
            border-radius: 10px;
            padding: 12px 14px;
            color: #92400e;
            font-size: 11.5px;
            line-height: 1.4;
        }

        /* ── 4. AMENITIES & FACILITIES ──────────────────── */
        .amenities-section {
            background: #ffffff;
            border-radius: 10px;
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .amenity-chip {
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12.5px;
            font-weight: 700;
            color: #1e293b;
        }

        .amenity-chip i {
            color: #4338ca;
            font-size: 16px;
        }

        /* ── 5. FOOTER HELPDESK ──────────────────────────── */
        .poster-footer {
            background: #0f172a;
            color: white;
            padding: 16px 22px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-left {
            font-size: 14px;
            font-weight: 800;
        }

        .footer-left strong {
            color: #f59e0b;
            font-size: 16px;
        }

        .footer-right {
            font-size: 11px;
            color: #94a3b8;
            text-align: right;
            font-weight: 500;
        }

        /* ── PRINT MEDIA STYLES ───────────────────────── */
        @media print {
            @page {
                size: A4 portrait;
                margin: 0;
            }

            html, body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
            }

            .print-toolbar {
                display: none !important;
            }

            .a4-page {
                width: 210mm !important;
                height: 297mm !important;
                min-height: 297mm !important;
                max-height: 297mm !important;
                margin: 0 !important;
                padding: 8mm !important;
                box-shadow: none !important;
                border: none !important;
                page-break-after: avoid !important;
                page-break-inside: avoid !important;
            }

            .a4-inner-frame {
                height: 100% !important;
                border: 2px solid #0f172a !important;
            }
        }
    </style>
</head>
<body>

<!-- Screen Print Controller Bar -->
<div class="print-toolbar no-print">
    <div>
        <strong><i class="fas fa-print me-1"></i> A4 Admission Poster (Full Edge-to-Edge)</strong> &bull; <?= htmlspecialchars($c_info['center_name']) ?>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
        <a href="admission_qr.php" class="btn-back">
            <i class="fas fa-arrow-left me-1"></i> Back to Center Panel
        </a>
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Print on A4 Sheet
        </button>
    </div>
</div>

<!-- A4 PRINT POSTER SHEET (210mm x 297mm) -->
<div class="a4-page">
    <div class="a4-inner-frame">

        <!-- 1. HEADER BAND WITH BRANDING -->
        <div class="poster-header">
            <div class="header-logo">
                <?php if (!empty($c_info['logo']) && file_exists("../uploads/" . $c_info['logo'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($c_info['logo']) ?>" alt="Logo">
                <?php else: ?>
                    <i class="fas fa-book-reader fa-3x" style="color:#1e1b4b;"></i>
                <?php endif; ?>
            </div>
            <div class="header-text">
                <h1 class="center-name"><?= htmlspecialchars($c_info['center_name']) ?></h1>
                <div class="center-tagline">
                    <i class="fas fa-graduation-cap me-1"></i> Self-Study Library & Digital Reading Zone
                </div>
                <div class="center-address">
                    <i class="fas fa-map-marker-alt me-1 text-warning"></i>
                    <?= htmlspecialchars($c_info['address'] ?? 'Library Self Study Center') ?>
                    <?php if (!empty($c_info['mobile'])): ?>
                        &bull; <i class="fas fa-phone-alt me-1 text-warning"></i> +91 <?= htmlspecialchars($c_info['mobile']) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 2. HERO CALL TO ACTION -->
        <div class="admission-banner">
            <span class="admission-badge">★ ADMISSIONS OPEN • JOIN TODAY ★</span>
            <div class="admission-title">SCAN TO APPLY FOR ONLINE ADMISSION</div>
            <div class="admission-desc">Scan with your smartphone camera to choose your study slot and submit your admission form</div>
        </div>

        <!-- 3. MAIN SECTION: QR CODE & 3 STEPS (GRID) -->
        <div class="poster-body">
            
            <!-- LEFT: PROMINENT QR CODE -->
            <div class="qr-hero-card">
                <div class="qr-frame">
                    <div class="qr-corner top-left"></div>
                    <div class="qr-corner top-right"></div>
                    <div class="qr-corner bottom-left"></div>
                    <div class="qr-corner bottom-right"></div>

                    <div id="qrcode"></div>
                </div>

                <div class="qr-scan-prompt">
                    <i class="fas fa-camera text-primary"></i> Scan with Phone Camera or Google Lens
                </div>
            </div>

            <!-- RIGHT: APPLICATION STEPS & SEAT NOTICE -->
            <div class="steps-card">
                <div>
                    <div class="section-title">
                        <i class="fas fa-list-ol text-primary"></i> 3 Simple Steps to Join
                    </div>

                    <div class="step-block">
                        <div class="step-badge">1</div>
                        <div>
                            <div class="step-info-title">Scan QR Code</div>
                            <div class="step-info-desc">Open your phone camera or Google Lens to open the online admission form.</div>
                        </div>
                    </div>

                    <div class="step-block">
                        <div class="step-badge">2</div>
                        <div>
                            <div class="step-info-title">Submit Details & Slot</div>
                            <div class="step-info-desc">Enter your name, mobile, preferred study shift & desired seat.</div>
                        </div>
                    </div>

                    <div class="step-block">
                        <div class="step-badge">3</div>
                        <div>
                            <div class="step-info-title">Verify & Occupy Seat</div>
                            <div class="step-info-desc">Show your reference code at the front desk to confirm your seat.</div>
                        </div>
                    </div>
                </div>

                <!-- Seat Occupancy & Verification Notice -->
                <div class="verification-callout">
                    <strong><i class="fas fa-shield-alt text-warning me-1"></i> Seat Confirmation Notice:</strong>
                    Seats requested are reserved and locked once verified by the library administration.
                </div>
            </div>

        </div>

        <!-- 4. FACILITIES & AMENITIES -->
        <div class="amenities-section">
            <div class="section-title" style="margin-bottom: 10px;">
                <i class="fas fa-star text-warning"></i> Premium Library Amenities & Facilities
            </div>
            <div class="amenities-grid">
                <div class="amenity-chip">
                    <i class="fas fa-wifi"></i> High-Speed Wi-Fi
                </div>
                <div class="amenity-chip">
                    <i class="fas fa-snowflake"></i> Fully Air Conditioned
                </div>
                <div class="amenity-chip">
                    <i class="fas fa-plug"></i> Personal Power Socket
                </div>
                <div class="amenity-chip">
                    <i class="fas fa-chair"></i> Ergonomic Study Desks
                </div>
                <div class="amenity-chip">
                    <i class="fas fa-tint"></i> Clean RO Drinking Water
                </div>
                <div class="amenity-chip">
                    <i class="fas fa-video"></i> 24x7 CCTV Security
                </div>
                <div class="amenity-chip">
                    <i class="fas fa-volume-mute"></i> Silent Peaceful Zone
                </div>
                <div class="amenity-chip">
                    <i class="fas fa-newspaper"></i> Daily News & Magazines
                </div>
            </div>
        </div>

        <!-- 5. FOOTER WITH RECEPTION CONTACT -->
        <div class="poster-footer">
            <div class="footer-left">
                <i class="fas fa-headset me-2 text-warning"></i>
                For Admissions & Queries: <strong>+91 <?= htmlspecialchars($c_info['mobile'] ?? 'Contact Desk') ?></strong>
                <?php if (!empty($c_info['email'])): ?>
                    &bull; <span style="font-size: 13px; font-weight: 500;"><?= htmlspecialchars($c_info['email']) ?></span>
                <?php endif; ?>
            </div>
            <div class="footer-right">
                <div><?= htmlspecialchars($c_info['center_name']) ?></div>
                <div style="font-size: 9px; opacity: 0.75;">SelfStudy Library Management System</div>
            </div>
        </div>

    </div>
</div>

<!-- QRCode.js Library from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var admissionUrl = <?= json_encode($admission_url) ?>;
    var qrContainer = document.getElementById("qrcode");
    qrContainer.innerHTML = "";
    
    new QRCode(qrContainer, {
        text: admissionUrl,
        width: 230,
        height: 230,
        colorDark: "#0f172a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

</body>
</html>
