<?php
// Include the navbar for a consistent header (as requested).

require '../config/db.php';

// --- Path Definitions (CRITICAL FIXES) ---

// Path for profile pictures (relative to current file: pages/item.php)
$base_url_profile = '../user/profile_picture/';

// Path for student reports (relative to current file: pages/item.php)
// C:\xampp\htdocs\lostandfound\pages\ -> C:\xampp\htdocs\lostandfound\user\inserted_report\
$base_url_student_reports = '../user/';

// Path for admin reports (relative to current file: pages/item.php)
// C:\xampp\htdocs\lostandfound\pages\ -> C:\xampp\htdocs\lostandfound\admin\inserted_report\
$base_url_admin_reports = '../admin/';

// --- Database Fetching Function ---

function fetchPublicItems($conn)
{
    try {
        $sql = "
            (SELECT 
                r.id, 
                r.category, 
                r.item_name, 
                r.description, 
                r.location, 
                r.date_reported, 
                r.image_path, 
                r.status,
                s.full_name AS reporter_name,
                s.email AS reporter_email,
                'Student' AS reporter_type,
                s.profile_picture AS reporter_image,
                r.created_at
            FROM reports r
            JOIN students s ON r.student_id = s.id
            WHERE r.status IN ('Approved', 'Found'))
            
            UNION ALL

            (SELECT 
                ar.id, 
                ar.category, 
                ar.item_name, 
                ar.description, 
                ar.location, 
                ar.date_reported, 
                ar.image_path, 
                ar.status,
                a.full_name AS reporter_name,
                a.email AS reporter_email,
                'Admin' AS reporter_type,
                NULL AS reporter_image,
                ar.created_at
            FROM admin_reports ar
            JOIN admins a ON ar.admin_id = a.id
            WHERE ar.status IN ('Approved', 'Found'))
            
            ORDER BY date_reported DESC, created_at DESC
        ";

        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error for debugging
        error_log("Database Error in view_items: " . $e->getMessage());
        return [];
    }
}

$all_items = fetchPublicItems($conn);

// Prepare data for JavaScript
$items_json = json_encode($all_items);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found Feed | Lost and Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a2d9d5e76d.js" crossorigin="anonymous"></script>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>

<body>
    <?php include '../includes/navbar.php'; ?>

    <section class="relative bg-gradient-to-r from-blue-700 via-blue-800 to-blue-900 text-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-12 py-32 sm:py-40 relative z-10 text-center lg:text-left">
            <div data-aos="fade-up" data-aos-duration="800">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold leading-tight mb-4">
                    <span class="block">Lost & Found Feed</span>
                    <span class="block text-blue-100">Browse Items and Get in Touch</span>
                </h1>
            </div>

            <div data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                <p class="text-lg sm:text-xl text-blue-100 max-w-2xl mx-auto lg:mx-0">
                    Browse all recently <b>Approved</b> and <b>Found</b> items reported by the community and staff.
                    Follow the guidelines below to ensure smooth and verified processing of your report.
                </p>
            </div>

        </div>

        <div class="absolute top-0 right-0 transform translate-x-1/4 -translate-y-1/4 opacity-10">
            <svg class="w-80 h-80 text-blue-200" fill="none" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                <path fill="currentColor"
                    d="M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256 256-114.6 256-256S397.4 0 256 0zm0 96c26.5 0 48 21.5 48 48s-21.5 48-48 48-48-21.5-48-48 21.5-48 48-48zm96 320H160v-32c0-35.3 28.7-64 64-64h64c35.3 0 64 28.7 64 64v32z" />
            </svg>
        </div>
    </section>

    <div class="py-10 bg-gray-50 min-h-screen">
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <aside class="col-span-1 space-y-8">
                    <div class="bg-white p-6 rounded-xl shadow-lg border-t-4 border-red-500 sticky lg:top-20" data-aos="fade-right">
                        <h2 class="text-2xl font-bold text-red-700 mb-4 flex items-center">
                            <i class="fas fa-map-marked-alt mr-3"></i> Lost & Found Office Locations
                        </h2>
                        <p class="text-gray-600 mb-6">
                            Visit the office corresponding to your campus to claim or report items.
                        </p>

                        <div class="mb-8">
                            <h3 class="font-bold text-lg text-gray-800 border-b pb-1 mb-3 flex items-center"><i class="fas fa-building mr-2 text-red-500"></i> MV Campus Office</h3>
                            <div class="w-full rounded-lg overflow-hidden border border-gray-300 shadow-md">
                                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3858.7671205663496!2d121.03574593216057!3d14.725754162148753!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b0ff7eb3d621%3A0x437d85420878d598!2sBestlink%20College%20of%20the%20Philippines!5e0!3m2!1sen!2sph!4v1760611109697!5m2!1sen!2sph"
                                    width="100%"
                                    height="300"
                                    style="border:0;"
                                    allowfullscreen=""
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-bold text-lg text-gray-800 border-b pb-1 mb-3 flex items-center"><i class="fas fa-university mr-2 text-red-500"></i> Main Campus Office</h3>
                            <div class="w-full rounded-lg overflow-hidden border border-gray-300 shadow-md">
                                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3858.716540546587!2d121.03909907492651!3d14.728611385773045!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b05657720dfb%3A0x7a7efa0f0a24a9be!2sBestlink%20College%20of%20the%20Philippines!5e0!3m2!1sen!2sph!4v1760611162624!5m2!1sen!2sph"
                                    width="100%"
                                    height="300"
                                    style="border:0;"
                                    allowfullscreen=""
                                    loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade"></iframe>
                            </div>
                        </div>

                        <p class="text-sm text-gray-500 mt-5 text-center italic">
                            Always check office hours before visiting.
                        </p>
                    </div>
                </aside>

                <div class="col-span-1 lg:col-span-2 space-y-8">

                    <div class="bg-white p-5 rounded-xl shadow-lg sticky top-16 z-20 border border-gray-100" data-aos="fade-up">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="relative md:col-span-2">
                                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <input
                                    type="text"
                                    id="searchInput"
                                    placeholder="Search item name, category, or location..."
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-shadow" />
                            </div>

                            <div class="relative">
                                <i class="fas fa-tag absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                <select
                                    id="categoryFilter"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm appearance-none bg-white">
                                    <option value="">All Categories</option>
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
                        </div>
                    </div>

                    <div id="itemsFeed" class="space-y-8 pb-10">
                        <div class="text-center py-10 text-gray-500 italic bg-white rounded-xl shadow-md" id="initialLoader">
                            <i class="fas fa-spinner fa-spin text-4xl mb-3 text-gray-400"></i>
                            <p class="text-lg">Loading lost and found items...</p>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <div id="itemModal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex justify-center items-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-xl w-full max-h-[90vh] overflow-y-auto transform scale-90 opacity-0 transition-all duration-300">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4 border-b pb-3">
                    <h3 class="text-2xl font-bold text-blue-700" id="modalItemName"></h3>
                    <button onclick="closeModal()" class="text-gray-500 hover:text-gray-800 text-2xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <img id="modalImage" src="" alt="Item Image" class="w-full max-h-72 object-contain rounded-lg mb-4 bg-gray-50 border p-2" onerror="this.style.display='none'">

                <div class="p-3 mb-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm font-medium">
                    <i class="fas fa-shield-alt mr-2"></i>
                    To claim this item, please visit the Lost & Found Office. The original detailed description is <b>hidden</b> for security verification purposes.
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <p class="text-gray-500"><strong class="text-gray-700">Category:</strong> <span id="modalCategory" class="font-medium"></span></p>
                    <p class="text-gray-500"><strong class="text-gray-700">Location:</strong> <span id="modalLocation" class="font-medium"></span></p>
                    <p class="text-gray-500"><strong class="text-gray-700">Date Reported:</strong> <span id="modalDate" class="font-medium"></span></p>
                    <p class="text-gray-500"><strong class="text-gray-700">Status:</strong> <span id="modalStatus" class="font-bold text-lg"></span></p>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200">
                    <p class="text-sm font-semibold text-gray-700">Reported By:</p>
                    <p class="text-lg font-bold text-gray-900" id="modalReporterName"></p>
                    <p class="text-sm text-gray-500" id="modalReporterEmail"></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        const allItems = <?= $items_json ?>;
        const itemsFeed = document.getElementById('itemsFeed');
        const searchInput = document.getElementById('searchInput');
        const categoryFilter = document.getElementById('categoryFilter');
        const modal = document.getElementById('itemModal');
        const modalContent = modal.querySelector('div:first-child');

        // Use the CORRECTED base paths defined in PHP
        const BASE_URL_PROFILE = '<?= $base_url_profile ?>';
        const BASE_URL_STUDENT_REPORTS = '<?= $base_url_student_reports ?>'; // ../user/
        const BASE_URL_ADMIN_REPORTS = '<?= $base_url_admin_reports ?>'; // ../admin/


        // Helper function for status badge classes
        function getStatusClasses(status) {
            if (status === 'Approved') return 'bg-blue-100 text-blue-700 border border-blue-200';
            if (status === 'Found') return 'bg-green-100 text-green-700 border border-green-200';
            return 'bg-gray-100 text-gray-700 border border-gray-200';
        }

        // Helper function to get profile picture/icon HTML
        function getProfilePicture(item) {
            // BASE_URL_PROFILE is '../user/profile_picture/'
            const profileImagePath = item.reporter_image ? `${BASE_URL_PROFILE}${item.reporter_image}` : null;

            if (item.reporter_type === 'Admin') {
                return '<div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center"><i class="fas fa-user-tie text-xl text-purple-600"></i></div>';
            }
            if (profileImagePath) {
                // Final Path Example: ../user/profile_picture/1760368071_....jpg
                return `<img src="${profileImagePath}" onerror="this.onerror=null;this.src='../assets/default_profile.jpg';" alt="Profile" class="w-10 h-10 object-cover rounded-full border-2 border-blue-400">`;
            }
            return '<div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center"><i class="fas fa-user-circle text-xl text-blue-600"></i></div>';
        }

        /**
         * Get the correct image path for the item report.
         * The image_path field contains the subdirectory AND filename (e.g., inserted_report/123456_item.jpg)
         */
        function getItemImagePath(item) {
            if (!item.image_path) return null;

            if (item.reporter_type === 'Student') {
                // ../user/ + inserted_report/1234.jpg -> ../user/inserted_report/1234.jpg
                return `${BASE_URL_STUDENT_REPORTS}${item.image_path}`;
            } else if (item.reporter_type === 'Admin') {
                // ../admin/ + inserted_report/1234.jpg -> ../admin/inserted_report/1234.jpg
                return `${BASE_URL_ADMIN_REPORTS}${item.image_path}`;
            }
            return null;
        }


        // --- Rendering Function ---
        function renderItems(data) {
            itemsFeed.innerHTML = ''; // Clear previous content

            if (data.length === 0) {
                itemsFeed.innerHTML = `
                <div class="text-center py-20 text-gray-500 italic bg-white rounded-xl shadow-md" data-aos="fade-in">
                    <i class="fas fa-box-open text-5xl mb-3 text-gray-300"></i>
                    <p class="text-lg font-semibold">No items found matching your criteria.</p>
                    <p class="text-sm">Try adjusting your search or filter.</p>
                </div>
                `;
                return;
            }

            data.forEach(item => {
                const statusClasses = getStatusClasses(item.status);
                let reporterDisplay = item.reporter_name;
                let reporterSubtitle = `Reported by ${item.reporter_type}`;

                if (item.reporter_type === 'Admin') {
                    reporterDisplay = 'Lost & Found Staff';
                    reporterSubtitle = `Posted by: ${item.reporter_name} (${item.reporter_email})`;
                }

                // Get the correctly formatted path
                const itemImagePath = getItemImagePath(item);

                const imageHtml = itemImagePath ?
                    `<img src="${itemImagePath}" alt="${item.item_name}" class="item-image w-full h-auto max-h-96 object-cover bg-gray-50 rounded-lg border-b shadow-sm cursor-pointer" onclick="openModal(${item.id})" onerror="this.onerror=null;this.src='../assets/no_image_placeholder.jpg';">` :
                    `<div class="w-full h-48 bg-gray-200 flex flex-col items-center justify-center text-gray-500 italic rounded-lg border-b">
                    <i class="fas fa-camera text-3xl mb-2"></i>
                    No Image Available
                </div>`;

                const postHtml = `
                <div class="post-card bg-white rounded-xl shadow-xl border border-gray-200" data-item-id="${item.id}" data-aos="fade-up">
                    
                    <div class="p-5 flex items-center space-x-4 border-b border-gray-100">
                        <div class="flex-shrink-0">
                            ${getProfilePicture(item)}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">${reporterDisplay}</p>
                            <p class="text-xs text-gray-500">${reporterSubtitle}</p>
                            <p class="text-xs text-gray-500 italic mt-0.5">Reported on ${item.date_reported}</p>
                        </div>
                    </div>

                    <div class="p-5 pt-0 pb-3">
                        ${imageHtml}
                    </div>

                    <div class="p-5 pt-0">
                        <h2 class="text-2xl font-extrabold text-blue-800 mb-2">${item.item_name}</h2>
                        
                        <p class="text-gray-700 mb-4 text-sm font-medium italic">
                            Claiming: Item details (e.g., contents, condition) are kept confidential for verification at the office.
                        </p>
                        
                        <div class="grid grid-cols-2 gap-y-2 text-sm">
                            <p class="text-gray-600 flex items-center"><i class="fas fa-tag text-blue-500 mr-2 w-4"></i><strong class="font-semibold">Category:</strong> <span class="ml-1">${item.category}</span></p>
                            <p class="text-gray-600 flex items-center"><i class="fas fa-map-marker-alt text-red-500 mr-2 w-4"></i><strong class="font-semibold">Location:</strong> <span class="ml-1">${item.location}</span></p>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100">
                            <span class="inline-flex items-center text-xs font-bold px-3 py-1.5 rounded-full ${statusClasses}">
                                <i class="fas fa-circle text-[6px] mr-1.5"></i>
                                ${item.status.toUpperCase()}
                            </span>
                            <button onclick="openModal(${item.id})" class="text-blue-600 hover:text-blue-800 font-semibold text-sm transition flex items-center">
                                <i class="fas fa-info-circle mr-1"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
                `;
                itemsFeed.innerHTML += postHtml;
            });
        }

        // --- Filtering and Searching Logic (unchanged) ---
        function filterAndRender() {
            const search = searchInput.value.toLowerCase();
            const category = categoryFilter.value;

            const filtered = allItems.filter(item => {
                const searchMatch =
                    item.item_name.toLowerCase().includes(search) ||
                    item.category.toLowerCase().includes(search) ||
                    item.location.toLowerCase().includes(search);

                const categoryMatch = !category || item.category === category;

                return searchMatch && categoryMatch;
            });

            renderItems(filtered);
        }

        searchInput.addEventListener('input', filterAndRender);
        categoryFilter.addEventListener('change', filterAndRender);

        // --- Modal Functions (unchanged logic) ---
        function openModal(itemId) {
            const item = allItems.find(i => i.id == itemId);
            if (!item) return;

            document.getElementById('modalItemName').textContent = item.item_name;
            document.getElementById('modalCategory').textContent = item.category;
            document.getElementById('modalLocation').textContent = item.location;
            document.getElementById('modalDate').textContent = item.date_reported;
            document.getElementById('modalStatus').textContent = item.status.toUpperCase();
            document.getElementById('modalStatus').className = `font-bold text-lg ${getStatusClasses(item.status)}`;

            // Get the correct image path for the modal
            const itemImagePath = getItemImagePath(item);
            const modalImage = document.getElementById('modalImage');

            modalImage.src = itemImagePath || '';
            modalImage.style.display = itemImagePath ? 'block' : 'none';
            // Add a fallback for broken image links
            modalImage.onerror = function() {
                this.onerror = null;
                this.src = '../assets/no_image_placeholder.jpg'; // Assuming you have a default placeholder
                this.style.display = 'block';
            };


            let reporterNameDisplay = item.reporter_name;
            let reporterEmailDisplay = item.reporter_email;

            if (item.reporter_type === 'Admin') {
                reporterNameDisplay = `Lost & Found Office Staff: ${item.reporter_name}`;
                reporterEmailDisplay = `(Email: ${item.reporter_email} - Contact them via the office)`;
            } else {
                reporterNameDisplay = item.reporter_name;
                reporterEmailDisplay = item.reporter_email ? `(${item.reporter_email})` : 'Email not public/available';
            }

            document.getElementById('modalReporterName').textContent = reporterNameDisplay;
            document.getElementById('modalReporterEmail').textContent = reporterEmailDisplay;


            modal.classList.remove('hidden');
            // Trigger transition effects
            setTimeout(() => {
                modalContent.classList.remove('scale-90', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeModal() {
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-90', 'opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300); // Match transition duration
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('initialLoader').style.display = 'none'; // Hide loader
            renderItems(allItems);
            // Initialize AOS for scroll animations
            if (typeof AOS !== 'undefined') {
                AOS.init({
                    duration: 800,
                    once: true,
                });
            } else {
                console.warn("AOS library not found. Animations disabled.");
            }
        });
    </script>
</body>

</html>