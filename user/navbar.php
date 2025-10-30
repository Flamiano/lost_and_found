<?php
require '../config/db.php';

// The basename function is crucial here, as it grabs 'filename.php'
$current_page = basename($_SERVER['PHP_SELF']);

// Get user info if logged in (Unchanged)
$user_profile = null;
if (isset($_SESSION['student_id'])) {
    $stmt = $conn->prepare("SELECT profile_picture FROM students WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['student_id']]);
    $user_profile = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Define the active class styles to use a consistent blue background
// We'll use bg-blue-700 for the active link in both scroll states
$active_class_desktop = 'bg-blue-700 text-white font-semibold shadow-inner';
$inactive_class_desktop = 'hover:bg-blue-700 hover:text-white';

$active_class_mobile = 'bg-blue-900 font-semibold';
$inactive_class_mobile = 'hover:bg-blue-600';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php
                                    echo (strpos($_SERVER['PHP_SELF'], '/user/') !== false)
                                        ? '../node_modules/@fortawesome/fontawesome-free/css/all.min.css'
                                        : './node_modules/@fortawesome/fontawesome-free/css/all.min.css';
                                    ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: "Poppins", sans-serif;
        }

        .navbar {
            /* Keep the transition for the navbar itself */
            transition: background-color 0.4s ease, color 0.4s ease, box-shadow 0.4s ease;
        }

        /* The original slide animations remain */
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

    <nav id="navbar" class="navbar fixed w-full top-0 z-50 bg-blue-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">

            <div class="flex items-center space-x-3">
                <img id="bcp-logo" src="../assets/bcp-logo.png" alt="BCP Logo" class="h-10 w-10">
                <div class="hidden sm:block">
                    <h1 class="font-semibold text-sm md:text-base leading-tight">Lost and Found Dashboard</h1>
                    <p class="text-xs text-blue-100">Bestlink College of the Philippines</p>
                </div>
            </div>

            <div class="hidden md:flex space-x-3 font-medium">
                <a href="reports.php"
                    class="nav-link px-3 py-2 rounded-lg transition-all duration-300 
                <?= $current_page === 'reports.php' ? $active_class_desktop : $inactive_class_desktop ?>">
                    Reports
                </a>

                <a href="view_items.php"
                    class="nav-link px-3 py-2 rounded-lg transition-all duration-300 
                <?= $current_page === 'view_items.php' ? $active_class_desktop : $inactive_class_desktop ?>">
                    View Items
                </a>

                <a href="overview.php"
                    class="nav-link px-3 py-2 rounded-lg transition-all duration-300 
                <?= $current_page === 'overview.php' ? $active_class_desktop : $inactive_class_desktop ?>">
                    Overview
                </a>

                <a href="settings.php"
                    class="nav-link px-3 py-2 rounded-lg transition-all duration-300 
                <?= $current_page === 'settings.php' ? $active_class_desktop : $inactive_class_desktop ?>">
                    Settings
                </a>
            </div>

            <div class="hidden md:flex items-center gap-4">
                <button id="logoutBtn"
                    class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition-all flex items-center gap-2">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>

                <a href="settings.php" class="relative flex items-center justify-center">
                    <?php if (!empty($user_profile['profile_picture'])): ?>
                        <img src="profile_picture/<?php echo htmlspecialchars($user_profile['profile_picture']); ?>"
                            alt="User Profile"
                            class="w-10 h-10 rounded-full border-2 border-white object-cover hover:opacity-90 transition">
                    <?php else: ?>
                        <i class="fas fa-user-circle text-3xl text-white hover:text-blue-200 transition"></i>
                    <?php endif; ?>
                </a>

            </div>


            <div class="md:hidden relative z-50">
                <button id="menu-toggle" class="text-white hover:text-blue-200">
                    <i id="menu-icon" class="fas fa-bars text-lg"></i>
                </button>
            </div>

        </div>

        <div id="mobile-menu"
            class="md:hidden hidden bg-blue-700/90 border-t border-blue-500 rounded-b-lg shadow-lg backdrop-blur-md">
            <div class="flex flex-col divide-y divide-blue-500">
                <a href="reports.php"
                    class="nav-link px-4 py-3 text-white transition 
            <?= $current_page === 'reports.php' ? $active_class_mobile : $inactive_class_mobile ?>">
                    Reports
                </a>

                <a href="view_items.php"
                    class="nav-link px-4 py-3 text-white transition 
            <?= $current_page === 'view_items.php' ? $active_class_mobile : $inactive_class_mobile ?>">
                    View Items
                </a>

                <a href="overview.php"
                    class="nav-link px-4 py-3 text-white transition 
            <?= $current_page === 'overview.php' ? $active_class_mobile : $inactive_class_mobile ?>">
                    Overview
                </a>

                <a href="settings.php"
                    class="nav-link px-4 py-3 text-white transition 
            <?= $current_page === 'settings.php' ? $active_class_mobile : $inactive_class_mobile ?>">
                    Settings
                </a>

                <div class="p-4">
                    <button id="logoutBtnMobile"
                        class="block w-full bg-red-600 text-white font-semibold py-3 rounded-xl shadow-lg hover:shadow-xl hover:bg-red-700 transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div id="logoutModal"
        class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-80 p-6 text-center">
            <i class="fas fa-sign-out-alt text-red-600 text-3xl mb-3"></i>
            <h2 class="text-lg font-semibold mb-2 text-gray-800">Are you sure you want to logout?</h2>
            <p class="text-sm text-gray-500 mb-5">You will need to log in again to access your dashboard.</p>
            <div class="flex justify-center gap-3">
                <button id="cancelLogout"
                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 text-sm font-semibold rounded-lg transition">
                    Cancel
                </button>
                <a href="../auth/logout.php"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition">
                    Logout
                </a>
            </div>
        </div>
    </div>

    <script>
        const navbar = document.getElementById("navbar");
        const mobileMenu = document.getElementById("mobile-menu");
        const menuToggle = document.getElementById("menu-toggle");
        const menuIcon = document.getElementById("menu-icon");

        // Scroll listener for navbar color (Updated to use a lighter blue)
        window.addEventListener("scroll", () => {
            const isScrolled = window.scrollY > 30;

            if (isScrolled) {
                // Change to a lighter blue navbar (e.g., bg-blue-700) and keep text white (text-white)
                navbar.classList.remove("bg-blue-800");
                navbar.classList.add("bg-blue-700", "shadow-lg");
            } else {
                // Change back to the initial dark blue (bg-blue-800)
                navbar.classList.remove("bg-blue-700", "shadow-lg");
                navbar.classList.add("bg-blue-800");
            }

            // Note: We don't need to change text-white/text-blue-800 classes anymore
            // because we are sticking to blue backgrounds and white text.

            // The blue-100 text for the subtitle should always stay text-blue-100 (which is light blue)
            // when the navbar is blue. When the navbar is light blue, blue-100 still works well.
        });

        // Mobile menu toggle (Unchanged)
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

        // Close mobile menu when clicking outside (Unchanged)
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

        // Logout modal logic (Unchanged)
        const logoutModal = document.getElementById("logoutModal");
        const logoutBtn = document.getElementById("logoutBtn");
        const logoutBtnMobile = document.getElementById("logoutBtnMobile");
        const cancelLogout = document.getElementById("cancelLogout");

        logoutBtn?.addEventListener("click", () => logoutModal.classList.remove("hidden"));
        logoutBtnMobile?.addEventListener("click", () => logoutModal.classList.remove("hidden"));
        cancelLogout.addEventListener("click", () => logoutModal.classList.add("hidden"));
        logoutModal.addEventListener("click", (e) => {
            if (e.target === logoutModal) logoutModal.classList.add("hidden");
        });
    </script>


</body>

</html>