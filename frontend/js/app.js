// assets/js/app.js
document.addEventListener('DOMContentLoaded', function() {
    // Scroll-aware navbar
    const navbar = document.querySelector('.sc-navbar.fixed-top');
    if (navbar) {
        // Initialize state
        if (window.scrollY > 50) {
            navbar.classList.remove('navbar-transparent');
            navbar.classList.add('shadow-sm');
        } else {
            // Only add transparent if it has the data attribute indicating it should be transparent on top
            if (navbar.hasAttribute('data-transparent')) {
                navbar.classList.add('navbar-transparent');
                navbar.classList.remove('shadow-sm');
            }
        }

        // Scroll event listener
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.remove('navbar-transparent');
                navbar.classList.add('shadow-sm');
            } else {
                if (navbar.hasAttribute('data-transparent')) {
                    navbar.classList.add('navbar-transparent');
                    navbar.classList.remove('shadow-sm');
                }
            }
        });
    }
});
