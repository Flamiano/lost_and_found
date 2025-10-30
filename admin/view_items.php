<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];


$user_report_dir_fs = "../user/inserted_report/";
$admin_report_dir_fs = "inserted_report/"; // Relative path from 'admin/'
$upload_dir_admin = __DIR__ . "/inserted_report/"; // Absolute path for admin file operations

// Ensure the Admin upload directory exists
if (!is_dir($upload_dir_admin)) {
    mkdir($upload_dir_admin, 0777, true);
}


// WEB URL BASE PATH (Used in HTML <img> tags)
$user_report_dir_web_base = "../user/inserted_report/";
$admin_report_dir_web_base = "./inserted_report/";

// MASKING FUNCTION (Kept for student ID display)
function mask_student_number($student_number)
{
    // Implementation is unchanged and correct
    $digits_only = ltrim($student_number, 's');
    $length = strlen($digits_only);

    if ($length <= 3) {
        return $student_number;
    }

    $first_char = substr($digits_only, 0, 1);
    $last_two_chars = substr($digits_only, -2);
    $stars_count = max(0, $length - 3);
    $masked_middle = str_repeat('*', $stars_count);

    return $first_char . $masked_middle . $last_two_chars;
}

// Define categories for the Filter and Form
$categories = [
    'ID Cards',
    'Gadgets & Electronics',
    'Money & Wallets',
    'Keys & Keychains',
    'Clothing & Accessories',
    'Bags & Containers',
    'Books & Documents',
    'School Supplies',
    'Sports Equipment',
    'Personal Items',
    'Others'
];

// Message variables for New Admin Report Form
$new_report_message = '';
$new_report_message_class = '';
$form_data = [
    'category' => '',
    'item_name' => '',
    'description' => '',
    'location' => '',
    'date_reported' => date('Y-m-d')
];



// Check for AJAX request (used for Delete, Status Update, and Claim)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid action.'];
    $action = $_POST['action'] ?? '';

    // ID validation is done below, as 'claim_item' uses 'item_report_id'
    $id = filter_var($_POST['id'] ?? $_POST['item_report_id'] ?? 0, FILTER_VALIDATE_INT);
    $table_type = $_POST['table_type'] ?? $_POST['item_table_type'] ?? 'user'; // 'user' or 'admin'

    // Determine the base table and image path for the ID provided (if needed)
    if ($table_type === 'admin') {
        $table = 'admin_reports';
        $image_base_dir = __DIR__ . "/inserted_report/"; // Admin file system path
    } else {
        $table = 'reports';
        $image_base_dir = __DIR__ . "/../user/inserted_report/"; // User file system path
    }

    // Allow ID 0 for the add_report action (if you add that logic later)
    if ($id <= 0 && $action !== 'add_report') {
        $response['message'] = 'Invalid report ID.';
        echo json_encode($response);
        exit;
    }

    try {
        if ($action === 'update_status') {
            // Your existing Update Status Action logic...
            $new_status = $_POST['new_status'] ?? '';
            $allowed_statuses = ['Pending', 'Approved', 'Found', 'Claimed'];

            if ($table === 'admin_reports') {
                $allowed_statuses = ['Approved', 'Found', 'Claimed'];
            }

            if (empty($new_status) || !in_array($new_status, $allowed_statuses)) {
                $response['message'] = "Invalid status: '{$new_status}' for table '{$table}'.";
            } else {
                // Fetch current status before update
                $stmt_fetch = $conn->prepare("SELECT status FROM {$table} WHERE id = ?");
                $stmt_fetch->execute([$id]);
                $old_status = $stmt_fetch->fetchColumn();

                $stmt = $conn->prepare("UPDATE {$table} SET status = ? WHERE id = ?");

                if ($stmt->execute([$new_status, $id])) {
                    $response = [
                        'success' => true,
                        'message' => "Status updated to '{$new_status}' successfully!",
                        'new_status' => $new_status,
                        'old_status' => $old_status, // Send old status back to JS
                        'id' => $id
                    ];
                } else {
                    $response['message'] = "Failed to update status in database.";
                }
            }
        } elseif ($action === 'log_claim') { // Action name changed to 'log_claim' in the final JS for clarity

            // Sanitize and validate all claim details
            $item_report_id = $id; // ID is already validated as the item_report_id
            $old_status = $_POST['old_status'] ?? '';

            // Claimant data from the form
            $claimant_name = trim($_POST['claimant_name'] ?? '');
            $claimant_contact = trim($_POST['claimant_contact'] ?? '');
            $relationship_to_item = trim($_POST['relationship_to_item'] ?? '');
            // NOTE: We use $admin_id from the session, not the form.
            $verification_method = trim($_POST['verification_method'] ?? '');
            $notes = trim($_POST['notes'] ?? NULL);
            $new_status = 'Claimed';

            // Only 'admin_reports' items (found items) can be claimed and logged
            // The item must be from the 'admin_reports' table AND must be 'Found'.
            if ($table_type !== 'admin') {
                $response['message'] = "Only Found items (Admin Reports) can be logged as claimed.";
                echo json_encode($response);
                exit;
            }
            $table = 'admin_reports'; // Ensure we are working with the correct table

            if (empty($claimant_name) || empty($claimant_contact) || empty($relationship_to_item) || empty($verification_method)) {
                $response['message'] = 'Missing required claimant fields.';
                echo json_encode($response);
                exit;
            }

            // Start a transaction
            $conn->beginTransaction();

            try {
                // 1. Insert the claim into the item_claims table
                $stmt_claim = $conn->prepare("
                    INSERT INTO item_claims 
                    (item_report_id, claimant_name, claimant_contact, relationship_to_item, admin_id, verification_method, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                if (!$stmt_claim->execute([
                    $item_report_id,
                    $claimant_name,
                    $claimant_contact,
                    $relationship_to_item,
                    $admin_id, // Use the admin ID from the session, which is secure
                    $verification_method,
                    $notes
                ])) {
                    throw new Exception("Claim log insertion failed.");
                }

                // Update the status of the item in admin_reports to 'Claimed'
                $stmt_status = $conn->prepare("UPDATE {$table} SET status = ? WHERE id = ?");

                if (!$stmt_status->execute([$new_status, $item_report_id])) {
                    throw new Exception("Report status update failed.");
                }

                $conn->commit(); // Success!
                $response = [
                    'success' => true,
                    'message' => "Item successfully marked as '{$new_status}' and claim logged!",
                    'new_status' => $new_status,
                    'old_status' => $old_status,
                    'id' => $item_report_id
                ];
            } catch (Exception $e) {
                $conn->rollBack();
                $response['message'] = 'Claim processing failed. Error: ' . $e->getMessage();
            }
        } elseif ($action === 'delete_report') {
            // Your existing Delete Action logic... (It seems correct for PDO use)

            // Fetch image path and status before deletion
            $stmt_img = $conn->prepare("SELECT image_path, status FROM {$table} WHERE id = ?");
            $stmt_img->execute([$id]);
            $report_data = $stmt_img->fetch(PDO::FETCH_ASSOC);

            if (!$report_data) {
                $response['message'] = "Could not find report to delete.";
            } else {
                // Delete the database record
                $stmt = $conn->prepare("DELETE FROM {$table} WHERE id = ?");
                $stmt->execute([$id]);

                if ($stmt->rowCount()) {
                    $response = [
                        'success' => true,
                        'message' => 'Report deleted successfully!',
                        'id' => $id,
                        'old_status' => $report_data['status'] // Send old status back for stat update
                    ];

                    // Attempt to delete image file
                    if (!empty($report_data['image_path'])) {
                        $file_only_name = basename($report_data['image_path']);
                        $file_to_delete = $image_base_dir . $file_only_name;

                        if (file_exists($file_to_delete)) {
                            if (@unlink($file_to_delete)) {
                                $response['file_deleted'] = true;
                            } else {
                                $response['file_error'] = "File found but failed to delete from disk: " . $file_to_delete;
                            }
                        }
                    }
                } else {
                    $response['message'] = "Database reported no rows deleted.";
                }
            }
        } else {
            $response['message'] = 'Action not recognized.';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database Error: Failed to perform action. ' . $e->getMessage();
    }

    echo json_encode($response);
    exit;
}


// Check for regular POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {

    $category = trim($_POST['category'] ?? '');
    $item_name = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $date_reported = trim($_POST['date_reported'] ?? date('Y-m-d'));

    // Repopulate form data in case of error
    $form_data = [
        'category' => $category,
        'item_name' => $item_name,
        'description' => $description,
        'location' => $location,
        'date_reported' => $date_reported
    ];

    if (empty($category) || empty($item_name) || empty($description) || empty($location)) {
        $new_report_message = 'All fields except image are required.';
        $new_report_message_class = 'bg-red-100 text-red-800 border-red-200';
    } else {
        $image_path = null;

        // Handle File Upload
        if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['item_image']['tmp_name'];
            $file_name = $_FILES['item_image']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($file_ext, $allowed_extensions)) {
                $new_report_message = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
                $new_report_message_class = 'bg-red-100 text-red-800 border-red-200';
            } else {
                // Generate a unique filename: timestamp_originalfilename
                $new_file_name = time() . '_' . basename($file_name);
                $destination = $upload_dir_admin . $new_file_name;

                if (move_uploaded_file($file_tmp_name, $destination)) {
                    // Store only the filename in the database (relative to the admin path)
                    $image_path = $admin_report_dir_fs . $new_file_name;
                } else {
                    $new_report_message = 'Failed to move uploaded file.';
                    $new_report_message_class = 'bg-red-100 text-red-800 border-red-200';
                }
            }
        }

        // Only insert if no error occurred
        if (empty($new_report_message)) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO admin_reports 
                    (admin_id, category, item_name, description, location, date_reported, image_path, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'Approved')
                ");

                if ($stmt->execute([
                    $admin_id,
                    $category,
                    $item_name,
                    $description,
                    $location,
                    $date_reported,
                    $image_path
                ])) {
                    // Success! Use session for SweetAlert display
                    $_SESSION['report_success_title'] = "Success!";
                    $_SESSION['report_success_text'] = "Report '{$item_name}' added successfully!";
                    header("Location: view_items.php");
                    exit();
                } else {
                    $new_report_message = 'Failed to insert report into the database.';
                    $new_report_message_class = 'bg-red-100 text-red-800 border-red-200';
                }
            } catch (PDOException $e) {
                $new_report_message = 'Database Error: ' . $e->getMessage();
                $new_report_message_class = 'bg-red-100 text-red-800 border-red-200';
            }
        }
    }
}


// FETCH ALL REPORTS (User and Admin) 

$stmt_all = $conn->prepare("
    -- USER-SUBMITTED REPORTS
    SELECT 
        r.id, r.category, r.item_name, r.description, r.location, r.date_reported, r.image_path, r.status, r.created_at,
        s.full_name AS reported_by_name, s.student_number,
        'user' AS source_table,  -- IDENTIFIER FOR JS/HTML
        NULL AS admin_name
    FROM reports r
    JOIN students s ON r.student_id = s.id

    UNION ALL

    -- ADMIN-CREATED REPORTS
    SELECT 
        a.id, a.category, a.item_name, a.description, a.location, a.date_reported, a.image_path, a.status, a.created_at,
        'ADMIN' AS reported_by_name, -- Placeholder Name
        NULL AS student_number,      -- No Student ID
        'admin' AS source_table,     -- IDENTIFIER FOR JS/HTML
        ad.full_name AS admin_name
    FROM admin_reports a
    JOIN admins ad ON a.admin_id = ad.id

    ORDER BY created_at DESC
");
$stmt_all->execute();
$reports = $stmt_all->fetchAll(PDO::FETCH_ASSOC);


// Count Stats
$total_reports = count($reports);
$pending_reports = count(array_filter($reports, fn($r) => $r['status'] === 'Pending'));
$approved_reports = count(array_filter($reports, fn($r) => $r['status'] === 'Approved'));
$found_reports = count(array_filter($reports, fn($r) => $r['status'] === 'Found'));
$claimed_reports = count(array_filter($reports, fn($r) => $r['status'] === 'Claimed'));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Lost and Found System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        * {
            font-family: "Poppins", sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>

    <div class="ml-64 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <h3 class="text-3xl font-bold text-blue-800 flex items-center gap-3">
                <i class="fas fa-list-ul text-blue-600"></i> All Reports
            </h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
            <div class="flex items-center gap-4 p-4 border-l-8 border-blue-600 bg-white shadow-lg rounded-xl transition hover:shadow-xl">
                <div class="p-3 bg-blue-100 text-blue-700 rounded-full"><i class="fas fa-list-check text-2xl"></i></div>
                <div>
                    <p class="text-sm text-gray-500">Total Reports</p>
                    <p class="text-xl font-bold text-gray-800" id="stat_total"><?= $total_reports ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-4 border-l-8 border-yellow-600 bg-white shadow-lg rounded-xl transition hover:shadow-xl" data-status-key="Pending">
                <div class="p-3 bg-yellow-100 text-yellow-700 rounded-full"><i class="fas fa-hourglass-half text-2xl"></i></div>
                <div>
                    <p class="text-sm text-gray-500">Pending</p>
                    <p class="text-xl font-bold text-gray-800" id="stat_Pending"><?= $pending_reports ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-4 border-l-8 border-cyan-600 bg-white shadow-lg rounded-xl transition hover:shadow-xl" data-status-key="Approved">
                <div class="p-3 bg-cyan-100 text-cyan-700 rounded-full"><i class="fas fa-certificate text-2xl"></i></div>
                <div>
                    <p class="text-sm text-gray-500">Approved</p>
                    <p class="text-xl font-bold text-gray-800" id="stat_Approved"><?= $approved_reports ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-4 border-l-8 border-green-600 bg-white shadow-lg rounded-xl transition hover:shadow-xl" data-status-key="Found">
                <div class="p-3 bg-green-100 text-green-700 rounded-full"><i class="fas fa-check-double text-2xl"></i></div>
                <div>
                    <p class="text-sm text-gray-500">Found</p>
                    <p class="text-xl font-bold text-gray-800" id="stat_Found"><?= $found_reports ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4 p-4 border-l-8 border-red-600 bg-white shadow-lg rounded-xl transition hover:shadow-xl" data-status-key="Claimed">
                <div class="p-3 bg-red-100 text-red-700 rounded-full"><i class="fas fa-handshake text-2xl"></i></div>
                <div>
                    <p class="text-sm text-gray-500">Claimed</p>
                    <p class="text-xl font-bold text-gray-800" id="stat_Claimed"><?= $claimed_reports ?></p>
                </div>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 mb-5">

            <div class="flex flex-col sm:flex-row gap-2 flex-grow">
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" id="searchInput" placeholder="Search item or location..."
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-sm shadow-sm"
                        style="height: 42px;">
                </div>

                <div class="relative w-full sm:w-40">
                    <i class="fas fa-filter absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <select id="statusFilter"
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-sm shadow-sm"
                        style="height: 42px;">
                        <option value="all">All Statuses</option>
                        <option value="Pending">Pending (<?= $pending_reports ?>)</option>
                        <option value="Approved">Approved (<?= $approved_reports ?>)</option>
                        <option value="Found">Found (<?= $found_reports ?>)</option>
                        <option value="Claimed">Claimed (<?= $claimed_reports ?>)</option>
                    </select>
                </div>

                <div class="relative w-full sm:w-40">
                    <i class="fas fa-tags absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <select id="categoryFilter"
                        class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-sm shadow-sm"
                        style="height: 42px;">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button onclick="openAddReportModal()"
                class="w-full lg:w-auto bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-lg shadow-md transition duration-200 flex-shrink-0"
                style="height: 42px;">
                <i class="fas fa-plus-circle mr-2"></i> Add Found Item
            </button>
        </div>


        <div class="overflow-x-auto bg-white border border-gray-200 rounded-2xl shadow-md">
            <table class="min-w-full divide-y divide-gray-200 text-sm" id="reportsTable">
                <thead class="bg-blue-800 text-white sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left">Item Image</th>
                        <th class="px-4 py-3 text-left">Item Name</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-left">Location</th>
                        <th class="px-4 py-3 text-left">Source / Reported By</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody id="reportsTableBody" class="divide-y divide-gray-100">
                    <?php foreach ($reports as $report): ?>
                        <?php
                        // --- Image Path Logic ---
                        $image_base_name_full_path = $report['image_path'];
                        $full_image_path_web = null;
                        $file_system_path = null;
                        $file_exists = false;

                        // Determine which directory to use
                        if ($report['source_table'] === 'admin') {
                            $current_dir_fs_base = __DIR__ . "/inserted_report/";
                            $current_dir_web = $admin_report_dir_web_base;
                        } else {
                            // 'user' reports
                            $current_dir_fs_base = __DIR__ . "/../user/inserted_report/";
                            $current_dir_web = $user_report_dir_web_base;
                        }

                        if (!empty($image_base_name_full_path)) {
                            // 1. Extract only the filename (e.g., '176055...png')
                            $file_only_name = basename($image_base_name_full_path);

                            // 2. Construct the full file system path
                            $file_system_path = $current_dir_fs_base . $file_only_name;

                            if (file_exists($file_system_path)) {
                                $file_exists = true;
                                $full_image_path_web = $current_dir_web . $file_only_name;
                            }
                        }

                        // --- Status Class Logic (Unchanged) ---
                        $status_class = [
                            'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'Approved' => 'bg-cyan-100 text-cyan-800 border-cyan-200',
                            'Found' => 'bg-green-100 text-green-800 border-green-200',
                            'Claimed' => 'bg-red-100 text-red-800 border-red-200'
                        ][$report['status']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                        ?>
                        <tr id="report-row-<?= $report['id'] ?>" class="report-row border-b hover:bg-gray-50 transition-colors duration-200"
                            data-id="<?= $report['id'] ?>"
                            data-name="<?= htmlspecialchars(strtolower($report['item_name'])) ?>"
                            data-location="<?= htmlspecialchars(strtolower($report['location'])) ?>"
                            data-status="<?= htmlspecialchars($report['status']) ?>"
                            data-category="<?= htmlspecialchars($report['category']) ?>"
                            data-table-source="<?= $report['source_table'] ?>">
                            <td class="px-4 py-2">
                                <?php if ($file_exists && $full_image_path_web): ?>
                                    <img src="<?= htmlspecialchars($full_image_path_web) ?>"
                                        class="w-12 h-12 object-cover rounded-md border cursor-pointer" alt="Item Image"
                                        onclick="showFullImage('<?= htmlspecialchars($full_image_path_web) ?>')">
                                <?php else: ?>
                                    <i class="fas fa-box text-3xl text-gray-400" title="No Image"></i>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 font-semibold"><?= htmlspecialchars($report['item_name']) ?></td>
                            <td class="px-4 py-2 text-gray-600"><?= htmlspecialchars($report['category']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($report['location']) ?></td>
                            <td class="px-4 py-2">
                                <?php if ($report['source_table'] === 'user'): ?>
                                    <span class="font-medium">
                                        <?= htmlspecialchars($report['reported_by_name']) ?>
                                    </span>
                                    <span class="text-xs text-gray-500 block">
                                        (Student: <?= mask_student_number($report['student_number'] ?? '') ?>)
                                    </span>
                                <?php else: // 'admin' reports 
                                ?>
                                    <span class="font-medium text-blue-600">
                                        <?= htmlspecialchars($report['admin_name']) ?>
                                    </span>
                                    <span class="text-xs text-blue-500 block">
                                        (Created by Admin)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 text-gray-500"><?= date('M d, Y', strtotime($report['date_reported'])) ?></td>
                            <td class="px-4 py-2 status-cell">
                                <span class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-full border <?= $status_class ?>">
                                    <?= htmlspecialchars($report['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-2 flex gap-2">
                                <button onclick='openEditStatusModal(<?= json_encode(['id' => $report['id'], 'status' => $report['status'], 'source_table' => $report['source_table']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                    class="text-blue-600 hover:text-blue-800 p-1 rounded hover:bg-blue-50 transition" title="Change Status / Edit"><i class="fas fa-pen"></i></button>
                                <button onclick="confirmDelete(<?= $report['id'] ?>, '<?= $report['source_table'] ?>')"
                                    class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50 transition" title="Delete Report"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($reports)): ?>
                <div id="noInitialReports" class="text-center py-10 text-gray-500 italic">
                    <i class="fas fa-folder-open text-3xl mb-2"></i><br>No reports have been filed yet.
                </div>
            <?php endif; ?>
            <div id="noResultsRow" class="hidden text-center py-10 text-gray-500 italic">
                <i class="fas fa-search-minus text-3xl mb-2"></i><br>No reports match your current filter.
            </div>
        </div>
    </div>

    <div id="editStatusModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50 backdrop-blur-sm">
        <div class="bg-white w-full max-w-sm rounded-2xl shadow-xl transform transition-all scale-100">
            <div class="flex justify-between items-center px-6 py-4 bg-blue-700 text-white rounded-t-2xl">
                <h2 class="text-lg font-semibold"><i class="fas fa-pen-to-square mr-2"></i> Update Report Status</h2>
                <button type="button" onclick="closeEditStatusModal()"><i class="fas fa-times text-xl"></i></button>
            </div>

            <form id="editStatusForm" class="px-6 py-6 space-y-4" onsubmit="handleStatusUpdate(event)">
                <input type="hidden" name="report_id" id="edit_report_id">
                <input type="hidden" name="table_type" id="edit_table_type">

                <div>
                    <label for="new_status" class="block mb-1 text-sm font-medium text-gray-700">Select New Status:</label>
                    <select name="new_status" id="new_status" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    </select>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md text-sm transition shadow-lg">
                        <i class="fas fa-save"></i> Save Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 justify-center items-center p-4" onclick="closeImageModal()">
        <div class="relative max-w-4xl max-h-full" onclick="event.stopPropagation()">
            <img id="fullImage" src="" alt="Full Item Image" class="max-w-full max-h-[80vh] rounded-lg shadow-2xl">
            <button class="absolute top-4 right-4 text-white text-3xl p-2 rounded-full hover:bg-black hover:bg-opacity-50 transition" onclick="closeImageModal()">
                &times;
            </button>
        </div>
    </div>

    <div id="addReportModal" class="fixed inset-0 bg-black bg-opacity-50 hidden justify-center items-center z-50 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl transform transition-all scale-100">
            <div class="flex justify-between items-center px-6 py-4 bg-blue-700 text-white rounded-t-2xl">
                <h2 class="text-lg font-semibold"><i class="fas fa-file-circle-plus mr-2"></i> Add New Found Item (Admin)</h2>
                <button type="button" onclick="closeAddReportModal()"><i class="fas fa-times text-xl"></i></button>
            </div>

            <form method="POST" action="view_items.php" enctype="multipart/form-data" class="p-6 space-y-4">

                <div>
                    <label for="modal_item_name" class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
                    <input type="text" id="modal_item_name" name="item_name" required
                        class="w-full border-gray-300 rounded-none shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        value="<?= htmlspecialchars($form_data['item_name']) ?>" placeholder="e.g., Apple Airpods">
                </div>

                <div>
                    <label for="modal_category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select id="modal_category" name="category" required
                        class="w-full border-gray-300 rounded-none shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Select a Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"
                                <?= ($form_data['category'] === $cat) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="modal_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="modal_description" name="description" rows="3" required
                        class="w-full border-gray-300 rounded-none shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        placeholder="Provide details about the item's appearance."><?= htmlspecialchars($form_data['description']) ?></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="modal_location" class="block text-sm font-medium text-gray-700 mb-1">Found Location</label>
                        <input type="text" id="modal_location" name="location" required
                            class="w-full border-gray-300 rounded-none shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                            value="<?= htmlspecialchars($form_data['location']) ?>" placeholder="e.g., Library">
                    </div>
                    <div>
                        <label for="modal_date_reported" class="block text-sm font-medium text-gray-700 mb-1">Date Found</label>
                        <input type="date" id="modal_date_reported" name="date_reported"
                            class="w-full border-gray-300 rounded-none shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                            value="<?= htmlspecialchars($form_data['date_reported']) ?>">
                    </div>
                </div>

                <div>
                    <label for="modal_item_image" class="block text-sm font-medium text-gray-700 mb-1">Item Image (Optional)</label>
                    <input type="file" id="modal_item_image" name="item_image" accept="image/*"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-6 rounded-lg transition duration-200 shadow-md">
                        <i class="fas fa-plus-circle mr-2"></i> Add Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="claimItemModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900 bg-opacity-50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 transform transition-all duration-300 scale-100">
            <div class="flex justify-between items-center mb-4 border-b pb-3">
                <h3 class="text-xl font-bold text-red-600 flex items-center gap-2"><i class="fas fa-handshake"></i> Log Item Claim</h3>
                <button type="button" onclick="closeClaimItemModal()" class="text-gray-500 hover:text-gray-700 p-1 rounded-full hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="claimItemForm" method="POST">
                <input type="hidden" name="action" value="log_claim">
                <input type="hidden" name="item_report_id" id="claim_item_report_id">
                <input type="hidden" name="item_table_type" id="claim_item_table_type">
                <input type="hidden" name="old_status" id="claim_old_status">

                <p class="mb-4 text-sm text-gray-700 border-l-4 border-red-400 pl-3 py-2 bg-red-50 rounded-md">
                    This action will mark the item as **Claimed** and log the hand-over details.
                </p>

                <div class="space-y-4">
                    <div>
                        <label for="claimant_name" class="block text-sm font-medium text-gray-700">Claimant Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="claimant_name" id="claimant_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm p-2.5 border">
                    </div>

                    <div>
                        <label for="claimant_contact" class="block text-sm font-medium text-gray-700">Contact/Student ID <span class="text-red-500">*</span></label>
                        <input type="text" name="claimant_contact" id="claimant_contact" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm p-2.5 border" placeholder="e.g., 09xxxxxxxxx or s2022xxxx">
                    </div>

                    <div>
                        <label for="relationship_to_item" class="block text-sm font-medium text-gray-700">Relationship to Item <span class="text-red-500">*</span></label>
                        <select name="relationship_to_item" id="relationship_to_item" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm p-2.5 border">
                            <option value="">Select...</option>
                            <option value="Owner">Owner</option>
                            <option value="Representative">Representative</option>
                            <option value="Finder">Finder (Receiving item)</option>
                        </select>
                    </div>

                    <div>
                        <label for="verification_method" class="block text-sm font-medium text-gray-700">Verification Method <span class="text-red-500">*</span></label>
                        <textarea name="verification_method" id="verification_method" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm p-2.5 border" placeholder="e.g., Confirmed item description details, checked ID photo, provided receipt."></textarea>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                        <textarea name="notes" id="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm p-2.5 border" placeholder="e.g., Minor crack was noted, representative showed authorization letter."></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeClaimItemModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg shadow-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition">
                        <i class="fas fa-check-circle mr-1"></i> Log Claim & Mark as Claimed
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-auto p-6">
            <h4 class="text-xl font-bold mb-4 border-b pb-2">Update Report Status</h4>
            <div class="mb-4">
                <p class="text-sm font-semibold mb-1">Report ID: <span id="modalReportId" class="font-normal text-blue-600"></span></p>
                <p class="text-sm font-semibold">Current Status: <span id="modalCurrentStatus" class="font-normal text-gray-600"></span></p>
            </div>
            <form id="statusUpdateForm">
                <input type="hidden" id="modalHiddenId" name="id">
                <input type="hidden" id="modalHiddenTableType" name="table_type">
                <input type="hidden" name="action" value="update_status">

                <label for="new_status" class="block text-sm font-medium text-gray-700 mb-2">Select New Status</label>
                <select id="new_status" name="new_status" required
                    class="block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-blue-500 focus:border-blue-500">
                </select>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="closeStatusModal()"
                        class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="statusUpdateBtn"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md shadow-md transition duration-200">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
      
        function getStatusClass(status) {
            switch (status) {
                case 'Pending':
                    return 'bg-yellow-100 text-yellow-800 border-yellow-200';
                case 'Approved':
                    return 'bg-cyan-100 text-cyan-800 border-cyan-200';
                case 'Found':
                    return 'bg-green-100 text-green-800 border-green-200';
                case 'Claimed':
                    return 'bg-red-100 text-red-800 border-red-200';
                default:
                    return 'bg-gray-100 text-gray-800 border-gray-200';
            }
        }

        
        function updateStats(oldStatus, newStatus, isDelete = false) {
            const totalStat = document.getElementById('stat_total');

            // Decrement old status count
            const oldStatElement = document.getElementById(`stat_${oldStatus}`);
            if (oldStatElement) {
                oldStatElement.textContent = Math.max(0, parseInt(oldStatElement.textContent) - 1);
            }

            if (isDelete) {
                // Decrement total count if deleting
                if (totalStat) {
                    totalStat.textContent = Math.max(0, parseInt(totalStat.textContent) - 1);
                }
            } else {
                // Increment new status count if updating
                const newStatElement = document.getElementById(`stat_${newStatus}`);
                if (newStatElement) {
                    newStatElement.textContent = parseInt(newStatElement.textContent) + 1;
                }
            }

            // Update filter dropdown options with new counts
            const statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.querySelectorAll('option').forEach(option => {
                    const statusKey = option.value;
                    if (statusKey !== 'all') {
                        const count = document.getElementById(`stat_${statusKey}`)?.textContent || '0';
                        option.textContent = `${statusKey} (${count})`;
                    }
                });
            }
        }

       
        function closeClaimItemModal() {
            // Use class list for consistency with other modal functions, but style.display is fine if modal is implemented with it
            const modal = document.getElementById('claimItemModal');
            if (modal) {
                modal.style.display = 'none'; // Assuming the modal uses inline style for display
            }

            const form = document.getElementById('claimItemForm');
            if (form) {
                form.reset();
            }

            // Remove temporary old status value when closing the modal
            document.getElementById('claim_old_status').value = '';
        }

      
        function updateTableRowStatus(id, tableType, newStatus, oldStatus) {
            const row = document.getElementById(`report-row-${id}`);

            if (row) {
                row.setAttribute('data-status', newStatus);
                const statusCell = row.querySelector('.status-cell > span');
                if (statusCell) {
                    statusCell.textContent = newStatus;
                    // Reapply the full class string using the utility function
                    statusCell.className = `inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-full border ${getStatusClass(newStatus)}`;
                }
            }

            // Update statistics
            updateStats(oldStatus, newStatus);
            // Reapply filters to potentially hide/show the row based on new status
            applyFilters();
        }

        /**
         * Handles the full claim submission, saving claimant data and updating status.
         * @param {Event} event - The form submission event.
         */
        async function handleClaimSubmission(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);
            const reportId = formData.get('item_report_id');
            const tableType = formData.get('item_table_type'); // Get table type
            const oldStatus = formData.get('old_status'); // Get old status

            // Client-side validation for required fields
            if (!reportId || !formData.get('claimant_name') || !formData.get('claimant_contact') || !formData.get('relationship_to_item') || !formData.get('verification_method')) {
                Swal.fire('Error', 'Please fill in all required claimant details.', 'error');
                return;
            }

            Swal.fire({
                title: 'Logging Claim...',
                text: 'Please wait while the claim is being processed and the status is updated.',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const response = await fetch('view_items.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest' // Identify as AJAX
                    },
                    body: formData // Send the whole form data, including action=log_claim
                });

                const data = await response.json();

                if (data.success) {
                    // 1. Update the table row in the UI (Status will be 'Claimed')
                    updateTableRowStatus(reportId, tableType, 'Claimed', oldStatus);

                    // 2. Close modal and show success
                    closeClaimItemModal();
                    Swal.fire('Claim Logged!', data.message, 'success');
                } else {
                    Swal.fire('Claim Failed', data.message, 'error');
                }

            } catch (error) {
                Swal.fire('Error', 'An unexpected error occurred during the claim process.', 'error');
                console.error('Claim AJAX error:', error);
            }
        }

        // --- MODAL FUNCTIONS ---

        function showFullImage(src) {
            document.getElementById('fullImage').src = src;
            document.getElementById('imageModal').classList.remove('hidden');
            document.getElementById('imageModal').classList.add('flex');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('flex');
            document.getElementById('imageModal').classList.add('hidden');
        }

        function openAddReportModal() {
            document.getElementById('addReportModal').classList.remove('hidden');
            document.getElementById('addReportModal').classList.add('flex');
        }

        function closeAddReportModal() {
            document.getElementById('addReportModal').classList.remove('flex');
            document.getElementById('addReportModal').classList.add('hidden');
        }


        /**
         * Opens the status edit modal and populates it with report data.
         * @param {object} report - Object containing 'id', 'status', and 'source_table'.
         */
        function openEditStatusModal(report) {
            const modal = document.getElementById('editStatusModal');
            document.getElementById('edit_report_id').value = report.id;
            document.getElementById('edit_table_type').value = report.source_table;
            const statusSelect = document.getElementById('new_status');

            // Admin reports shouldn't go back to Pending
            const availableStatuses = report.source_table === 'admin' ? ['Approved', 'Found', 'Claimed'] : ['Pending', 'Approved', 'Found', 'Claimed'];

            statusSelect.innerHTML = '';

            availableStatuses.forEach(status => {
                const option = document.createElement('option');
                option.value = status;
                option.textContent = status;
                if (status === report.status) {
                    option.selected = true;
                }
                statusSelect.appendChild(option);
            });

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Add event listener to STATUS CHANGE inside the modal
            // This logic handles switching to the 'Claimed' modal
            statusSelect.onchange = function() {
                if (this.value === 'Claimed') {
                    closeEditStatusModal();
                    // Populate and open the CLAIM MODAL
                    document.getElementById('claim_item_report_id').value = report.id;
                    document.getElementById('claim_item_table_type').value = report.source_table;
                    document.getElementById('claim_old_status').value = report.status;

                    const claimModal = document.getElementById('claimItemModal');
                    if (claimModal) {
                        claimModal.style.display = 'flex';
                    }
                }
            };
        }

        function closeEditStatusModal() {
            document.getElementById('editStatusModal').classList.remove('flex');
            document.getElementById('editStatusModal').classList.add('hidden');
            // Remove the temporary change listener to prevent duplicates and stale data
            document.getElementById('new_status').onchange = null;
        }


        // AJAX & SWEETALERT FUNCTIONS

      
        async function handleStatusUpdate(event) {
            event.preventDefault();

            const form = event.target;
            const id = form.report_id.value;
            const newStatus = form.new_status.value;
            const tableType = form.table_type.value;
            const row = document.getElementById(`report-row-${id}`);
            const oldStatus = row ? row.getAttribute('data-status') : null;

            if (newStatus === 'Claimed') {
                // Should not happen if openEditStatusModal works, but remains as a safety
                closeEditStatusModal();
                Swal.fire('Error', 'Please log the claim details by filling out the claim form.', 'warning');
                return;
            }

            if (!row || oldStatus === newStatus) {
                closeEditStatusModal();
                if (oldStatus !== newStatus) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Action Cancelled',
                        text: 'Status is already set to ' + newStatus
                    });
                }
                return;
            }

            try {
                Swal.fire({
                    title: 'Updating...',
                    text: 'Please wait, updating status in the database.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const response = await fetch('view_items.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        action: 'update_status',
                        id: id,
                        new_status: newStatus,
                        table_type: tableType
                    })
                });

                const data = await response.json();
                Swal.close();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    });

                    // Update UI
                    updateTableRowStatus(id, tableType, newStatus, oldStatus);
                    closeEditStatusModal();

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message || 'Failed to update status.'
                    });
                }
            } catch (error) {
                Swal.close();
                console.error('Error updating status:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not connect to the server.'
                });
            }
        }

       
        function confirmDelete(id, tableType) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to delete this report. The image will also be permanently deleted from the server. This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Red for delete
                cancelButtonColor: '#3085d6', // Blue for cancel
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteReport(id, tableType);
                }
            });
        }

      
        async function deleteReport(id, tableType) {
            const row = document.getElementById(`report-row-${id}`);
            const oldStatus = row ? row.getAttribute('data-status') : null;

            try {
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait, deleting report and its image.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const response = await fetch('view_items.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        action: 'delete_report',
                        id: id,
                        table_type: tableType // Pass table type
                    })
                });

                const data = await response.json();
                Swal.close();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    });

                    if (row) {
                        row.remove(); // Remove the row from the table
                    }

                    if (oldStatus) {
                        updateStats(oldStatus, null, true); // Update stats: decrement old status and total
                    }

                    applyFilters(); // Re-check filter state, especially if 'no results' row should appear/disappear

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: data.message || 'Failed to delete report.'
                    });
                }
            } catch (error) {
                Swal.close();
                console.error('Error deleting report:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not connect to the server.'
                });
            }
        }


        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        const reportsTableBody = document.getElementById('reportsTableBody');
        const noResultsRow = document.getElementById('noResultsRow');
        const noInitialReports = document.getElementById('noInitialReports');

        function applyFilters() {
            if (!reportsTableBody || !searchInput || !statusFilter || !categoryFilter) return;

            const rows = reportsTableBody.querySelectorAll('.report-row');
            const searchText = searchInput.value.toLowerCase();
            const selectedStatus = statusFilter.value;
            const selectedCategory = categoryFilter.value;
            let visibleCount = 0;

            rows.forEach(row => {
                const name = row.getAttribute('data-name')?.toLowerCase() || '';
                const location = row.getAttribute('data-location')?.toLowerCase() || '';
                const status = row.getAttribute('data-status') || '';
                const category = row.getAttribute('data-category') || '';

                const searchMatch = name.includes(searchText) || location.includes(searchText);
                const statusMatch = selectedStatus === 'all' || status === selectedStatus;
                const categoryMatch = selectedCategory === 'all' || category === selectedCategory;

                if (searchMatch && statusMatch && categoryMatch) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Hide the initial message if reports exist or filters are applied
            if (noInitialReports) {
                noInitialReports.classList.add('hidden');
            }

            // Show/Hide the 'no results' row
            if (noResultsRow) {
                if (visibleCount === 0) {
                    noResultsRow.classList.remove('hidden');
                } else {
                    noResultsRow.classList.add('hidden');
                }
            }
        }

        // Event Listeners for Filters
        if (searchInput) searchInput.addEventListener('keyup', applyFilters);
        if (statusFilter) statusFilter.addEventListener('change', applyFilters);
        if (categoryFilter) categoryFilter.addEventListener('change', applyFilters);

        // Initial filter application and DOM binding when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            applyFilters();

            // Bind listeners for modal forms (ensure elements exist before binding)
            const claimForm = document.getElementById('claimItemForm');
            if (claimForm) claimForm.addEventListener('submit', handleClaimSubmission);

            const statusForm = document.getElementById('editStatusForm');
            if (statusForm) statusForm.addEventListener('submit', handleStatusUpdate);


            // This PHP block must be run by the server (e.g., inside a .php file)
            <?php if (isset($_SESSION['report_success_title'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: '<?= htmlspecialchars($_SESSION['report_success_title']) ?>',
                    text: '<?= htmlspecialchars($_SESSION['report_success_text']) ?>',
                    showConfirmButton: false,
                    timer: 2000
                });
                <?php
                unset($_SESSION['report_success_title']);
                unset($_SESSION['report_success_text']);
                ?>
            <?php endif; ?>
        });
    </script>
</body>

</html>
