<?php
session_start();
require '../config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = trim($_POST['student_number']);
    $password = $_POST['password'];

    try {
        // Fetch user by student_number instead of email
        $stmt = $conn->prepare("SELECT id, full_name, password, is_verified FROM students WHERE student_number = :student_number");
        $stmt->execute([':student_number' => $student_number]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (!password_verify($password, $user['password'])) {
                $_SESSION['error'] = "Incorrect password.";
            } elseif (!$user['is_verified']) {
                $_SESSION['error'] = "Account not verified. Please check your email for the verification code.";
            } else {
                // Login successful
                $_SESSION['student_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                header("Location: ../user/reports.php"); // redirect to user dashboard
                exit();
            }
        } else {
            $_SESSION['error'] = "No account found with that username.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | Lost and Found System</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png" />
    <link rel="stylesheet" href="./node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

    <!-- Login Card -->
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl p-6 sm:p-8" data-aos="fade-up" data-aos-duration="800">

        <!-- Dual Logos -->
        <a href="../index.php" class="flex justify-center items-center gap-3 mb-4 sm:mb-6">
            <img src="../assets/bcp-logo.png" alt="BCP Logo" class="w-12 h-12 sm:w-16 sm:h-16 object-contain">
        </a>

        <!-- Title -->
        <h2 class="text-lg sm:text-2xl font-bold text-blue-700 text-center mb-1 sm:mb-2">Welcome Back</h2>
        <p class="text-gray-500 text-center text-xs sm:text-sm mb-6 sm:mb-8">Login to your account and manage your items</p>

        <!-- Login Form -->
        <form action="login.php" method="POST" class="space-y-3 sm:space-y-5">

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-center">
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Username -->
            <div>
                <label for="student_number" class="block text-gray-700 text-sm font-medium mb-1">Username</label>
                <input type="text" id="student_number" name="student_number" required
                    pattern="s[0-9]+"
                    title="Use your LMS/SMS username"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                    placeholder="Enter your LMS/SMS username">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-gray-700 text-sm font-medium mb-1">Password</label>
                <input type="password" id="password" name="password" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                    placeholder="Enter your password">
            </div>

            <!-- Forgot Password -->
            <div class="flex justify-end text-xs sm:text-sm">
                <a href="../auth/forgot_password.php" class="text-blue-600 hover:underline">Forgot Password?</a>
            </div>

            <!-- Login Button -->
            <button type="submit"
                class="w-full bg-blue-700 text-white font-semibold py-2 rounded-lg hover:bg-blue-800 text-sm transition duration-300">
                Login
            </button>

            <!-- Divider -->
            <div class="flex items-center my-3 sm:my-4">
                <div class="flex-grow h-px bg-gray-300"></div>
                <span class="px-2 text-gray-400 text-xs sm:text-sm">or</span>
                <div class="flex-grow h-px bg-gray-300"></div>
            </div>

            <!-- Register Link -->
            <p class="text-xs sm:text-sm text-center text-gray-600">
                Don’t have an account?
                <a href="../auth/register.php" class="text-blue-600 font-semibold hover:underline">Register here</a>
            </p>
        </form>
    </div>

    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const button = form.querySelector('button[type="submit"]');

            form.addEventListener('submit', function(event) {
                event.preventDefault();
                button.disabled = true;
                button.textContent = 'Logging in...';
                button.classList.add('opacity-70', 'cursor-not-allowed');
                setTimeout(() => {
                    form.submit();
                }, 3000);
            });
        });
    </script>

</body>

</html>