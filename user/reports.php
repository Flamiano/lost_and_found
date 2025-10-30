<?php
session_start();
require '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$web_dir = 'inserted_report/';
$targetDir = __DIR__ . '/' . $web_dir;

// Ensure the upload directory exists (moved up for both logic branches)
if (!is_dir($targetDir)) {
    @mkdir($targetDir, 0777, true);
}


// =====================================================================
// === 1. AJAX ENDPOINT LOGIC (RETURNS JSON) ===
// This block runs when JavaScript requests data via `?data=fetch`
// =====================================================================

if (isset($_GET['data']) && $_GET['data'] === 'fetch') {
    header('Content-Type: application/json');
    $response = [];

    try {
        // Fetch Dashboard Card Data
        $stmt_total = $conn->prepare("SELECT COUNT(*) AS total_reports FROM reports WHERE student_id = :id");
        $stmt_total->execute([':id' => $student_id]);
        $response['total_reports'] = $stmt_total->fetch(PDO::FETCH_ASSOC)['total_reports'] ?? 0;

        $stmt_approved = $conn->prepare("SELECT COUNT(*) AS approved_reports FROM reports WHERE student_id = :id AND status IN ('Found','Claimed')");
        $stmt_approved->execute([':id' => $student_id]);
        $response['approved_reports'] = $stmt_approved->fetch(PDO::FETCH_ASSOC)['approved_reports'] ?? 0;

        // Fetch Recent Report Data
        $stmt_recent = $conn->prepare("SELECT id, category, item_name, description, location, date_reported, image_path, status FROM reports WHERE student_id = :student_id ORDER BY created_at DESC LIMIT 1");
        $stmt_recent->execute([':student_id' => $student_id]);
        $response['recent_report'] = $stmt_recent->fetch(PDO::FETCH_ASSOC);

        // Fetch All Reports Data
        $stmt_all = $conn->prepare("SELECT id, category, item_name, description, location, date_reported, image_path, status FROM reports WHERE student_id = :student_id ORDER BY created_at DESC");
        $stmt_all->execute([':student_id' => $student_id]);
        $response['all_reports'] = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($response);
        exit();
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database Error']);
        exit();
    }
}


// =====================================================================
// === 2. STANDARD PAGE LOGIC (HANDLES POST/DELETE/GENERATES HTML) ===
// =====================================================================


// Handle delete
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    try {
        // Fetch the image path to delete the file
        $stmt_fetch = $conn->prepare("SELECT image_path FROM reports WHERE id = :id AND student_id = :student_id");
        $stmt_fetch->execute([':id' => $delete_id, ':student_id' => $student_id]);
        $report_to_delete = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if ($report_to_delete && $report_to_delete['image_path']) {
            $file_path = __DIR__ . '/' . $report_to_delete['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Then, delete the database record
        $stmt = $conn->prepare("DELETE FROM reports WHERE id = :id AND student_id = :student_id");
        $stmt->execute([':id' => $delete_id, ':student_id' => $student_id]);

        header("Location: reports.php?status=success&message=" . urlencode("Report deleted successfully!"));
        exit();
    } catch (PDOException $e) {
        header("Location: reports.php?status=error&message=" . urlencode("Error deleting report."));
        exit();
    }
}

// Handle form submission (Add Report)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    if (empty($_POST['item_name']) || empty($_POST['category']) || empty($_POST['description']) || empty($_POST['location'])) {
        header("Location: reports.php?status=error&message=" . urlencode("Please fill out all required fields."));
        exit();
    }

    $item_name = trim($_POST['item_name']);
    $category = $_POST['category'];
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $date_reported = $_POST['date_reported'];
    $image_path = null;
    $has_error = false;
    $error_message = "An unknown error occurred.";

    // Handle file upload
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $fileName;

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowedTypes)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $image_path = $web_dir . $fileName;
            } else {
                $error_message = "Error uploading the image. Check directory permissions (777 on 'inserted_report').";
                $has_error = true;
            }
        } else {
            $error_message = "Only JPG, JPEG, PNG, and GIF files are allowed.";
            $has_error = true;
        }
    }

    // Insert into DB
    if (!$has_error) {
        try {
            $stmt = $conn->prepare("INSERT INTO reports (student_id, category, item_name, description, location, date_reported, image_path)
            VALUES (:student_id, :category, :item_name, :description, :location, :date_reported, :image_path)");
            $stmt->execute([
                ':student_id' => $student_id,
                ':category' => $category,
                ':item_name' => $item_name,
                ':description' => $description,
                ':location' => $location,
                ':date_reported' => $date_reported,
                ':image_path' => $image_path
            ]);

            header("Location: reports.php?status=success&message=" . urlencode("Report added successfully! Your item is now under review."));
            exit();
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
            $has_error = true;
        }
    }

    // If an error occurred during submission, redirect with error message
    if ($has_error) {
        if ($image_path && file_exists($targetDir . $fileName)) {
            unlink($targetDir . $fileName);
        }
        header("Location: reports.php?status=error&message=" . urlencode($error_message));
        exit();
    }
}


// --- Initial Student Data Fetch (Only needed once for the top card) ---
$stmt_student = $conn->prepare("SELECT full_name, student_number, email FROM students WHERE id = :id");
$stmt_student->execute([':id' => $student_id]);
$student = $stmt_student->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard & Reports | Lost and Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        * {
            font-family: "Poppins", sans-serif;
        }

        /* Enhancing the background gradient */
        body {
            background-color: #f3f4f6;
            /* bg-gray-100 fallback */
            padding-top: 4.5rem;
        }

        /* Better card and container styling */
        .rounded-3xl {
            border-radius: 1.5rem !important;
        }

        .p-7 {
            padding: 1.75rem !important;
        }

        /* Table stripe and hover for better readability */
        #reportsTableBody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        #reportsTableBody tr:hover {
            background-color: #eff6ff !important;
            /* Light blue hover */
        }

        /* Status badge hover effect */
        .status-badge {
            transition: all 0.2s ease;
        }

        .status-badge:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }
    </style>

</head>

<body class="bg-gradient-to-b from-blue-50 to-gray-100 min-h-screen">
    <?php include 'navbar.php'; // Ensure your navbar uses the correct path here 
    ?>

    <main class="max-w-6xl mx-auto px-4 sm:px-5 lg:px-6 overflow-hidden">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8" data-aos="fade-down">
            <div class="bg-white rounded-3xl shadow-xl p-6 border-l-4 border-blue-600 transform hover:scale-[1.01] transition-all duration-300">
                <i class="fas fa-user-circle text-4xl text-blue-600 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Hello, <?= htmlspecialchars($student['full_name']) ?>!</h3>
                <p class="text-gray-600 text-sm">
                    <strong class="font-medium">Student No:</strong> <?= htmlspecialchars(substr($student['student_number'], 1)) ?><br>
                    <strong class="font-medium">Email:</strong> <?= htmlspecialchars($student['email']) ?>
                </p>
            </div>

            <div class="bg-white rounded-3xl shadow-xl p-6 border-l-4 border-amber-500 text-center transform hover:scale-[1.01] transition-all duration-300">
                <i class="fas fa-clipboard-list text-4xl text-amber-500 mb-3"></i>
                <h3 class="text-xl font-semibold text-gray-800 mb-1">Total Reports</h3>
                <p class="text-gray-800 text-4xl font-extrabold" id="totalReportsCount">0</p>
            </div>

            <div class="bg-white rounded-3xl shadow-xl p-6 border-l-4 border-green-500 text-center transform hover:scale-[1.01] transition-all duration-300">
                <i class="fas fa-check-circle text-4xl text-green-500 mb-3"></i>
                <h3 class="text-xl font-semibold text-gray-800 mb-1">Approved Reports</h3>
                <p class="text-gray-800 text-4xl font-extrabold" id="approvedReportsCount">0</p>
            </div>
        </div>

        <hr class="my-10 border-gray-200">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10 text-sm leading-relaxed">

            <div class="bg-white shadow-2xl rounded-3xl p-7 border-t-4 border-blue-500" data-aos="fade-right">
                <h3 class="text-2xl font-bold text-blue-700 mb-5 flex items-center gap-2">
                    <i class="fas fa-pen-square text-blue-600 text-2xl"></i> Submit a Report
                </h3>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-4" id="reportForm">
                    <div>
                        <label class="block text-gray-700 mb-1 text-sm font-semibold">Item Name <span class="text-red-500">*</span></label>
                        <input type="text" name="item_name" required
                            class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-sm transition-shadow">
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-1 text-sm font-semibold">Category <span class="text-red-500">*</span></label>
                        <select name="category" required
                            class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-sm appearance-none bg-white">
                            <option value="" disabled selected>Select Category</option>
                            <option>ID Cards</option>
                            <option>Gadgets & Electronics</option>
                            <option>Money & Wallets</option>
                            <option>Keys & Keychains</option>
                            <option>Clothing & Accessories</option>
                            <option>Bags & Containers</option>
                            <option>Books & Documents</option>
                            <option>School Supplies</option>
                            <option>Sports Equipment</option>
                            <option>Personal Items</option>
                            <option>Others</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-1 text-sm font-semibold">Detailed Description <span class="text-red-500">*</span></label>
                        <textarea name="description" rows="3" required placeholder="Color, brand, unique marks..."
                            class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-sm transition-shadow"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-1 text-sm font-semibold">Location Found/Lost <span class="text-red-500">*</span></label>
                            <input type="text" name="location" required
                                class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-sm transition-shadow">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-1 text-sm font-semibold">Date Reported</label>
                            <input type="date" name="date_reported" required
                                value="<?= date('Y-m-d'); ?>"
                                readonly
                                class="w-full border border-gray-300 rounded-lg p-2.5 bg-gray-100 text-gray-600 cursor-not-allowed text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-gray-700 mb-1 text-sm font-semibold">Upload Image (Optional but Recommended)</label>
                        <input type="file" name="image" accept="image/*"
                            class="w-full border border-gray-300 rounded-lg p-2.5 file:mr-4 file:py-1.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer text-sm">
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-700 hover:bg-blue-600 text-white font-bold py-3 mt-4 rounded-lg shadow-lg transition-all duration-300 text-base transform hover:scale-[1.01]">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Report
                    </button>
                </form>
            </div>

            <div class="p-0 transition-all duration-300" data-aos="fade-left" id="recentReportContainer">
                <div class="bg-white shadow-xl rounded-3xl p-6 flex flex-col items-center justify-center h-full text-gray-500">
                    <i class="fas fa-spinner fa-spin text-4xl mb-3 text-gray-400"></i>
                    <p class="italic text-base">Loading recent report details...</p>
                </div>
            </div>

        </div>


        <section class="mb-20" data-aos="fade-up">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <h3 class="text-2xl font-bold text-blue-800 flex items-center gap-2">
                    <i class="fas fa-list-alt text-blue-700"></i> All Your Reports
                </h3>

                <div class="flex flex-col sm:flex-row gap-2 items-center w-full md:w-auto">
                    <div class="relative w-full sm:w-60">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input
                            type="text"
                            id="searchInput"
                            placeholder="Search by item or location..."
                            class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm transition-shadow" />
                    </div>

                    <div class="relative w-full sm:w-48">
                        <i class="fas fa-filter absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <select
                            id="statusFilter"
                            class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none text-sm appearance-none bg-white">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Found">Found</option>
                            <option value="Claimed">Claimed</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto bg-white border border-gray-200 rounded-2xl shadow-xl">
                <table class="min-w-full divide-y divide-gray-200 text-sm" id="reportsTable">
                    <thead class="bg-blue-700 text-white shadow-md">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Image</th>
                            <th class="px-5 py-3 text-left font-semibold">Item</th>
                            <th class="px-5 py-3 text-left font-semibold hidden sm:table-cell">Category</th>
                            <th class="px-5 py-3 text-left font-semibold hidden md:table-cell">Location</th>
                            <th class="px-5 py-3 text-left font-semibold hidden lg:table-cell">Date</th>
                            <th class="px-5 py-3 text-left font-semibold">Status</th>
                            <th class="px-5 py-3 text-center font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="reportsTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-10 text-gray-500 italic"><i class="fas fa-spinner fa-spin text-3xl mb-2"></i><br>Fetching reports...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </section>


    </main>

    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
        <div class="bg-white p-6 rounded-xl shadow-2xl text-center w-80 animate-fadeIn">
            <i class="fas fa-exclamation-triangle text-red-600 text-3xl mb-3"></i>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Are you sure?</h3>
            <p class="text-gray-500 text-sm mb-4">This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="delete_id" id="delete_id">
                <div class="flex justify-center gap-3">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400 text-gray-700 font-semibold text-sm transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 rounded-lg hover:bg-red-700 text-white font-semibold text-sm transition">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // --- Modal Functions (Unchanged) ---
        function openDeleteModal(id) {
            document.getElementById("delete_id").value = id;
            document.getElementById("deleteModal").classList.remove("hidden");
        }

        function closeDeleteModal() {
            document.getElementById("deleteModal").classList.add("hidden");
        }

        // Table image enlarge view
        function viewImage(imagePath) {
            Swal.fire({
                imageUrl: imagePath,
                imageAlt: "Report Image",
                showConfirmButton: false,
                background: "#f9fafb",
            });
        }


        // --- Real-time Fetching with AJAX ---
        const totalReportsCount = document.getElementById('totalReportsCount');
        const approvedReportsCount = document.getElementById('approvedReportsCount');
        const recentReportContainer = document.getElementById('recentReportContainer');
        const reportsTableBody = document.getElementById('reportsTableBody');
        const searchInput = document.getElementById("searchInput");
        const statusFilter = document.getElementById("statusFilter");
        let allReportsData = []; // Store fetched data for client-side filtering

        // Helper function for status badge classes
        function getStatusClasses(status) {
            if (status === 'Pending') return 'bg-amber-100 text-amber-800 border border-amber-200'; // Changed yellow to amber for better contrast
            if (status === 'Found') return 'bg-green-100 text-green-800 border border-green-200';
            if (status === 'Claimed') return 'bg-blue-100 text-blue-800 border border-blue-200';
            return 'bg-gray-100 text-gray-800 border border-gray-200';
        }

        // 1. Renders the main reports table based on current filters and stored data
        function renderReportsTable() {
            const search = searchInput.value.toLowerCase();
            const status = statusFilter.value;

            const filteredReports = allReportsData.filter(report => {
                const item = report.item_name.toLowerCase();
                const location = report.location.toLowerCase();
                const rowStatus = report.status.trim();

                const matchesSearch = item.includes(search) || location.includes(search);
                const matchesStatus = !status || rowStatus === status;

                return matchesSearch && matchesStatus;
            });

            let tableHTML = '';
            if (filteredReports.length > 0) {
                filteredReports.forEach(report => {
                    const imageHtml = report.image_path ?
                        `<img src="${report.image_path}" alt="Report Image" class="w-12 h-12 object-cover rounded-lg shadow cursor-pointer hover:scale-105 transition-transform duration-200" onclick="viewImage('${report.image_path}')">` :
                        `<i class="fas fa-image text-gray-400 text-xl opacity-75"></i>`;

                    const statusClasses = getStatusClasses(report.status);

                    tableHTML += `
                        <tr class="report-row divide-x divide-gray-100">
                            <td class="px-5 py-3">${imageHtml}</td>
                            <td class="px-5 py-3 font-medium text-gray-800">${report.item_name}</td>
                            <td class="px-5 py-3 text-gray-600 hidden sm:table-cell">${report.category}</td>
                            <td class="px-5 py-3 text-gray-600 hidden md:table-cell">${report.location}</td>
                            <td class="px-5 py-3 text-gray-600 hidden lg:table-cell">${report.date_reported}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center text-xs font-semibold px-3 py-1.5 rounded-full shadow-sm status-badge ${statusClasses}">
                                    <i class="fas fa-circle text-[6px] mr-1.5"></i>
                                    ${report.status}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <button onclick="openDeleteModal(${report.id})" class="text-red-500 hover:text-red-700 p-2 rounded-full hover:bg-red-50 transition-colors duration-200">
                                    <i class="fas fa-trash text-base"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                tableHTML = `
                    <tr class="no-data">
                        <td colspan="7" class="text-center py-10 text-gray-500 italic">
                            <i class="fas fa-folder-open text-3xl mb-2"></i><br>
                            No reports found matching the criteria.
                        </td>
                    </tr>
                `;
            }
            reportsTableBody.innerHTML = tableHTML;
        }

        // 2. Renders the recent report card (Adjusted for new card styling)
        function renderRecentReport(report) {
            let content;
            if (report) {
                const imageHtml = report.image_path ?
                    `<div class="overflow-hidden bg-white/50 h-52 flex items-center justify-center"><img src="${report.image_path}" alt="Reported Item" class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-500 ease-out"></div>` :
                    `<div class="bg-gray-100 h-52 flex items-center justify-center text-gray-500 italic text-sm"><i class="fas fa-image text-4xl mb-2 text-gray-300"></i></div>`;

                const statusClasses = getStatusClasses(report.status);

                content = `
                    <div class="bg-white border border-gray-200 rounded-3xl overflow-hidden shadow-2xl transition-all duration-300 group h-full flex flex-col justify-between" data-aos-delay="200">
                        <div class="relative">
                             <div class="p-5 flex items-center justify-between border-b border-gray-100">
                                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-clock text-blue-600 text-lg"></i>
                                    Latest Report
                                </h3>
                                <span class="text-xs text-gray-400">ID: ${report.id}</span>
                            </div>
                            ${imageHtml}
                        </div>
                       
                        <div class="p-5 space-y-3 flex-grow">
                            <h4 class="font-bold text-xl text-blue-700 leading-tight">${report.item_name}</h4>
                            <p class="text-sm text-gray-600"><span class="font-medium text-gray-700">Category:</span> ${report.category}</p>
                            <p class="text-sm text-gray-700 italic mt-1 line-clamp-3">${report.description}</p>
                            <div class="mt-4 space-y-1 text-sm pt-2 border-t border-gray-100">
                                <p class="text-gray-500">
                                    <i class="fas fa-map-marker-alt mr-1 text-blue-600/70"></i>
                                    <strong class="font-medium">Location:</strong> ${report.location}
                                </p>
                                <p class="text-gray-500">
                                    <i class="fas fa-calendar-alt mr-1 text-blue-600/70"></i>
                                    <strong class="font-medium">Date:</strong> ${report.date_reported}
                                </p>
                            </div>
                        </div>

                        <div class="p-5 pt-0 flex justify-between items-center">
                            <span class="inline-flex items-center text-sm font-semibold px-4 py-2 rounded-full shadow-md status-badge ${statusClasses}">
                                <i class="fas fa-circle text-[7px] mr-2"></i>
                                ${report.status}
                            </span>
                            <button class="text-red-500 hover:text-red-700 bg-red-50 rounded-full p-2 shadow-sm transition-all duration-200"
                                onclick="openDeleteModal(${report.id})">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                    </div>
                `;
            } else {
                content = `
                    <div class="bg-white shadow-xl rounded-3xl p-7 h-full flex flex-col justify-center items-center text-gray-500">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                             <i class="fas fa-clock text-blue-600 text-lg"></i>
                            Latest Report
                        </h3>
                        <i class="fas fa-folder-open text-5xl mb-3 text-gray-300"></i>
                        <p class="italic text-base">You haven't submitted any reports yet.</p>
                        <p class="text-sm text-gray-400 mt-1">Use the form to the left to start.</p>
                    </div>
                `;
            }
            recentReportContainer.innerHTML = content;
        }


        // 3. Main function to fetch all data via AJAX (Unchanged)
        async function fetchReportsData() {
            try {
                // Call the same PHP file but trigger the JSON response block
                const response = await fetch('reports.php?data=fetch');
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                const data = await response.json();

                // Update Dashboard Cards
                totalReportsCount.textContent = data.total_reports;
                approvedReportsCount.textContent = data.approved_reports;

                // Update Recent Report Card
                renderRecentReport(data.recent_report);

                // Store and Render Table Data (applies current filters)
                allReportsData = data.all_reports;
                renderReportsTable();

            } catch (error) {
                console.error('Error fetching reports data:', error);
                // Optionally update UI to show error
                if (reportsTableBody.innerHTML.includes('Fetching reports')) {
                    reportsTableBody.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-red-500 italic"><i class="fas fa-exclamation-triangle text-3xl mb-2"></i><br>Error loading reports.</td></tr>';
                }
            }
        }


        // --- Initialization and Event Listeners (Unchanged logic) ---
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const status = urlParams.get('status');
            const message = urlParams.get('message');

            if (status && message) {
                Swal.fire({
                    icon: status === 'success' ? 'success' : 'error',
                    title: decodeURIComponent(message),
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });

                // Clean URL history
                setTimeout(() => {
                    const cleanUrl = window.location.pathname;
                    history.replaceState(null, '', cleanUrl);
                }, 3100);
            }

            // Start the real-time data fetching loop
            fetchReportsData();
            // Poll every 5 seconds (5000 ms) for updates
            setInterval(fetchReportsData, 5000);

            // Attach event listeners for client-side filtering (this triggers renderReportsTable)
            searchInput.addEventListener("input", renderReportsTable);
            statusFilter.addEventListener("change", renderReportsTable);

        });

        // --- Form Validation (Unchanged logic) ---
        document.getElementById("reportForm").addEventListener("submit", function(event) {
            const form = event.target;
            const itemName = form.item_name.value.trim();
            const category = form.category.value.trim();
            const description = form.description.value.trim();
            const location = form.location.value.trim();

            if (!itemName || !category || !description || !location) {
                event.preventDefault(); // Stop the default submission
                Swal.fire({
                    icon: "warning",
                    title: "Missing Information",
                    text: "Please fill out all required fields before submitting your report.",
                    confirmButtonColor: "#2563eb",
                });
                return;
            }
        });
    </script>
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
    <script src="https://kit.fontawesome.com/a2d9d5b6e2.js" crossorigin="anonymous"></script>

</body>

</html>