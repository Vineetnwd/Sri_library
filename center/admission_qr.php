<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* Fetch Center Info */
$c_info = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM centers WHERE id='$cid'")
);

if (!$c_info) {
    echo "<div class='container p-4'><div class='alert alert-danger'>Center not found.</div></div>";
    include "layout/footer.php";
    exit;
}

/* Pending Admissions Count */
$pending_count = 0;
$pq = mysqli_query($conn, "SELECT COUNT(*) as total FROM students WHERE center_id='$cid' AND status='pending'");
if ($pq) {
    $pending_count = (int)mysqli_fetch_assoc($pq)['total'];
}

/* Public Admission URL */
$admission_url = BASE_URL . "public/admission.php?center=" . $cid;
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid py-3">

    <!-- Top Breadcrumb & Actions (Screen Only) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 no-print gap-3">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fas fa-qrcode text-warning me-2"></i> Admission QR & Standee
            </h4>
            <p class="text-muted small mb-0">High-resolution A4 printable poster and tabletop standee for student admission</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($pending_count > 0): ?>
                <a href="pending_admissions.php" class="btn btn-warning btn-sm fw-semibold shadow-sm">
                    <i class="fas fa-hourglass-half me-1"></i> Pending Verifications 
                    <span class="badge bg-dark ms-1"><?= $pending_count ?></span>
                </a>
            <?php endif; ?>
            <a href="<?= $admission_url ?>" target="_blank" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="fas fa-external-link-alt me-1"></i> Test Public Link
            </a>
            <a href="print_admission_qr.php" target="_blank" class="btn btn-primary btn-sm fw-semibold shadow-sm">
                <i class="fas fa-print me-1"></i> Open A4 Print View
            </a>
            <button onclick="window.print()" class="btn btn-dark btn-sm fw-semibold shadow-sm">
                <i class="fas fa-file-pdf me-1"></i> Quick Print
            </button>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="row g-4">

        <!-- Standee / A4 Preview Column -->
        <div class="col-lg-8 col-xl-7">
            
            <div class="a4-preview-wrapper mx-auto">
                <div class="a4-sheet" id="a4Sheet">
                    
                    <div class="a4-inner-border">
                        
                        <!-- 1. Header with Center Branding -->
                        <div class="poster-header">
                            <div class="header-logo-box">
                                <?php if (!empty($c_info['logo']) && file_exists("../uploads/" . $c_info['logo'])): ?>
                                    <img src="../uploads/<?= htmlspecialchars($c_info['logo']) ?>" alt="Logo">
                                <?php else: ?>
                                    <i class="fas fa-book-reader fa-2x text-primary"></i>
                                <?php endif; ?>
                            </div>
                            <div class="header-text-box">
                                <h2 class="poster-center-name"><?= htmlspecialchars($c_info['center_name']) ?></h2>
                                <div class="poster-center-tagline">
                                    <i class="fas fa-graduation-cap me-1"></i> Self-Study Library & Digital Reading Zone
                                </div>
                                <div class="poster-center-address">
                                    <i class="fas fa-map-marker-alt me-1 text-warning"></i>
                                    <?= htmlspecialchars($c_info['address'] ?? 'Study & Library Center') ?>
                                    <?php if (!empty($c_info['mobile'])): ?>
                                        &bull; <i class="fas fa-phone-alt me-1 text-warning"></i> +91 <?= htmlspecialchars($c_info['mobile']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Admission Call to Action Banner -->
                        <div class="poster-cta-banner">
                            <span class="poster-cta-badge">★ ADMISSIONS OPEN • JOIN TODAY ★</span>
                            <div class="poster-cta-title">SCAN TO APPLY FOR ONLINE ADMISSION</div>
                            <div class="poster-cta-subtitle">Scan with your phone camera to pick your study slot and register instantly</div>
                        </div>

                        <!-- 3. Two-Column Body: QR Code + 3 Steps -->
                        <div class="poster-grid-body">
                            
                            <!-- Left: Large QR Code -->
                            <div class="poster-qr-card">
                                <div class="qr-target-frame">
                                    <div class="qr-corner top-left"></div>
                                    <div class="qr-corner top-right"></div>
                                    <div class="qr-corner bottom-left"></div>
                                    <div class="qr-corner bottom-right"></div>

                                    <div id="qrcode" class="qr-canvas-holder"></div>
                                </div>

                                <div class="qr-scan-text">
                                    <i class="fas fa-camera text-primary me-1"></i> Scan with Camera or Google Lens
                                </div>
                            </div>

                            <!-- Right: 3-Step Process & Verification Box -->
                            <div class="poster-steps-card">
                                <div>
                                    <div class="steps-heading">
                                        <i class="fas fa-list-ol text-primary me-1"></i> 3 Easy Steps to Join
                                    </div>

                                    <div class="step-entry">
                                        <div class="step-circle">1</div>
                                        <div>
                                            <div class="step-title">Scan QR Code</div>
                                            <div class="step-sub">Open your mobile camera or scanner to open the admission page.</div>
                                        </div>
                                    </div>

                                    <div class="step-entry">
                                        <div class="step-circle">2</div>
                                        <div>
                                            <div class="step-title">Submit Details & Slot</div>
                                            <div class="step-sub">Enter your name, mobile number, preferred shift & seat.</div>
                                        </div>
                                    </div>

                                    <div class="step-entry">
                                        <div class="step-circle">3</div>
                                        <div>
                                            <div class="step-title">Verify & Occupy Seat</div>
                                            <div class="step-sub">Show application reference at the desk to confirm your seat.</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Verification Notice -->
                                <div class="poster-verify-notice">
                                    <strong><i class="fas fa-shield-alt text-warning me-1"></i> Seat Confirmation Notice:</strong>
                                    Seats requested are confirmed upon verification by library administration.
                                </div>
                            </div>

                        </div>

                        <!-- 4. Amenities Grid -->
                        <div class="poster-amenities-section">
                            <div class="amenities-title">
                                <i class="fas fa-star text-warning me-1"></i> Premium Library Amenities & Facilities
                            </div>
                            <div class="amenities-container">
                                <div class="amenity-item"><i class="fas fa-wifi text-primary"></i> High-Speed Wi-Fi</div>
                                <div class="amenity-item"><i class="fas fa-snowflake text-info"></i> Air Conditioned</div>
                                <div class="amenity-item"><i class="fas fa-plug text-warning"></i> Personal Socket</div>
                                <div class="amenity-item"><i class="fas fa-chair text-success"></i> Ergonomic Desks</div>
                                <div class="amenity-item"><i class="fas fa-tint text-primary"></i> Clean RO Water</div>
                                <div class="amenity-item"><i class="fas fa-video text-danger"></i> CCTV Security</div>
                                <div class="amenity-item"><i class="fas fa-volume-mute text-secondary"></i> Silent Ambience</div>
                                <div class="amenity-item"><i class="fas fa-newspaper text-info"></i> Daily News & GK</div>
                            </div>
                        </div>

                        <!-- 5. Footer & Front Desk Helpline -->
                        <div class="poster-footer-strip">
                            <div class="footer-help">
                                <i class="fas fa-headset me-1 text-warning"></i>
                                For Admissions & Support: <strong>+91 <?= htmlspecialchars($c_info['mobile'] ?? 'Contact Desk') ?></strong>
                                <?php if (!empty($c_info['email'])): ?>
                                    &bull; <span style="font-size: 11.5px; font-weight: 500;"><?= htmlspecialchars($c_info['email']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="footer-brand">
                                <div><?= htmlspecialchars($c_info['center_name']) ?></div>
                                <div style="font-size: 8px; opacity: 0.75;">SelfStudy LMS &bull; Library Management System</div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>

        <!-- Controls & Sharing Sidebar (Screen Only) -->
        <div class="col-lg-4 col-xl-5 no-print">

            <!-- Standee Print Settings & Options Card -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h5 class="fw-bold mb-1 text-dark">
                        <i class="fas fa-print text-primary me-2"></i> A4 Poster & Standee Printing
                    </h5>
                    <p class="text-muted small">Formatted specifically for full-page A4 printing edge-to-edge</p>
                </div>
                <div class="card-body">
                    
                    <div class="d-grid gap-2 mb-3">
                        <a href="print_admission_qr.php" target="_blank" class="btn btn-primary btn-lg fw-bold shadow-sm py-3">
                            <i class="fas fa-print me-2"></i> Print Official A4 Poster
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-dark fw-semibold py-2">
                            <i class="fas fa-file-pdf me-1"></i> Quick Print from this Page
                        </button>
                    </div>

                    <div class="alert alert-light border rounded-3 p-3 mb-0" style="font-size: 0.85rem;">
                        <div class="fw-bold text-dark mb-1">
                            <i class="fas fa-lightbulb text-warning me-1"></i> Print Setting Recommendations:
                        </div>
                        <ul class="mb-0 ps-3 text-muted">
                            <li><strong>Paper size:</strong> A4 (210 × 297 mm)</li>
                            <li><strong>Orientation:</strong> Portrait</li>
                            <li><strong>Margins:</strong> None or Minimum</li>
                            <li><strong>Options:</strong> Check <em>"Background graphics"</em> so all headers and colors render sharply.</li>
                        </ul>
                    </div>

                </div>
            </div>

            <!-- Digital Sharing & Direct Link Card -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h5 class="fw-bold mb-1 text-dark">
                        <i class="fas fa-share-alt text-success me-2"></i> Share Admission Link
                    </h5>
                    <p class="text-muted small">Send direct admission link to prospective students</p>
                </div>
                <div class="card-body">

                    <!-- Copy URL Group -->
                    <label class="form-label fw-semibold small text-muted">Public Admission URL</label>
                    <div class="input-group mb-3">
                        <input type="text" id="admissionUrlInput" class="form-control bg-light" 
                               value="<?= htmlspecialchars($admission_url) ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="copyBtn" onclick="copyAdmissionLink()">
                            <i class="fas fa-copy me-1"></i> Copy
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                        $wa_msg = urlencode("Hello! You can apply for admission at *" . $c_info['center_name'] . "* using the link below:\n\n" . $admission_url . "\n\nFill your details and your study slot will be verified promptly.");
                        ?>
                        <a href="https://api.whatsapp.com/send?text=<?= $wa_msg ?>" target="_blank" 
                           class="btn btn-success fw-semibold flex-grow-1">
                            <i class="fab fa-whatsapp me-1"></i> Share on WhatsApp
                        </a>

                        <!-- Download PNG -->
                        <button onclick="downloadQrImage()" class="btn btn-outline-dark fw-semibold flex-grow-1">
                            <i class="fas fa-download me-1"></i> Download QR Image
                        </button>
                    </div>

                </div>
            </div>

            <!-- Verification Status Card -->
            <div class="card border-0 shadow-sm" style="border-radius: 14px; background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%); border: 1px solid #fde68a !important;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; flex-shrink: 0;">
                            <i class="fas fa-user-check fa-lg text-dark"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Online Admission Verification</h6>
                            <p class="text-muted small mb-2">
                                Students submitting via this QR remain in <strong>Pending</strong> status until you verify them. Seats are protected from double booking!
                            </p>
                            <a href="pending_admissions.php" class="btn btn-dark btn-sm fw-semibold">
                                <i class="fas fa-list me-1"></i> Review Pending Admissions (<?= $pending_count ?>)
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- A4 STANDEE & PRINT CSS -->
<style>
/* ── Screen A4 Sheet Wrapper ──────────────── */
.a4-preview-wrapper {
    width: 100%;
    max-width: 620px;
}

.a4-sheet {
    background: #ffffff;
    border: 2px solid #0f172a;
    border-radius: 16px;
    box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.12);
    padding: 16px;
    position: relative;
    box-sizing: border-box;
}

.a4-inner-border {
    border: 1.5px solid #cbd5e1;
    border-radius: 12px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* 1. Header */
.poster-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
    color: white;
    padding: 18px 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 18px;
}

.header-logo-box {
    width: 72px;
    height: 72px;
    border-radius: 14px;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    padding: 4px;
    border: 2.5px solid #f59e0b;
}

.header-logo-box img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.poster-center-name {
    font-size: 25px;
    font-weight: 900;
    color: #ffffff;
    margin-bottom: 2px;
    line-height: 1.15;
    text-transform: uppercase;
}

.poster-center-tagline {
    font-size: 11px;
    font-weight: 700;
    color: #f59e0b;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.poster-center-address {
    font-size: 11.5px;
    color: #cbd5e1;
    margin-top: 4px;
}

/* 2. CTA Banner */
.poster-cta-banner {
    background: #f8fafc;
    border: 1.5px dashed #4338ca;
    border-radius: 10px;
    padding: 12px 16px;
    text-align: center;
}

.poster-cta-badge {
    display: inline-block;
    background: #f59e0b;
    color: #0f172a;
    font-size: 10.5px;
    font-weight: 900;
    letter-spacing: 1px;
    padding: 3px 14px;
    border-radius: 20px;
}

.poster-cta-title {
    font-size: 19px;
    font-weight: 900;
    color: #1e1b4b;
    margin-top: 4px;
    letter-spacing: -0.2px;
}

.poster-cta-subtitle {
    font-size: 11.5px;
    color: #475569;
    margin-top: 2px;
}

/* 3. Body: QR + Steps */
.poster-grid-body {
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: 14px;
}

.poster-qr-card {
    background: #ffffff;
    border: 1.5px solid #1e1b4b;
    border-radius: 12px;
    padding: 14px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.qr-target-frame {
    width: 210px;
    height: 210px;
    padding: 10px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.qr-canvas-holder canvas, .qr-canvas-holder img {
    max-width: 100%;
    height: auto;
    display: block;
}

/* Corner markers */
.qr-corner {
    position: absolute;
    width: 20px;
    height: 20px;
    border-color: #1e1b4b;
    border-style: solid;
}
.qr-corner.top-left { top: -2px; left: -2px; border-width: 4px 0 0 4px; border-top-left-radius: 8px; }
.qr-corner.top-right { top: -2px; right: -2px; border-width: 4px 4px 0 0; border-top-right-radius: 8px; }
.qr-corner.bottom-left { bottom: -2px; left: -2px; border-width: 0 0 4px 4px; border-bottom-left-radius: 8px; }
.qr-corner.bottom-right { bottom: -2px; right: -2px; border-width: 0 4px 4px 0; border-bottom-right-radius: 8px; }

.qr-scan-text {
    margin-top: 10px;
    font-size: 12px;
    font-weight: 800;
    color: #1e1b4b;
}

.qr-link-subtext {
    font-size: 9px;
    color: #64748b;
    background: #f1f5f9;
    padding: 3px 10px;
    border-radius: 4px;
    margin-top: 5px;
    word-break: break-all;
    font-family: monospace;
}

/* Right Steps Card */
.poster-steps-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.steps-heading {
    font-size: 13px;
    font-weight: 800;
    color: #0f172a;
    text-transform: uppercase;
    margin-bottom: 10px;
}

.step-entry {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
}

.step-circle {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #1e1b4b;
    color: white;
    font-size: 12px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.step-title {
    font-size: 12.5px;
    font-weight: 800;
    color: #0f172a;
}

.step-sub {
    font-size: 10.5px;
    color: #475569;
    line-height: 1.3;
}

.poster-verify-notice {
    background: #fffbeb;
    border: 1.5px solid #fde68a;
    border-radius: 8px;
    padding: 8px 10px;
    color: #92400e;
    font-size: 10px;
    line-height: 1.35;
}

/* 4. Amenities */
.poster-amenities-section {
    background: #ffffff;
}

.amenities-title {
    font-size: 12.5px;
    font-weight: 800;
    color: #0f172a;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.amenities-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.amenity-item {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 7px 8px;
    font-size: 10.5px;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 6px;
}

.amenity-item i {
    font-size: 13px;
}

/* 5. Footer */
.poster-footer-strip {
    background: #0f172a;
    color: white;
    padding: 12px 16px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.footer-help {
    font-size: 12px;
    font-weight: 800;
}

.footer-help strong {
    color: #f59e0b;
    font-size: 14px;
}

.footer-brand {
    font-size: 9.5px;
    color: #94a3b8;
    text-align: right;
}

/* ── PRINT MEDIA RULES ─────────────────────── */
@media print {
    @page {
        size: A4 portrait;
        margin: 0;
    }

    body {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .sidebar-wrap,
    .mobile-header,
    .sidebar-overlay,
    .no-print,
    .main-content > .impersonate-banner,
    .main-content > .flash-container {
        display: none !important;
    }

    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    .a4-preview-wrapper {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .a4-sheet {
        border: none !important;
        box-shadow: none !important;
        padding: 8mm !important;
        width: 210mm !important;
        height: 297mm !important;
    }

    .a4-inner-border {
        border: 2px solid #0f172a !important;
        height: 100% !important;
        justify-content: space-between !important;
    }

    .poster-header {
        background: #0f172a !important;
        padding: 22px 26px !important;
    }

    .poster-center-name {
        font-size: 30px !important;
    }

    .qr-target-frame {
        width: 240px !important;
        height: 240px !important;
    }
}
</style>

<!-- QRCode.js Library from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var admissionUrl = <?= json_encode($admission_url) ?>;

    var qrContainer = document.getElementById("qrcode");
    qrContainer.innerHTML = "";
    
    new QRCode(qrContainer, {
        text: admissionUrl,
        width: 190,
        height: 190,
        colorDark: "#0f172a",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});

// Copy Admission Link
function copyAdmissionLink() {
    var copyText = document.getElementById("admissionUrlInput");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value).then(function(){
        var copyBtn = document.getElementById("copyBtn");
        var oldHtml = copyBtn.innerHTML;
        copyBtn.innerHTML = '<i class="fas fa-check text-success me-1"></i> Copied!';
        copyBtn.classList.add("btn-success", "text-white");
        copyBtn.classList.remove("btn-outline-secondary");
        setTimeout(function(){
            copyBtn.innerHTML = oldHtml;
            copyBtn.classList.remove("btn-success", "text-white");
            copyBtn.classList.add("btn-outline-secondary");
        }, 2500);
    });
}

// Download QR Code image as PNG
function downloadQrImage() {
    var qrCanvas = document.querySelector("#qrcode canvas");
    if (qrCanvas) {
        var link = document.createElement("a");
        link.download = "admission-qr-<?= $cid ?>.png";
        link.href = qrCanvas.toDataURL("image/png");
        link.click();
    } else {
        var qrImg = document.querySelector("#qrcode img");
        if (qrImg) {
            var link = document.createElement("a");
            link.download = "admission-qr-<?= $cid ?>.png";
            link.href = qrImg.src;
            link.click();
        } else {
            alert("QR Code image is still generating. Please try again in a moment.");
        }
    }
}
</script>

<?php include "layout/footer.php"; ?>
