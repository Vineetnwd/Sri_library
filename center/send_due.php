<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();
$student_id = $_GET['id'];

/* Get student and fee details */
$student = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT s.*, sf.due_amount, sf.end_date
         FROM students s
         LEFT JOIN student_fees sf ON sf.student_id = s.id AND sf.status='running'
         WHERE s.id='$student_id' AND s.center_id='$cid'")
);

if(!$student){
    set_flash("danger", "Student not found");
    redirect("students.php");
}

/* Get WhatsApp settings */
$settings = mysqli_fetch_assoc(
    mysqli_query($conn,
        "SELECT * FROM settings WHERE center_id='$cid'")
);

$center = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM centers WHERE id='$cid'")
);

/* Prepare message */
$message = "Dear {$student['name']},\n\n";
$message .= "This is a reminder from {$center['center_name']}.\n\n";

if($student['due_amount'] > 0){
    $message .= "Your pending fee: ₹{$student['due_amount']}\n";
}

if($student['end_date']){
    $days_left = floor((strtotime($student['end_date']) - time()) / 86400);
    if($days_left > 0 && $days_left <= 7){
        $message .= "Your subscription expires in $days_left days on " . date('d M Y', strtotime($student['end_date'])) . "\n";
    } elseif($days_left <= 0){
        $message .= "Your subscription has expired on " . date('d M Y', strtotime($student['end_date'])) . "\n";
    }
}

$message .= "\nPlease contact us for payment.\n";
$message .= "Thank you!";

$whatsapp_number = $student['mobile'];
$encoded_message = urlencode($message);
$whatsapp_url = "https://wa.me/+91{$whatsapp_number}?text={$encoded_message}";

/* Send via WhatsApp Business API if configured */
if($settings && $settings['wa_token']){
    // API implementation here
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<div class="card shadow" style="max-width:600px; margin:auto;">
    <div class="card-header bg-success text-white">
        <i class="fab fa-whatsapp me-2"></i>Send WhatsApp Reminder
    </div>
    
    <div class="card-body">
        <div class="mb-3">
            <label class="form-label fw-bold">Student Name</label>
            <input type="text" class="form-control" value="<?= $student['name'] ?>" readonly>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">Mobile Number</label>
            <input type="text" class="form-control" value="+91 <?= $student['mobile'] ?>" readonly>
        </div>
        
        <div class="mb-3">
            <label class="form-label fw-bold">Message Preview</label>
            <textarea class="form-control" rows="8" readonly><?= $message ?></textarea>
        </div>
        
        <div class="d-grid gap-2">
            <a href="<?= $whatsapp_url ?>" 
               target="_blank" 
               class="btn btn-success btn-lg">
                <i class="fab fa-whatsapp me-2"></i>Send via WhatsApp
            </a>
            
            <a href="students.php" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </div>
</div>

</div>

<?php include "layout/footer.php"; ?>