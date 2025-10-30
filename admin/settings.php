<?php
session_start();
require '../config/db.php'; 

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Initialize $message and $status
$message = '';
$status = ''; // 'success' or 'error'

// Fetch Current Admin Data, EXCLUDING 'username'
try {
    // UPDATED: Selecting only full_name, email, and password
    $stmt = $conn->prepare("SELECT full_name, email, password FROM admins WHERE id = :id");
    $stmt->execute([':id' => $admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        // Admin user not found, force logout
        session_destroy();
        header("Location: ../auth/admin_login.php");
        exit();
    }

    $admin_name = $admin['full_name']; // Used for the header welcome message

} catch (PDOException $e) {
    // Handle database error
    $message = "Database error: Could not fetch admin data.";
    $status = 'error';
    // Initialize array keys in case of error (NOTE: 'username' removed)
    $admin = ['full_name' => '', 'email' => '', 'password' => ''];
}


// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Profile Update (Name, Email)
    if ($action === 'update_profile') {
        $new_full_name = trim($_POST['full_name'] ?? '');
        $new_email = trim($_POST['email'] ?? ''); // NOTE: username removed

        if (empty($new_full_name) || empty($new_email)) {
            $message = "Full Name and Email fields are required."; // UPDATED message
            $status = 'error';
        } else {
            try {
                // Check for duplicate email only
                $stmt_check = $conn->prepare("SELECT COUNT(*) FROM admins WHERE (email = :email) AND id != :id");
                $stmt_check->execute([
                    ':email' => $new_email,
                    ':id' => $admin_id
                ]);

                if ($stmt_check->fetchColumn() > 0) {
                    $message = "Email is already taken by another user."; // UPDATED message
                    $status = 'error';
                } else {
                    // Update the profile (NOTE: username removed from UPDATE)
                    $stmt_update = $conn->prepare("UPDATE admins SET full_name = :full_name, email = :email WHERE id = :id");
                    $stmt_update->execute([
                        ':full_name' => $new_full_name,
                        ':email' => $new_email,
                        ':id' => $admin_id
                    ]);

                    // Update session variable for display and redirect
                    $_SESSION['admin_full_name'] = $new_full_name;
                    header("Location: settings.php?status=success&message=" . urlencode("Profile updated successfully."));
                    exit();
                }
            } catch (PDOException $e) {
                $message = "Database error during profile update: " . $e->getMessage();
                $status = 'error';
            }
        }
    }

    // Password Update 
    elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = "All password fields are required.";
            $status = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = "New Password and Confirm Password do not match.";
            $status = 'error';
        } elseif (strlen($new_password) < 8) {
            $message = "New password must be at least 8 characters long.";
            $status = 'error';
        } elseif (!password_verify($current_password, $admin['password'])) {
            $message = "The current password you entered is incorrect.";
            $status = 'error';
        } else {
            try {
                // Hash the new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update the password
                $stmt_update = $conn->prepare("UPDATE admins SET password = :password WHERE id = :id");
                $stmt_update->execute([
                    ':password' => $hashed_password,
                    ':id' => $admin_id
                ]);

                header("Location: settings.php?status=success&message=" . urlencode("Password updated successfully."));
                exit();
            } catch (PDOException $e) {
                $message = "Database error during password update.";
                $status = 'error';
            }
        }
    }

    // Re-fetch admin data after a failed profile update to show current values
    if ($action === 'update_profile' && $status === 'error') {
        // UPDATED Selecting only full_name, email, and password
        $stmt = $conn->prepare("SELECT full_name, email, password FROM admins WHERE id = :id");
        $stmt->execute([':id' => $admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Handle URL Status/Message for alerts (using null coalescing operator for safety)
$status = $_GET['status'] ?? $status;
$message = $_GET['message'] ?? $message;

if ($status && $message) {
    $message = urldecode($message);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings | Lost and Found System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png" />
    <link rel="stylesheet" href="../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: "Poppins", sans-serif;
        }

        body {
            min-height: 100vh;
        }

        /* Custom margin-left for content on large screens (sidebar width: 16rem or 64 units) */
        @media (min-width: 1024px) {
            .lg-sidebar-offset {
                margin-left: 16rem;
            }
        }

        /* Override fontawesome path if needed */
        @font-face {
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            src: url('../node_modules/@fortawesome/fontawesome-free/webfonts/fa-solid-900.woff2') format('woff2');
        }
    </style>
</head>

<body class="bg-gray-100">

    <?php include 'sidebar.php'; ?>

    <div class="p-6 lg-sidebar-offset">
        <header class="bg-blue-700 text-white p-6 rounded-xl shadow-lg mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <i class="fas fa-cog text-blue-300"></i> Account Settings
            </h1>
            <span class="text-sm mt-2 sm:mt-0 opacity-80">
                Logged in as: <strong><?= htmlspecialchars($admin_name) ?></strong>
            </span>
        </header>

        <main class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            <div class="bg-white p-6 rounded-xl shadow-xl border-t-4 border-blue-600 hover:shadow-2xl transition-shadow duration-300">
                <h3 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b flex items-center gap-2">
                    <i class="fas fa-user-circle text-blue-600"></i> Update Profile Information
                </h3>

                <form action="settings.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_profile">

                    <div>
                        <label for="full_name" class="block text-sm font-semibold text-gray-700 mb-1">Full Name</label>
                        <input type="text" name="full_name" id="full_name" required
                            class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            value="<?= htmlspecialchars($admin['full_name']) ?>">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" id="email" required
                            class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            value="<?= htmlspecialchars($admin['email']) ?>">
                    </div>

                    <div class="pt-4">
                        <button type="submit"
                            class="w-full py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                            <i class="fas fa-save mr-2"></i> Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-xl border-t-4 border-red-600 hover:shadow-2xl transition-shadow duration-300">
                <h3 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b flex items-center gap-2">
                    <i class="fas fa-key text-red-600"></i> Change Password
                </h3>

                <form action="settings.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_password">

                    <div>
                        <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-1">Current Password</label>
                        <input type="password" name="current_password" id="current_password" required
                            class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm">
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-1">New Password</label>
                        <input type="password" name="new_password" id="new_password" required
                            class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required
                            class="mt-1 block w-full border border-gray-300 rounded-lg shadow-sm p-3 focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm">
                    </div>

                    <div class="pt-4">
                        <button type="submit"
                            class="w-full py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition duration-150 ease-in-out">
                            <i class="fas fa-lock mr-2"></i> Change Password
                        </button>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const status = '<?= $status ?>';
            const message = '<?= addslashes($message) ?>';

            if (status && message) {
                Swal.fire({
                    icon: status,
                    title: status === 'success' ? 'Success!' : 'Error!',
                    text: message,
                    confirmButtonColor: status === 'success' ? '#2563eb' : '#dc2626',
                    timer: 4000,
                    timerProgressBar: true,
                });

                // Clean the URL of parameters after showing the alert
                setTimeout(() => {
                    if (window.history.replaceState) {
                        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                        window.history.replaceState({
                            path: cleanUrl
                        }, '', cleanUrl);
                    }
                }, 100);
            }
        });
    </script>
    <script src="https://kit.fontawesome.com/a2d9d5b6e2.js" crossorigin="anonymous"></script>
</body>

</html>
