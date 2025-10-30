<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Terms & Conditions | BCP Lost and Found System</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../../assets/bcp-logo.png" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-900">
    <?php include '../../includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-r from-blue-700 via-blue-800 to-blue-900 text-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-12 py-32 sm:py-40 relative z-10 text-center lg:text-left">
            <div data-aos="fade-up" data-aos-duration="800">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold leading-tight mb-4">
                    Terms & Conditions
                </h1>
            </div>

            <div data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                <p class="text-lg sm:text-xl text-blue-100 max-w-2xl mx-auto lg:mx-0">
                    Understand your rights and responsibilities when using the BCP Lost and Found System.
                </p>
            </div>
        </div>

        <!-- Decorative SVG (kept unchanged) -->
        <div class="absolute top-0 right-0 transform translate-x-1/3 -translate-y-1/3 opacity-20">
            <svg class="w-64 h-64 text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                <path fill-rule="evenodd"
                    d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                    clip-rule="evenodd" />
            </svg>
        </div>
    </section>


    <!-- Terms Content Section -->
    <section class="py-16 px-6">
        <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-xl p-8 md:p-12 border border-gray-100"
            data-aos="fade-up" data-aos-duration="1000">

            <h2 class="text-3xl font-extrabold text-center text-blue-700 mb-10">
                Terms and Conditions
            </h2>

            <div class="space-y-6 text-gray-700 leading-relaxed text-justify">
                <p>
                    Welcome to the <strong>Bestlink College of the Philippines Lost and Found System</strong>. By using this
                    platform, you agree to comply with and be bound by the following terms and conditions. Please read them
                    carefully before accessing or using the system.
                </p>

                <h3 class="text-xl font-semibold text-blue-700 mt-6 mb-2">1. System Usage</h3>
                <p>
                    The Lost and Found System is designed solely for the Bestlink College community. Users must provide accurate
                    and truthful information when submitting lost or found item reports. Misuse of the platform, including
                    submitting false reports or fraudulent claims, is strictly prohibited.
                </p>

                <h3 class="text-xl font-semibold text-blue-700 mt-6 mb-2">2. Data Collection</h3>
                <p>
                    The system may collect basic personal information such as name, email address, and contact number for the
                    purpose of verification and communication. All data is handled in accordance with our
                    <a href="./privacy_policy.php" class="text-blue-600 hover:underline">Privacy Policy</a>.
                </p>

                <h3 class="text-xl font-semibold text-blue-700 mt-6 mb-2">3. Responsibility of Users</h3>
                <p>
                    Users are responsible for maintaining the confidentiality of their account credentials and ensuring the
                    accuracy of the information they provide. Any attempt to impersonate another person or manipulate item records
                    may lead to disciplinary actions by the institution.
                </p>

                <h3 class="text-xl font-semibold text-blue-700 mt-6 mb-2">4. Administration Rights</h3>
                <p>
                    System administrators reserve the right to verify, approve, or decline any lost or found item reports that do
                    not meet verification standards or appear suspicious. The administration may also modify, suspend, or
                    terminate access to the system for violations of these terms.
                </p>

                <h3 class="text-xl font-semibold text-blue-700 mt-6 mb-2">5. Limitation of Liability</h3>
                <p>
                    While the system aims to assist in item recovery, <strong>Bestlink College of the Philippines</strong> shall
                    not be held liable for any loss, damage, or disputes that may arise from user interactions or false reporting.
                    Users are encouraged to report any misuse or security concerns to the system administrators immediately.
                </p>

                <h3 class="text-xl font-semibold text-blue-700 mt-6 mb-2">6. Policy Updates</h3>
                <p>
                    These terms and conditions may be updated periodically to improve service or comply with institutional
                    policies. Users are advised to review this page regularly to stay informed of any changes.
                </p>

                <p class="pt-4">
                    By continuing to use the <strong>BCP Lost and Found System</strong>, you acknowledge that you have read,
                    understood, and agreed to the above terms and conditions.
                </p>
            </div>

            <div class="mt-10 text-center">
                <h3 class="text-xl font-semibold text-blue-700 mb-2">Questions or Concerns?</h3>
                <p>
                    You may reach us at
                    <a href="mailto:helpdesk@bcp.edu.ph" class="text-blue-600 hover:underline">
                        helpdesk@bcp.edu.ph
                    </a>
                    or visit our
                    <a href="../contact.php" class="text-blue-600 hover:underline">
                        Contact Page
                    </a>
                    for assistance.
                </p>
            </div>
        </div>
    </section>

    <?php include '../../includes/footer.php'; ?>

    <!-- Initialize AOS -->
    <script>
        AOS.init();
    </script>
</body>

</html>