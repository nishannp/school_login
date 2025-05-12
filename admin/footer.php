
        <footer class="mt-5">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6 small">
                        Copyright &copy; Campus Connect 2025
                    </div>
                    <div class="col-md-6 text-md-end small">
                        <a href="#" class="text-decoration-none">Privacy Policy</a>
                        &middot;
                        <a href="#" class="text-decoration-none">Terms &amp; Conditions</a>
                    </div>
                </div>
            </div>
        </footer>

    </div>
   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  
    
    <script>

        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('active');
        });

        document.addEventListener('DOMContentLoaded', function() {

            const currentPage = window.location.pathname.split('/').pop();

            const navLinks = document.querySelectorAll('.sidebar-nav li a');
            navLinks.forEach(function(link) {
                const href = link.getAttribute('href');
                if (href === currentPage) {
                    link.parentElement.classList.add('active');
                }
            });

            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function(popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
        });

        const monthlyEvents = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            data: [15, 22, 18, 24, 32, 27]
        };

        const userStats = {
            labels: ['Students', 'Faculty', 'Admin', 'Visitors'],
            data: [65, 15, 5, 15]
        };

    </script>
</body>
</html>
<?php

?>