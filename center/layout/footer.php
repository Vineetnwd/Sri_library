    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Responsive Sidebar Logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarWrap = document.getElementById('sidebarWrap');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebarWrap && sidebarOverlay) {
        sidebarToggle.addEventListener('click', function() {
            sidebarWrap.classList.add('show');
            sidebarOverlay.classList.add('show');
            document.body.style.overflow = 'hidden'; // prevent background scrolling
        });

        sidebarOverlay.addEventListener('click', function() {
            sidebarWrap.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        });
    }
});
</script>

</body>
</html>
