</div>
    </div>

    <script>
    (function() {
        const toggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (toggle && sidebar && overlay) {
            toggle.addEventListener('click', function() {
                sidebar.classList.add('open');
                overlay.classList.add('open');
            });
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                overlay.classList.remove('open');
            });
        }
    })();
    </script>
</body>
</html>