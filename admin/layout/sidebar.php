<div class="d-flex" style="min-height: 100vh;">

<div class="sidebar" style="width: 280px; background: linear-gradient(180deg, #2d3748 0%, #1a202c 100%); color: white; box-shadow: 4px 0 20px rgba(0,0,0,0.1);">

    <div class="p-4">
        <div class="text-center mb-4" style="padding: 20px 0;">
            <div style="width: 70px; height: 70px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);">
                <i class="fas fa-crown" style="font-size: 32px;"></i>
            </div>
            <h5 class="mb-0" style="font-weight: 700; letter-spacing: 0.5px;">SUPER ADMIN</h5>
            <small style="opacity: 0.7;">SaaS Management</small>
        </div>

        <ul class="nav flex-column gap-2" style="list-style: none; padding: 0;">

            <li>
                <a href="dashboard.php" class="nav-link text-white d-flex align-items-center" style="padding: 14px 18px; border-radius: 10px; transition: all 0.3s; background: <?= basename($_SERVER['PHP_SELF'])=='dashboard.php' ? 'rgba(102, 126, 234, 0.2)' : 'transparent' ?>;">
                    <i class="fas fa-chart-line me-3" style="font-size: 18px; width: 24px;"></i>
                    <span style="font-weight: 500;">Dashboard</span>
                </a>
            </li>

            <li>
                <a href="centers.php" class="nav-link text-white d-flex align-items-center" style="padding: 14px 18px; border-radius: 10px; transition: all 0.3s; background: <?= basename($_SERVER['PHP_SELF'])=='centers.php' ? 'rgba(102, 126, 234, 0.2)' : 'transparent' ?>;">
                    <i class="fas fa-building me-3" style="font-size: 18px; width: 24px;"></i>
                    <span style="font-weight: 500;">Centers</span>
                </a>
            </li>

            <li>
                <a href="revenue_report.php" class="nav-link text-white d-flex align-items-center" style="padding: 14px 18px; border-radius: 10px; transition: all 0.3s; background: <?= basename($_SERVER['PHP_SELF'])=='revenue_report.php' ? 'rgba(102, 126, 234, 0.2)' : 'transparent' ?>;">
                    <i class="fas fa-chart-bar me-3" style="font-size: 18px; width: 24px;"></i>
                    <span style="font-weight: 500;">Revenue Report</span>
                </a>
            </li>

            <li class="mt-4">
                <a href="../logout.php" class="nav-link text-danger d-flex align-items-center" style="padding: 14px 18px; border-radius: 10px; transition: all 0.3s; background: rgba(220, 53, 69, 0.1);">
                    <i class="fas fa-sign-out-alt me-3" style="font-size: 18px; width: 24px;"></i>
                    <span style="font-weight: 500;">Logout</span>
                </a>
            </li>

        </ul>
    </div>

</div>

<div class="flex-grow-1 p-4" style="background: rgba(255,255,255,0.05); backdrop-filter: blur(10px);">

<style>
.sidebar .nav-link:hover {
    background: rgba(102, 126, 234, 0.15) !important;
    transform: translateX(5px);
}
</style>