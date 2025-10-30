<?php
session_start();
require '../config/db.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $student_number = trim($_POST['student_number']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Password strength validation
    $pattern = "/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";
    if (!preg_match($pattern, $password)) {
        $_SESSION['error'] = "Password must be at least 8 characters long, include at least 1 uppercase letter, 1 number, and 1 special character.";
    } elseif ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        try {
            // Check if student number or email already exists
            $stmt = $conn->prepare("SELECT id FROM students WHERE student_number = :student_number OR email = :email");
            $stmt->execute([':student_number' => $student_number, ':email' => $email]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = "Student number or email already exists.";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $verification_code = rand(100000, 999999);

                // Insert new student
                $stmt = $conn->prepare("INSERT INTO students (full_name, student_number, email, phone_number, password, verification_code) 
                                        VALUES (:full_name, :student_number, :email, :phone_number, :password, :verification_code)");
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':student_number' => $student_number,
                    ':email' => $email,
                    ':phone_number' => $phone_number,
                    ':password' => $hashed_password,
                    ':verification_code' => $verification_code
                ]);

                // Send verification email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'viavanta.web@gmail.com';
                    $mail->Password   = 'qsqoycanowkvgzxw';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    $mail->setFrom('viavanta.web@gmail.com', 'Lost and Found System');
                    $mail->addAddress($email, $full_name);

                    $mail->isHTML(true);
                    $mail->Subject = 'Email Verification';
                    $mail->Body    = "<h3>Welcome, $full_name!</h3>
                                      <p>Your verification code is: <b>$verification_code</b></p>
                                      <p>Enter this code to verify your email.</p>";

                    $mail->send();
                    $_SESSION['verify_email'] = $email;
                    $_SESSION['success'] = "Registration successful! Verification code sent to email.";
                    header("Location: verify.php");
                    exit();
                } catch (Exception $e) {
                    $_SESSION['error'] = "Registration successful but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register | Lost and Found System</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <style>
        * {
            font-family: "Poppins", sans-serif;
        }
    </style>
</head>

<body class="bg-gradient-to-r from-blue-700 via-blue-800 to-blue-900 flex items-center justify-center min-h-screen px-4 py-8 overflow-hidden">

    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl p-2 sm:p-8 lg:p-5" data-aos="fade-up" data-aos-duration="800">

        <!-- Logos -->
        <a href="../index.php" class="flex justify-center items-center gap-3 mb-4 sm:mb-6">
            <img src="../assets/bcp-logo.png" alt="BCP Logo" class="w-12 h-12 sm:w-16 sm:h-16 object-contain">
        </a>

        <!-- Title -->
        <h2 class="text-2xl sm:text-3xl font-bold text-blue-700 text-center mb-2 sm:mb-3">Create Account</h2>
        <p class="text-gray-500 text-center text-[12px] sm:text-base mb-4 sm:mb-6">Register to report and manage lost items</p>

        <!-- Session messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-3 sm:px-4 py-2 sm:py-3 rounded mb-3 sm:mb-4 text-xs sm:text-base">
                <?php echo $_SESSION['error'];
                unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-3 sm:px-4 py-2 sm:py-3 rounded mb-3 sm:mb-4 text-xs sm:text-base">
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-4">

            <!-- Full Name -->
            <div class="flex flex-col">
                <label for="full_name" class="text-gray-700 font-medium text-xs sm:text-base mb-1">Full Name</label>
                <input type="text" id="full_name" name="full_name" required
                    class="border border-gray-300 rounded-lg px-2 py-1 sm:px-3 sm:py-2 text-xs sm:text-base focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                    placeholder="Full name">
            </div>

            <!-- Student Number -->
            <div class="flex flex-col">
                <label for="student_number" class="text-gray-700 font-medium text-xs sm:text-base mb-1">Student Number</label>
                <input type="text" id="student_number" name="student_number" required
                    pattern="s[0-9]+"
                    title="Use your LMS/SMS username."
                    class="border border-gray-300 rounded-lg px-2 py-1 sm:px-3 sm:py-2 text-xs sm:text-base focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                    placeholder="LMS/SMS username">
                <span class="text-[10px] sm:text-xs text-red-500 mt-0.5 sm:mt-1">* LMS username</span>
            </div>

            <!-- Email -->
            <div class="flex flex-col">
                <label for="email" class="text-gray-700 font-medium text-xs sm:text-base mb-1">Email</label>
                <input type="email" id="email" name="email" required
                    class="border border-gray-300 rounded-lg px-2 py-1 sm:px-3 sm:py-2 text-xs sm:text-base focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                    placeholder="example@email.com">
                <span class="text-[10px] sm:text-xs text-red-500 mt-0.5 sm:mt-1">* Active email only</span>
            </div>

            <!-- Phone Number -->
            <div class="flex flex-col">
                <label for="phone_number" class="text-gray-700 font-medium text-xs sm:text-base mb-1">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number" required maxlength="11" pattern="09[0-9]{9}" value="09"
                    class="border border-gray-300 rounded-lg px-2 py-1 sm:px-3 sm:py-2 text-xs sm:text-base focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                    placeholder="09XXXXXXXXX">
                <span class="text-[10px] sm:text-xs text-red-500 mt-0.5 sm:mt-1">* Must start with 09</span>
            </div>

            <!-- Password -->
            <div class="flex flex-col">
                <label for="password" class="text-gray-700 font-medium text-xs sm:text-base mb-1">Password</label>
                <input type="password" id="password" name="password" required
                    pattern="(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}"
                    title="Minimum 8 characters, include 1 uppercase, 1 number, 1 special character"
                    class="border border-gray-300 rounded-lg px-2 py-1 sm:px-3 sm:py-2 text-xs sm:text-base focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                    placeholder="Password">
                <span class="text-[10px] sm:text-xs text-red-500 mt-0.5 sm:mt-1">* Must be at least 8 characters that contains text, numbers, special characters</span>
            </div>

            <!-- Confirm Password -->
            <div class="flex flex-col">
                <label for="confirm_password" class="text-gray-700 font-medium text-xs sm:text-base mb-1">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                    class="border border-gray-300 rounded-lg px-2 py-1 sm:px-3 sm:py-2 text-xs sm:text-base focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200"
                    placeholder="Re-enter password">
            </div>

            <!-- Register Button -->
            <div class="md:col-span-2">
                <button type="submit"
                    class="w-full bg-blue-700 text-white font-semibold text-xs sm:text-base py-2 sm:py-2.5 rounded-lg hover:bg-blue-800 transition duration-300">
                    Register
                </button>
            </div>

            <!-- Login Link -->
            <div class="md:col-span-2 text-center">
                <p class="text-gray-600 text-[11px] sm:text-sm">
                    Already have an account?
                    <a href="../auth/login.php" class="text-blue-600 font-semibold hover:underline">Login</a>
                </p>
            </div>
        </form>
    </div>

    <!-- AOS Animation Script -->
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const button = form.querySelector('button[type="submit"]');

            form.addEventListener('submit', function(event) {
                event.preventDefault(); // Stop form from submitting immediately

                // Disable button and show loading text
                button.disabled = true;
                button.textContent = 'Registering...';
                button.classList.add('opacity-70', 'cursor-not-allowed');

                // Wait 3 seconds before submitting
                setTimeout(() => {
                    form.submit();
                }, 3000);
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('successMessage');

            if (successMessage) {
                // After 3 seconds, fade out the message
                setTimeout(() => {
                    successMessage.style.transition = 'opacity 0.6s ease';
                    successMessage.style.opacity = '0';

                    // Then remove it from layout completely
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                    }, 600); // wait for fade-out
                }, 3000);
            }
        });
    </script>


</body>



</html>