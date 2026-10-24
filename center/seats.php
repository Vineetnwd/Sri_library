<?php
require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();
$view_mode = $_GET['view'] ?? 'grid';
$slot_filter = (int)($_GET['slot'] ?? 0);

/* ================================
   GET ALL SLOTS
================================ */
$slots_raw = [];
$q_slots = mysqli_query($conn, "SELECT * FROM slots WHERE center_id='$cid' AND status='active' ORDER BY start_time");
while($sl = mysqli_fetch_assoc($q_slots)){
    $slots_raw[$sl['id']] = $sl;
}

/* ================================
   GET ALL SEATS
================================ */
$seats_raw = [];
$q_seats = mysqli_query($conn, "SELECT * FROM seats WHERE center_id='$cid' ORDER BY seat_no ASC");
while($seat = mysqli_fetch_assoc($q_seats)){
    $seats_raw[$seat['id']] = $seat;
    $seats_raw[$seat['id']]['students'] = [];
}

/* ================================
   GET ALL ACTIVE STUDENTS & GROUP BY SEAT
================================ */
$student_where = $slot_filter ? "AND st.slot_id='$slot_filter'" : "";
$q_students = mysqli_query($conn,
    "SELECT st.seat_id, st.id as student_id, st.name, st.registration_no, st.slot_id
     FROM students st
     WHERE st.center_id='$cid' AND st.status='active' AND st.seat_id IS NOT NULL $student_where
     ORDER BY st.slot_id"
);
while($stu = mysqli_fetch_assoc($q_students)){
    $sid = $stu['seat_id'];
    if(isset($seats_raw[$sid])){
        $seats_raw[$sid]['students'][] = $stu;
    }
}

/* ================================
   STATS (based on ALL slots, not filter)
================================ */
$total_seats = count($seats_raw);

// For stats, count unique seats that have at least one student in any slot
$q_occ = mysqli_query($conn,
    "SELECT COUNT(DISTINCT seat_id) as cnt FROM students WHERE center_id='$cid' AND status='active' AND seat_id IS NOT NULL"
);
$occ_row = mysqli_fetch_assoc($q_occ);
$occupied_count = (int)$occ_row['cnt'];
$available = $total_seats - $occupied_count;
$occupancy_rate = $total_seats > 0 ? round(($occupied_count/$total_seats)*100) : 0;
?>

<?php include "layout/sidebar.php"; ?>

<style>
/* ---- Seat Grid ---- */
.seat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 12px;
    padding: 16px;
}

.seat-card {
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 12px 8px;
    cursor: pointer;
    transition: transform .2s, box-shadow .2s;
    position: relative;
    text-align: center;
}

.seat-card.available {
    background: #f0fdf4;
    border: 2px solid #86efac;
    color: #166534;
}

.seat-card.occupied {
    background: #fff7ed;
    border: 2px solid #fb923c;
    color: #9a3412;
}

.seat-card.multi {
    background: #eff6ff;
    border: 2px solid #60a5fa;
    color: #1e40af;
}

.seat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}

.seat-number {
    font-size: 22px;
    font-weight: 700;
    line-height: 1;
}

.seat-label {
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-top: 4px;
    opacity: .75;
}

.seat-names {
    font-size: 10px;
    margin-top: 6px;
    line-height: 1.3;
    font-weight: 500;
}

/* ---- Stats ---- */
.stat-card {
    border: none;
    border-radius: 12px;
    padding: 16px 20px;
}
</style>

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">
        <i class="fas fa-chair me-2 text-primary"></i>Seat Management
    </h4>
    <div class="d-flex gap-2">
        <a href="seat_add.php" class="btn btn-primary btn-sm fw-semibold">
            <i class="fas fa-plus me-1"></i>Add Seat
        </a>
        <div class="btn-group btn-group-sm">
            <a href="?view=list<?= $slot_filter ? '&slot='.$slot_filter : '' ?>" 
               class="btn btn-<?= $view_mode=='list'?'dark':'outline-dark' ?>">
                <i class="fas fa-list"></i> List
            </a>
            <a href="?view=grid<?= $slot_filter ? '&slot='.$slot_filter : '' ?>"
               class="btn btn-<?= $view_mode=='grid'?'dark':'outline-dark' ?>">
                <i class="fas fa-th"></i> Grid
            </a>
        </div>
    </div>
</div>

<!-- STATS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card bg-light shadow-sm d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary" style="width:42px;height:42px;display:flex!important;align-items:center;justify-content:center;">
                <i class="fas fa-chair"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;">Total</div>
                <div class="fw-bold fs-4 lh-1"><?= $total_seats ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-light shadow-sm d-flex align-items-center gap-3">
            <div class="rounded-circle bg-danger bg-opacity-10 p-2 text-danger" style="width:42px;height:42px;display:flex!important;align-items:center;justify-content:center;">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;">Occupied</div>
                <div class="fw-bold fs-4 lh-1"><?= $occupied_count ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-light shadow-sm d-flex align-items-center gap-3">
            <div class="rounded-circle bg-success bg-opacity-10 p-2 text-success" style="width:42px;height:42px;display:flex!important;align-items:center;justify-content:center;">
                <i class="fas fa-check"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;">Available</div>
                <div class="fw-bold fs-4 lh-1"><?= $available ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-light shadow-sm d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning bg-opacity-10 p-2 text-warning" style="width:42px;height:42px;display:flex!important;align-items:center;justify-content:center;">
                <i class="fas fa-percent"></i>
            </div>
            <div>
                <div class="text-muted" style="font-size:11px;text-transform:uppercase;font-weight:600;">Occupancy</div>
                <div class="fw-bold fs-4 lh-1"><?= $occupancy_rate ?>%</div>
            </div>
        </div>
    </div>
</div>

<!-- FILTER -->
<div class="card shadow-sm border-0 mb-4" style="border-radius:12px;">
    <div class="card-body py-3">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="view" value="<?= $view_mode ?>">
            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted mb-1" style="font-size:12px;text-transform:uppercase;">Filter by Slot</label>
                <select name="slot" class="form-select form-select-sm">
                    <option value="">All Slots</option>
                    <?php foreach($slots_raw as $sl){ ?>
                        <option value="<?= $sl['id'] ?>" <?= $slot_filter==$sl['id']?'selected':'' ?>>
                            <?= $sl['slot_name'] ?> (<?= date('h:i A', strtotime($sl['start_time'])) ?> - <?= date('h:i A', strtotime($sl['end_time'])) ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="?view=<?= $view_mode ?>" class="btn btn-outline-secondary btn-sm ms-1"><i class="fas fa-redo me-1"></i>Reset</a>
            </div>
        </form>
    </div>
</div>

<?php if($view_mode == 'grid'){ ?>

<!-- GRID VIEW -->
<div class="card shadow border-0" style="border-radius:12px;">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
        <span class="fw-semibold"><i class="fas fa-th me-2 text-primary"></i>Seat Layout</span>
        <div class="d-flex gap-3 align-items-center" style="font-size:12px;">
            <span><span class="d-inline-block rounded me-1" style="width:12px;height:12px;background:#86efac;"></span>Available</span>
            <span><span class="d-inline-block rounded me-1" style="width:12px;height:12px;background:#fb923c;"></span>Occupied (1 slot)</span>
            <span><span class="d-inline-block rounded me-1" style="width:12px;height:12px;background:#60a5fa;"></span>Multi-slot</span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if(empty($seats_raw)){ ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-chair fa-3x mb-3 d-block opacity-25"></i>
                <p>No seats found. <a href="seat_add.php">Add a seat</a> to get started.</p>
            </div>
        <?php } else { ?>
        <div class="seat-grid">
            <?php foreach($seats_raw as $seat){
                $stus = $seat['students'];
                $count = count($stus);
                if($count === 0) $class = 'available';
                elseif($count === 1) $class = 'occupied';
                else $class = 'multi';
            ?>
                <div class="seat-card <?= $class ?>" 
                     onclick="showSeat(<?= $seat['id'] ?>)"
                     title="Seat <?= $seat['seat_no'] ?> — click to preview">
                    <i class="fas fa-chair mb-1" style="font-size:20px;opacity:.6;"></i>
                    <div class="seat-number"><?= $seat['seat_no'] ?></div>
                    <div class="seat-label"><?= $count === 0 ? 'Free' : ($count > 1 ? $count.' slots' : 'Occupied') ?></div>
                    <?php if($count > 0){ ?>
                        <div class="seat-names">
                            <?php foreach($stus as $stu){ ?>
                                <div>
                                    <?= substr($stu['name'], 0, 12) ?><?= strlen($stu['name'])>12?'..':'' ?>
                                    <?php if(isset($slots_raw[$stu['slot_id']])){ ?>
                                        <br><span style="opacity:.65;font-size:9px;"><?= $slots_raw[$stu['slot_id']]['slot_name'] ?></span>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</div>

<?php } else { ?>

<!-- LIST VIEW -->
<div class="card shadow border-0" style="border-radius:12px;">
    <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold"><i class="fas fa-table me-2 text-primary"></i>All Seats</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 text-uppercase text-muted" style="font-size:11px;width:60px;">Seat</th>
                    <th class="text-uppercase text-muted" style="font-size:11px;">Status</th>
                    <th class="text-uppercase text-muted" style="font-size:11px;">Students (All Slots)</th>
                    <th class="text-uppercase text-muted text-end pe-3" style="font-size:11px;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if(empty($seats_raw)){ ?>
                <tr>
                    <td colspan="4" class="text-center py-5 text-muted">
                        <i class="fas fa-chair fa-2x mb-2 d-block opacity-25"></i>
                        No seats found
                    </td>
                </tr>
            <?php } ?>
            <?php foreach($seats_raw as $seat){
                $stus = $seat['students'];
                $count = count($stus);
                $occupied = $count > 0;
            ?>
                <tr onclick="showSeat(<?= $seat['id'] ?>)" style="cursor:pointer;">
                    <td class="ps-3 fw-bold text-dark" style="font-size:15px;"><?= $seat['seat_no'] ?></td>
                    <td>
                        <?php if($count === 0){ ?>
                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success" style="font-size:10px;">Free</span>
                        <?php } elseif($count === 1){ ?>
                            <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger" style="font-size:10px;">Occupied</span>
                        <?php } else { ?>
                            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary" style="font-size:10px;"><?= $count ?> Slots</span>
                        <?php } ?>
                    </td>
                    <td>
                        <?php if($count === 0){ ?>
                            <span class="text-muted fst-italic" style="font-size:12px;">—</span>
                        <?php } else { ?>
                            <div class="d-flex flex-column gap-1">
                            <?php foreach($stus as $stu){ ?>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-user-circle text-secondary" style="font-size:13px;"></i>
                                    <div>
                                        <span class="fw-semibold text-dark" style="font-size:13px;"><?= htmlspecialchars($stu['name']) ?></span>
                                        <small class="text-muted ms-1">(<?= $stu['registration_no'] ?>)</small>
                                        <?php if(isset($slots_raw[$stu['slot_id']])){ 
                                            $sl = $slots_raw[$stu['slot_id']];
                                        ?>
                                            <span class="badge bg-light text-dark border ms-1" style="font-size:10px;">
                                                <?= $sl['slot_name'] ?> · <?= date('h:i A', strtotime($sl['start_time'])) ?>
                                            </span>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                            </div>
                        <?php } ?>
                    </td>
                    <td class="text-end pe-3">
                        <a href="seat_edit.php?id=<?= $seat['id'] ?>" class="btn btn-sm btn-light border text-primary fw-semibold" style="font-size:12px;">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <?php if(!$occupied){ ?>
                            <a href="seat_delete.php?id=<?= $seat['id'] ?>"
                               onclick="return confirm('Delete seat <?= $seat['seat_no'] ?>?')"
                               class="btn btn-sm btn-light border text-danger fw-semibold ms-1" style="font-size:12px;">
                                <i class="fas fa-trash me-1"></i>Delete
                            </a>
                        <?php } else { ?>
                            <button class="btn btn-sm btn-light border text-muted ms-1" disabled title="Seat is occupied" style="font-size:12px;">
                                <i class="fas fa-lock me-1"></i>Locked
                            </button>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php } ?>

</div>

<?php include "layout/footer.php"; ?>

<!-- SEAT PREVIEW MODAL -->
<div class="modal fade" id="seatModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius:16px;overflow:hidden;">

      <div class="modal-header border-0 pb-0" id="seatModalHeader" style="background:#f9fafb;">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="seatModalTitle">Seat —</h5>
          <small id="seatModalSub" class="text-muted"></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-0" id="seatModalBody" style="background:#f9fafb;">
        <div class="text-center py-5 text-muted">
          <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
          <div class="mt-2" style="font-size:13px;">Loading...</div>
        </div>
      </div>

      <div class="modal-footer border-0 bg-white" id="seatModalFooter">
        <button class="btn btn-sm btn-light border fw-semibold" data-bs-dismiss="modal">Close</button>
        <a id="seatEditBtn" href="#" class="btn btn-sm btn-primary fw-semibold">
          <i class="fas fa-edit me-1"></i>Edit Seat
        </a>
      </div>

    </div>
  </div>
</div>

<script>
function showSeat(seatId) {
    const modal = new bootstrap.Modal(document.getElementById('seatModal'));
    const body = document.getElementById('seatModalBody');
    const title = document.getElementById('seatModalTitle');
    const sub = document.getElementById('seatModalSub');
    const editBtn = document.getElementById('seatEditBtn');

    // Reset to loading state
    body.innerHTML = `
        <div class="text-center py-5 text-muted">
            <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
            <div class="mt-2" style="font-size:13px;">Loading...</div>
        </div>`;
    title.textContent = 'Seat — ';
    sub.textContent = '';
    editBtn.href = 'seat_edit.php?id=' + seatId;

    modal.show();

    fetch('ajax_seat_info.php?id=' + seatId)
        .then(r => r.json())
        .then(data => {
            const seat = data.seat;
            const students = data.students;

            title.textContent = 'Seat ' + seat.seat_no;
            sub.textContent = students.length === 0 ? 'Available' : students.length + ' student(s) assigned';

            if(students.length === 0){
                body.innerHTML = `
                    <div class="text-center py-5">
                        <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center mx-auto mb-3" style="width:56px;height:56px;">
                            <i class="fas fa-check" style="font-size:22px;"></i>
                        </div>
                        <div class="fw-semibold text-dark">Seat is Available</div>
                        <div class="text-muted" style="font-size:13px;">No active student assigned to this seat.</div>
                    </div>`;
            } else {
                let html = '<div class="p-3">';
                students.forEach(s => {
                    const due = parseFloat(s.plan_amount || 0) - parseFloat(s.total_paid || 0);
                    const dueLabel = due > 0
                        ? `<span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger" style="font-size:10px;">Due ₹${parseInt(due).toLocaleString('en-IN')}</span>`
                        : `<span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success" style="font-size:10px;">Cleared</span>`;

                    html += `
                    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-bold text-dark">${s.name}</div>
                                    <small class="text-muted">${s.registration_no}</small>
                                </div>
                                ${dueLabel}
                            </div>
                            <div class="row g-2" style="font-size:12px;">
                                <div class="col-6 d-flex gap-2 align-items-center text-muted">
                                    <i class="fas fa-clock text-primary" style="width:14px;"></i>
                                    <span>${s.slot_name || 'No Slot'} ${ s.start_time ? '(' + formatTime(s.start_time) + ')' : '' }</span>
                                </div>
                                <div class="col-6 d-flex gap-2 align-items-center text-muted">
                                    <i class="fas fa-phone text-success" style="width:14px;"></i>
                                    <span>${s.mobile || '—'}</span>
                                </div>
                                <div class="col-6 d-flex gap-2 align-items-center text-muted">
                                    <i class="fas fa-layer-group text-info" style="width:14px;"></i>
                                    <span>${s.plan_name || 'No Plan'}</span>
                                </div>
                                <div class="col-6 d-flex gap-2 align-items-center text-muted">
                                    <i class="fas fa-calendar text-warning" style="width:14px;"></i>
                                    <span>Joined: ${formatDate(s.joining_date)}</span>
                                </div>
                            </div>
                            <div class="mt-2 text-end">
                                <a href="payment_add.php?student_id=${s.id}" class="btn btn-sm btn-light border text-success fw-semibold" style="font-size:11px;">
                                    <i class="fas fa-rupee-sign me-1"></i>Pay Now
                                </a>
                                <a href="student_edit.php?id=${s.id}" class="btn btn-sm btn-light border text-primary fw-semibold ms-1" style="font-size:11px;">
                                    <i class="fas fa-user-edit me-1"></i>Edit Student
                                </a>
                            </div>
                        </div>
                    </div>`;
                });
                html += '</div>';
                body.innerHTML = html;
            }
        })
        .catch(() => {
            body.innerHTML = '<div class="text-center py-4 text-danger">Failed to load seat info.</div>';
        });
}

function formatTime(t){
    if(!t) return '';
    const [h, m] = t.split(':');
    const hr = parseInt(h);
    return (hr > 12 ? hr - 12 : hr || 12) + ':' + m + (hr >= 12 ? ' PM' : ' AM');
}

function formatDate(d){
    if(!d) return '—';
    const [y, mo, day] = d.split('-');
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return day + ' ' + months[parseInt(mo)-1] + ' ' + y;
}
</script>