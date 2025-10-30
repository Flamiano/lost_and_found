<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FAQs | BCP Lost and Found System</title>

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

        .faq-item.active .faq-answer {
            max-height: 500px;
            opacity: 1;
            padding-top: 0.75rem;
        }

        .faq-answer {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: all 0.3s ease;
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
                    <span class="block">Frequently Asked</span>
                    <span class="block text-blue-100">Questions (FAQs)</span>
                </h1>
            </div>

            <div data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                <p class="text-lg sm:text-xl text-blue-100 max-w-2xl mx-auto lg:mx-0">
                    Find quick answers to common questions about reporting, tracking, and retrieving lost or found items in our system.
                </p>
            </div>
        </div>

        <!-- Decorative Gear SVG -->
        <div class="absolute top-0 right-0 transform translate-x-1/4 -translate-y-1/4 opacity-10">
            <svg class="w-72 h-72 text-blue-200" fill="none" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                <path fill="currentColor"
                    d="M487.4 315.7l-37.5-21.6c2.2-14.4 2.2-29.3 0-43.7l37.5-21.6c9.1-5.3 13-16.4 9.1-26.4-11.6-29.3-29.2-56-51.2-78l-1.4-1.4c-7.2-7.2-18-9.2-26.7-4.4l-37.5 21.6c-11.3-9.3-23.8-17.2-37.3-23.3V60.1c0-10.5-6.8-19.8-16.7-22.7-31.3-9.5-64.9-11-97.2 0-9.9 2.9-16.7 12.2-16.7 22.7v43.4c-13.5 6.1-26 14-37.3 23.3l-37.5-21.6c-8.7-4.9-19.5-2.8-26.7 4.4l-1.4 1.4c-22 22-39.6 48.7-51.2 78-3.9 10-0.1 21.1 9.1 26.4l37.5 21.6c-2.2 14.4-2.2 29.3 0 43.7l-37.5 21.6c-9.1 5.3-13 16.4-9.1 26.4 11.6 29.3 29.2 56 51.2 78l1.4 1.4c7.2 7.2 18 9.2 26.7 4.4l37.5-21.6c11.3 9.3 23.8 17.2 37.3 23.3v43.4c0 10.5 6.8 19.8 16.7 22.7 31.3 9.5 64.9 11 97.2 0 9.9-2.9 16.7-12.2 16.7-22.7v-43.4c13.5-6.1 26-14 37.3-23.3l37.5 21.6c8.7 4.9 19.5 2.8 26.7-4.4l1.4-1.4c22-22 39.6-48.7 51.2-78 3.9-10 .1-21.1-9.1-26.4zM256 336c-44.2 0-80-35.8-80-80s35.8-80 80-80 80 35.8 80 80-35.8 80-80 80z" />
            </svg>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 px-6">
        <div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-xl p-8 md:p-12 border border-gray-100"
            data-aos="fade-up" data-aos-duration="1000">

            <h2 class="text-3xl font-extrabold text-center text-blue-700 mb-10">
                Have Questions? We’ve Got Answers!
            </h2>

            <div class="space-y-4">

                <!-- FAQ Item -->
                <div class="faq-item bg-gray-50 border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-gray-100 transition">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-lg text-blue-700">What is the BCP Lost and Found System?</h3>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform duration-300"></i>
                    </div>
                    <div class="faq-answer text-gray-700 mt-2">
                        <p>
                            The BCP Lost and Found System is a digital platform designed for students and staff to report, locate, and
                            recover lost or found items within the Bestlink College of the Philippines campus.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item -->
                <div class="faq-item bg-gray-50 border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-gray-100 transition">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-lg text-blue-700">How do I report a lost item?</h3>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform duration-300"></i>
                    </div>
                    <div class="faq-answer text-gray-700 mt-2">
                        <p>
                            Simply log in to your account, go to the “Report Item” section, and fill out the form with details about
                            your lost item. Our admin will verify your report before it’s posted.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item -->
                <div class="faq-item bg-gray-50 border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-gray-100 transition">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-lg text-blue-700">Can I report an item I found on campus?</h3>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform duration-300"></i>
                    </div>
                    <div class="faq-answer text-gray-700 mt-2">
                        <p>
                            Yes! You can report found items through the system to help reunite them with their rightful owners. You
                            will need to provide clear item details and, if possible, an image.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item -->
                <div class="faq-item bg-gray-50 border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-gray-100 transition">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-lg text-blue-700">Is my personal information secure?</h3>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform duration-300"></i>
                    </div>
                    <div class="faq-answer text-gray-700 mt-2">
                        <p>
                            Yes. We value your privacy and ensure that all data is securely stored and processed in accordance with our
                            <a href="./privacy_policy.php" class="text-blue-600 hover:underline">Privacy Policy</a>.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item -->
                <div class="faq-item bg-gray-50 border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-gray-100 transition">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-lg text-blue-700">Who verifies the reports?</h3>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform duration-300"></i>
                    </div>
                    <div class="faq-answer text-gray-700 mt-2">
                        <p>
                            All item reports are reviewed by the system administrator to ensure authenticity and prevent fraudulent
                            claims before being displayed publicly.
                        </p>
                    </div>
                </div>

                <!-- FAQ Item -->
                <div class="faq-item bg-gray-50 border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-gray-100 transition">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-lg text-blue-700">How can I contact support?</h3>
                        <i class="fas fa-chevron-down text-blue-600 transition-transform duration-300"></i>
                    </div>
                    <div class="faq-answer text-gray-700 mt-2">
                        <p>
                            If you need help, you can reach out via
                            <a href="mailto:helpdesk@bcp.edu.ph" class="text-blue-600 hover:underline">helpdesk@bcp.edu.ph</a>
                            or visit our <a href="../contact.php" class="text-blue-600 hover:underline">Contact Page</a>.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <?php include '../../includes/footer.php'; ?>

    <!-- AOS & FAQ Toggle Script -->
    <script>
        AOS.init();

        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach(item => {
            item.addEventListener('click', () => {
                item.classList.toggle('active');
                const icon = item.querySelector('i');
                icon.classList.toggle('rotate-180');
            });
        });
    </script>
</body>

</html>