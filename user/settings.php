<?php
session_start();
require '../config/db.php';

// Check login
if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$success = "";
$error = "";

// Fetch user info
$stmt = $conn->prepare("SELECT * FROM students WHERE id = :id");
$stmt->execute([':id' => $student_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $profile_picture = $user['profile_picture'] ?? null;

    // Handle profile picture upload
    if (!empty($_FILES['profile_picture']['name'])) {
        $target_dir = __DIR__ . "/profile_picture/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["profile_picture"]["name"]);
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
            $profile_picture = $file_name;
        } else {
            $error = "Failed to upload profile picture.";
        }
    }

    // Validate password match
    if (!empty($password) && $password !== $confirm_password) {
        $error = "Passwords do not match.";
    }

    // Update if no errors
    if (empty($error)) {
        try {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update = "UPDATE students 
                           SET full_name = :full_name, 
                               password = :password, 
                               profile_picture = :profile_picture,
                               updated_at = NOW()
                           WHERE id = :id";
                $stmt = $conn->prepare($update);
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':password' => $hashed_password,
                    ':profile_picture' => $profile_picture,
                    ':id' => $student_id
                ]);
            } else {
                $update = "UPDATE students 
                           SET full_name = :full_name, 
                               profile_picture = :profile_picture,
                               updated_at = NOW()
                           WHERE id = :id";
                $stmt = $conn->prepare($update);
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':profile_picture' => $profile_picture,
                    ':id' => $student_id
                ]);
            }

            $success = "Profile updated successfully!";
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM students WHERE id = :id");
            $stmt->execute([':id' => $student_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings | Lost and Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a2d9d5e76d.js" crossorigin="anonymous"></script>
</head>

<body class="bg-gray-100 font-[Poppins]">
    <?php include 'navbar.php'; ?>

    <div class="flex flex-col md:flex-row mt-20 mx-auto max-w-7xl bg-white rounded-2xl shadow-md overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-full md:w-1/4 bg-gray-50 border-b md:border-b-0 md:border-r border-gray-200 py-4 md:py-6">
            <div class="flex items-center justify-between px-6 md:block">
                <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-2 md:mb-6">Settings</h2>
            </div>

            <nav class="hidden md:flex md:flex-col overflow-x-auto md:overflow-visible space-y-1 px-4 md:px-0">
                <a href="#" class="flex items-center whitespace-nowrap px-4 py-2 md:px-6 md:py-3 text-blue-700 bg-blue-100 font-medium rounded-lg md:rounded-none">
                    <i class="fas fa-user-circle mr-2 md:mr-3"></i> Account
                </a>
            </nav>
        </aside>

        <!-- Content Area -->
        <div class="flex-1 p-4 sm:p-6 md:p-8">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-800 mb-6">Account</h3>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <!-- Avatar -->
                <div class="flex flex-col items-center">
                    <div class="relative">
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img id="avatarPreview"
                                src="profile_picture/<?php echo htmlspecialchars($user['profile_picture']); ?>"
                                class="w-28 h-28 sm:w-32 sm:h-32 rounded-full border-4 border-gray-200 object-cover">
                        <?php else: ?>
                            <div id="avatarPreview"
                                class="w-28 h-28 sm:w-32 sm:h-32 rounded-full border-4 border-gray-200 flex items-center justify-center bg-gray-100 text-gray-400 text-5xl">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <label for="profile_picture" class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full cursor-pointer shadow-md">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="hidden">
                    </div>
                    <p class="text-gray-500 text-sm mt-2">Display Picture</p>
                </div>

                <!-- Profile Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-8">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Full Name</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']); ?>"
                            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Student Number</label>
                        <input type="text" value="<?= htmlspecialchars($user['student_number']); ?>"
                            class="w-full border border-gray-300 rounded-lg p-2 bg-gray-100 cursor-not-allowed" disabled>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Email</label>
                        <input type="email" value="<?= htmlspecialchars($user['email']); ?>"
                            class="w-full border border-gray-300 rounded-lg p-2 bg-gray-100 cursor-not-allowed" disabled>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Phone Number</label>
                        <input type="text" value="<?= htmlspecialchars($user['phone_number']); ?>"
                            class="w-full border border-gray-300 rounded-lg p-2 bg-gray-100 cursor-not-allowed" disabled>
                    </div>
                </div>

                <hr class="my-6 border-gray-200">

                <!-- Password -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">New Password</label>
                        <input type="password" name="password" placeholder="Enter new password"
                            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-1">Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="Confirm new password"
                            class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="text-center sm:text-right mt-6">
                    <button type="submit"
                        class="bg-blue-600 text-white w-full sm:w-auto px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SweetAlert Messages -->
    <?php if (!empty($success)): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: '<?= $success; ?>',
                showConfirmButton: false,
                timer: 2000
            });
        </script>
    <?php elseif (!empty($error)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: '<?= $error; ?>',
                showConfirmButton: true
            });
        </script>
    <?php endif; ?>

    <script>
        // Live preview for uploaded image
        const fileInput = document.getElementById('profile_picture');
        const avatarPreview = document.getElementById('avatarPreview');

        fileInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (avatarPreview.tagName.toLowerCase() === 'img') {
                        avatarPreview.src = e.target.result;
                    } else {
                        avatarPreview.innerHTML = `<img src="${e.target.result}" class="w-28 h-28 sm:w-32 sm:h-32 rounded-full object-cover">`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>

</html>