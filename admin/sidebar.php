<?php
// sidebar.php (Ensures consistent icons)
require '../config/db.php';

// Check for session existence before attempting to start (though typically done earlier)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Using a try-catch block for robust DB access, though not strictly required for this file's function
try {
    $stmt = $conn->prepare("SELECT full_name, email FROM admins WHERE id = :id");
    $stmt->bindValue(':id', $admin_id, PDO::PARAM_INT);
    $stmt->execute();

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $admin_name = $admin['full_name'] ?? 'Admin';
    $admin_email = $admin['email'] ?? 'admin@example.com';
} catch (PDOException $e) {
    error_log("Sidebar DB Error: " . $e->getMessage());
    $admin_name = 'Admin Error';
    $admin_email = 'db_error@example.com';
}


$current_page = basename($_SERVER['PHP_SELF']);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sidebar | Lost and Found System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLMDJc5wQ/9rYl3Kq/o4fM3sH/A0W9P4+9m/Lw1c5kK3O9P8r9W4wG7rXnLwT5+t4Q==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: "Poppins", sans-serif;
        }

        a {
            transition: all 0.3s ease;
        }

        a.active {
            background-color: #374151;
            /* Gray-700 for active link */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        a:hover:not(#openLogoutModal, .logout-modal-link) {
            background-color: #4b5563;
            /* Gray-600 for hover */
            transform: scale(1.02);
        }

        @media (max-width: 768px) {
            aside {
                width: 16rem;
            }
        }

        @media (max-width: 640px) {
            body {
                overflow-x: hidden;
            }

            aside {
                position: fixed;
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                z-index: 50;
            }

            aside.open {
                transform: translateX(0);
            }
        }
    </style>
</head>

<body class="bg-gray-100">

    <button id="menu-btn" class="p-3 fixed top-4 left-4 bg-gray-900 text-white rounded-lg lg:hidden z-50">
        <i class="fas fa-bars"></i>
    </button>

    <aside id="sidebar" class="bg-gradient-to-b from-gray-900 to-gray-800 text-white h-screen flex flex-col justify-between w-64 fixed shadow-xl lg:translate-x-0">

        <div class="flex justify-center items-center py-6 border-b border-gray-700 gap-3">
            <img src="../assets/bcp-logo.png" alt="BCP Logo" class="h-20 w-auto">
        </div>

        <nav class="flex-1 px-4 py-6 space-y-3">
            <?php
            // Defined links with consistent icons
            $navLinks = [
                ['name' => 'Dashboard', 'icon' => 'fas fa-tachometer-alt', 'url' => 'dashboard.php'],
                ['name' => 'Students', 'icon' => 'fas fa-user-graduate', 'url' => 'students.php'],
                ['name' => 'View Items', 'icon' => 'fas fa-box-open', 'url' => 'view_items.php'],
                ['name' => 'Claimed Items', 'icon' => 'fas fa-handshake', 'url' => 'claim_items.php'], // Consistent Icon
                ['name' => 'Settings', 'icon' => 'fas fa-cog', 'url' => 'settings.php']
            ];

            foreach ($navLinks as $link) {
                $activeClass = $current_page === $link['url'] ? 'active' : '';
                // Outputting the link with the defined icon class
                echo '<a href="' . $link['url'] . '" class="flex items-center gap-4 px-4 py-3 rounded-lg ' . $activeClass . '">';
                echo '<i class="' . $link['icon'] . ' text-lg w-5 text-center"></i>'; // Fixed width for alignment
                echo '<span class="font-medium">' . $link['name'] . '</span>';
                echo '</a>';
            }
            ?>
        </nav>

        <div class="px-4 py-5 border-t border-gray-700 flex flex-col items-center gap-2 bg-gray-800 rounded-t-lg shadow-inner">
            <div class="flex flex-col items-center gap-1">
                <div class="flex items-center gap-2">
                    <i class="fas fa-user-circle text-3xl text-white"></i>
                    <div class="flex flex-col">
                        <span class="font-semibold"><?= htmlspecialchars($admin_name) ?></span>
                        <span class="text-gray-400 text-xs"><?= htmlspecialchars($admin_email) ?></span>
                    </div>
                </div>
                <span class="text-green-400 text-sm flex items-center gap-1 mt-1">
                    <span class="h-2 w-2 bg-green-400 rounded-full animate-pulse"></span> Online
                </span>

                <a href="#" id="openLogoutModal"
                    class="flex items-center gap-2 mt-3 px-4 py-2 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 hover:shadow-lg transition-all duration-300 transform hover:scale-[1.03]">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </div>
        </div>

    </aside>

    <div id="logoutModal"
        class="hidden fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl w-80 p-6 text-center animate-fade-in">
            <i class="fas fa-sign-out-alt text-red-600 text-3xl mb-3"></i>
            <h2 class="text-lg font-semibold mb-2 text-gray-800">Are you sure you want to logout?</h2>
            <p class="text-sm text-gray-500 mb-5">You will need to log in again to access your dashboard.</p>
            <div class="flex justify-center gap-3">
                <button id="cancelLogout"
                    class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 text-sm font-semibold rounded-lg transition">
                    Cancel
                </button>
                <a href="../auth/logout.php"
                    class="logout-modal-link px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition">
                    Logout
                </a>
            </div>
        </div>
    </div>
    <script>
        const btn = document.getElementById('menu-btn');
        const sidebar = document.getElementById('sidebar');
        const logoutModal = document.getElementById('logoutModal');
        const openLogoutBtn = document.getElementById('openLogoutModal');
        const cancelLogoutBtn = document.getElementById('cancelLogout');

        // Toggle sidebar for mobile
        btn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        // Open Modal
        openLogoutBtn.addEventListener('click', (e) => {
            e.preventDefault(); // Stop the default link navigation
            logoutModal.classList.remove('hidden');
            // Optional: Hide sidebar on mobile when modal opens
            if (sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
            }
        });

        // Close Modal via Cancel button
        cancelLogoutBtn.addEventListener('click', () => {
            logoutModal.classList.add('hidden');
        });

        // Close Modal by clicking outside (optional but recommended)
        logoutModal.addEventListener('click', (e) => {
            if (e.target === logoutModal) {
                logoutModal.classList.add('hidden');
            }
        });

        // Optional: Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !logoutModal.classList.contains('hidden')) {
                logoutModal.classList.add('hidden');
            }
        });
    </script>

</body>

</html>