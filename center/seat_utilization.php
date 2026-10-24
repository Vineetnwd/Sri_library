<?php
require_once "layout/header.php";
require_once "../system/db.php";
require_once "../system/functions.php";

$cid = center_id();

/* ================================
   GLOBAL STATS
================================ */
$total_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total FROM seats WHERE center_id='$cid'"));
$total_seats = (int)$total_row['total'];

$used_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT seat_id) as total FROM students
     WHERE center_id='$cid' AND status='active' AND seat_id IS NOT NULL"));
$occupied = (int)$used_row['total'];

$free = $total_seats - $occupied;
$rate = $total_seats > 0 ? round(($occupied / $total_seats) * 100) : 0;

/* ================================
   SLOT-WISE BREAKDOWN
================================ */
$slots_data = [];
$q_slots = mysqli_query($conn,
    "SELECT * FROM slots WHERE center_id='$cid' AND status='active' ORDER BY start_time");

while($sl = mysqli_fetch_assoc($q_slots)){
    $slot_id = $sl['id'];

    // Students in this slot
    $stu_count = (int)mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as c FROM students
         WHERE center_id='$cid' AND status='active' AND slot_id='$slot_id'"))['c'];

    // Unique seats used in this slot
    $seats_used = (int)mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(DISTINCT seat_id) as c FROM students
         WHERE center_id='$cid' AND status='active' AND slot_id='$slot_id'"))['c'];

    $sl['stu_count'] = $stu_count;
    $sl['seats_used'] = $seats_used;
    $sl['seats_free'] = $total_seats - $seats_used;
    $sl['slot_rate'] = $total_seats > 0 ? round(($seats_used / $total_seats) * 100) : 0;

    // Get student list for this slot
    $q_stus = mysqli_query($conn,
        "SELECT name, registration_no, seat_id,
                (SELECT seat_no FROM seats WHERE id=students.seat_id) as seat_no
         FROM students
         WHERE center_id='$cid' AND status='active' AND slot_id='$slot_id'
         ORDER BY name");
    $sl['students'] = [];
    while($stu = mysqli_fetch_assoc($q_stus)){
        $sl['students'][] = $stu;
    }

    $slots_data[] = $sl;
}

/* ================================
   TOP OCCUPIED SEATS
================================ */
$top_seats = [];
$q_top = mysqli_query($conn,
    "SELECT s.seat_no, COUNT(st.id) as usage_count
     FROM seats s
     LEFT JOIN students st ON st.seat_id = s.id AND st.status='active'
     WHERE s.center_id='$cid'
     GROUP BY s.id
     ORDER BY usage_count DESC
     LIMIT 10");
while($row = mysqli_fetch_assoc($q_top)){
    $top_seats[] = $row;
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">
        <i class="fa fa-chart-pie me-2 text-info"></i> Seat Utilization
    </h4>
    <a href="seats.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-chair me-1"></i> Manage Seats
    </a>
</div>

<!-- GLOBAL STATS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width:44px;height:44px;flex-shrink:0;">
                    <i class="fas fa-chair" style="font-size:16px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Total Seats</div>
                    <div class="fw-bold" style="font-size:22px;line-height:1.1;"><?= $total_seats ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width:44px;height:44px;flex-shrink:0;">
                    <i class="fas fa-user" style="font-size:16px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Occupied</div>
                    <div class="fw-bold text-danger" style="font-size:22px;line-height:1.1;"><?= $occupied ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width:44px;height:44px;flex-shrink:0;">
                    <i class="fas fa-check" style="font-size:16px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Available</div>
                    <div class="fw-bold text-success" style="font-size:22px;line-height:1.1;"><?= $free ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px;">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width:44px;height:44px;flex-shrink:0;">
                    <i class="fas fa-percent" style="font-size:16px;"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:10px;text-transform:uppercase;font-weight:600;">Utilization</div>
                    <div class="fw-bold" style="font-size:22px;line-height:1.1;"><?= $rate ?>%</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- OVERALL PROGRESS BAR -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold text-muted" style="font-size:12px;text-transform:uppercase;">Overall Occupancy Rate</span>
            <span class="fw-bold text-dark"><?= $occupied ?> / <?= $total_seats ?> seats</span>
        </div>
        <div class="progress" style="height:10px;border-radius:99px;background:#f0f0f0;">
            <div class="progress-bar <?= $rate > 80 ? 'bg-danger' : ($rate > 50 ? 'bg-warning' : 'bg-success') ?>"
                 role="progressbar"
                 style="width:<?= $rate ?>%;border-radius:99px;"
                 title="<?= $rate ?>% occupied">
            </div>
        </div>
        <div class="d-flex justify-content-between mt-1">
            <small class="text-muted">0%</small>
            <small class="fw-semibold <?= $rate > 80 ? 'text-danger' : ($rate > 50 ? 'text-warning' : 'text-success') ?>"><?= $rate ?>% Full</small>
            <small class="text-muted">100%</small>
        </div>
    </div>
</div>

<!-- SLOT-WISE BREAKDOWN -->
<?php if(!empty($slots_data)){ ?>
<h6 class="text-muted fw-semibold mb-3" style="text-transform:uppercase;font-size:11px;letter-spacing:.5px;">
    <i class="fas fa-clock me-1"></i> Slot-wise Breakdown
</h6>
<div class="row g-3 mb-4">
    <?php foreach($slots_data as $sl){ ?>
    <div class="col-md-6 col-xl-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body">
                <!-- Slot Header -->
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($sl['slot_name']) ?></div>
                        <div class="text-muted" style="font-size:12px;">
                            <i class="fas fa-clock me-1"></i>
                            <?= date('h:i A', strtotime($sl['start_time'])) ?> – <?= date('h:i A', strtotime($sl['end_time'])) ?>
                        </div>
                    </div>
                    <span class="badge rounded-pill <?= $sl['slot_rate'] > 80 ? 'bg-danger' : ($sl['slot_rate'] > 50 ? 'bg-warning text-dark' : 'bg-success') ?>"
                          style="font-size:11px;">
                        <?= $sl['slot_rate'] ?>% Full
                    </span>
                </div>

                <!-- Mini Stats -->
                <div class="d-flex gap-3 mb-3">
                    <div class="text-center">
                        <div class="fw-bold text-danger" style="font-size:18px;"><?= $sl['seats_used'] ?></div>
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase;">Occupied</div>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold text-success" style="font-size:18px;"><?= $sl['seats_free'] ?></div>
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase;">Free</div>
                    </div>
                    <div class="text-center">
                        <div class="fw-bold text-primary" style="font-size:18px;"><?= $sl['stu_count'] ?></div>
                        <div class="text-muted" style="font-size:10px;text-transform:uppercase;">Students</div>
                    </div>
                </div>

                <!-- Progress -->
                <div class="progress mb-3" style="height:6px;border-radius:99px;background:#f0f0f0;">
                    <div class="progress-bar <?= $sl['slot_rate'] > 80 ? 'bg-danger' : ($sl['slot_rate'] > 50 ? 'bg-warning' : 'bg-success') ?>"
                         style="width:<?= $sl['slot_rate'] ?>%;border-radius:99px;"></div>
                </div>

                <!-- Student List -->
                <?php if(!empty($sl['students'])){ ?>
                <div style="max-height:160px;overflow-y:auto;">
                    <?php foreach($sl['students'] as $stu){ ?>
                    <div class="d-flex align-items-center gap-2 py-1 border-bottom border-light">
                        <i class="fas fa-user-circle text-secondary" style="font-size:13px;"></i>
                        <div style="min-width:0;">
                            <span class="fw-semibold text-dark" style="font-size:12px;"><?= htmlspecialchars($stu['name']) ?></span>
                            <span class="text-muted" style="font-size:11px;"> · Seat <?= $stu['seat_no'] ?? '?' ?></span>
                        </div>
                    </div>
                    <?php } ?>
                </div>
                <?php } else { ?>
                    <div class="text-muted fst-italic text-center" style="font-size:12px;">No students in this slot</div>
                <?php } ?>
            </div>
        </div>
    </div>
    <?php } ?>
</div>
<?php } ?>

<!-- SEAT USAGE TABLE -->
<div class="card shadow border-0 mb-4" style="border-radius:12px;">
    <div class="card-header bg-white border-bottom py-3">
        <span class="fw-semibold"><i class="fas fa-table me-2 text-info"></i>Seat Usage Overview</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3 text-uppercase text-muted" style="font-size:11px;">Seat No.</th>
                    <th class="text-uppercase text-muted" style="font-size:11px;">Used in Slots</th>
                    <th class="text-uppercase text-muted text-end pe-3" style="font-size:11px;">Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($top_seats as $seat){ ?>
            <tr>
                <td class="ps-3 fw-bold text-dark" style="font-size:15px;"><?= $seat['seat_no'] ?></td>
                <td class="text-muted" style="font-size:13px;"><?= $seat['usage_count'] ?> <?= $seat['usage_count'] == 1 ? 'slot' : 'slots' ?></td>
                <td class="text-end pe-3">
                    <?php if($seat['usage_count'] == 0){ ?>
                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success" style="font-size:10px;">Free</span>
                    <?php } elseif($seat['usage_count'] == 1){ ?>
                        <span class="badge rounded-pill bg-danger bg-opacity-10 text-danger border border-danger" style="font-size:10px;">Occupied</span>
                    <?php } else { ?>
                        <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary" style="font-size:10px;"><?= $seat['usage_count'] ?> Slots</span>
                    <?php } ?>
                </td>
            </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<?php include "layout/footer.php"; ?>
