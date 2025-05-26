</main>
    <footer class="bg-dark text-light py-3 mt-5">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> Personal Finance Tracker. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js - Multiple CDN options for reliability -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        // Fallback Chart.js loading if primary CDN fails
        if (typeof Chart === 'undefined') {
            console.log('Primary Chart.js CDN failed, trying backup...');
            document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"><\/script>');
        }
    </script>
    
    <!-- Chart.js verification and initialization -->
    <script>
        // Wait a bit for Chart.js to load, then verify
        setTimeout(function() {
            if (typeof Chart === 'undefined') {
                console.error('All Chart.js CDNs failed to load');
                // Load from another CDN as last resort
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/chart.js@3.9.1/dist/chart.min.js';
                script.onload = function() {
                    console.log('✅ Backup Chart.js loaded successfully');
                    window.dispatchEvent(new Event('chartjs-loaded'));
                };
                script.onerror = function() {
                    console.error('❌ All Chart.js sources failed');
                    alert('Unable to load Chart.js. Charts will not work. Please check your internet connection.');
                };
                document.head.appendChild(script);
            } else {
                console.log('✅ Chart.js loaded successfully');
                window.dispatchEvent(new Event('chartjs-loaded'));
            }
        }, 100);
    </script>
    
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html>