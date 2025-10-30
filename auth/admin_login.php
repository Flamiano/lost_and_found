<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        // Fetch admin by email only
        $stmt = $conn->prepare("SELECT id, full_name, email, password FROM admins WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            if (password_verify($password, $admin['password'])) {
                // Login successful
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                header("Location: ../admin/dashboard.php");
                exit();
            } else {
                $_SESSION['error'] = "Incorrect password.";
            }
        } else {
            $_SESSION['error'] = "No admin account found with that email.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Lost and Found System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png" />
    <link rel="stylesheet" href="./node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        * {
            font-family: "Poppins", sans-serif;
        }

        @media (max-width: 640px) {
            body {
                overflow: hidden;
            }
        }
    </style>
</head>

<body class="overflow-auto lg:overflow-hidden bg-gradient-to-r from-blue-700 via-blue-800 to-blue-900 flex items-center justify-center min-h-screen px-4">

    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl p-6 sm:p-8" data-aos="fade-up" data-aos-duration="800">

        <!-- Logos -->
        <a href="../index.php" class="flex justify-center items-center gap-3 mb-4 sm:mb-6">
            <img src="../assets/bcp-logo.png" alt="BCP Logo" class="w-12 h-12 sm:w-16 sm:h-16 object-contain">
        </a>

        <h2 class="text-lg sm:text-2xl font-bold text-blue-700 text-center mb-1 sm:mb-2">Admin Login</h2>
        <p class="text-gray-500 text-center text-xs sm:text-sm mb-6 sm:mb-8">Login to manage your system</p>

        <form action="" method="POST" class="space-y-3 sm:space-y-5">

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-center">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Email -->
            <div>
                <label for="email" class="block text-gray-700 text-sm font-medium mb-1">Email</label>
                <input type="email" id="email" name="email" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                    placeholder="Enter your email">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-gray-700 text-sm font-medium mb-1">Password</label>
                <input type="password" id="password" name="password" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                    placeholder="Enter your password">
            </div>

            <!-- Login Button -->
            <button type="submit"
                class="w-full bg-blue-700 text-white font-semibold py-2 rounded-lg hover:bg-blue-800 text-sm transition duration-300">
                Login
            </button>
        </form>
    </div>

    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();

        // Button animation like student login
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const button = form.querySelector('button[type="submit"]');

            form.addEventListener('submit', function(event) {
                event.preventDefault();
                button.disabled = true;
                button.textContent = 'Logging in...';
                button.classList.add('opacity-70', 'cursor-not-allowed');
                setTimeout(() => form.submit(), 1500); // short delay for effect
            });
        });
    </script>

</body>

</html>