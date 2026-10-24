<?php
require_once "layout/header.php";
require_once "../system/db.php";

$cid = center_id();

/* Get all students */
$students = mysqli_query($conn,
    "SELECT s.*, sl.slot_name,
     (SELECT sf.due_amount FROM student_fees sf 
      WHERE sf.student_id=s.id AND sf.status='running' LIMIT 1) as due_amount
     FROM students s
     LEFT JOIN slots sl ON sl.id = s.slot_id
     WHERE s.center_id='$cid' AND s.status='active'
     ORDER BY s.name"
);

/* Send Bulk Notification */
if(isset($_POST['send_bulk'])){
    $message = clean($_POST['message']);
    $recipients = $_POST['students'] ?? [];
    
    if(empty($recipients)){
        set_flash("danger", "Please select at least one student");
        redirect("notifications.php");
    }
    
    $sent_count = 0;
    foreach($recipients as $student_id){
        $student = mysqli_fetch_assoc(
            mysqli_query($conn,
                "SELECT * FROM students WHERE id='$student_id'")
        );
        
        /* Send WhatsApp message */
        $mobile = $student['mobile'];
        $final_message = "Dear {$student['name']},\n\n$message\n\nRegards,\nYour Library";
        
        // Here you would integrate with WhatsApp API or SMS API
        // For now, we'll just log it
        
        mysqli_query($conn,
            "INSERT INTO notifications 
            (center_id, student_id, message, sent_date, status)
            VALUES
            ('$cid', '$student_id', '$final_message', NOW(), 'sent')"
        );
        
        $sent_count++;
    }
    
    set_flash("success", "Message sent to $sent_count students successfully");
    redirect("notifications.php");
}
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-bell me-2"></i>Send Notifications
    </h1>
</div>

<!-- Send Notification Form -->
<form method="post">
    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-paper-plane me-2"></i>Compose Message
                </div>
                <div class="card-body">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold">Message *</label>
                        <textarea name="message" class="form-control" rows="8" 
                                  required placeholder="Type your message here..."></textarea>
                        <small class="text-muted">
                            Use {NAME} for student name, {DUE} for due amount
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            <input type="checkbox" id="select_all" class="form-check-input me-2">
                            Select Recipients
                        </label>
                        
                        <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                            <?php while($s = mysqli_fetch_assoc($students)){ ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input student-check" type="checkbox" 
                                           name="students[]" value="<?= $s['id'] ?>" 
                                           id="student_<?= $s['id'] ?>">
                                    <label class="form-check-label" for="student_<?= $s['id'] ?>">
                                        <strong><?= $s['name'] ?></strong> 
                                        (<?= $s['registration_no'] ?>)
                                        <br>
                                        <small class="text-muted">
                                            <?= $s['mobile'] ?> | <?= $s['slot_name'] ?>
                                            <?php if($s['due_amount'] > 0){ ?>
                                                | <span class="text-danger">Due: ₹<?= $s['due_amount'] ?></span>
                                            <?php } ?>
                                        </small>
                                    </label>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    
                    <button name="send_bulk" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-paper-plane me-2"></i>Send Message
                    </button>
                    
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Quick Templates -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-file-alt me-2"></i>Message Templates
                </div>
                <div class="card-body">
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-2"
                                onclick="useTemplate('fee')">
                            Fee Reminder
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-2"
                                onclick="useTemplate('expiry')">
                            Subscription Expiry
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary w-100 mb-2"
                                onclick="useTemplate('holiday')">
                            Holiday Announcement
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary w-100"
                                onclick="useTemplate('general')">
                            General Notice
                        </button>
                    </div>
                    
                </div>
            </div>
            
            <!-- Stats -->
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-chart-bar me-2"></i>Quick Stats
                </div>
                <div class="card-body">
                    <?php
                    mysqli_data_seek($students, 0);
                    $total = mysqli_num_rows($students);
                    $with_due = 0;
                    while($s = mysqli_fetch_assoc($students)){
                        if($s['due_amount'] > 0) $with_due++;
                    }
                    ?>
                    
                    <div class="mb-3">
                        <label class="small text-muted">Total Students</label>
                        <h4><?= $total ?></h4>
                    </div>
                    
                    <div class="mb-0">
                        <label class="small text-muted">Students with Dues</label>
                        <h4 class="text-danger"><?= $with_due ?></h4>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</form>

</div>

<script>
// Select All
document.getElementById('select_all').addEventListener('change', function(){
    const checkboxes = document.querySelectorAll('.student-check');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

// Templates
function useTemplate(type){
    const textarea = document.querySelector('textarea[name="message"]');
    
    const templates = {
        'fee': 'Dear {NAME},\n\nYour pending fee amount is ₹{DUE}. Please clear your dues at the earliest.\n\nThank you!',
        'expiry': 'Dear {NAME},\n\nYour subscription is expiring soon. Please renew to continue your services.\n\nThank you!',
        'holiday': 'Dear Students,\n\nOur library will remain closed on [DATE] due to [REASON].\n\nThank you!',
        'general': 'Dear {NAME},\n\n[Your message here]\n\nThank you!'
    };
    
    textarea.value = templates[type];
}
</script>

<?php include "layout/footer.php"; ?>