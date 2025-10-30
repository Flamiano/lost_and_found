<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['student_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require '../config/db.php'; 
$student_id = $_SESSION['student_id'];

include 'navbar.php';


// A. Get Total Counts
$data_counts = [
    'total_reported' => 0,
    'status_pending' => 0,
    'status_found'   => 0,
    'status_claimed' => 0,
];

try {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(id) AS total_reported,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS status_pending,
            SUM(CASE WHEN status = 'Found' THEN 1 ELSE 0 END) AS status_found,
            SUM(CASE WHEN status = 'Claimed' THEN 1 ELSE 0 END) AS status_claimed
        FROM reports
        WHERE student_id = :student_id
    ");
    $stmt->execute([':student_id' => $student_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $data_counts = $result;
    }
} catch (PDOException $e) {
    // Log the error and display a friendly message
    error_log("Database Error: " . $e->getMessage());
    // Optionally set counts to 0 or display an error card
}


// Get Reports by Category for the Chart
$category_data = [];
try {
    $stmt = $conn->prepare("
        SELECT category, COUNT(id) as count
        FROM reports
        WHERE student_id = :student_id
        GROUP BY category
        ORDER BY count DESC
    ");
    $stmt->execute([':student_id' => $student_id]);
    $category_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $chart_series = [];
    $chart_labels = [];
    foreach ($category_data as $row) {
        $chart_series[] = (int)$row['count'];
        $chart_labels[] = $row['category'];
    }
} catch (PDOException $e) {
    error_log("Category Chart Error: " . $e->getMessage());
    $chart_series = [0];
    $chart_labels = ['Error'];
}


// Get Recent Reports
$recent_reports = [];
try {
    $stmt = $conn->prepare("
        SELECT item_name, status, date_reported
        FROM reports
        WHERE student_id = :student_id
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([':student_id' => $student_id]);
    $recent_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Recent Reports Error: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overview | Lost and Found</title>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://kit.fontawesome.com/a2d9d5e76d.js" crossorigin="anonymous"></script>

    <style>
        .main-content {
            padding-top: 80px;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="main-content min-h-screen overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-2">Your Dashboard Overview 📊</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

                <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-600" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Total Reports</p>
                        <i class="fas fa-list-ul text-2xl text-blue-400"></i>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($data_counts['total_reported']) ?></p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-yellow-500" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Pending Review</p>
                        <i class="fas fa-clock text-2xl text-yellow-500"></i>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($data_counts['status_pending']) ?></p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-600" data-aos="fade-up" data-aos-delay="300">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Item Located</p>
                        <i class="fas fa-search-dollar text-2xl text-green-500"></i>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($data_counts['status_found']) ?></p>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-red-600" data-aos="fade-up" data-aos-delay="400">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Successfully Claimed</p>
                        <i class="fas fa-check-circle text-2xl text-red-500"></i>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($data_counts['status_claimed']) ?></p>
                </div>

            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg" data-aos="fade-right">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Your Reported Item Categories</h2>
                    <div id="category-chart"></div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-lg" data-aos="fade-left">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Recent Reports</h2>

                    <?php if (count($recent_reports) > 0): ?>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($recent_reports as $report): ?>
                                <li class="py-3 flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            <?= htmlspecialchars($report['item_name']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Reported: <?= date('M d, Y', strtotime($report['date_reported'])) ?>
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-medium 
                                        status-<?= htmlspecialchars($report['status']) ?>">
                                        <?= htmlspecialchars($report['status']) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="mt-4 text-center">
                            <a href="reports.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All Reports →</a>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-5">You haven't submitted any reports yet. 🤷‍♂️</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
        });

        // ApexCharts Configuration

        const chartSeries = <?= json_encode($chart_series) ?>;
        const chartLabels = <?= json_encode($chart_labels) ?>;

        const options = {
            series: chartSeries,
            chart: {
                type: 'donut',
                height: 350
            },
            labels: chartLabels,
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
            // Use a professional color palette
            colors: [
                '#3B82F6', // Blue
                '#10B981', // Green
                '#F59E0B', // Yellow
                '#EF4444', // Red
                '#6366F1', // Indigo
                '#84CC16', // Lime
                '#D946EF', // Fuchsia
                '#F97316', // Orange
                '#14B8A6', // Teal
                '#A855F7', // Purple
                '#374151' // Gray
            ],
            legend: {
                position: 'right',
                offsetY: 0,
                height: 230,
            }
        };

        const chart = new ApexCharts(document.querySelector("#category-chart"), options);
        chart.render();
    </script>
</body>

</html>
