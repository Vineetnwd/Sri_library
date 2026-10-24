<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "../system/functions.php";
require_once "../system/db.php";

$cid = center_id();

/* =============================
   VALIDATE REQUEST
============================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect("student_add.php");
    exit;
}

/* =============================
   COLLECT DATA
============================= */
$name   = clean($_POST['name'] ?? '');
$mobile = clean($_POST['mobile'] ?? '');
$email  = clean($_POST['email'] ?? '');
$gender = clean($_POST['gender'] ?? '');
$seat   = (int)($_POST['seat_id'] ?? 0);
$slot   = (int)($_POST['slot_id'] ?? 0);
$address = clean($_POST['address'] ?? '');

$joining_date = clean($_POST['joining_date'] ?? '');
if (empty($joining_date)) {
    $joining_date = date('Y-m-d');
}

$plan_id     = (int)($_POST['plan_id'] ?? 0);
$paid_amount = (float)($_POST['paid_amount'] ?? 0);

/* =============================
   PHOTO UPLOAD
============================= */
$photo_name = NULL;

if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {

    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png'];

    if (!in_array($ext, $allowed)) {
        die("Invalid file type (Only JPG, PNG allowed)");
    }

    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        die("File too large (Max 2MB)");
    }

    $photo_name = time() . rand(1000,9999) . "." . $ext;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/" . $photo_name)) {
        die("Photo upload failed");
    }
}

/* =============================
   GENERATE REGISTRATION NO
============================= */
$reg = "STU" . time();

/* =============================
   SEAT + SLOT CONFLICT CHECK
============================= */
$check = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT COUNT(*) AS total FROM students
         WHERE center_id='$cid'
         AND seat_id='$seat'
         AND slot_id='$slot'
         AND status='active'")
);

if ($check['total'] > 0) {
    set_flash("danger", "This seat is already occupied in selected slot");
    redirect("student_add.php");
    exit;
}

/* =============================
   INSERT STUDENT
============================= */
$query = "INSERT INTO students 
(center_id, registration_no, name, mobile, email, gender,
 joining_date, address, photo, seat_id, slot_id, status, plan_id)
VALUES
('$cid','$reg','$name','$mobile','$email','$gender',
 '$joining_date','$address','$photo_name','$seat','$slot','active', " . ($plan_id > 0 ? "'$plan_id'" : "NULL") . ")";

if (!mysqli_query($conn, $query)) {
    die("Insert Error: " . mysqli_error($conn));
}

$student_id = mysqli_insert_id($conn);

/* =============================
   CREATE STUDENT LOGIN
============================= */
$pass = password_hash($mobile, PASSWORD_DEFAULT);

$user_query = "INSERT INTO users 
(center_id, user_id, username, password, role, status)
VALUES
('$cid','$student_id','$reg','$pass','student','active')";

if (!mysqli_query($conn, $user_query)) {
    die("User Insert Error: " . mysqli_error($conn));
}

/* =============================
   INITIAL PAYMENT
============================= */
if ($plan_id > 0 && $paid_amount > 0) {
    mysqli_query($conn,
        "INSERT INTO payments
        (center_id, student_id, amount, payment_mode, payment_date, payment_type)
        VALUES
        ('$cid','$student_id','$paid_amount','cash',CURDATE(),'fee')"
    );
}

/* =============================
   FINAL REDIRECT
============================= */
set_flash("success", "Student added successfully");
redirect("center/students.php");
exit;