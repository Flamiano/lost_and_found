<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BCP | Lost and Found System</title>

    <link rel="icon" type="image/png" href="./assets/bcp-logo.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <style>
        html {
            scroll-behavior: smooth;
        }

        * {
            font-family: "Poppins", sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50">

    <?php include 'includes/navbar.php'; ?>

    <section
        class="relative bg-gradient-to-r from-blue-700 via-blue-800 to-blue-900 text-white overflow-hidden pt-[100px] md:pt-[120px] pb-24">

        <!-- Background Glow -->
        <div class="absolute inset-0">
            <div
                class="absolute top-1/3 left-1/2 transform -translate-x-1/2 w-[400px] sm:w-[600px] h-[400px] sm:h-[600px] bg-blue-400/20 rounded-full blur-3xl">
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-6 lg:px-12 relative z-10 text-center">
            <!-- Logo -->
            <div data-aos="fade-down" data-aos-duration="800" class="flex justify-center mb-6">
                <img src="./assets/bcp-logo.png" alt="BCP Logo" class="w-20 sm:w-24 drop-shadow-lg">
            </div>

            <div data-aos="fade-up" data-aos-duration="800">
                <h1 class="text-xl md:text-4xl lg:text-6xl font-extrabold leading-tight mb-4">
                    <span class="block">Empowering a Centralized</span>
                    <span class="block text-blue-100">Lost and Found System</span>
                </h1>
            </div>

            <div data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                <p class="text-base md:text-lg lg:text-xl text-blue-100 max-w-3xl mx-auto">
                    A secure and digital platform designed to help BSIT students and staff of
                    <span class="font-semibold">Bestlink College of the Philippines, Quezon City</span> easily
                    report, track, and recover lost belongings through a unified system.
                </p>
            </div>

            <!-- Buttons -->
            <div data-aos="fade-up" data-aos-delay="400" data-aos-duration="1000"
                class="flex flex-col sm:flex-row justify-center gap-4 mt-10 flex-wrap">

                <a href="./pages/reports.php"
                    class="bg-white text-blue-700 px-6 py-3 rounded-lg font-semibold text-base shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all duration-200 flex items-center justify-center">
                    <i class="fas fa-box-open mr-2"></i> Report Lost Item
                </a>

                <a href="./pages/item.php"
                    class="bg-blue-800 text-white px-6 py-3 rounded-lg font-semibold text-base shadow-lg hover:bg-blue-900 hover:-translate-y-1 transition-all duration-200 flex items-center justify-center">
                    <i class="fas fa-hand-holding-box mr-2"></i> View Found Items
                </a>
            </div>
        </div>

        <!-- Decorative Rings -->
        <div class="absolute top-0 right-0 transform translate-x-1/4 -translate-y-1/4 opacity-10">
            <svg class="w-80 h-80 text-blue-200" viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg" fill="none"
                stroke="currentColor" stroke-width="12">
                <circle cx="250" cy="250" r="200" />
                <circle cx="250" cy="250" r="140" />
                <circle cx="250" cy="250" r="80" />
            </svg>
        </div>
    </section>

    <!-- ABOUT SECTION WITH ANIMATION -->
    <section class="py-20 bg-gradient-to-b from-white to-blue-50" data-aos="fade-up" data-aos-duration="1000">
        <div class="max-w-6xl mx-auto px-5 sm:px-6 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-800 mb-4" data-aos="zoom-in" data-aos-delay="100">
                About Our Website
            </h2>
            <p class="text-gray-600 max-w-2xl mx-auto text-sm sm:text-base mb-12" data-aos="fade-up" data-aos-delay="200">
                The Lost and Found System at Bestlink College of the Philippines, Quezon City was developed to streamline the process of
                reporting, tracking, and recovering lost items with ease, efficiency, and transparency.
            </p>

            <!-- Features -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
                <!-- Box 1 -->
                <div
                    class="group bg-white p-8 rounded-2xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border-t-4 border-blue-600"
                    data-aos="fade-right" data-aos-duration="800">
                    <div
                        class="text-blue-600 text-4xl mb-4 flex justify-center items-center group-hover:scale-110 transition-transform duration-300 animate-bounce-slow">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-gray-800">Item Tracking</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Easily report and locate lost or found items across the campus, with live updates and real-time tracking.
                    </p>
                </div>

                <!-- Box 2 -->
                <div
                    class="group bg-white p-8 rounded-2xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border-t-4 border-blue-600"
                    data-aos="zoom-in" data-aos-duration="800" data-aos-delay="100">
                    <div
                        class="text-blue-600 text-4xl mb-4 flex justify-center items-center group-hover:scale-110 transition-transform duration-300 animate-bounce-slow">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-gray-800">Centralized Record</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        All reports are securely stored in one database, ensuring transparency and easy management for administrators.
                    </p>
                </div>

                <!-- Box 3 -->
                <div
                    class="group bg-white p-8 rounded-2xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border-t-4 border-blue-600"
                    data-aos="fade-left" data-aos-duration="800" data-aos-delay="200">
                    <div
                        class="text-blue-600 text-4xl mb-4 flex justify-center items-center group-hover:scale-110 transition-transform duration-300 animate-bounce-slow">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-gray-800">Campus Collaboration</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Encourages integrity, cooperation, and responsibility among students, staff, and faculty within the campus.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 sm:py-20 bg-blue-700 text-white relative">
        <div
            class="max-w-5xl mx-auto px-5 sm:px-6 text-center"
            data-aos="fade-up"
            data-aos-duration="1000"
            data-aos-offset="200">
            <h2
                class="text-2xl sm:text-3xl md:text-4xl font-bold mb-5"
                data-aos="zoom-in"
                data-aos-delay="100">
                How Our Website Helps Bestlink College
            </h2>

            <p
                class="text-blue-100 text-sm sm:text-base md:text-lg leading-relaxed"
                data-aos="fade-up"
                data-aos-delay="300">
                The Lost and Found System acts as a modern solution to campus challenges by simplifying the recovery process for
                lost items. It ensures that every member of the community can report and retrieve belongings with fairness and
                transparency.
                <br><br>
                This innovation strengthens trust, accountability, and digital progress — supporting Bestlink College of the
                Philippines’ mission to create a responsible and technology-driven academic environment.
            </p>
        </div>
    </section>


    <?php include 'includes/footer.php'; ?>

    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true
        });
    </script>

</body>

</html>