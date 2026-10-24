<?php
require_once "layout/header.php";
?>

<?php include "layout/sidebar.php"; ?>

<div class="container-fluid">

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-question-circle me-2"></i>Help & Support
    </h1>
</div>

<div class="row g-4">
    
    <!-- FAQs -->
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-question me-2"></i>Frequently Asked Questions
            </div>
            <div class="card-body">
                
                <div class="accordion" id="faqAccordion">
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#faq1">
                                How do I add a new student?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" 
                             data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Go to <strong>Students</strong> menu</li>
                                    <li>Click on <strong>"Add New Student"</strong> button</li>
                                    <li>Fill in all required details</li>
                                    <li>Select a slot and seat</li>
                                    <li>Click <strong>"Add Student"</strong></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#faq2">
                                How do I collect fees from students?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" 
                             data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Go to <strong>Fees</strong> menu</li>
                                    <li>Find the student with pending dues</li>
                                    <li>Click on <strong>"Collect"</strong> button</li>
                                    <li>Enter the amount and payment method</li>
                                    <li>Click <strong>"Save Payment"</strong></li>
                                    <li>Receipt will be generated automatically</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#faq3">
                                How do I create slots/time shifts?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" 
                             data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Go to <strong>Slots</strong> menu</li>
                                    <li>Click <strong>"Add New Slot"</strong></li>
                                    <li>Enter slot name (e.g., Morning Shift)</li>
                                    <li>Set start and end times</li>
                                    <li>Click <strong>"Add Slot"</strong></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#faq4">
                                How do I manage seat allocation?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" 
                             data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <p>Seats are automatically assigned when adding students. You can:</p>
                                <ul>
                                    <li>View all seats in 2D grid view from <strong>Seats</strong> menu</li>
                                    <li>Add new seats or bulk add multiple seats</li>
                                    <li>Edit seat numbers</li>
                                    <li>Release occupied seats</li>
                                    <li>Delete unused seats</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#faq5">
                                How do I send notifications to students?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" 
                             data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Go to <strong>Notifications</strong> menu</li>
                                    <li>Compose your message</li>
                                    <li>Select recipients (individual or bulk)</li>
                                    <li>Click <strong>"Send Message"</strong></li>
                                </ol>
                                <p class="mb-0 mt-2">
                                    <strong>Note:</strong> Configure SMS/WhatsApp settings from 
                                    <strong>Settings</strong> menu first.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#faq6">
                                How do I generate reports?
                            </button>
                        </h2>
                        <div id="faq6" class="accordion-collapse collapse" 
                             data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Go to <strong>Reports</strong> menu</li>
                                    <li>Select date range using filters</li>
                                    <li>View revenue statistics and charts</li>
                                    <li>Click <strong>"Print Report"</strong> to generate PDF</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
            </div>
        </div>
        
        <!-- Video Tutorials -->
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <i class="fas fa-video me-2"></i>Video Tutorials
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 text-center">
                            <i class="fas fa-play-circle fa-4x text-primary mb-3"></i>
                            <h6>Getting Started</h6>
                            <p class="small text-muted">Basic setup and navigation</p>
                            <button class="btn btn-sm btn-outline-primary" disabled>Coming Soon</button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 text-center">
                            <i class="fas fa-play-circle fa-4x text-success mb-3"></i>
                            <h6>Student Management</h6>
                            <p class="small text-muted">Adding and managing students</p>
                            <button class="btn btn-sm btn-outline-success" disabled>Coming Soon</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Support -->
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <i class="fas fa-headset me-2"></i>Contact Support
            </div>
            <div class="card-body">
                
                <div class="text-center mb-4">
                    <i class="fas fa-life-ring fa-4x text-info mb-3"></i>
                    <h5>Need Help?</h5>
                    <p class="text-muted">Our support team is here to assist you</p>
                </div>
                
                <div class="mb-3">
                    <label class="small text-muted">
                        <i class="fas fa-envelope me-2"></i>Email
                    </label>
                    <h6>support@librarysystem.com</h6>
                </div>
                
                <div class="mb-3">
                    <label class="small text-muted">
                        <i class="fas fa-phone me-2"></i>Phone
                    </label>
                    <h6>+91 1234567890</h6>
                </div>
                
                <div class="mb-3">
                    <label class="small text-muted">
                        <i class="fas fa-clock me-2"></i>Support Hours
                    </label>
                    <h6>Mon-Sat: 9:00 AM - 6:00 PM</h6>
                </div>
                
                <hr>
                
                <a href="mailto:support@librarysystem.com" class="btn btn-info w-100 mb-2">
                    <i class="fas fa-envelope me-2"></i>Email Us
                </a>
                
            </div>
        </div>
        
        <!-- System Info -->
        <div class="card shadow">
            <div class="card-header bg-dark text-white">
                <i class="fas fa-info-circle me-2"></i>System Information
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Version</td>
                        <td><strong>1.0.0</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Build Date</td>
                        <td>January 2026</td>
                    </tr>
                    <tr>
                        <td class="text-muted">License</td>
                        <td>Commercial</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
</div>

</div>

<?php include "layout/footer.php"; ?>