<?php
require_once "functions.php";

/* ---------- LOGIN REQUIRED ---------- */
function require_login()
{
    if (!is_logged_in()) {
        redirect("login.php");
        exit;
    }
}

/* ---------- ROLE CHECK ---------- */
function require_role($role)
{
    if (user_role() !== $role) {
        redirect("login.php");
        exit;
    }
}

/* ---------- CENTER PANEL GUARD ---------- */
function require_center()
{
    require_login();

    // NORMAL CENTER ADMIN
    if (user_role() === 'admin') {
        return;
    }

    // SUPER ADMIN IMPERSONATING CENTER
    if (
        user_role() === 'super_admin' &&
        isset($_SESSION['impersonate_center_id'])
    ) {
        return;
    }

    redirect("login.php");
    exit;
}

/* ---------- SUPER ADMIN GUARD ---------- */
function require_super()
{
    require_login();

    if (user_role() !== 'super') {
        redirect("login.php");
        exit;
    }
}
