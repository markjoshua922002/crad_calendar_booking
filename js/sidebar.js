document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const appContainer = document.querySelector('.app-container');
    const mainContent = document.querySelector('.main-content');
    
    // Check localStorage for sidebar state on page load
    const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isSidebarCollapsed) {
        sidebar.classList.add('collapsed');
        appContainer.classList.add('sidebar-collapsed');
    }
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            appContainer.classList.toggle('sidebar-collapsed');
            
            // Store sidebar state in localStorage
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }
    
    // Handle responsive behavior
    function handleResponsive() {
        if (window.innerWidth <= 768) {
            if (!sidebar.classList.contains('active')) {
                sidebar.classList.add('collapsed');
                appContainer.classList.add('sidebar-collapsed');
            }
            localStorage.setItem('sidebarCollapsed', 'true');
        }
    }
    
    // Initial check
    handleResponsive();
    
    // Listen for window resize
    window.addEventListener('resize', handleResponsive);
    
    // Handle mobile sidebar
    function handleMobileSidebar() {
        const menuButton = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        if (!menuButton || !sidebar) return;
        
        // Create overlay for mobile sidebar
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
        
        // Toggle sidebar on menu button click
        menuButton.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            
            if (window.innerWidth <= 768) {
                if (sidebar.classList.contains('active')) {
                    sidebar.classList.remove('collapsed');
                    appContainer.classList.remove('sidebar-collapsed');
                } else {
                    sidebar.classList.add('collapsed');
                    appContainer.classList.add('sidebar-collapsed');
                }
            }
        });
        
        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            if (window.innerWidth <= 768) {
                sidebar.classList.add('collapsed');
                appContainer.classList.add('sidebar-collapsed');
            }
        });
        
        // Close sidebar when clicking main content on mobile
        mainContent.addEventListener('click', function() {
            if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                sidebar.classList.add('collapsed');
                appContainer.classList.add('sidebar-collapsed');
            }
        });
    }
    
    // Initialize mobile sidebar
    handleMobileSidebar();
}); 