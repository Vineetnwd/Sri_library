</div>
</div>

<!-- Toast Notifications -->
<?php if(isset($_SESSION['flash'])){ ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 11">
    <div class="toast show" role="alert">
        <div class="toast-header bg-<?= $_SESSION['flash_type'] ?> text-white">
            <i class="fas fa-<?= $_SESSION['flash_type']=='success'?'check-circle':'exclamation-circle' ?> me-2"></i>
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            <?= $_SESSION['flash'] ?>
        </div>
    </div>
</div>
<?php 
    unset($_SESSION['flash']);
    unset($_SESSION['flash_type']);
} ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Auto-hide toast after 5 seconds
setTimeout(function() {
    let toast = document.querySelector('.toast');
    if(toast) {
        let bsToast = new bootstrap.Toast(toast);
        bsToast.hide();
    }
}, 5000);

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href'))?.scrollIntoView({
            behavior: 'smooth'
        });
    });
});
</script>

</body>
</html>