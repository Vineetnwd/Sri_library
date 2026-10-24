<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once "system/functions.php";

if (is_logged_in()) {
    if (user_role() == 'super') redirect("admin/dashboard.php");
    if (user_role() == 'admin') redirect("center/dashboard.php");
}

if (isset($_POST['login'])) {

    $username = clean($_POST['username']);
    $password = $_POST['password'];

    $q = mysqli_query($conn,
        "SELECT * FROM users 
         WHERE username='$username' 
         AND status='active' 
         LIMIT 1"
    );

    $user = mysqli_fetch_assoc($q);

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['center_id'] = $user['center_id'];
        $_SESSION['role']      = $user['role'];

        if ($user['role'] == 'super') {
            redirect("admin/dashboard.php");
        } else {
            redirect("center/dashboard.php");
        }

    } else {
        set_flash("danger", "Invalid username or password");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | SelfStudy CRM</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #4f46e5, #9333ea);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,.25);
        }

        .login-header {
            background: #111827;
            color: #fff;
            padding: 25px;
            text-align: center;
        }

        .login-header h4 {
            margin: 0;
            font-weight: 600;
        }

        .login-body {
            padding: 30px;
            background: #fff;
        }

        .form-control {
            height: 48px;
        }

        .btn-login {
            height: 48px;
            font-weight: 600;
        }

        .brand {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

<div class="login-card">

    <div class="login-header">
        <div class="brand">
            <i class="fa-solid fa-book-open-reader me-2"></i>
            SelfStudy CRM
        </div>
        <small>Student Self Study Center Management</small>
    </div>

    <div class="login-body">

        <?= get_flash(); ?>

        <form method="post">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fa fa-user"></i>
                    </span>
                    <input type="text" name="username" class="form-control" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fa fa-lock"></i>
                    </span>
                    <input type="password" name="password" class="form-control" required>
                </div>
            </div>
            <a href="forgot_password.php" class="text-decoration-none">
            Forgot password?
            </a>

            <button type="submit" name="login" class="btn btn-primary w-100 btn-login">
                <i class="fa fa-sign-in-alt me-1"></i> Login
            </button>

        </form>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
