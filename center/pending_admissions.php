<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* -------------------------------------------
   Ensure students table status column allows 'pending' & 'rejected'
------------------------------------------- */
@mysqli_query($conn, "ALTER TABLE students MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'");

/* -------------------------------------------
   Handle Verification / Approval
------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {

    $action = clean($_POST['action_type']);
    $student_id = (int)($_POST['student_id'] ?? 0);

    // Verify student belongs to center
    $st_check = mysqli_query($conn, "SELECT * FROM students WHERE id='$student_id' AND center_id='$cid'");
    $student = mysqli_fetch_assoc($st_check);

    if (!$student) {
        set_flash("danger", "Student application not found.");
        redirect("center/pending_admissions.php");
        exit;
    }

    if ($action === 'approve') {
        $slot_id      = (int)($_POST['slot_id'] ?? 0);
        $seat_id      = (int)($_POST['seat_id'] ?? 0);
        $plan_id      = (int)($_POST['plan_id'] ?? 0);
        $joining_date = clean($_POST['joining_date'] ?? date('Y-m-d'));
        $paid_amount  = (float)($_POST['paid_amount'] ?? 0);
        $payment_mode = clean($_POST['payment_mode'] ?? 'cash');

        if ($slot_id <= 0 || $seat_id <= 0) {
            set_flash("danger", "Please allocate both a Slot and Seat before approving the student.");
            redirect("center/pending_admissions.php");
            exit;
        }

        // Check if the seat is already occupied by an active student in this slot
        $conflict = mysqli_fetch_assoc(
            mysqli_query($conn,
                "SELECT COUNT(*) AS total FROM students
                 WHERE center_id='$cid'
                 AND seat_id='$seat_id'
                 AND slot_id='$slot_id'
                 AND status='active'
                 AND id != '$student_id'")
        );

        if ($conflict && $conflict['total'] > 0) {
            set_flash("danger", "Seat conflict! This seat is already occupied by another active student in the selected slot. Please pick another seat.");
            redirect("center/pending_admissions.php");
            exit;
        }

        // Activate student and occupy seat!
        $plan_val = ($plan_id > 0) ? "'$plan_id'" : "NULL";
        $upd = mysqli_query($conn,
            "UPDATE students SET
                status='active',
                slot_id='$slot_id',
                seat_id='$seat_id',
                plan_id=$plan_val,
                joining_date='$joining_date'
             WHERE id='$student_id' AND center_id='$cid'"
        );

        if (!$upd) {
            set_flash("danger", "Update failed: " . mysqli_error($conn));
            redirect("center/pending_admissions.php");
            exit;
        }

        // Create or activate Student User Account
        $reg = $student['registration_no'];
        $user_check = mysqli_query($conn, "SELECT id FROM users WHERE center_id='$cid' AND user_id='$student_id'");
        if (mysqli_num_rows($user_check) === 0) {
            $pass_hash = password_hash($student['mobile'], PASSWORD_DEFAULT);
            mysqli_query($conn,
                "INSERT INTO users (center_id, user_id, username, password, role, status)
                 VALUES ('$cid', '$student_id', '$reg', '$pass_hash', 'student', 'active')"
            );
        } else {
            mysqli_query($conn,
                "UPDATE users SET status='active' WHERE center_id='$cid' AND user_id='$student_id'"
            );
        }

        // Record Initial Payment if entered
        if ($paid_amount > 0) {
            mysqli_query($conn,
                "INSERT INTO payments
                 (center_id, student_id, amount, payment_mode, payment_date, payment_type)
                 VALUES
                 ('$cid', '$student_id', '$paid_amount', '$payment_mode', CURDATE(), 'fee')"
            );
        }

        set_flash("success", "Student <strong>" . htmlspecialchars($student['name']) . "</strong> verified and admitted successfully! Seat is now officially occupied.");
        redirect("center/pending_admissions.php");
        exit;

    } elseif ($action === 'reject') {
        mysqli_query($conn,
            "UPDATE students SET status='rejected' WHERE id='$student_id' AND center_id='$cid'"
        );
        set_flash("warning", "Application for <strong>" . htmlspecialchars($student['name']) . "</strong> was marked as rejected.");
        redirect("center/pending_admissions.php");
        exit;

    } elseif ($action === 'delete') {
        if (!empty($student['photo']) && file_exists("../uploads/" . $student['photo'])) {
            @unlink("../uploads/" . $student['photo']);
        }
        mysqli_query($conn, "DELETE FROM students WHERE id='$student_id' AND center_id='$cid'");
        mysqli_query($conn, "DELETE FROM users WHERE user_id='$student_id' AND center_id='$cid'");
        set_flash("info", "Admission application deleted.");
        redirect("center/pending_admissions.php");
        exit;
    }
}

/* -------------------------------------------
   Fetch Pending Students
------------------------------------------- */
$pending_query = mysqli_query($conn,
    "SELECT s.*, sl.slot_name, sl.start_time, sl.end_time, se.seat_no, fp.plan_name, fp.amount as plan_amount
     FROM students s
     LEFT JOIN slots sl ON sl.id = s.slot_id
     LEFT JOIN seats se ON se.id = s.seat_id
     LEFT JOIN fee_plans fp ON fp.id = s.plan_id
     WHERE s.center_id='$cid' AND s.status='pending'
     ORDER BY s.id DESC"
);

$pending_total = mysqli_num_rows($pending_query);

/* Fetch all active slots & fee plans for modal */
$slots_list = [];
$sq = mysqli_query($conn, "SELECT * FROM slots WHERE center_id='$cid' AND status='active' ORDER BY start_time ASC");
if ($sq) while ($s = mysqli_fetch_assoc($sq)) $slots_list[] = $s;

$plans_list = [];
$pq = mysqli_query($conn, "SELECT * FROM fee_plans WHERE center_id='$cid' ORDER BY amount ASC");
if ($pq) while ($p = mysqli_fetch_assoc($pq)) $plans_list[] = $p;
?>

<?php include "layout/sidebar.php"; ?>

<style>
/* Prevent dropdown clipping in table container */
.table-responsive-custom {
    min-height: 260px;
}
@media (min-width: 992px) {
    .table-responsive-custom {
        overflow: visible !important;
    }
}
.dropdown-menu {
    z-index: 1065 !important;
}
</style>

<div class="container-fluid py-3">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="fas fa-user-clock text-warning me-2"></i>Pending Online Admissions
            </h4>
            <p class="text-muted small mb-0">Review students who registered via the QR code. Verify details to approve and occupy their seats.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="admission_qr.php" class="btn btn-outline-primary btn-sm fw-semibold shadow-sm">
                <i class="fas fa-qrcode me-1"></i> View Admission QR
            </a>
            <a href="students.php" class="btn btn-light border btn-sm fw-semibold text-dark shadow-sm">
                <i class="fas fa-users me-1 text-primary"></i> All Students
            </a>
        </div>
    </div>

    <!-- Alert / Notice -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: #fffdf5; border-left: 4px solid #f59e0b !important;">
        <div class="card-body py-2.5 px-3 d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background-color: rgba(245, 158, 11, 0.15); color: #d97706;">
                <i class="fas fa-shield-alt"></i>
            </div>
            <div style="font-size: 13px;">
                <strong class="text-dark">Seat Occupancy Protection:</strong>
                <span class="text-secondary ms-1">Seats selected in online applications are <em>tentative</em> and not locked until you approve them. Review details and click <strong>Verify & Approve</strong> to occupy the seat and avoid double booking.</span>
            </div>
        </div>
    </div>

    <!-- Applications Table Card -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white border-bottom py-3 px-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark" style="font-size: 14px;">
                    <i class="fas fa-list-check me-1 text-primary"></i> Awaiting Verification
                </span>
                <span class="badge rounded-pill bg-warning text-dark px-2 py-1 fw-bold" style="font-size: 11px;"><?= $pending_total ?></span>
            </div>
            <span class="text-muted small d-flex align-items-center gap-1" style="font-size: 11px;">
                <i class="fas fa-circle text-success" style="font-size: 7px;"></i> Auto-refreshed
            </span>
        </div>

        <div class="card-body p-0">
            <?php if ($pending_total === 0): ?>
                <div class="text-center py-5">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 64px; height: 64px;">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">No Pending Admissions!</h6>
                    <p class="text-muted small mb-3">All online student applications have been reviewed and verified.</p>
                    <a href="admission_qr.php" class="btn btn-primary btn-sm fw-semibold shadow-sm">
                        <i class="fas fa-print me-1"></i> Print / Display Admission QR
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive table-responsive-custom">
                    <table class="table table-hover align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3 text-uppercase text-muted" style="font-size: 11px; width: 40px;">#</th>
                                <th class="text-uppercase text-muted" style="font-size: 11px;">Student</th>
                                <th class="text-uppercase text-muted" style="font-size: 11px;">Contact</th>
                                <th class="text-uppercase text-muted" style="font-size: 11px;">Requested Slot</th>
                                <th class="text-uppercase text-muted" style="font-size: 11px;">Requested Seat</th>
                                <th class="text-uppercase text-muted" style="font-size: 11px;">Fee Plan</th>
                                <th class="text-uppercase text-muted" style="font-size: 11px;">Applied Date</th>
                                <th class="text-uppercase text-muted text-end pe-3" style="font-size: 11px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $idx = 1;
                            while ($row = mysqli_fetch_assoc($pending_query)): 
                            ?>
                                <tr>
                                    <td class="ps-3 text-muted" style="font-size: 12px;"><?= $idx++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2.5">
                                            <?php if (!empty($row['photo']) && file_exists("../uploads/" . $row['photo'])): ?>
                                                <img src="../uploads/<?= htmlspecialchars($row['photo']) ?>" class="rounded-circle shadow-sm" style="width: 38px; height: 38px; object-fit: cover;" alt="Photo">
                                            <?php else: ?>
                                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 38px; height: 38px; font-size: 14px;">
                                                    <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 13px;"><?= htmlspecialchars($row['name']) ?></div>
                                                <div class="d-flex align-items-center gap-1 mt-0.5">
                                                    <span class="badge rounded-pill bg-light text-secondary border" style="font-size: 10px; font-weight: 500;">
                                                        <?= htmlspecialchars($row['registration_no']) ?>
                                                    </span>
                                                    <?php if (!empty($row['gender'])): ?>
                                                        <span class="badge rounded-pill bg-light text-muted border" style="font-size: 10px; font-weight: 500;">
                                                            <?= htmlspecialchars($row['gender']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-dark fw-semibold" style="font-size: 12px;">
                                            <i class="fas fa-phone-alt text-muted me-1 small"></i> <?= htmlspecialchars($row['mobile']) ?>
                                        </div>
                                        <?php if ($row['email']): ?>
                                            <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($row['email']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($row['address']): ?>
                                            <div class="text-muted text-truncate" style="max-width: 170px; font-size: 11px;" title="<?= htmlspecialchars($row['address']) ?>">
                                                <i class="fas fa-map-marker-alt text-muted me-1"></i> <?= htmlspecialchars($row['address']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['slot_name']): ?>
                                            <div class="fw-semibold text-dark" style="font-size: 12px;"><?= htmlspecialchars($row['slot_name']) ?></div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                <i class="far fa-clock text-muted me-1"></i><?= date('h:i A', strtotime($row['start_time'])) ?> - <?= date('h:i A', strtotime($row['end_time'])) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-light text-muted border" style="font-size: 11px;">Not specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['seat_no']): ?>
                                            <span class="badge rounded-pill bg-info bg-opacity-10 text-info border border-info px-2.5 py-1 fw-semibold" style="font-size: 11px;">
                                                <i class="fas fa-chair me-1"></i> Seat <?= htmlspecialchars($row['seat_no']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-light text-muted border px-2 py-1" style="font-size: 11px;">Auto-Assign</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['plan_name']): ?>
                                            <div class="fw-semibold text-dark" style="font-size: 12px;"><?= htmlspecialchars($row['plan_name']) ?></div>
                                            <div class="text-success fw-bold" style="font-size: 12px;">₹<?= number_format($row['plan_amount'], 0) ?></div>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 11px;">To be confirmed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="text-muted" style="font-size: 12px;">
                                            <i class="far fa-calendar-alt me-1 text-muted small"></i> <?= date('d M Y', strtotime($row['joining_date'])) ?>
                                        </div>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group shadow-sm">
                                            <button type="button" class="btn btn-sm btn-success fw-semibold px-2.5"
                                                    onclick='openVerifyModal(<?= json_encode($row) ?>)'
                                                    title="Verify and allocate seat"
                                                    style="font-size: 12px; white-space: nowrap;">
                                                <i class="fas fa-user-check me-1"></i> Verify & Approve
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success dropdown-toggle dropdown-toggle-split"
                                                    data-bs-toggle="dropdown" 
                                                    data-bs-boundary="viewport"
                                                    data-bs-popper-config='{"strategy":"fixed"}'
                                                    aria-expanded="false" style="font-size: 12px;">
                                                <span class="visually-hidden">Toggle Actions</span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-1" style="border-radius: 8px; font-size: 12px; min-width: 175px;">
                                                <li>
                                                    <a class="dropdown-item py-2 text-success fw-medium" href="https://wa.me/91<?= preg_replace('/[^0-9]/', '', $row['mobile']) ?>" target="_blank">
                                                        <i class="fab fa-whatsapp me-2"></i> WhatsApp Student
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <form method="post" onsubmit="return confirm('Mark this application as rejected?');" class="m-0">
                                                        <input type="hidden" name="action_type" value="reject">
                                                        <input type="hidden" name="student_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="dropdown-item py-2 text-warning fw-medium">
                                                            <i class="fas fa-ban me-2"></i> Mark as Rejected
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="post" onsubmit="return confirm('Permanently delete this application? This cannot be undone.');" class="m-0">
                                                        <input type="hidden" name="action_type" value="delete">
                                                        <input type="hidden" name="student_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="dropdown-item py-2 text-danger fw-medium">
                                                            <i class="fas fa-trash me-2"></i> Delete Application
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- VERIFICATION & SEAT ALLOCATION MODAL -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-labelledby="verifyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            
            <form method="post" id="verifyForm">
                <input type="hidden" name="action_type" value="approve">
                <input type="hidden" name="student_id" id="modal_student_id">

                <div class="modal-header bg-success text-white py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-user-check fs-5"></i>
                        <h5 class="modal-title fw-bold mb-0" id="verifyModalLabel" style="font-size: 16px;">
                            Verify Admission & Allocate Seat
                        </h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    
                    <!-- Student Info Summary Box -->
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 mb-4 border">
                        <div id="modal_avatar_box" class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 48px; height: 48px; font-size: 18px;">
                            S
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1" id="modal_student_name">-</h6>
                            <div class="text-muted small d-flex flex-wrap align-items-center gap-2" style="font-size: 12px;">
                                <span id="modal_reg_no" class="badge bg-white text-dark border fw-medium">-</span>
                                <span><i class="fas fa-phone-alt text-muted me-1"></i><span id="modal_mobile">-</span></span>
                                <span><i class="fas fa-user text-muted me-1"></i><span id="modal_gender">-</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        
                        <!-- Slot Selection -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small mb-1">Slot <span class="text-danger">*</span></label>
                            <select name="slot_id" id="modal_slot_id" class="form-select form-select-sm py-2" required>
                                <option value="">-- Choose Slot --</option>
                                <?php foreach ($slots_list as $sl): ?>
                                    <option value="<?= $sl['id'] ?>">
                                        <?= htmlspecialchars($sl['slot_name']) ?> (<?= date('h:i A', strtotime($sl['start_time'])) ?> - <?= date('h:i A', strtotime($sl['end_time'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Seat Allocation (Dynamic based on slot) -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small mb-1">Seat to Occupy <span class="text-danger">*</span></label>
                            <select name="seat_id" id="modal_seat_id" class="form-select form-select-sm py-2" required>
                                <option value="">-- Select Slot First --</option>
                            </select>
                            <div id="modal_seat_hint" class="text-muted mt-1" style="font-size: 11px;">
                                Only vacant seats in the chosen slot will be shown.
                            </div>
                        </div>

                        <!-- Fee Plan -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small mb-1">Fee Plan</label>
                            <select name="plan_id" id="modal_plan_id" class="form-select form-select-sm py-2">
                                <option value="">-- No Plan / Custom --</option>
                                <?php foreach ($plans_list as $pl): ?>
                                    <option value="<?= $pl['id'] ?>" data-amount="<?= $pl['amount'] ?>">
                                        <?= htmlspecialchars($pl['plan_name']) ?> — ₹<?= number_format($pl['amount'], 0) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Joining Date -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small mb-1">Joining Date</label>
                            <input type="date" name="joining_date" id="modal_joining_date" class="form-control form-control-sm py-2" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Initial Payment Received -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small mb-1">Initial Payment Received (₹)</label>
                            <input type="number" name="paid_amount" id="modal_paid_amount" class="form-control form-control-sm py-2" value="0" min="0" step="any">
                            <small class="text-muted" style="font-size: 11px;">Enter 0 if payment will be collected later.</small>
                        </div>

                        <!-- Payment Mode -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small mb-1">Payment Mode</label>
                            <select name="payment_mode" class="form-select form-select-sm py-2">
                                <option value="cash">Cash</option>
                                <option value="online">Online / UPI</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>

                    </div>

                    <div class="alert alert-light border rounded-3 mt-4 mb-0 py-2.5 px-3 d-flex align-items-center gap-2 text-secondary" style="font-size: 12px;">
                        <i class="fas fa-shield-alt text-success fs-6"></i>
                        <span>Approving will activate student login credentials (default password: mobile number) and officially lock the seat.</span>
                    </div>

                </div>

                <div class="modal-footer bg-light py-2.5 px-4 border-top" style="border-radius: 0 0 16px 16px;">
                    <button type="button" class="btn btn-sm btn-outline-secondary fw-semibold px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-success fw-bold px-4 py-2 shadow-sm">
                        <i class="fas fa-check-circle me-1"></i> Approve & Occupy Seat
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
var currentTargetSeat = 0;

function openVerifyModal(student) {
    $('#modal_student_id').val(student.id);
    $('#modal_student_name').text(student.name);
    $('#modal_reg_no').text(student.registration_no);
    $('#modal_mobile').text(student.mobile);
    $('#modal_gender').text(student.gender || 'Not specified');
    $('#modal_avatar_box').text((student.name || 'S').charAt(0).toUpperCase());
    $('#modal_joining_date').val(student.joining_date || '<?= date('Y-m-d') ?>');

    currentTargetSeat = parseInt(student.seat_id) || 0;

    // Set slot
    if (student.slot_id) {
        $('#modal_slot_id').val(student.slot_id);
    }

    // Set fee plan
    if (student.plan_id) {
        $('#modal_plan_id').val(student.plan_id);
        var planAmt = $('#modal_plan_id option:selected').data('amount') || 0;
        $('#modal_paid_amount').val(planAmt);
    } else {
        $('#modal_plan_id').val('');
        $('#modal_paid_amount').val(0);
    }

    // Load available seats for this slot
    loadModalSeats($('#modal_slot_id').val(), currentTargetSeat);

    var myModal = new bootstrap.Modal(document.getElementById('verifyModal'));
    myModal.show();
}

function loadModalSeats(slotId, targetSeat) {
    if (!slotId) {
        $('#modal_seat_id').html('<option value="">-- Select Slot First --</option>');
        return;
    }

    $('#modal_seat_hint').html('<i class="fas fa-spinner fa-spin me-1"></i> Checking seat availability...');

    $.get('ajax_get_seats.php', {
        slot_id: slotId,
        current_seat: targetSeat
    }, function(data){
        $('#modal_seat_id').html(data);
        if (targetSeat) {
            $('#modal_seat_id').val(targetSeat);
        }
        $('#modal_seat_hint').text('Only vacant seats in the chosen slot are shown.');
    }).fail(function(){
        $('#modal_seat_hint').text('Failed to load seats. Please try again.');
    });
}

$('#modal_slot_id').on('change', function(){
    loadModalSeats($(this).val(), 0);
});

$('#modal_plan_id').on('change', function(){
    var amt = $('option:selected', this).data('amount') || 0;
    $('#modal_paid_amount').val(amt);
});
</script>

<?php include "layout/footer.php"; ?>
