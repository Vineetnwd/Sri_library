<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

/* Search functionality */
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where = "WHERE s.center_id='$cid'";

if ($search) {
    $where .= " AND (s.name LIKE '%$search%' OR s.registration_no LIKE '%$search%' OR s.mobile LIKE '%$search%')";
}

if ($status_filter) {
    $where .= " AND s.status='$status_filter'";
}

$q = mysqli_query(
    $conn,
    "SELECT s.*, st.slot_name, se.seat_no
     FROM students s
     LEFT JOIN slots st ON st.id = s.slot_id
     LEFT JOIN seats se ON se.id = s.seat_id
     $where
     ORDER BY s.id DESC"
);

$total = mysqli_num_rows($q);
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">
            <i class="fas fa-users me-2 text-primary"></i> Students Management
        </h4>
        <a href="student_add.php" class="btn btn-primary btn-sm fw-semibold">
            <i class="fas fa-user-plus me-1"></i> Add New Student
        </a>
    </div>

    <!-- Filters Card -->
    <div class="card shadow-sm border-0 mb-3" style="border-radius:12px;">
        <div class="card-body p-3">
            <form method="get" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i
                                class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0"
                            placeholder="Search Name, Reg No, Mobile..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm text-muted">
                        <option value="">-- All Status --</option>
                        <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending Verification</option>
                        <option value="left" <?= $status_filter == 'left' ? 'selected' : '' ?>>Left</option>
                        <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <div class="col-md-5 d-flex gap-2 align-items-center">
                    <button class="btn btn-primary btn-sm fw-semibold px-3">Filter</button>
                    <a href="students.php" class="btn btn-light border btn-sm text-muted px-3">Reset</a>
                    <span class="ms-auto text-muted" style="font-size:12px;">
                        <i class="fas fa-users text-primary"></i> <strong><?= $total ?></strong> students found
                    </span>
                </div>
            </form>
        </div>
    </div>

    <?php
    $pending_cnt_students = 0;
    $pq_st = mysqli_query($conn, "SELECT COUNT(*) c FROM students WHERE center_id='$cid' AND status='pending'");
    if ($pq_st) {
        $pending_cnt_students = (int)mysqli_fetch_assoc($pq_st)['c'];
    }
    ?>

    <?php if ($pending_cnt_students > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between p-3 mb-3 rounded-3 gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-hourglass-half fa-lg text-warning"></i>
                <span class="text-dark">
                    <strong><?= $pending_cnt_students ?> online student application(s)</strong> are waiting for your verification. Seats are not occupied until verified.
                </span>
            </div>
            <a href="pending_admissions.php" class="btn btn-dark btn-sm fw-semibold">
                <i class="fas fa-check-circle me-1 text-warning"></i> Review & Verify Now
            </a>
        </div>
    <?php endif; ?>

    <form method="post" id="printForm" target="_blank">

        <!-- Action Buttons -->
        <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
            <button type="button" onclick="submitForm('print_id_cards.php')"
                class="btn btn-sm btn-light border text-dark fw-semibold" style="font-size:12px;">
                <i class="fas fa-id-badge me-1 text-primary"></i> Print Selected IDs
            </button>
            <a href="print_id_cards.php?all=1" target="_blank" class="btn btn-sm btn-light border text-dark fw-semibold"
                style="font-size:12px;">
                <i class="fas fa-print me-1 text-success"></i> Print All IDs
            </a>
            <button type="button" onclick="submitForm('reg_slip.php')"
                class="btn btn-sm btn-light border text-dark fw-semibold" style="font-size:12px;">
                <i class="fas fa-file-invoice me-1 text-warning"></i> Print Reg. Slips
            </button>
            <a href="export_students.php?search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>"
                class="btn btn-sm btn-light border text-dark fw-semibold" style="font-size:12px;">
                <i class="fas fa-file-excel me-1 text-success"></i> Export Excel
            </a>
            <a href="admission_qr.php" class="btn btn-sm btn-outline-warning text-dark fw-semibold ms-auto" style="font-size:12px;">
                <i class="fas fa-qrcode me-1 text-warning"></i> Admission QR & Standee
            </a>
            <?php if ($pending_cnt_students > 0): ?>
                <a href="pending_admissions.php" class="btn btn-sm btn-warning fw-semibold shadow-sm" style="font-size:12px;">
                    <i class="fas fa-hourglass-half me-1"></i> Pending (<?= $pending_cnt_students ?>)
                </a>
            <?php endif; ?>
        </div>

        <!-- Students Table Card -->
        <div class="card shadow-sm border-0" style="border-radius:12px;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="studentTable" class="table table-hover table-sm align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width:30px;">
                                    <input class="form-check-input" type="checkbox" id="select_all">
                                </th>
                                <th class="text-uppercase text-muted" style="font-size:11px;">#</th>
                                <th class="text-uppercase text-muted" style="font-size:11px;">Reg No</th>
                                <th class="text-uppercase text-muted" style="font-size:11px;">Student Details</th>
                                <th class="text-uppercase text-muted" style="font-size:11px;">Contact</th>
                                <th class="text-uppercase text-muted" style="font-size:11px;">Seat</th>
                                <th class="text-uppercase text-muted" style="font-size:11px;">Slot</th>
                                <th class="text-uppercase text-muted" style="font-size:11px;">Joining</th>
                                <th class="text-uppercase text-muted text-center" style="font-size:11px;">Status</th>
                                <th class="text-uppercase text-muted text-end pe-4" style="font-size:11px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            while ($row = mysqli_fetch_assoc($q)) {
                                $st = $row['status'];
                                $status_color = ($st === 'active') ? 'success' : (($st === 'pending') ? 'warning' : (($st === 'rejected') ? 'danger' : 'secondary'));
                                ?>
                                <tr>
                                    <td class="ps-3">
                                        <input class="form-check-input" type="checkbox" name="student_id[]"
                                            value="<?= $row['id'] ?>">
                                    </td>
                                    <td class="text-muted" style="font-size:12px;"><?= $i++ ?></td>
                                    <td>
                                        <span class="badge rounded-pill bg-light text-dark border" style="font-size:11px;">
                                            <?= htmlspecialchars($row['registration_no']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size:13px;">
                                            <?= htmlspecialchars($row['name']) ?></div>
                                        <?php if ($row['email']) { ?>
                                            <div class="text-muted" style="font-size:11px;">
                                                <?= htmlspecialchars($row['email']) ?></div>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <div class="text-dark" style="font-size:12px;"><i
                                                class="fas fa-phone me-1 text-muted"></i><?= htmlspecialchars($row['mobile']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-info bg-opacity-10 text-info border border-info"
                                            style="font-size:10px;"><?= htmlspecialchars($row['seat_no'] ?: 'N/A') ?></span>
                                    </td>
                                    <td>
                                        <div class="text-muted" style="font-size:11px;">
                                            <?= htmlspecialchars($row['slot_name'] ?: 'No Slot') ?></div>
                                    </td>
                                    <td class="text-muted" style="font-size:12px;">
                                        <?= date('d M Y', strtotime($row['joining_date'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge rounded-pill bg-<?= $status_color ?> bg-opacity-10 text-<?= $status_color ?> border border-<?= $status_color ?>"
                                            style="font-size:10px;text-transform:uppercase;">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group shadow-sm">
                                            <?php if ($row['status'] === 'pending'): ?>
                                                <a href="pending_admissions.php" class="btn btn-sm btn-warning text-dark fw-bold" title="Verify Student Admission" style="font-size:11px;">
                                                    <i class="fas fa-check-circle me-1"></i> Verify
                                                </a>
                                            <?php endif; ?>
                                            <a href="student_edit.php?id=<?= $row['id'] ?>"
                                                class="btn btn-sm btn-light border text-primary" title="Edit Student"
                                                style="font-size:12px;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="student_ledger.php?id=<?= $row['id'] ?>"
                                                class="btn btn-sm btn-light border text-info" title="Student Ledger"
                                                style="font-size:12px;">
                                                <i class="fas fa-book"></i>
                                            </a>
                                            <a href="send_due.php?id=<?= $row['id'] ?>"
                                                class="btn btn-sm btn-light border text-success" title="WhatsApp Message"
                                                style="font-size:12px;">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                            <a href="student_delete.php?id=<?= $row['id'] ?>"
                                                class="btn btn-sm btn-light border text-danger"
                                                onclick="return confirm('Delete this student? This action cannot be undone.')"
                                                title="Delete Student" style="font-size:12px;">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </form>
</div>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
<!-- DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function () {
        $('#studentTable').DataTable({
            dom: "<'row p-3 pb-0'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-4 text-center'B><'col-sm-12 col-md-4'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row p-3 pt-0'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf me-1"></i> Export PDF',
                    className: 'btn btn-danger btn-sm fw-semibold',
                    title: 'All Students Report',
                    exportOptions: {
                        columns: [1, 2, 3, 4, 5, 6, 7, 8]
                    },
                    customize: function (doc) {
                        // Set page margins [left, top, right, bottom]
                        doc.pageMargins = [20, 30, 20, 30];

                        // Style the title
                        doc.styles.title = {
                            color: '#2c3e50',
                            fontSize: 18,
                            bold: true,
                            alignment: 'center',
                            margin: [0, 0, 0, 15]
                        };

                        // Document default styles
                        doc.defaultStyle.fontSize = 9;
                        doc.defaultStyle.color = '#333';

                        // Table Layout (Borders, padding, alternating rows)
                        var objLayout = {};
                        objLayout['hLineWidth'] = function (i) { return 0.5; };
                        objLayout['vLineWidth'] = function (i) { return 0.5; };
                        objLayout['hLineColor'] = function (i) { return '#e0e0e0'; };
                        objLayout['vLineColor'] = function (i) { return '#e0e0e0'; };
                        objLayout['paddingLeft'] = function (i) { return 5; };
                        objLayout['paddingRight'] = function (i) { return 5; };
                        objLayout['paddingTop'] = function (i) { return 5; };
                        objLayout['paddingBottom'] = function (i) { return 5; };
                        objLayout['fillColor'] = function (rowIndex, node, columnIndex) {
                            return (rowIndex === 0) ? '#343a40' : (rowIndex % 2 === 0) ? '#f9f9f9' : null;
                        };
                        doc.content[1].layout = objLayout;

                        // Table Header Style
                        doc.styles.tableHeader = {
                            color: '#ffffff',
                            fontSize: 10,
                            bold: true,
                            alignment: 'left',
                            margin: [0, 2, 0, 2]
                        };

                        // Modify widths for better distribution
                        doc.content[1].table.widths = ['5%', '12%', '26%', '15%', '8%', '14%', '12%', '8%'];

                        // Add a nice footer with page numbers
                        doc['footer'] = (function (page, pages) {
                            return {
                                columns: [
                                    {
                                        alignment: 'left',
                                        text: 'SelfStudy CRM - Generated on ' + new Date().toLocaleString(),
                                        fontSize: 8,
                                        margin: [20, 10],
                                        color: '#666'
                                    },
                                    {
                                        alignment: 'right',
                                        text: 'Page ' + page.toString() + ' of ' + pages.toString(),
                                        fontSize: 8,
                                        margin: [20, 10],
                                        color: '#666'
                                    }
                                ]
                            }
                        });
                    }
                }
            ],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [[1, 'asc']], // Order by # column
            language: {
                search: "",
                searchPlaceholder: "Search students..."
            },
            columnDefs: [
                { orderable: false, targets: [0, 9] } // Disable sorting on checkbox and actions
            ]
        });
    });

    document.getElementById("select_all").onclick = function () {
        let checkboxes = document.getElementsByName("student_id[]");
        for (let checkbox of checkboxes) {
            checkbox.checked = this.checked;
        }
    };

    function submitForm(action) {
        let form = document.getElementById('printForm');
        form.action = action;
        form.submit();
    }
</script>

<?php include "layout/footer.php"; ?>