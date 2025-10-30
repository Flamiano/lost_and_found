<?php
session_start();

require '../config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/admin_login.php");
    exit();
}

// Set Timezone and Current Time
date_default_timezone_set('Asia/Manila');
$current_time = date('F j, Y | h:i:s A');
$today_date = date('Y-m-d');

// Fetch Admin Name
$admin_id = $_SESSION['admin_id'];
$admin_name = 'Admin Staff';
try {
    $stmt = $conn->prepare("SELECT full_name FROM admins WHERE id = :id");
    $stmt->execute([':id' => $admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        $admin_name = $admin['full_name'];
    }
} catch (PDOException $e) {
    // Error handling
}


$total_lost_items = 0;
$total_found_items = 0;
$total_claimed_items = 0;
$total_users = 0;
$lost_today = 0;
$found_today = 0;
$claim_rate = 0;
$daily_activities = [];

// NEW DATA POINTS
$total_pending_reports = 0; // New Card
$total_verified_users = 0; // New Card and Chart Data
$total_unverified_users = 0; // New Card and Chart Data

try {
    // Stats Fetching 
    $total_lost_items = (int)$conn->query("SELECT COUNT(*) FROM reports")->fetchColumn();
    $total_found_items = (int)$conn->query("SELECT COUNT(*) FROM admin_reports")->fetchColumn();
    $total_claimed_items = (int)$conn->query("SELECT (SELECT COUNT(*) FROM reports WHERE status = 'Claimed') + (SELECT COUNT(*) FROM admin_reports WHERE status = 'Claimed')")->fetchColumn();
    $total_users = (int)$conn->query("SELECT COUNT(*) FROM students")->fetchColumn();

    // New Stat: Total Pending Reports (from Students)
    $total_pending_reports = (int)$conn->query("SELECT COUNT(*) FROM reports WHERE status = 'Pending'")->fetchColumn();

    // New Stat: User Verification Breakdown
    $total_verified_users = (int)$conn->query("SELECT COUNT(*) FROM students WHERE is_verified = TRUE")->fetchColumn();
    $total_unverified_users = $total_users - $total_verified_users;


    // Today's Stats
    $stmt_lost_today = $conn->prepare("SELECT COUNT(*) FROM reports WHERE DATE(created_at) = :today");
    $stmt_lost_today->execute([':today' => $today_date]);
    $lost_today = (int)$stmt_lost_today->fetchColumn();

    $stmt_found_today = $conn->prepare("SELECT COUNT(*) FROM admin_reports WHERE DATE(created_at) = :today");
    $stmt_found_today->execute([':today' => $today_date]);
    $found_today = (int)$stmt_found_today->fetchColumn();

    // Daily Activity Table Data (Limited to 10 combined)
    $stmt_activity = $conn->prepare("
        (SELECT 'Lost' as type, item_name, location, category, created_at FROM reports WHERE DATE(created_at) = :today_r ORDER BY created_at DESC)
        UNION ALL
        (SELECT 'Found' as type, item_name, location, category, created_at FROM admin_reports WHERE DATE(created_at) = :today_a ORDER BY created_at DESC)
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt_activity->execute([':today_r' => $today_date, ':today_a' => $today_date]);
    $daily_activities = $stmt_activity->fetchAll(PDO::FETCH_ASSOC);

    // Calculated Metric 
    $total_items = $total_lost_items + $total_found_items;
    $claim_rate = ($total_items > 0) ? round(($total_claimed_items / $total_items) * 100) : 0;
} catch (PDOException $e) {
    // In case of error, set all to 0 and log the error.
    error_log("Dashboard Data Fetch Error: " . $e->getMessage());
    $total_lost_items = 0;
    // You could set a visual error message here if needed.
}


// Fetch Data for Charts (Static Load)

$category_data = [];
$category_labels = [];
$weekly_lost_data = [0, 0, 0, 0, 0, 0, 0]; // Sun to Sat
$weekly_found_data = [0, 0, 0, 0, 0, 0, 0]; // Sun to Sat
$day_map = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

// NEW CHART DATA
$user_verification_data = [$total_verified_users, $total_unverified_users];
$user_verification_labels = ['Verified Users', 'Unverified Users'];

// **********************************************
// ** NEW: Data for Grouped Bar Charts 📊 **
// **********************************************
$report_category_counts = [];
$report_status_counts = [];

try {
    // Chart 1: Item Category Breakdown (Lost & Found Combined - Existing Logic)
    $stmt = $conn->query("
        SELECT category, COUNT(*) as count 
        FROM reports 
        GROUP BY category
        UNION ALL
        SELECT category, COUNT(*) as count 
        FROM admin_reports 
        GROUP BY category
    ");
    $all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $aggregated_categories = [];
    foreach ($all_categories as $item) {
        $cat = $item['category'];
        if (!isset($aggregated_categories[$cat])) {
            $aggregated_categories[$cat] = 0;
        }
        $aggregated_categories[$cat] += $item['count'];
    }

    foreach ($aggregated_categories as $cat => $count) {
        $category_labels[] = $cat;
        $category_data[] = (int)$count;
    }

    // NEW SQL FOR CATEGORY COUNTS (Student vs Admin)
    $stmt_cat_split = $conn->query("
        SELECT 'Student Reports' as source, category, COUNT(*) as count FROM reports GROUP BY category
        UNION ALL
        SELECT 'Admin Reports' as source, category, COUNT(*) as count FROM admin_reports GROUP BY category
    ");
    $cat_split_results = $stmt_cat_split->fetchAll(PDO::FETCH_ASSOC);

    // Initialize the structure for a grouped bar chart
    $all_categories_list = array_unique(array_column($cat_split_results, 'category'));
    $report_category_counts = [
        'labels' => $all_categories_list,
        'student' => array_fill_keys($all_categories_list, 0),
        'admin' => array_fill_keys($all_categories_list, 0),
    ];

    foreach ($cat_split_results as $row) {
        if ($row['source'] == 'Student Reports') {
            $report_category_counts['student'][$row['category']] = (int)$row['count'];
        } else {
            $report_category_counts['admin'][$row['category']] = (int)$row['count'];
        }
    }

    // NEW SQL FOR STATUS COUNTS (Student vs Admin)
    $stmt_status_split = $conn->query("
        SELECT 'Student Reports' as source, status, COUNT(*) as count FROM reports GROUP BY status
        UNION ALL
        SELECT 'Admin Reports' as source, status, COUNT(*) as count FROM admin_reports GROUP BY status
    ");
    $status_split_results = $stmt_status_split->fetchAll(PDO::FETCH_ASSOC);

    // Get all possible statuses across both tables
    $all_statuses = array_unique(array_column($status_split_results, 'status'));
    sort($all_statuses); // Sort for better chart presentation

    $report_status_counts = [
        'labels' => $all_statuses,
        'student' => array_fill_keys($all_statuses, 0),
        'admin' => array_fill_keys($all_statuses, 0),
    ];

    foreach ($status_split_results as $row) {
        // Ensure status key exists before assigning count
        if (!in_array($row['status'], $all_statuses)) continue;

        if ($row['source'] == 'Student Reports') {
            $report_status_counts['student'][$row['status']] = (int)$row['count'];
        } else {
            $report_status_counts['admin'][$row['status']] = (int)$row['count'];
        }
    }


    // Chart 2: Weekly Report Data (Lost Items by Day) - Existing Logic
    $stmt_lost = $conn->query("
        SELECT DAYOFWEEK(date_reported) as day, COUNT(*) as count 
        FROM reports 
        WHERE date_reported >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY day
    ");
    $weekly_lost_results = $stmt_lost->fetchAll(PDO::FETCH_ASSOC);

    foreach ($weekly_lost_results as $row) {
        $day_index = $row['day'] - 1;
        if (isset($weekly_lost_data[$day_index])) {
            $weekly_lost_data[$day_index] = (int)$row['count'];
        }
    }

    // Chart 2: Weekly Report Data (Found Items by Day) - Existing Logic
    $stmt_found = $conn->query("
        SELECT DAYOFWEEK(date_reported) as day, COUNT(*) as count 
        FROM admin_reports 
        WHERE date_reported >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY day
    ");
    $weekly_found_results = $stmt_found->fetchAll(PDO::FETCH_ASSOC);

    foreach ($weekly_found_results as $row) {
        $day_index = $row['day'] - 1;
        if (isset($weekly_found_data[$day_index])) {
            $weekly_found_data[$day_index] = (int)$row['count'];
        }
    }
} catch (PDOException $e) {
    // In case of chart error, leave data empty to prevent JS error.
    error_log("Chart Data Fetch Error: " . $e->getMessage());
}

// Convert PHP arrays to JSON for use in JavaScript
$js_category_data = json_encode($category_data);
$js_category_labels = json_encode($category_labels);
$js_weekly_lost_data = json_encode($weekly_lost_data);
$js_weekly_found_data = json_encode($weekly_found_data);
$js_day_map = json_encode($day_map);
$js_user_verification_data = json_encode($user_verification_data);
$js_user_verification_labels = json_encode($user_verification_labels);

// NEW JSON VARIABLES for Grouped Bar Charts
$js_report_category_labels = json_encode(array_values($report_category_counts['labels'] ?? []));
$js_student_category_data = json_encode(array_values($report_category_counts['student'] ?? []));
$js_admin_category_data = json_encode(array_values($report_category_counts['admin'] ?? []));

$js_report_status_labels = json_encode(array_values($report_status_counts['labels'] ?? []));
$js_student_status_data = json_encode(array_values($report_status_counts['student'] ?? []));
$js_admin_status_data = json_encode(array_values($report_status_counts['admin'] ?? []));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Lost and Found System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png" />
    <link rel="stylesheet" href="../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        * {
            font-family: "Poppins", sans-serif;
        }

        body {
            min-height: 100vh;
        }

        .lg-sidebar-offset {
            margin-left: 16rem;
        }

        @media (max-width: 1024px) {
            .lg-sidebar-offset {
                margin-left: 0;
            }
        }
    </style>
</head>

<body class="bg-gray-100">

    <?php include 'sidebar.php'; ?>

    <div class="p-6 lg-sidebar-offset">
        <header class="bg-blue-700 text-white p-6 rounded-xl shadow-lg mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <i class="fas fa-chart-line text-blue-300"></i> Admin Dashboard
            </h1>
            <div class="text-sm mt-2 sm:mt-0 opacity-80 text-right">
                Welcome, <strong><?= htmlspecialchars($admin_name) ?></strong>!
                <br>
                <span id="live-time" class="font-bold text-lg"><?= $current_time ?></span>
            </div>
        </header>

        <main>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">

                <div class="bg-white p-5 rounded-xl shadow-2xl border-t-4 border-red-500 flex flex-col transition-all duration-300 hover:shadow-red-200/50 hover:scale-[1.02]">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Lost Items Reported</p>
                            <p id="total_lost_items" class="text-4xl font-extrabold text-red-700 mt-1"><?= $total_lost_items ?></p>
                        </div>
                        <i class="fas fa-bullhorn text-5xl text-red-300 opacity-50"></i>
                    </div>
                    <div id="lost_today_container" class="mt-3 pt-2 border-t border-red-100">
                        <?php if ($lost_today > 0): ?>
                            <div class="text-xs font-bold text-red-600 flex items-center"><i class="fas fa-plus-circle mr-1 text-base"></i> <?= $lost_today ?> NEW TODAY</div>
                        <?php else: ?>
                            <div class="text-xs font-medium text-gray-400">No new reports today.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-2xl border-t-4 border-green-500 flex flex-col transition-all duration-300 hover:shadow-green-200/50 hover:scale-[1.02]">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Items Found (Admin)</p>
                            <p id="total_found_items" class="text-4xl font-extrabold text-green-700 mt-1"><?= $total_found_items ?></p>
                        </div>
                        <i class="fas fa-box-open text-5xl text-green-300 opacity-50"></i>
                    </div>
                    <div id="found_today_container" class="mt-3 pt-2 border-t border-green-100">
                        <?php if ($found_today > 0): ?>
                            <div class="text-xs font-bold text-green-600 flex items-center"><i class="fas fa-plus-circle mr-1 text-base"></i> <?= $found_today ?> NEW TODAY</div>
                        <?php else: ?>
                            <div class="text-xs font-medium text-gray-400">No new finds logged.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-2xl border-t-4 border-purple-500 flex flex-col transition-all duration-300 hover:shadow-purple-200/50 hover:scale-[1.02]">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Combined Reports</p>
                            <p id="total_reports" class="text-4xl font-extrabold text-purple-700 mt-1"><?= $total_lost_items + $total_found_items ?></p>
                        </div>
                        <i class="fas fa-scroll text-5xl text-purple-300 opacity-50"></i>
                    </div>
                    <div class="mt-3 pt-2 border-t border-purple-100">
                        <div class="text-xs font-medium text-gray-500">Lost: <?= $total_lost_items ?> | Found: <?= $total_found_items ?></div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-2xl border-t-4 border-orange-500 flex flex-col transition-all duration-300 hover:shadow-orange-200/50 hover:scale-[1.02]">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Pending Student Reports</p>
                            <p id="total_pending_reports" class="text-4xl font-extrabold text-orange-700 mt-1"><?= $total_pending_reports ?></p>
                        </div>
                        <i class="fas fa-exclamation-triangle text-5xl text-orange-300 opacity-50"></i>
                    </div>
                    <div class="mt-3 pt-2 border-t border-orange-100">
                        <div class="text-xs font-medium text-gray-500">Needs administrative review.</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-2xl border-t-4 border-blue-500 flex flex-col transition-all duration-300 hover:shadow-blue-200/50 hover:scale-[1.02]">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Items Claimed</p>
                            <p id="total_claimed_items" class="text-4xl font-extrabold text-blue-700 mt-1"><?= $total_claimed_items ?></p>
                        </div>
                        <i class="fas fa-handshake text-5xl text-blue-300 opacity-50"></i>
                    </div>
                    <div class="mt-3 pt-2 border-t border-blue-100">
                        <p class="text-xs font-medium text-gray-500 mb-1">Overall Claim Rate:</p>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div id="claim_rate_bar" class="bg-blue-600 h-2.5 rounded-full" style="width: <?= $claim_rate ?>%"></div>
                        </div>
                        <p id="claim_rate_text" class="text-sm font-bold text-blue-600 mt-1"><?= $claim_rate ?>%</p>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-2xl border-t-4 border-yellow-500 flex items-center justify-between transition-all duration-300 hover:shadow-yellow-200/50 hover:scale-[1.02]">
                    <div>
                        <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Registered Students</p>
                        <p id="total_users" class="text-4xl font-extrabold text-yellow-700 mt-1"><?= $total_users ?></p>
                    </div>
                    <i class="fas fa-users text-5xl text-yellow-300 opacity-50"></i>
                </div>

            </div>

            <div class="bg-white p-6 mb-10 rounded-xl shadow-xl border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-clock text-indigo-500"></i> Today's Reports (<span id="daily_count"><?= count($daily_activities) ?></span> total)
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            </tr>
                        </thead>
                        <tbody id="daily_activity_body" class="bg-white divide-y divide-gray-200">
                            <?php if (empty($daily_activities)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-sm text-gray-500 text-center">No new reports recorded today.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($daily_activities as $activity):
                                    $type_class = $activity['type'] === 'Lost' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
                                    // Use DateTime to safely format the time part
                                    try {
                                        $dateTime = new DateTime($activity['created_at']);
                                        $time_part = $dateTime->format('h:i A');
                                    } catch (Exception $e) {
                                        $time_part = 'N/A';
                                    }
                                ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $type_class ?>">
                                                <?= htmlspecialchars($activity['type']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($activity['item_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($activity['category']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($activity['location']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $time_part ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-blue-500"></i> Weekly Report: Lost & Found Trends
                    </h3>
                    <div id="weeklyChart"></div>
                </div>

                <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-shapes text-green-500"></i> Item Category Breakdown
                    </h3>
                    <div id="categoryChart"></div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                <div class="bg-white p-6 rounded-xl shadow-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-exchange-alt text-purple-500"></i> Lifetime Reports: Lost vs. Found
                    </h3>
                    <div id="lostFoundBarChart"></div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-user-check text-yellow-500"></i> Student Verification Status
                    </h3>
                    <div id="userVerificationBarChart"></div>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                <div class="bg-white p-6 rounded-xl shadow-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-layer-group text-red-500"></i> Report Breakdown by Category
                    </h3>
                    <div id="categoryGroupedBarChart"></div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-xl border border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-500"></i> Report Breakdown by Status
                    </h3>
                    <div id="statusGroupedBarChart"></div>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Function to update the live clock
        function updateTime() {
            const timeElement = document.getElementById('live-time');
            if (timeElement) {
                const now = new Date();
                const datePart = now.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }).replace(',', '');
                const timePart = now.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                });
                timeElement.textContent = `${datePart} | ${timePart}`;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Start the live clock
            updateTime();
            setInterval(updateTime, 1000);

            // =========================================================================
            // CHART 1: Weekly Report (Lost vs. Found) - Existing
            // =========================================================================
            const weeklyOptions = {
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: {
                        show: false
                    }
                },
                series: [{
                    name: 'Lost Reports',
                    data: <?= $js_weekly_lost_data ?>
                }, {
                    name: 'Found (Admin) Reports',
                    data: <?= $js_weekly_found_data ?>
                }],
                xaxis: {
                    categories: <?= $js_day_map ?>,
                    title: {
                        text: 'Day of the Week (Last 7 Days)'
                    }
                },
                yaxis: {
                    title: {
                        text: 'Number of Reports'
                    },
                    forceNiceScale: true,
                    min: 0,
                    tickAmount: 5
                },
                colors: ['#ef4444', '#10b981'],
                stroke: {
                    curve: 'smooth'
                },
                dataLabels: {
                    enabled: false
                }
            };
            var weeklyChart = new ApexCharts(document.querySelector("#weeklyChart"), weeklyOptions);
            weeklyChart.render();

            // =========================================================================
            // CHART 2: Category Breakdown (Donut Chart) - Existing
            // =========================================================================
            const categoryOptions = {
                chart: {
                    type: 'donut',
                    height: 350
                },
                series: <?= $js_category_data ?>,
                labels: <?= $js_category_labels ?>,
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 200
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }],
                legend: {
                    position: 'bottom'
                }
            };
            var categoryChart = new ApexCharts(document.querySelector("#categoryChart"), categoryOptions);
            categoryChart.render();

            // =========================================================================
            // CHART 3: Lost vs. Found Totals (Bar Chart) - Existing
            // =========================================================================
            const lostFoundOptions = {
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: {
                        show: false
                    }
                },
                series: [{
                    name: 'Total Reports',
                    data: [<?= $total_lost_items ?>, <?= $total_found_items ?>]
                }],
                xaxis: {
                    categories: ['Lost Reports (Students)', 'Found Reports (Admin)'],
                    labels: {
                        style: {
                            colors: ['#ef4444', '#10b981'],
                            fontSize: '14px'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Count'
                    },
                    min: 0,
                    forceNiceScale: true,
                    tickAmount: 5
                },
                colors: ['#ef4444', '#10b981'],
                plotOptions: {
                    bar: {
                        distributed: true,
                        borderRadius: 4,
                        horizontal: false,
                    }
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        colors: ['#fff']
                    },
                    formatter: function(val) {
                        return val;
                    }
                },
                legend: {
                    show: false
                }
            };
            var lostFoundBarChart = new ApexCharts(document.querySelector("#lostFoundBarChart"), lostFoundOptions);
            lostFoundBarChart.render();

            // =========================================================================
            // CHART 4: User Verification Status (Bar Chart) - Existing
            // =========================================================================
            const userVerificationOptions = {
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: {
                        show: false
                    }
                },
                series: [{
                    name: 'Student Count',
                    data: <?= $js_user_verification_data ?>
                }],
                xaxis: {
                    categories: <?= $js_user_verification_labels ?>,
                    labels: {
                        style: {
                            colors: ['#1d4ed8', '#f59e0b'],
                            fontSize: '14px'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Student Count'
                    },
                    min: 0,
                    forceNiceScale: true,
                    tickAmount: 5
                },
                colors: ['#3b82f6', '#fbbf24'],
                plotOptions: {
                    bar: {
                        distributed: true,
                        borderRadius: 4,
                        horizontal: false,
                    }
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        colors: ['#fff']
                    },
                    formatter: function(val) {
                        return val;
                    }
                },
                legend: {
                    show: false
                }
            };
            var userVerificationBarChart = new ApexCharts(document.querySelector("#userVerificationBarChart"), userVerificationOptions);
            userVerificationBarChart.render();

            // =========================================================================
            // ** NEW CHART 5: Category Grouped Bar Chart (Student vs Admin) **
            // =========================================================================
            const categoryGroupedOptions = {
                chart: {
                    type: 'bar',
                    height: 400,
                    toolbar: {
                        show: false
                    }
                },
                series: [{
                    name: 'Student Lost Reports',
                    data: <?= $js_student_category_data ?>
                }, {
                    name: 'Admin Found Reports',
                    data: <?= $js_admin_category_data ?>
                }],
                xaxis: {
                    categories: <?= $js_report_category_labels ?>,
                    labels: {
                        rotate: -45, // Rotate labels for long category names
                        style: {
                            fontSize: '12px'
                        },
                        // Ensure labels are centered under the grouped bars
                        offsetX: 0,
                        offsetY: 0
                    },
                    // Prevent cutting off the rotated labels
                    tickPlacement: 'on'
                },
                yaxis: {
                    title: {
                        text: 'Count'
                    },
                    min: 0,
                    forceNiceScale: true,
                    tickAmount: 5
                },
                colors: ['#ef4444', '#10b981'], // Red for Lost, Green for Found
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '70%',
                        endingShape: 'rounded'
                    },
                },
                dataLabels: {
                    enabled: false
                },
                legend: {
                    position: 'top'
                },
                grid: {
                    // Padding at the bottom to account for rotated labels
                    padding: {
                        bottom: 40
                    }
                }
            };
            var categoryGroupedBarChart = new ApexCharts(document.querySelector("#categoryGroupedBarChart"), categoryGroupedOptions);
            categoryGroupedBarChart.render();

            // =========================================================================
            // ** NEW CHART 6: Status Grouped Bar Chart (Student vs Admin) **
            // We'll use a stacked bar for a better status comparison.
            // =========================================================================
            const statusGroupedOptions = {
                chart: {
                    type: 'bar',
                    height: 400,
                    stacked: true, // Use stacked to show total count per status
                    toolbar: {
                        show: false
                    }
                },
                series: [{
                    name: 'Student Reports',
                    data: <?= $js_student_status_data ?>
                }, {
                    name: 'Admin Reports',
                    data: <?= $js_admin_status_data ?>
                }],
                xaxis: {
                    categories: <?= $js_report_status_labels ?>,
                    title: {
                        text: 'Report Status'
                    }
                },
                yaxis: {
                    title: {
                        text: 'Count'
                    },
                    min: 0,
                    forceNiceScale: true,
                    tickAmount: 5
                },
                colors: ['#fbbf24', '#3b82f6'], // Yellow for Student, Blue for Admin
                plotOptions: {
                    bar: {
                        horizontal: false,
                        borderRadius: 4,
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, {
                        seriesIndex,
                        dataPointIndex,
                        w
                    }) {
                        // Only show data label if the value is greater than zero
                        return val > 0 ? val : '';
                    },
                    style: {
                        colors: ['#000', '#fff']
                    }
                },
                legend: {
                    position: 'top'
                }
            };
            var statusGroupedBarChart = new ApexCharts(document.querySelector("#statusGroupedBarChart"), statusGroupedOptions);
            statusGroupedBarChart.render();
        });
    </script>
    <script src="https://kit.fontawesome.com/a2d9d5b6e2.js" crossorigin="anonymous"></script>
</body>

</html>