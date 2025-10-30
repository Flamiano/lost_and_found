<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Privacy Policy | BCP Lost and Found System</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../../assets/bcp-logo.png" />
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <link rel="stylesheet" href="../../node_modules/@fortawesome/fontawesome-free/css/all.min.css">

    <!-- Google Fonts -->
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
                    Your Privacy, Our Commitment
                </h1>
            </div>

            <div data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                <p class="text-lg sm:text-xl text-blue-100 max-w-2xl mx-auto lg:mx-0">
                    Learn how the BCP Lost and Found System protects your data and ensures transparency.
                </p>
            </div>
        </div>

        <!-- Decorative SVG (unchanged) -->
        <div class="absolute top-0 right-0 transform translate-x-1/3 -translate-y-1/3 opacity-20">
            <svg class="w-64 h-64 text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                <path fill-rule="evenodd"
                    d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                    clip-rule="evenodd" />
            </svg>
        </div>
    </section>


    <!-- Privacy Content Section -->
    <section class="py-16 px-6">
        <div class="max-w-5xl mx-auto bg-white rounded-2xl shadow-xl p-8 md:p-12 border border-gray-100"
            data-aos="fade-up" data-aos-duration="1000">
            <h2 class="text-3xl font-extrabold text-center text-blue-700 mb-10">
                Privacy Policy
            </h2>

            <div class="space-y-6 text-gray-700 leading-relaxed text-justify">
                <p>
                    At <strong>Bestlink College of the Philippines</strong>, we value your privacy and are committed to protecting
                    your personal information when using the <strong>Lost and Found System</strong>. This policy outlines how we
                    collect, use, and safeguard your data to maintain your trust and confidence.
                </p>

                <p>
                    When you report or search for lost and found items, we may collect personal details such as your name, email,
                    and contact number. This information helps us verify item ownership and facilitate communication between users
                    and administrators.
                </p>

                <p>
                    All collected data is handled securely and used only for the purpose of improving the Lost and Found service.
                    We do not share, sell, or disclose personal information to third parties without your consent unless required
                    by law.
                </p>

                <p>
                    We implement strict security measures to prevent unauthorized access, data misuse, or loss. Our team regularly
                    updates the system to ensure your information remains safe and confidential.
                </p>

                <p>
                    You have the right to access, update, or request the deletion of your personal information. To exercise these
                    rights or for privacy-related inquiries, please contact us through the official BCP Helpdesk.
                </p>
            </div>

            <div class="mt-10 text-center">
                <h3 class="text-xl font-semibold text-blue-700 mb-2">Need Assistance?</h3>
                <p>
                    You may reach us at
                    <a href="mailto:helpdesk@bcp.edu.ph" class="text-blue-600 hover:underline">
                        helpdesk@bcp.edu.ph
                    </a>
                    or visit our <a href="../contact.php" class="text-blue-600 hover:underline">Contact Page</a> for support.
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