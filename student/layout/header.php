<?php
require_once "../system/auth.php";

require_login();

if (user_role() != 'student') {
    redirect("../login.php");
}

$student_user_id = $_SESSION['user_id'];
require_once "../system/db.php";

/* get student record */
$student = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT s.* 
         FROM students s
         JOIN users u ON u.username = s.registration_no
         WHERE u.id='$student_user_id'")
);
?>
<!DOCTYPE html>
<html>
<head>
<title>Student Panel</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

</head>
<body>
