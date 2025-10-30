<?php
// claim_items.php

session_start();

require '../config/db.php'; // Adjust path as needed

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/admin_login.php");
    exit();
}

// Set Timezone and Current Time
date_default_timezone_set('Asia/Manila');
$current_time = date('F j, Y | h:i:s A');
$admin_id = $_SESSION['admin_id'];

// Fetch Admin Name
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


$claimed_items = [];
$stats = [
    'total_claimed' => 0,
    'claimed_found' => 0,
    'claimed_lost' => 0,
    'category_counts' => []
];

try {
    // FIX 1: UNION ALL Query to fetch claimed items from both admin_reports and reports
    $stmt_claimed = $conn->prepare("
        -- 1. Claimed Found Items (from admin_reports)
        SELECT 
            ar.id AS report_id,
            ar.item_name,
            ar.category,
            ar.image_path, 
            ar.location,
            ar.date_reported,
            ar.admin_id AS report_admin_id,
            'found' AS source_type, 
            
            ic.claimant_name,
            ic.claimant_contact,
            ic.verification_method,
            ic.date_claimed,
            ic.relationship_to_item,
            a.full_name AS processed_by_admin_name
        FROM 
            admin_reports ar
        JOIN 
            item_claims ic ON ar.id = ic.item_report_id
        JOIN
            admins a ON ic.admin_id = a.id
        WHERE
            ar.status = 'Claimed'
        
        UNION ALL
        
        -- 2. Claimed Lost Items (from reports)
        SELECT
            r.id AS report_id,
            r.item_name,
            r.category,
            r.image_path,
            r.location,
            r.date_reported,
            r.student_id AS report_admin_id, 
            'lost' AS source_type, 
            
            ic.claimant_name,
            ic.claimant_contact,
            ic.verification_method,
            ic.date_claimed,
            ic.relationship_to_item,
            a.full_name AS processed_by_admin_name
        FROM
            reports r
        JOIN
            item_claims ic ON r.id = ic.item_report_id
        JOIN
            admins a ON ic.admin_id = a.id
        WHERE
            r.status = 'Claimed'

        ORDER BY 
            date_claimed DESC
    ");

    $stmt_claimed->execute();
    $claimed_items = $stmt_claimed->fetchAll(PDO::FETCH_ASSOC);

    // Calculate Stats for Cards and Chart
    $stats['total_claimed'] = count($claimed_items);
    foreach ($claimed_items as $item) {
        if ($item['source_type'] === 'found') {
            $stats['claimed_found']++;
        } elseif ($item['source_type'] === 'lost') {
            $stats['claimed_lost']++;
        }

        $category = $item['category'];
        $stats['category_counts'][$category] = ($stats['category_counts'][$category] ?? 0) + 1;
    }
} catch (PDOException $e) {
    error_log("Claimed Items Fetch Error: " . $e->getMessage());
    // Optionally set an error message for the user
}

// Function to format date/time
function format_date($datetime)
{
    try {
        $dateTimeObj = new DateTime($datetime);
        return $dateTimeObj->format('M j, Y - h:i A');
    } catch (Exception $e) {
        return 'N/A';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claimed Items Log | Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <style>
        * {
            font-family: "Poppins", sans-serif;
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
                <i class="fas fa-handshake text-purple-300"></i> Claimed Items Log
            </h1>
            <div class="text-sm mt-2 sm:mt-0 opacity-80 text-right">
                Welcome, <strong><?= htmlspecialchars($admin_name) ?></strong>!
                <br>
                <span id="live-time" class="font-bold text-lg"><?= $current_time ?></span>
            </div>
        </header>

        <main>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

                <div class="bg-white p-6 rounded-xl shadow-2xl border-l-4 border-blue-500 transition-all duration-300 hover:shadow-blue-200/50 hover:scale-[1.02]">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Total Claimed Items</p>
                            <p class="text-4xl font-extrabold text-blue-700 mt-1"><?= $stats['total_claimed'] ?></p>
                        </div>
                        <i class="fas fa-handshake text-5xl text-blue-300 opacity-50"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-2xl border-l-4 border-green-500 transition-all duration-300 hover:shadow-green-200/50 hover:scale-[1.02]">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Claimed FOUND Items</p>
                            <p class="text-4xl font-extrabold text-green-700 mt-1"><?= $stats['claimed_found'] ?></p>
                        </div>
                        <i class="fas fa-folder-open text-5xl text-green-300 opacity-50"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-2xl border-l-4 border-red-500 transition-all duration-300 hover:shadow-red-200/50 hover:scale-[1.02]">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Claimed LOST Items</p>
                            <p class="text-4xl font-extrabold text-red-700 mt-1"><?= $stats['claimed_lost'] ?></p>
                        </div>
                        <i class="fas fa-minus-circle text-5xl text-red-300 opacity-50"></i>
                    </div>
                </div>

                <?php
                $top_category = 'N/A';
                $top_count = 0;
                if (!empty($stats['category_counts'])) {
                    arsort($stats['category_counts']);
                    $top_category = key($stats['category_counts']);
                    $top_count = current($stats['category_counts']);
                }
                ?>
                <div class="bg-white p-6 rounded-xl shadow-2xl border-l-4 border-purple-500 transition-all duration-300 hover:shadow-purple-200/50 hover:scale-[1.02]">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Top Claimed Category</p>
                            <p class="text-xl font-extrabold text-purple-700 mt-1"><?= htmlspecialchars($top_category) ?></p>
                            <p class="text-sm text-gray-500"><?= $top_count ?> items</p>
                        </div>
                        <i class="fas fa-trophy text-5xl text-purple-300 opacity-50"></i>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
                <div class="xl:col-span-2 bg-white p-6 rounded-xl shadow-xl border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-chart-pie text-blue-500"></i> Distribution by Item Category
                    </h2>
                    <div id="category-chart" class="h-80"></div>
                </div>

                <div class="xl:col-span-1 bg-white p-6 rounded-xl shadow-xl border border-gray-200">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-teal-500"></i> Log Information
                    </h2>
                    <ul class="space-y-3 text-gray-700">
                        <li class="flex justify-between items-center border-b pb-2">
                            <span class="font-medium">Total Entries:</span>
                            <span class="font-semibold text-teal-600"><?= $stats['total_claimed'] ?></span>
                        </li>
                        <li class="flex justify-between items-center border-b pb-2">
                            <span class="font-medium">Average Daily Claims:</span>
                            <span class="font-semibold text-teal-600">N/A*</span>
                        </li>
                        <li class="flex justify-between items-center border-b pb-2">
                            <span class="font-medium">Oldest Claim:</span>
                            <span class="text-sm text-gray-500"><?= !empty($claimed_items) ? format_date(end($claimed_items)['date_claimed']) : 'N/A' ?></span>
                        </li>
                        <li class="flex justify-between items-center">
                            <span class="font-medium">Newest Claim:</span>
                            <span class="text-sm text-gray-500"><?= !empty($claimed_items) ? format_date(reset($claimed_items)['date_claimed']) : 'N/A' ?></span>
                        </li>
                    </ul>
                    <p class="text-xs text-gray-400 mt-4">*Requires more complex SQL/PHP date calculations.</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-xl border border-gray-200">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-list-alt text-purple-500"></i> Detailed Claim History
                </h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Claimed By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verification</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Claim Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed By</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($claimed_items)): ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-4 text-sm text-gray-500 text-center">No items have been successfully claimed yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($claimed_items as $item):
                                    // FIX 2: Determine the correct base directory based on the source_type
                                    $image_base_dir = '';
                                    $source_label = '';
                                    $source_color = '';
                                    if ($item['source_type'] === 'found') {
                                        $image_base_dir = '../admin/';
                                        $source_label = 'Found Item';
                                        $source_color = 'bg-green-100 text-green-800';
                                    } elseif ($item['source_type'] === 'lost') {
                                        $image_base_dir = '../user/';
                                        $source_label = 'Lost Item';
                                        $source_color = 'bg-red-100 text-red-800';
                                    }

                                    $final_image_path = !empty($item['image_path']) ? htmlspecialchars($image_base_dir . $item['image_path']) : '';
                                ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php if (!empty($final_image_path)): ?>
                                                <button onclick="showFullImage('<?= $final_image_path ?>')" class="focus:outline-none">
                                                    <img src="<?= $final_image_path ?>" alt="<?= htmlspecialchars($item['item_name']) ?>" class="h-10 w-10 rounded-lg object-cover border border-gray-300 hover:border-blue-500 transition duration-150">
                                                </button>
                                            <?php else: ?>
                                                <i class="fas fa-image text-gray-400 text-xl"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold"><?= htmlspecialchars($item['item_name']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['category']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $source_color ?>">
                                                <?= $source_label ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($item['claimant_name']) ?><br>
                                            <span class="text-xs italic text-blue-600">(<?= htmlspecialchars($item['relationship_to_item']) ?>)</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($item['claimant_contact']) ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <button onclick="Swal.fire('Verification Method', '<?= htmlspecialchars(nl2br($item['verification_method'])) ?>', 'info')" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                                View Details
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= format_date($item['date_claimed']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($item['processed_by_admin_name']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <div id="imageModal" class="hidden fixed inset-0 z-50 bg-black bg-opacity-75 flex items-center justify-center transition-opacity" onclick="closeImageModal()">
        <div class="relative max-w-4xl max-h-full mx-auto p-4" onclick="event.stopPropagation()">
            <button class="absolute top-0 right-0 m-4 text-white text-3xl hover:text-red-500 transition duration-150 focus:outline-none" onclick="closeImageModal()">
                &times;
            </button>
            <img id="fullImage" src="" alt="Full Item Image" class="max-w-full max-h-[85vh] rounded-lg shadow-2xl">
        </div>
    </div>

    <script>
        // PHP variables for chart data
        const categoryLabels = <?= json_encode(array_keys($stats['category_counts'])) ?>;
        const categorySeries = <?= json_encode(array_values($stats['category_counts'])) ?>;

        // Function to update the live clock
        function updateTime() {
            const timeElement = document.getElementById('live-time');
            if (timeElement) {
                const now = new Date(new Date().toLocaleString("en-US", {
                    timeZone: "Asia/Manila"
                }));
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

        // --- Modal Functions ---

        function showFullImage(src) {
            document.getElementById('fullImage').src = src;
            document.getElementById('imageModal').classList.remove('hidden');
            document.getElementById('imageModal').classList.add('flex');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('flex');
            document.getElementById('imageModal').classList.add('hidden');
        }

        // --- ApexCharts Initialization ---
        function initializeCharts() {
            if (document.getElementById('category-chart') && categorySeries.length > 0) {
                const options = {
                    series: categorySeries.map(s => parseInt(s)), // Ensure data is numeric
                    chart: {
                        type: 'donut',
                        height: 320,
                    },
                    labels: categoryLabels,
                    colors: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899'], // Tailwind colors
                    legend: {
                        position: 'bottom',
                    },
                    dataLabels: {
                        formatter: function(val, opts) {
                            return opts.w.config.series[opts.seriesIndex]
                        },
                        style: {
                            fontSize: '14px',
                            fontFamily: 'Poppins, sans-serif',
                            fontWeight: 'bold',
                        }
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                labels: {
                                    show: true,
                                    total: {
                                        showAlways: true,
                                        show: true,
                                        label: 'Total Claims',
                                        formatter: function(w) {
                                            return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                        }
                                    }
                                }
                            }
                        }
                    }
                };

                const chart = new ApexCharts(document.querySelector("#category-chart"), options);
                chart.render();
            } else if (document.getElementById('category-chart')) {
                document.querySelector("#category-chart").innerHTML = '<p class="text-center text-gray-500 py-20">No data available for chart. Start claiming items!</p>';
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            // Start the live clock
            updateTime();
            setInterval(updateTime, 1000);

            // Initialize charts
            initializeCharts();
        });
    </script>
</body>

</html>