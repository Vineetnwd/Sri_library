<?php
require_once "config.php";
require_once "db.php";

/* ---------- CLEAN INPUT ---------- */
function clean($data)
{
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

/* ---------- REDIRECT ---------- */
function redirect($path)
{
    $url = BASE_URL . $path;

    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        echo "<script>window.location.href='$url';</script>";
        exit;
    }
}


/* ---------- CHECK LOGIN ---------- */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/* ---------- USER ROLE ---------- */
function user_role()
{
    return $_SESSION['role'] ?? null;
}

/* ---------- CENTER ID ---------- */
function center_id()
{
    return $_SESSION['center_id'] ?? null;
}

/* ---------- FLASH MESSAGE ---------- */
function set_flash($type, $msg)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'msg'  => $msg
    ];
}

function get_flash()
{
    if (!isset($_SESSION['flash'])) return '';

    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return "<div class='alert alert-{$f['type']} alert-dismissible fade show'>
                {$f['msg']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

function get_current_slot($center_id)
{
    global $conn;

    $time = date('H:i:s');

    $q = mysqli_query($conn,
        "SELECT * FROM slots
         WHERE center_id='$center_id'
         AND status='active'
         AND start_time <= '$time'
         AND end_time >= '$time'
         LIMIT 1"
    );

    return mysqli_fetch_assoc($q);
}

function subscription_status($end_date)
{
    $today = date('Y-m-d');

    if($end_date < $today){
        return 'expired';
    }

    if($end_date <= date('Y-m-d', strtotime('+3 days'))){
        return 'expiring';
    }

    return 'active';
}
