<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lost and Found System</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="<?php
                                    echo (strpos($_SERVER['PHP_SELF'], '/pages/footer/') !== false)
                                        ? '../../node_modules/@fortawesome/fontawesome-free/css/all.min.css'
                                        : ((strpos($_SERVER['PHP_SELF'], '/pages/') !== false)
                                            ? '../node_modules/@fortawesome/fontawesome-free/css/all.min.css'
                                            : './node_modules/@fortawesome/fontawesome-free/css/all.min.css');
                                    ?>">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: "Poppins", sans-serif;
        }

        .navbar {
            transition: background-color 0.4s ease, backdrop-filter 0.4s ease;
        }

        /* Desktop Active Link Style */
        .nav-link.active {
            font-weight: 600;
            /* Semibold for active link */
            color: #bfdbfe;
            /* Tailwind's text-blue-200 */
        }

        .slide-down {
            animation: slideDown 0.3s ease forwards;
        }

        .slide-up {
            animation: slideUp 0.3s ease forwards;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 1;
                transform: translateY(0);
            }

            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
    </style>
</head>

<body class="bg-gray-50">

    <?php
    // Helper function to determine the correct path prefix
    function get_path_prefix()
    {
        $path = $_SERVER['PHP_SELF'];
        if (strpos($path, '/pages/footer/') !== false) {
            return '../../';
        } elseif (strpos($path, '/pages/') !== false) {
            return '../';
        } else {
            return './';
        }
    }

    // Helper function to check if the current page is active
    function is_active($page_name)
    {
        // Adjust the logic based on the expected filename
        $current_path = $_SERVER['PHP_SELF'];
        $base_name = basename($current_path, '.php');

        if ($page_name === 'index' && ($base_name === 'index' || $current_path === '/index.php')) {
            return true;
        }
        return $base_name === $page_name;
    }

    $prefix = get_path_prefix();
    ?>

    <nav id="navbar" class="navbar fixed w-full top-0 z-50 text-white backdrop-blur-sm bg-transparent shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">

            <div class="flex items-center space-x-3">
                <img id="ccs-logo" src="" alt="CCS Logo" class="h-8 w-8 hidden">
                <img id="bcp-logo" src="./assets/bcp-logo.png" alt="BCP Logo" class="h-8 w-8">
                <div class="hidden sm:block">
                    <h1 class="font-semibold text-sm md:text-base leading-tight">Lost and Found System</h1>
                    <p class="text-xs text-blue-100">Bestlink College of the Philippines</p>
                </div>
            </div>

            <div class="hidden md:flex space-x-8">
                <a href="<?php echo $prefix . 'index.php'; ?>" class="nav-link hover:text-blue-200 font-medium <?php echo is_active('index') ? 'active' : ''; ?>">Home</a>
                <a href="<?php echo $prefix . 'pages/about.php'; ?>" class="nav-link hover:text-blue-200 font-medium <?php echo is_active('about') ? 'active' : ''; ?>">About</a>
                <a href="<?php echo $prefix . 'pages/item.php'; ?>" class="nav-link hover:text-blue-200 font-medium <?php echo is_active('item') ? 'active' : ''; ?>">View Items</a>
                <a href="<?php echo $prefix . 'pages/contact.php'; ?>" class="nav-link hover:text-blue-200 font-medium <?php echo is_active('contact') ? 'active' : ''; ?>">Contact</a>
            </div>

            <div class="hidden md:block">
                <a href="<?php echo $prefix . 'pages/reports.php'; ?>" class="bg-white text-blue-700 hover:bg-blue-100 px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2 <?php echo is_active('reports') ? 'bg-blue-100' : ''; ?>">
                    <i class="fas fa-file-alt"></i> Report
                </a>
            </div>

            <div class="md:hidden relative z-50">
                <button id="menu-toggle" class="text-white hover:text-blue-200">
                    <i id="menu-icon" class="fas fa-bars text-lg"></i>
                </button>
            </div>

        </div>

        <div id="mobile-menu" class="md:hidden hidden bg-blue-700/90 border-t border-blue-500 rounded-b-lg shadow-lg backdrop-blur-md">
            <div class="flex flex-col divide-y divide-blue-500">
                <a href="<?php echo $prefix . 'index.php'; ?>" class="nav-link-mobile px-4 py-3 text-white hover:bg-blue-600 <?php echo is_active('index') ? 'bg-blue-600' : ''; ?>">Home</a>
                <a href="<?php echo $prefix . 'pages/about.php'; ?>" class="nav-link-mobile px-4 py-3 text-white hover:bg-blue-600 <?php echo is_active('about') ? 'bg-blue-600' : ''; ?>">About</a>
                <a href="<?php echo $prefix . 'pages/item.php'; ?>" class="nav-link-mobile px-4 py-3 text-white hover:bg-blue-600 <?php echo is_active('item') ? 'bg-blue-600' : ''; ?>">View Items</a>
                <a href="<?php echo $prefix . 'pages/contact.php'; ?>" class="nav-link-mobile px-4 py-3 text-white hover:bg-blue-600 <?php echo is_active('contact') ? 'bg-blue-600' : ''; ?>">Contact</a>

                <div class="p-4">
                    <a href="<?php echo $prefix . 'pages/reports.php'; ?>" class="block w-full bg-white text-blue-700 font-semibold py-3 rounded-xl shadow-lg hover:shadow-xl hover:bg-blue-100 transition-all flex items-center justify-center gap-2 <?php echo is_active('reports') ? 'bg-blue-100' : ''; ?>">
                        <i class="fas fa-file-alt text-blue-600"></i> Report Lost Item
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <script>
        const navbar = document.getElementById("navbar");
        const mobileMenu = document.getElementById("mobile-menu");
        const menuToggle = document.getElementById("menu-toggle");
        const menuIcon = document.getElementById("menu-icon");
        const ccsLogo = document.getElementById("ccs-logo");
        const bcpLogo = document.getElementById("bcp-logo");
        const path = window.location.pathname;

        // --- Active Navlinks Logic (New) ---

        /**
         * Determines the base filename for comparison.
         * Converts paths like '/pages/item.php' or '../item.php' to 'item'.
         * Special case for '/' or '/index.php' which returns 'index'.
         */
        function getBasePageName(fullPath) {
            // Remove any path prefixes
            let cleanedPath = fullPath.replace(/^(\.\.\/|\.\/pages\/|\.\.\/pages\/)/, '');

            // Get the filename part
            let filename = cleanedPath.substring(cleanedPath.lastIndexOf('/') + 1);

            // Remove .php extension
            let baseName = filename.replace('.php', '');

            // Handle root/index page
            if (baseName === '' || baseName === 'index') {
                return 'index';
            }

            return baseName;
        }

        const currentPageName = getBasePageName(path);
        const desktopNavLinks = document.querySelectorAll('.hidden.md\\:flex a.nav-link');
        const mobileNavLinks = document.querySelectorAll('#mobile-menu a.nav-link-mobile');

        function setActiveLink(links, isMobile = false) {
            links.forEach(link => {
                const linkPageName = getBasePageName(link.getAttribute('href'));

                if (linkPageName === currentPageName) {
                    if (isMobile) {
                        // For mobile, the active class is set by PHP to ensure it's correct on load
                        // but if we were to dynamically manage it, we'd add/remove a background class.
                        // Since PHP handles this well on load, we'll keep the JS minimal.
                    } else {
                        // Desktop links
                        link.classList.add('active');
                        // Also, apply a lighter color on the 'Report' button for its active state
                        if (linkPageName === 'reports') {
                            const reportButton = document.querySelector('.hidden.md\\:block a[href*="reports.php"]');
                            if (reportButton) {
                                reportButton.classList.add('bg-blue-100');
                            }
                        }
                    }
                }
            });
        }

        // Apply active class on load
        setActiveLink(desktopNavLinks, false);
        setActiveLink(mobileNavLinks, true);


        // --- Original JS continued ---

        // Adjust image paths dynamically
        if (path.includes("/pages/footer/")) {
            // NOTE: ccsLogo was not in the original HTML but kept for path logic
            if (ccsLogo) ccsLogo.src = "../../assets/ccs-logo.png";
            if (bcpLogo) bcpLogo.src = "../../assets/bcp-logo.png";
        } else if (path.includes("/pages/")) {
            // NOTE: ccsLogo was not in the original HTML but kept for path logic
            if (ccsLogo) ccsLogo.src = "../assets/ccs-logo.png";
            if (bcpLogo) bcpLogo.src = "../assets/bcp-logo.png";
        } else {
            // NOTE: ccsLogo was not in the original HTML but kept for path logic
            if (ccsLogo) ccsLogo.src = "./assets/ccs-logo.png";
            if (bcpLogo) bcpLogo.src = "./assets/bcp-logo.png";
        }

        // Navbar scroll effect
        window.addEventListener("scroll", () => {
            if (window.scrollY > 30) {
                // Change to a darker blue on scroll
                navbar.classList.remove("bg-transparent");
                navbar.classList.add("bg-blue-800", "shadow-lg");
            } else {
                // Back to transparent/blurred on top
                navbar.classList.add("bg-transparent");
                navbar.classList.remove("bg-blue-800", "shadow-lg");
            }
        });

        // Toggle mobile menu
        menuToggle.addEventListener("click", (e) => {
            e.stopPropagation();
            if (mobileMenu.classList.contains("hidden")) {
                mobileMenu.classList.remove("hidden", "slide-up");
                mobileMenu.classList.add("slide-down");
                menuIcon.classList.replace("fa-bars", "fa-times");
            } else {
                mobileMenu.classList.remove("slide-down");
                mobileMenu.classList.add("slide-up");
                menuIcon.classList.replace("fa-times", "fa-bars");
                setTimeout(() => mobileMenu.classList.add("hidden"), 300);
            }
        });

        // Close menu when clicking outside
        document.addEventListener("click", (e) => {
            if (!mobileMenu.classList.contains("hidden") &&
                !mobileMenu.contains(e.target) &&
                !menuToggle.contains(e.target)) {
                mobileMenu.classList.remove("slide-down");
                mobileMenu.classList.add("slide-up");
                menuIcon.classList.replace("fa-times", "fa-bars");
                setTimeout(() => mobileMenu.classList.add("hidden"), 300);
            }
        });

        // Close mobile menu with animation when any link is clicked
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault(); // prevent immediate navigation
                mobileMenu.classList.remove("slide-down");
                mobileMenu.classList.add("slide-up");
                menuIcon.classList.replace("fa-times", "fa-bars");

                // Navigate after animation completes
                setTimeout(() => {
                    window.location.href = link.href;
                }, 300);
            });
        });
    </script>


</body>

</html>