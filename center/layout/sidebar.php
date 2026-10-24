<style>
.sidebar-wrap {
    width: 240px;
    height: 100vh;
    position: fixed;
    overflow-y: auto;
    overflow-x: hidden;
    background: #1e2227; /* sleek dark professional bg */
    color: #fff;
    padding: 20px 12px;
}
.sidebar-wrap::-webkit-scrollbar { width: 4px; }
.sidebar-wrap::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }

.sidebar-brand {
    font-size: 16px;
    font-weight: 700;
    color: #fff;
    text-align: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.sidebar-link {
    font-size: 13px;
    font-weight: 500;
    padding: 7px 12px;
    margin-bottom: 2px;
    border-radius: 6px;
    color: rgba(255,255,255,0.7) !important;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: all 0.2s;
}
.sidebar-link:hover {
    color: #fff !important;
    background: rgba(255,255,255,0.08);
}
.sidebar-link i {
    width: 20px;
    text-align: center;
    font-size: 13px;
    margin-right: 8px;
    opacity: 0.8;
}
.sidebar-heading {
    font-size: 10px;
    letter-spacing: 0.8px;
    color: rgba(255,255,255,0.4);
    text-transform: uppercase;
    font-weight: 700;
    margin-top: 20px;
    margin-bottom: 6px;
    padding-left: 12px;
}

/* Responsive Styles */
.main-content {
    margin-left: 240px;
    width: calc(100% - 240px);
    transition: margin 0.3s ease-in-out, width 0.3s ease-in-out;
    min-height: 100vh;
}
.mobile-header {
    display: none;
    background: #1e2227;
    color: white;
    padding: 15px 20px;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    z-index: 1030;
    backdrop-filter: blur(2px);
}
.sidebar-overlay.show {
    display: block;
}
@media (max-width: 991.98px) {
    .sidebar-wrap {
        transform: translateX(-100%);
        z-index: 1040;
        transition: transform 0.3s ease-in-out;
    }
    .sidebar-wrap.show {
        transform: translateX(0);
    }
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        padding: 15px !important;
    }
    .mobile-header {
        display: flex;
    }
}
</style>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Mobile Header -->
<div class="mobile-header d-lg-none">
    <div class="fw-bold fs-5">
        <i class="fa fa-book-reader me-2 text-primary"></i> SelfStudy
    </div>
    <button class="btn btn-link text-white p-0" id="sidebarToggle">
        <i class="fas fa-bars fs-4"></i>
    </button>
</div>

<div class="d-flex flex-column flex-lg-row">
    <div class="sidebar-wrap" id="sidebarWrap">
        <div class="sidebar-brand">
            <i class="fa fa-book-reader me-2 text-primary"></i> SelfStudy CRM
        </div>

        <ul class="nav flex-column" style="padding-bottom: 40px;">
            <li>
                <a href="dashboard.php" class="sidebar-link">
                    <i class="fa fa-chart-line text-info"></i> Dashboard
                </a>
            </li>

            <li class="sidebar-heading">Student Mgmt</li>
            <li>
                <a href="students.php" class="sidebar-link">
                    <i class="fa fa-users text-light"></i> All Students
                </a>
            </li>
            <li>
                <a href="student_add.php" class="sidebar-link">
                    <i class="fa fa-user-plus text-light"></i> Add Student
                </a>
            </li>
            <?php
            $sidebar_pending_cnt = 0;
            if (isset($conn) && function_exists('center_id') && center_id()) {
                $scid = center_id();
                $sp_q = mysqli_query($conn, "SELECT COUNT(*) c FROM students WHERE center_id='$scid' AND status='pending'");
                if ($sp_q) {
                    $sidebar_pending_cnt = (int)mysqli_fetch_assoc($sp_q)['c'];
                }
            }
            ?>
            <li>
                <a href="pending_admissions.php" class="sidebar-link <?= $sidebar_pending_cnt > 0 ? 'text-warning fw-bold' : '' ?>">
                    <i class="fa fa-hourglass-half <?= $sidebar_pending_cnt > 0 ? 'text-warning' : 'text-light' ?>"></i> 
                    <span>Pending Admissions</span>
                    <?php if ($sidebar_pending_cnt > 0): ?>
                        <span class="badge bg-warning text-dark ms-auto py-1 px-2" style="font-size:10px;"><?= $sidebar_pending_cnt ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="admission_qr.php" class="sidebar-link">
                    <i class="fa fa-qrcode text-warning"></i> Admission QR
                </a>
            </li>

            <li class="sidebar-heading">Facility Mgmt</li>
            <li>
                <a href="seats.php" class="sidebar-link">
                    <i class="fa fa-chair text-light"></i> Manage Seats
                </a>
            </li>
            <li>
                <a href="slots.php" class="sidebar-link">
                    <i class="fa fa-clock text-light"></i> Manage Slots
                </a>
            </li>
            <li>
                <a href="seat_utilization.php" class="sidebar-link">
                    <i class="fa fa-chart-pie text-light"></i> Seat Utilization
                </a>
            </li>

            <li class="sidebar-heading">Fees Mgmt</li>
            <li>
                <a href="fee_plans.php" class="sidebar-link">
                    <i class="fa fa-list text-light"></i> Fee Plans
                </a>
            </li>
            <li>
                <a href="fee_collection.php" class="sidebar-link">
                    <i class="fa fa-wallet text-light"></i> Fee Collection
                </a>
            </li>
            <li>
                <a href="dues_report.php" class="sidebar-link">
                    <i class="fa fa-file-invoice-dollar text-light"></i> Dues Report
                </a>
            </li>
            <li>
                <a href="payment_add.php" class="sidebar-link">
                    <i class="fa fa-credit-card text-light"></i> Add Payment
                </a>
            </li>
            <li>
                <a href="payment_history.php" class="sidebar-link">
                    <i class="fa fa-receipt text-light"></i> Payment History
                </a>
            </li>
            <!--<li>-->
            <!--    <a href="expiring_students.php" class="sidebar-link text-warning">-->
            <!--        <i class="fa fa-exclamation-triangle"></i> Expiring Soon-->
            <!--    </a>-->
            <!--</li>-->
            <!--<li>-->
            <!--    <a href="expired_students.php" class="sidebar-link text-danger">-->
            <!--        <i class="fa fa-times-circle"></i> Expired-->
            <!--    </a>-->
            <!--</li>-->

            <li class="sidebar-heading">Expenses</li>
            <li>
                <a href="expenses.php" class="sidebar-link">
                    <i class="fa fa-file-invoice-dollar text-danger"></i> Expenses
                </a>
            </li>
            <li>
                <a href="expense_categories.php" class="sidebar-link">
                    <i class="fa fa-tags text-info"></i> Categories
                </a>
            </li>

            <li class="sidebar-heading">Reports</li>
            <li>
                <a href="revenue_report.php" class="sidebar-link">
                    <i class="fa fa-chart-bar text-light"></i> Revenue Report
                </a>
            </li>
            <li>
                <a href="monthly_revenue.php" class="sidebar-link">
                    <i class="fa fa-chart-area text-light"></i> Monthly Revenue
                </a>
            </li>

            <li class="sidebar-heading">Settings</li>
            <li>
                <a href="profile.php" class="sidebar-link">
                    <i class="fa fa-user-circle text-light"></i> Profile
                </a>
            </li>
            <li>
                <a href="settings_whatsapp.php" class="sidebar-link">
                    <i class="fab fa-whatsapp text-success"></i> WhatsApp Config
                </a>
            </li>

            <li style="margin-top:24px; padding-top:12px; border-top:1px solid rgba(255,255,255,0.08);">
                <a href="../logout.php" class="sidebar-link text-danger">
                    <i class="fa fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="flex-grow-1 p-4 main-content">