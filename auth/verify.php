<?php
session_start();
require '../config/db.php';

// Redirect if no email session
if (!isset($_SESSION['verify_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['verify_email'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Combine OTP inputs
    $code = implode('', $_POST['otp']);

    try {
        $stmt = $conn->prepare("SELECT verification_code FROM students WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['verification_code'] == $code) {
            // Mark user as verified
            $update = $conn->prepare("UPDATE students SET is_verified = 1, verification_code = NULL WHERE email = :email");
            $update->execute([':email' => $email]);

            unset($_SESSION['verify_email']);
            $success = "Email verified successfully! You can now login.";
        } else {
            $error = "Incorrect verification code. Please try again.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify | Lost and Found System</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <style>
        * {
            font-family: "Poppins", sans-serif;
        }

        /* Style OTP boxes */
        .otp-input {
            width: 45px;
            height: 50px;
            text-align: center;
            font-size: 1.25rem;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
        }

        .otp-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
        }

        @media (max-width: 640px) {
            .otp-input {
                width: 40px;
                height: 45px;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-r from-blue-700 via-blue-800 to-blue-900 flex items-center justify-center min-h-screen px-4">

    <!-- Verification Card -->
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl p-6 sm:p-8" data-aos="fade-up" data-aos-duration="800">

        <!-- Logos -->
        <a href="../index.php" class="flex justify-center items-center gap-3 mb-4 sm:mb-6">
            <img src="../assets/bcp-logo.png" alt="BCP Logo" class="w-12 h-12 sm:w-16 sm:h-16 object-contain">
            <img src="../assets/ccs-logo.png" alt="CCS Logo" class="w-12 h-12 sm:w-16 sm:h-16 object-contain">
        </a>

        <!-- Title -->
        <h2 class="text-lg sm:text-2xl font-bold text-blue-700 text-center mb-1 sm:mb-2">Email Verification</h2>
        <p class="text-gray-500 text-center text-xs sm:text-sm mb-6 sm:mb-8">
            Enter the 6-digit code sent to your email
        </p>

        <!-- Error / Success Messages -->
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-center text-sm">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-center text-sm">
                <?= $success ?>
            </div>
            <div class="text-center">
                <a href="login.php"
                    class="bg-blue-700 text-white px-4 py-2 rounded-lg hover:bg-blue-800 transition text-sm">
                    Go to Login
                </a>
            </div>
        <?php else: ?>

            <!-- Verification Form -->
            <form id="verifyForm" action="" method="POST" class="space-y-6">

                <!-- OTP Inputs -->
                <div class="flex justify-center gap-2 sm:gap-3">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" maxlength="1" name="otp[]" required
                            class="otp-input" inputmode="numeric" pattern="[0-9]*">
                    <?php endfor; ?>
                </div>

                <button id="verifyButton" type="submit"
                    class="w-full bg-blue-700 text-white font-semibold py-2 rounded-lg hover:bg-blue-800 text-sm transition duration-300">
                    Verify
                </button>
            </form>

        <?php endif; ?>
    </div>

    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();

        const inputs = document.querySelectorAll('.otp-input');
        const form = document.getElementById('verifyForm');
        const button = document.getElementById('verifyButton');

        inputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                if (input.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }

                // Auto-submit when all boxes filled
                const allFilled = [...inputs].every(inp => inp.value.length === 1);
                if (allFilled) {
                    button.disabled = true;
                    button.innerText = "Verifying...";
                    button.classList.add("opacity-70", "cursor-not-allowed");
                    setTimeout(() => form.submit(), 1000); // Auto-submit
                }
            });

            input.addEventListener('keydown', e => {
                if (e.key === 'Backspace' && input.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });

        // Add loading animation if button manually clicked
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            button.disabled = true;
            button.innerText = "Verifying...";
            button.classList.add("opacity-70", "cursor-not-allowed");
            setTimeout(() => form.submit(), 3000);
        });
    </script>

</body>

</html>