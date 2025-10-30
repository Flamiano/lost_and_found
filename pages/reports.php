<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Lost and Found System</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800">

    <?php include '../includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-r from-blue-700 via-blue-800 to-blue-900 text-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-12 py-32 sm:py-40 relative z-10 text-center lg:text-left">
            <div data-aos="fade-up" data-aos-duration="800">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold leading-tight mb-4">
                    <span class="block">Lost Item Reporting Guide</span>
                    <span class="block text-blue-100">How to Submit a Report</span>
                </h1>
            </div>

            <div data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                <p class="text-lg sm:text-xl text-blue-100 max-w-2xl mx-auto lg:mx-0">
                    Learn the proper steps to report your lost or found items through our Lost and Found System.
                    Follow the guidelines below to ensure smooth and verified processing of your report.
                </p>
            </div>
        </div>

        <!-- Decorative SVG -->
        <div class="absolute top-0 right-0 transform translate-x-1/4 -translate-y-1/4 opacity-10">
            <svg class="w-80 h-80 text-blue-200" fill="none" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                <path fill="currentColor"
                    d="M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256 256-114.6 256-256S397.4 0 256 0zm0 96c26.5 0 48 21.5 48 48s-21.5 48-48 48-48-21.5-48-48 21.5-48 48-48zm96 320H160v-32c0-35.3 28.7-64 64-64h64c35.3 0 64 28.7 64 64v32z" />
            </svg>
        </div>
    </section>

    <!-- Instructions Section -->
    <section class="py-16 px-6 lg:px-20 max-w-6xl mx-auto text-center">
        <div data-aos="fade-up" class="max-w-3xl mx-auto">
            <h2 class="text-3xl font-bold text-blue-800 mb-6">Step-by-Step Guide to Reporting Lost Items</h2>
            <p class="text-gray-600 mb-10">
                Follow these simple steps to report a lost item in our system. This ensures that your report
                is accurate, verifiable, and can be quickly matched with any found items submitted by others.
            </p>

            <!-- Steps -->
            <div class="text-left space-y-6">
                <div class="flex items-start gap-4">
                    <div class="bg-blue-700 text-white w-8 h-8 flex items-center justify-center rounded-full font-semibold">1</div>
                    <p><strong>Login or Register:</strong> Before you can create a report, ensure you have an account. This helps us verify your identity and track submissions properly.</p>
                </div>

                <div class="flex items-start gap-4">
                    <div class="bg-blue-700 text-white w-8 h-8 flex items-center justify-center rounded-full font-semibold">2</div>
                    <p><strong>Navigate to the Report Page:</strong> Go to the Reports section in the website’s menu and click “Add Lost Item.”</p>
                </div>

                <div class="flex items-start gap-4">
                    <div class="bg-blue-700 text-white w-8 h-8 flex items-center justify-center rounded-full font-semibold">3</div>
                    <p><strong>Fill in Details:</strong> Provide all necessary information such as item name, description, location last seen, and any unique identifiers (color, brand, serial number, etc.).</p>
                </div>

                <div class="flex items-start gap-4">
                    <div class="bg-blue-700 text-white w-8 h-8 flex items-center justify-center rounded-full font-semibold">4</div>
                    <p><strong>Attach Evidence (Optional):</strong> Upload an image or proof if available. This increases the chances of identifying your item quickly.</p>
                </div>

                <div class="flex items-start gap-4">
                    <div class="bg-blue-700 text-white w-8 h-8 flex items-center justify-center rounded-full font-semibold">5</div>
                    <p><strong>Submit and Wait for Verification:</strong> After submitting, your report will be reviewed by our admin team to ensure authenticity before being published.</p>
                </div>
            </div>

            <!-- Rules Section -->
            <div class="mt-14 text-left">
                <h3 class="text-2xl font-semibold text-blue-800 mb-4">Reporting Rules and Guidelines</h3>
                <ul class="list-disc list-inside text-gray-600 space-y-2">
                    <li>Only report items that are truly lost or found within the school premises.</li>
                    <li>Provide accurate and truthful information; false reporting may result in account restrictions.</li>
                    <li>Respect others' privacy — do not include sensitive personal information in your report.</li>
                    <li>Reports must follow school policies and will be moderated by the admin before being visible to the public.</li>
                </ul>
            </div>

            <!-- Call to Action -->
            <div class="mt-14" data-aos="fade-up" data-aos-delay="200">
                <h3 class="text-2xl font-semibold text-blue-800 mb-3">Ready to Report a Lost Item?</h3>
                <p class="text-gray-600 mb-6">Click below to start your report and help our community recover lost belongings.</p>

                <a href="../auth/login.php"
                    class="px-8 py-3 bg-blue-700 hover:bg-blue-800 text-white font-medium rounded-lg shadow-md transition">
                    <i class="fas fa-plus-circle mr-2"></i> Add Lost Item Report
                </a>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>

</html>