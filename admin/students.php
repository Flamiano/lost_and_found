<?php
session_start();
require '../config/db.php'; 

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/admin_login.php");
    exit();
}

// MASKING FUNCTIONS (Needed for display and pre-populating edit form)
function mask_email($email)
{
    // Basic format check
    $parts = explode("@", $email);
    if (count($parts) !== 2) return $email;
    $name = $parts[0];
    $domain = $parts[1];
    // Mask name: Show first 2 chars, one last char, mask the middle (at least 2 stars)
    $name_length = strlen($name);
    // Determine number of characters to keep visible: min(2, $name_length) + min(1, $name_length-1)
    $visible_chars = min(2, $name_length) + min(1, max(0, $name_length - 2));
    // The number of stars to show: total length - visible chars
    $stars_count = max(0, $name_length - $visible_chars);

    $masked_name = substr($name, 0, 2) . str_repeat('*', $stars_count) . substr($name, -1);

    // Fallback for very short names (e.g., 'a@b.c' -> 'a*c@b.c')
    if ($name_length <= 3) {
        $masked_name = substr($name, 0, 1) . str_repeat('*', max(1, $name_length - 2)) . substr($name, -1);
    }

    return $masked_name . '@' . $domain;
}

function mask_phone($phone)
{
    // Masks all but the first two and last two digits
    // Example: 09171234567 -> 09*******67
    $length = strlen($phone);
    // Ensure the phone number is long enough to mask (e.g., at least 5 digits)
    if ($length <= 4) return $phone;

    $masked_middle = str_repeat('*', max(0, $length - 4)); // -4 for first two and last two
    return substr($phone, 0, 2) . $masked_middle . substr($phone, -2);
}

function mask_student_number($student_number)
{
    // Remove the 's' prefix if it exists, to work only with digits
    $digits_only = ltrim($student_number, 's');
    $length = strlen($digits_only);

    // If length is 3 or less, display as is
    if ($length <= 3) {
        return $student_number;
    }

    // Define the parts: first char + masked middle + last two chars
    $first_char = substr($digits_only, 0, 1);
    $last_two_chars = substr($digits_only, -2);

    // Calculate how many asterisks are needed
    $stars_count = max(0, $length - 3);
    $masked_middle = str_repeat('*', $stars_count);

    // Return the masked number, optionally adding 's' back if it was there
    $prefix = (substr($student_number, 0, 1) === 's') ? 's' : '';
    return $prefix . $first_char . $masked_middle . $last_two_chars;
}

// HANDLE DELETE ACTION
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];

    if (!is_numeric($id)) {
        $_SESSION['error_message'] = "Invalid user ID.";
        header("Location: students.php");
        exit();
    }

    try {
        // Optional: Get student name before deletion for a better success message
        $stmt_name = $conn->prepare("SELECT full_name FROM students WHERE id = ?");
        $stmt_name->execute([$id]);
        $student_data = $stmt_name->fetch(PDO::FETCH_ASSOC);
        $student_name = $student_data ? htmlspecialchars($student_data['full_name']) : "User";

        // Perform the deletion
        $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount()) {
            $_SESSION['flash_message'] = $student_name . " was successfully deleted.";
        } else {
            $_SESSION['error_message'] = "Could not find user to delete.";
        }
    } catch (PDOException $e) {
        // Catch database errors (e.g., foreign key constraints)
        $_SESSION['error_message'] = "Database error: Failed to delete user. The user might be linked to other records. " . $e->getMessage();
    }

    header("Location: students.php");
    exit();
}


// HANDLE ADD / EDIT FORM SUBMISSION 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['check_duplicate'])) {

    // 1. Get POST data
    $id = $_POST['id'] ?? '';
    $full_name = trim($_POST['full_name']);

    // Default to empty strings if not present, especially for EDIT mode where they are not displayed/required
    $student_number = trim($_POST['student_number'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 2. Data Cleaning and Formatting
    $is_add_mode = empty($id);
    $errors = [];

    // Data processing for ADD mode fields
    if ($is_add_mode) {
        // If adding, the input fields are raw, so we prepare them
        $raw_student_number_digits = preg_replace('/\D/', '', $student_number);
        $student_number_formatted = 's' . $raw_student_number_digits;
    } else {
        // In EDIT mode, we only expect full_name to be updated.
        // We set these to empty to prevent confusion in the update logic below.
        $student_number_formatted = '';
    }

    // 3. Server-side validation

    // Validation for both add and edit
    if (empty($full_name)) {
        $errors[] = "Full Name is required.";
    }


    // Validation specific to ADD mode
    if ($is_add_mode) {

        if (empty($student_number) || empty($phone_number) || empty($email) || empty($password) || empty($confirm_password)) {
            $errors[] = "All fields are required for adding a new user.";
        } else {
            // Student Number Format Check
            if (!preg_match('/^\d{5,}$/', $raw_student_number_digits)) {
                $errors[] = "Student number must be numeric (excluding 's' prefix) and reasonably long (e.g., at least 5 digits).";
            }

            // Phone Number Format Check
            if (!preg_match('/^09\d{9}$/', $phone_number)) {
                $errors[] = "Phone number must start with 09 and be 11 digits long.";
            }

            // Email Format Check
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email address.";
            }

            // Password Match and Strength Check
            if ($password !== $confirm_password) {
                $errors[] = "Password and Confirm Password do not match.";
            } else if (
                strlen($password) < 8 ||
                !preg_match('/[A-Za-z]/', $password) ||
                !preg_match('/\d/', $password) ||
                !preg_match('/[!@#$%^&*(),.?":{}|<>]|[^A-Za-z0-9]/', $password)
            ) {
                $errors[] = "Password must be at least 8 characters long with letters, numbers, and special characters.";
            }

            // Duplicate Check (Only in ADD mode where these values are new)
            try {
                // Check Student Number
                $stmt_s = $conn->prepare("SELECT COUNT(*) FROM students WHERE student_number = ?");
                $stmt_s->execute([$student_number_formatted]);
                if ($stmt_s->fetchColumn() > 0) $errors[] = "Student Number already exists.";

                // Check Email
                $stmt_e = $conn->prepare("SELECT COUNT(*) FROM students WHERE email = ?");
                $stmt_e->execute([$email]);
                if ($stmt_e->fetchColumn() > 0) $errors[] = "Email already exists.";
            } catch (PDOException $e) {
                $errors[] = "Database check error: " . $e->getMessage();
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['error_message'] = implode('<br>', $errors);
        // Important: Store POST data in session for repopulating the form if needed (optional feature)
        $_SESSION['form_data'] = $_POST;
        header("Location: students.php");
        exit();
    }

    // 4. Insert / Update logic
    try {
        if (!empty($id)) {
            // EDIT Mode: Only update full_name (as per the provided logic)
            $sql = "UPDATE students SET full_name=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$full_name, $id]);
            $_SESSION['flash_message'] = "User updated successfully (Full Name only)!";
        } else {
            // ADD Mode: Insert all fields
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO students (full_name, student_number, phone_number, email, password, is_verified, created_at)
                    VALUES (?, ?, ?, ?, ?, 0, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$full_name, $student_number_formatted, $phone_number, $email, $hashed_password]);
            $_SESSION['flash_message'] = "User added successfully!";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database Transaction Error: " . $e->getMessage();
    }

    // Clear form data from session
    unset($_SESSION['form_data']);

    header("Location: students.php");
    exit();
}

// AJAX VALIDATION CHECK
if (isset($_POST['check_duplicate'])) {
    $field = $_POST['field'];
    $value = trim($_POST['value']);
    $is_edit = $_POST['is_edit'] ?? ''; // This is typically a boolean or a non-empty ID if in edit mode
    $exists = false;

    // Sanitize field name to prevent SQL injection in the column name
    $allowed_fields = ['student_number', 'email'];
    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['exists' => true, 'error' => 'Invalid field']);
        exit();
    }

    // Only proceed with duplicate check if in ADD mode (i.e., $is_edit is empty/falsy)
    // and/or we are not checking against the user being edited (not fully implemented in the original AJAX call, 
    // but the original logic only checks if $is_edit is empty, implying ADD mode.)
    if (empty($is_edit)) {
        // Add 's' prefix for student number if it's not present (and clean the number)
        if ($field === 'student_number') {
            $value = 's' . preg_replace('/\D/', '', $value);
        }

        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM students WHERE {$field} = ?");
            $stmt->execute([$value]);
            $exists = $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            // Log or handle error if needed
            $exists = true; // Fail safe on error
        }
    }

    echo json_encode(['exists' => $exists]);
    exit();
}


// FETCH ALL STUDENTS & STATS 
$stmt = $conn->prepare("SELECT * FROM students ORDER BY created_at DESC");
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_users = count($students);
$verified_users = count(array_filter($students, fn($s) => $s['is_verified']));
$pending_users = $total_users - $verified_users;

// Retrieve flash messages
$flash_message = $_SESSION['flash_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
$form_data = $_SESSION['form_data'] ?? null;

// Clear flash messages after retrieving
unset($_SESSION['flash_message']);
unset($_SESSION['error_message']);
unset($_SESSION['form_data']);

// HTML output starts here
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | Lost and Found System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        * {
            font-family: "Poppins", sans-serif;
        }

        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid #ccc;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            color: #777;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .step-circle.active {
            border-color: #2563eb;
            background-color: #2563eb;
            color: white;
        }

        /* Styling for the stepper line */
        #stepperContainer {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #stepperContainer::before {
            content: '';
            position: absolute;
            top: calc(32px/2);
            /* center with circle */
            left: 10%;
            right: 10%;
            height: 2px;
            background-color: #ccc;
            z-index: 0;
        }

        #stepperContainer>div {
            z-index: 1;
            /* Keep circles above the line */
            background-color: white;
            /* to make the line not cross the circle */
            padding: 0 5px;
            /* small padding to clear the line */
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>

    <div class="ml-64 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <h3 class="text-2xl font-bold text-blue-800 flex items-center gap-2">
                <i class="fas fa-user-graduate text-blue-700"></i> Users
            </h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="flex items-center gap-4 p-4 border-l-8 border-blue-600 bg-white shadow-lg rounded-lg">
                <div class="p-3 bg-blue-100 text-blue-700 rounded-full">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Users</p>
                    <p class="text-xl font-bold text-gray-800"><?= $total_users ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-4 border-l-8 border-green-600 bg-white shadow-lg rounded-lg">
                <div class="p-3 bg-green-100 text-green-700 rounded-full">
                    <i class="fas fa-user-check text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Verified Users</p>
                    <p class="text-xl font-bold text-gray-800"><?= $verified_users ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-4 border-l-8 border-yellow-600 bg-white shadow-lg rounded-lg">
                <div class="p-3 bg-yellow-100 text-yellow-700 rounded-full">
                    <i class="fas fa-user-clock text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Pending Users</p>
                    <p class="text-xl font-bold text-gray-800"><?= $pending_users ?></p>
                </div>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 w-full">
            <div class="flex flex-1 flex-col sm:flex-row gap-2 items-center w-full sm:w-auto">
                <div class="relative flex-1 w-full sm:w-auto">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    <input type="text" id="searchInput" placeholder="Search by name or email..."
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm">
                </div>
                <div class="relative w-full sm:w-48">
                    <i class="fas fa-filter absolute left-3 top-3 text-gray-400"></i>
                    <select id="statusFilter"
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-sm">
                        <option value="all">All (<?= $total_users ?>)</option>
                        <option value="verified">Verified (<?= $verified_users ?>)</option>
                        <option value="pending">Pending (<?= $pending_users ?>)</option>
                    </select>
                </div>
            </div>
            <button onclick="openAddModal()"
                class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm shadow-md transition duration-200 ml-auto sm:ml-0">
                <i class="fas fa-plus"></i> Add User
            </button>
        </div>
        <div class="overflow-x-auto bg-white border border-gray-200 rounded-2xl shadow-md">

            <table class="min-w-full divide-y divide-gray-200 text-sm <?= empty($students) ? 'hidden' : '' ?>" id="studentsTable">
                <thead class="bg-blue-800 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left">Profile</th>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left">Student Number</th>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2 text-left">Phone</th>
                        <th class="px-4 py-2 text-left">Verified</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($students as $student): ?>
                        <tr class="border-b hover:bg-gray-50 transition-colors duration-200"
                            data-name="<?= htmlspecialchars(strtolower($student['full_name'])) ?>"
                            data-email="<?= htmlspecialchars(strtolower($student['email'])) ?>"
                            data-status="<?= $student['is_verified'] ? 'verified' : 'pending' ?>">

                            <td class="px-4 py-2">
                                <?php if (!empty($student['profile_picture'])): ?>
                                    <img src="../user/profile_picture/<?= htmlspecialchars($student['profile_picture']) ?>"
                                        class="w-10 h-10 object-cover rounded-full" alt="Profile">
                                <?php else: ?>
                                    <i class="fas fa-user-circle text-4xl text-gray-400"></i>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-2"><?= htmlspecialchars($student['full_name']) ?></td>

                            <td class="px-4 py-2" data-student-number-full="<?= htmlspecialchars($student['student_number']) ?>">
                                <?= mask_student_number($student['student_number']) ?>
                            </td>

                            <td class="px-4 py-2" data-email-full="<?= htmlspecialchars($student['email']) ?>">
                                <?= mask_email($student['email']) ?>
                            </td>

                            <td class="px-4 py-2" data-phone-full="<?= htmlspecialchars($student['phone_number']) ?>">
                                <?= mask_phone($student['phone_number']) ?>
                            </td>

                            <td class="px-4 py-2">
                                <?php if ($student['is_verified']): ?>
                                    <span class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-full bg-green-100 text-green-800 border border-green-200">
                                        Verified
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
                                        Pending
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="px-4 py-2 flex gap-2">
                                <button onclick='openEditModal(<?= json_encode(array_merge($student, ['student_number' => $student['student_number'], 'email' => $student['email'], 'phone_number' => $student['phone_number']])) ?>)'
                                    class="text-blue-600 hover:text-blue-800" title="Edit Student">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="openDeleteModal(<?= $student['id'] ?>)"
                                    class="text-red-600 hover:text-red-800" title="Delete Student">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (empty($students)): ?>
                <div id="noInitialStudents" class="flex justify-center items-center h-48 py-10 text-gray-500 italic">
                    <div class="text-center">
                        <i class="fas fa-folder-open text-3xl mb-2"></i><br>No registered students found.
                    </div>
                </div>
            <?php endif; ?>

            <div id="noResultsRow" class="hidden text-center py-10 text-gray-500 italic">
                <i class="fas fa-search-minus text-3xl mb-2"></i><br>No users match your current filter.
            </div>
        </div>
        <div id="userModal"
            class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50 backdrop-blur-sm">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl overflow-hidden transform transition-all scale-95"
                id="userModalContent">
                <div class="flex justify-between items-center px-6 py-4 bg-blue-700 text-white">
                    <h2 id="modalTitle" class="text-lg font-semibold">Add User</h2>
                    <button type="button" onclick="closeUserModal()"><i class="fas fa-times text-xl"></i></button>
                </div>

                <div id="stepperContainer" class="flex justify-between items-center px-8 py-4">
                    <div class="flex-1 text-center">
                        <div class="step-circle active" id="step1Circle">1</div>
                        <p class="text-xs mt-2">Personal Info</p>
                    </div>
                    <div class="flex-1 text-center">
                        <div class="step-circle" id="step2Circle">2</div>
                        <p class="text-xs mt-2">Account Info</p>
                    </div>
                    <div class="flex-1 text-center">
                        <div class="step-circle" id="step3Circle">3</div>
                        <p class="text-xs mt-2">Confirmation</p>
                    </div>
                </div>
                <form id="userForm" class="px-6 pb-6" method="POST" enctype="multipart/form-data" action="">
                    <div id="errorContainer"
                        class="bg-red-100 border border-red-300 text-red-700 px-4 py-2 rounded-md mb-4 hidden">
                    </div>

                    <input type="hidden" name="id" id="userId">

                    <div class="step" id="step1">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="full_name" id="full_name" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 mb-4 focus:ring-blue-500 focus:border-blue-500">

                        <div id="addFields1">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Student Number</label>
                            <input type="text" name="student_number" id="student_number" required
                                class="w-full border border-gray-300 rounded-md px-3 py-2 mb-4 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g., 10001">
                            <label class="block mb-2 text-sm font-medium text-gray-700">Phone Number</label>
                            <input type="text" name="phone_number" id="phone_number" required
                                class="w-full border border-gray-300 rounded-md px-3 py-2 mb-4 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g., 09171234567">
                        </div>
                    </div>

                    <div class="step hidden" id="step2">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="email" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 mb-4 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="user@example.com">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" id="password" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 mb-4 focus:ring-blue-500 focus:border-blue-500">
                        <label class="block mb-2 text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 mb-4 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="step hidden text-center" id="step3">
                        <i class="fas fa-user-check text-blue-600 text-5xl mb-3"></i>
                        <h3 class="text-lg font-semibold mb-2" id="confirmTitle">Confirm Details</h3>
                        <p class="text-sm text-gray-500 mb-4" id="confirmSubtitle">Review user details before saving.</p>
                        <div class="text-left text-sm space-y-2 p-4 bg-gray-50 rounded-lg border">
                            <p><strong>Name:</strong> <span id="confirmName"></span></p>
                            <div id="addConfirmFields">
                                <p><strong>Email:</strong> <span id="confirmEmail"></span></p>
                                <p><strong>Student #:</strong> <span id="confirmStudent"></span></p>
                                <p><strong>Phone:</strong> <span id="confirmPhone"></span></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between mt-6">
                        <button type="button" id="prevBtn"
                            class="hidden bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm transition">Back</button>
                        <button type="button" id="nextBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm transition ml-auto">Next</button>
                        <button type="submit" id="saveBtn"
                            class="hidden bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm transition ml-auto">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        const form = document.getElementById('userForm');
        const errorContainer = document.getElementById('errorContainer');
        const stepperContainer = document.getElementById('stepperContainer');
        const addFields1 = document.getElementById('addFields1');
        const addConfirmFields = document.getElementById('addConfirmFields');
        const full_name_input = document.getElementById('full_name');
        const student_number_input = document.getElementById('student_number');
        const phone_number_input = document.getElementById('phone_number');
        const email_input = document.getElementById('email');
        const password_input = document.getElementById('password');
        const confirm_password_input = document.getElementById('confirm_password');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const saveBtn = document.getElementById('saveBtn');


        let currentStep = 1;
        let isEditMode = false;

        // === Modal Controls ===
        function setupModalForAdd() {
            document.getElementById('modalTitle').innerText = 'Add User';
            isEditMode = false;

            // Show all multi-step elements
            stepperContainer.classList.remove('hidden');
            addFields1.classList.remove('hidden');
            addConfirmFields.classList.remove('hidden');

            form.reset();
            document.getElementById('userId').value = '';

            // Ensure fields are required in ADD mode
            student_number_input.required = true;
            phone_number_input.required = true;
            email_input.required = true;
            password_input.required = true;
            confirm_password_input.required = true;

            resetSteps();
        }

        function setupModalForEdit(data) {
            document.getElementById('modalTitle').innerText = 'Edit User: ' + data.full_name;
            isEditMode = true;

            // Hide all multi-step elements
            stepperContainer.classList.add('hidden');
            addFields1.classList.add('hidden');
            addConfirmFields.classList.add('hidden');
            prevBtn.classList.add('hidden');
            nextBtn.classList.add('hidden');
            saveBtn.classList.remove('hidden');

            // Set form to Step 1 view
            document.getElementById('step1').classList.remove('hidden');
            document.getElementById('step2').classList.add('hidden');
            document.getElementById('step3').classList.add('hidden');

            // Pre-fill only the editable field (Full Name) and hidden ID
            document.getElementById('userId').value = data.id;
            full_name_input.value = data.full_name;

            // Make other fields NOT required in EDIT mode
            student_number_input.required = false;
            phone_number_input.required = false;
            email_input.required = false;
            password_input.required = false;
            confirm_password_input.required = false;

            errorContainer.classList.add('hidden');
        }

        function openAddModal() {
            setupModalForAdd();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function openEditModal(data) {
            setupModalForEdit(data);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeUserModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            errorContainer.classList.add('hidden');
            // Re-initialize for next use (default to Add setup)
            setupModalForAdd();
        }

        function openDeleteModal(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `students.php?action=delete&id=${id}`;
                }
            });
        }

        window.addEventListener('click', e => {
            if (e.target === modal) closeUserModal();
        });


        // === Stepper Logic (Only used for ADD mode) ===
        function resetSteps() {
            currentStep = 1;
            showStep(currentStep);
            errorContainer.classList.add('hidden');
        }

        function showStep(step) {
            const totalSteps = 3;
            // Hide all steps, then show the current one
            for (let i = 1; i <= totalSteps; i++) {
                document.getElementById(`step${i}`).classList.toggle('hidden', i !== step);
                // Activate circles up to and including the current step
                document.getElementById(`step${i}Circle`).classList.toggle('active', i <= step);
            }
            // Toggle visibility for navigation buttons
            prevBtn.classList.toggle('hidden', step === 1);
            nextBtn.classList.toggle('hidden', step === totalSteps);
            saveBtn.classList.toggle('hidden', step !== totalSteps);
        }

        // Helper to check duplicates (simplified for ADD only)
        async function checkDuplicate(field, value) {
            const formData = new FormData();
            formData.append('check_duplicate', '1');
            formData.append('field', field);
            formData.append('value', value);
            // We pass is_edit but PHP logic already gates on it being empty for ADD mode

            try {
                const res = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                return data.exists;
            } catch (e) {
                console.error("AJAX error during duplicate check:", e);
                return true; // Fail safe
            }
        }

        // Simple Polyfill for filter_var with FILTER_VALIDATE_EMAIL (since it's not native in JS)
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Next Step Validation & Handler (Only used for ADD mode)
        nextBtn.addEventListener('click', async () => {
            if (isEditMode) return;

            let errors = [];
            const fullName = full_name_input.value.trim();
            const studentNumber = student_number_input.value.trim();
            const phoneNumber = phone_number_input.value.trim();
            const email = email_input.value.trim();
            const password = password_input.value.trim();
            const confirmPassword = confirm_password_input.value.trim();

            errorContainer.classList.add('hidden');

            if (currentStep === 1) {
                // Step 1 Validation: Personal Info
                if (!fullName || !studentNumber || !phoneNumber) {
                    errors.push("Please fill out all fields in this step.");
                } else {
                    // Student Number Format Check (numeric and long enough)
                    const rawStudentDigits = studentNumber.startsWith('s') ? studentNumber.substring(1) : studentNumber;
                    if (!/^\d{5,}$/.test(rawStudentDigits)) {
                        errors.push("Student number must be numeric (excluding 's' prefix) and reasonably long (e.g., at least 5 digits).");
                    }

                    // Phone Number Format Check
                    if (!/^09\d{9}$/.test(phoneNumber)) {
                        errors.push("Phone number must start with 09 and be 11 digits long (e.g., 09171234567).");
                    }

                    // Duplicate Check for Student Number
                    const existsStudent = await checkDuplicate('student_number', studentNumber);
                    if (existsStudent) errors.push('Student Number already exists.');
                }
            } else if (currentStep === 2) {
                // Step 2 Validation: Account Info 
                if (!email || !password || !confirmPassword) {
                    errors.push("Please fill out all fields in this step.");
                } else {
                    if (!isValidEmail(email)) {
                        errors.push('Invalid email format. Must contain "@" symbol.');
                    } else {
                        // Duplicate Check for Email
                        const existsEmail = await checkDuplicate('email', email);
                        if (existsEmail) errors.push('Email already exists.');
                    }

                    if (password !== confirmPassword) {
                        errors.push("Password and Confirm Password do not match.");
                    } else if (password.length < 8 ||
                        !/[A-Za-z]/.test(password) ||
                        !/\d/.test(password) ||
                        !/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                        errors.push('Password must be at least 8 characters long with letters, numbers, and special characters.');
                    }
                }
            }

            // Show Errors or Move Next
            if (errors.length > 0) {
                errorContainer.innerHTML = errors.join('<br>');
                errorContainer.classList.remove('hidden');
                return;
            } else {
                errorContainer.classList.add('hidden');
            }

            // Move to next step
            if (currentStep < 3) {
                if (currentStep === 2) {
                    // Pre-fill confirmation step details
                    document.getElementById('confirmName').innerText = fullName;
                    document.getElementById('confirmEmail').innerText = email;
                    document.getElementById('confirmStudent').innerText = studentNumber;
                    document.getElementById('confirmPhone').innerText = phoneNumber;
                }
                currentStep++;
                showStep(currentStep);
            }
        });

        // Previous Button
        prevBtn.addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
                errorContainer.classList.add('hidden');
            }
        });

        // Form Submission Handler (Triggers on 'Save' button click, either in Step 3 or Edit mode)
        form.addEventListener('submit', function(e) {
            // For ADD mode, we assume the user is at step 3 and validation has passed.
            // For EDIT mode, we perform minimal client-side check here.

            if (isEditMode) {
                const fullName = full_name_input.value.trim();
                if (!fullName) {
                    e.preventDefault();
                    errorContainer.innerHTML = "Full Name is required for update.";
                    errorContainer.classList.remove('hidden');
                    return;
                }
                // All other fields are not required and won't be submitted/updated.
            }
            // For ADD mode (step 3), server-side will perform final validation.
            // Let the form submit normally.
        });


        // === Table Filtering and Searching ===

        document.getElementById('searchInput').addEventListener('keyup', filterTable);
        document.getElementById('statusFilter').addEventListener('change', filterTable);

        function filterTable() {
            const searchText = document.getElementById('searchInput').value.toLowerCase().trim();
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#studentsTable tbody tr');
            let visibleRowCount = 0;

            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const email = row.getAttribute('data-email');
                const status = row.getAttribute('data-status');

                const matchesSearch = name.includes(searchText) || email.includes(searchText);
                const matchesStatus = statusFilter === 'all' || status === statusFilter;

                if (matchesSearch && matchesStatus) {
                    row.classList.remove('hidden');
                    visibleRowCount++;
                } else {
                    row.classList.add('hidden');
                }
            });

            // Toggle visibility of 'No results' message
            document.getElementById('noResultsRow').classList.toggle('hidden', visibleRowCount > 0);

            // Show/hide the main table if there are no students initially or after filtering
            document.getElementById('studentsTable').classList.toggle('hidden', visibleRowCount === 0 && document.getElementById('noInitialStudents') === null);
            if (document.getElementById('noInitialStudents')) {
                document.getElementById('noInitialStudents').classList.add('hidden');
            }
        }


        // === SweetAlert2 Message Display ===
        <?php if ($flash_message): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                html: '<?= $flash_message ?>',
                confirmButtonColor: '#3b82f6'
            });
        <?php endif; ?>

        <?php if ($error_message): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                html: '<?= $error_message ?>',
                confirmButtonColor: '#ef4444'
            });

            // Re-open modal and re-populate if there was an error during POST (ADD mode)
            <?php if (!empty($form_data) && empty($form_data['id'])): ?>
                openAddModal();
                // Repopulate fields from session data
                full_name_input.value = '<?= htmlspecialchars($form_data['full_name'] ?? '') ?>';
                student_number_input.value = '<?= htmlspecialchars($form_data['student_number'] ?? '') ?>';
                phone_number_input.value = '<?= htmlspecialchars($form_data['phone_number'] ?? '') ?>';
                email_input.value = '<?= htmlspecialchars($form_data['email'] ?? '') ?>';
                // Note: Password fields are intentionally not repopulated for security

                // Determine which step to show (A simple way is to check if step 2 fields were filled)
                // If email was filled, show step 2, otherwise step 1.
                if (email_input.value) {
                    currentStep = 2;
                } else {
                    currentStep = 1;
                }
                showStep(currentStep);
                errorContainer.innerHTML = '<?= $error_message ?>';
                errorContainer.classList.remove('hidden');

            <?php endif; ?>

            // Re-open modal if there was an error during POST (EDIT mode)
            <?php if (!empty($form_data) && !empty($form_data['id'])): ?>
                openEditModal({
                    id: '<?= htmlspecialchars($form_data['id']) ?>',
                    full_name: '<?= htmlspecialchars($form_name ?? '') ?>'
                });
                errorContainer.innerHTML = '<?= $error_message ?>';
                errorContainer.classList.remove('hidden');
            <?php endif; ?>
        <?php endif; ?>
    </script>
</body>

</html>