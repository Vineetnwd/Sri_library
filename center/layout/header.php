<?php
require_once "../system/saas_check.php";
require_once "../system/auth.php";
require_center();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Center Dashboard - SelfStudy CRM</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --secondary-color: #858796;
            --dark-color: #2c3e50;
        }

        body { 
            background: #f8f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Impersonation Banner */
        .impersonate-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            position: sticky;
            top: 0;
            z-index: 1050;
            animation: slideDown 0.4s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .impersonate-banner i.fa-user-secret {
            font-size: 1.2rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .impersonate-banner a {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
            margin-left: auto;
        }

        .impersonate-banner a:hover {
            background: rgba(255, 255, 255, 0.35);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .impersonate-banner a i {
            font-size: 1rem;
        }

        /* Flash Messages */
        .flash-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1060;
            max-width: 400px;
        }

        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            animation: slideInRight 0.4s ease-out;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d4fc79 0%, #96e6a1 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert-warning {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .alert-info {
            background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%);
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        .alert i {
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }

        .alert .btn-close {
            background: transparent;
            opacity: 0.7;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .alert .btn-close:hover {
            opacity: 1;
            transform: rotate(90deg);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.25);
        }

        .card-stat {
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,.08);
            border-left: 4px solid;
        }

        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .bg-gradient-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        }

        .nav-link {
            transition: all 0.3s ease;
            padding: 10px 15px;
            margin-bottom: 2px;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            padding-left: 20px;
        }

        .table {
            background: white;
        }

        .btn {
            border-radius: 8px;
            padding: 8px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .badge {
            padding: 6px 12px;
            font-weight: 500;
            border-radius: 6px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #e3e6f0;
            padding: 10px 15px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .card-header {
            border-radius: 12px 12px 0 0 !important;
            font-weight: 600;
            padding: 15px 20px;
        }

        h4, h5, h6 {
            color: var(--dark-color);
            font-weight: 600;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Table Styling */
        .table thead th {
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fc;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .impersonate-banner {
                flex-direction: column;
                padding: 1rem;
                gap: 0.5rem;
            }

            .impersonate-banner a {
                margin-left: 0;
                width: 100%;
                justify-content: center;
            }

            .flash-container {
                right: 10px;
                left: 10px;
                max-width: none;
            }
        }
    </style>
</head>
<body>

<?php if(isset($_SESSION['impersonate'])) { ?>
<div class="impersonate-banner">
    <div class="d-flex align-items-center gap-2">
        <i class="fas fa-user-secret"></i>
        <span>You are impersonating a Center Admin</span>
    </div>
    <a href="../admin/back_to_admin.php">
        <i class="fas fa-sign-out-alt"></i>
        <span>Exit Impersonation</span>
    </a>
</div>
<?php } ?>

<?php
// Flash message display
if(isset($_SESSION['flash'])){
    $type = $_SESSION['flash']['type'];
    $msg = $_SESSION['flash']['message'] ?? 'Processed Successfully';
    
    // Icon mapping
    $icons = [
        'success' => 'fa-check-circle',
        'danger' => 'fa-exclamation-circle',
        'warning' => 'fa-exclamation-triangle',
        'info' => 'fa-info-circle'
    ];
    
    $icon = $icons[$type] ?? 'fa-check-circle';
    
    echo "<div class='flash-container'>
            <div class='alert alert-$type alert-dismissible fade show' role='alert'>
                <i class='fas $icon'></i>
                <strong>$msg</strong>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
          </div>";
    unset($_SESSION['flash']);
}
?>

<script>
// Auto-dismiss flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.flash-container .alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>