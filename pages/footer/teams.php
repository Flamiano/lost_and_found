<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Our Team | BCP Lost and Found System</title>

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

        .team-card:hover img {
            transform: scale(1.1);
        }

        .team-card img {
            transition: all 0.4s ease;
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
                    <span class="block">Meet the Team</span>
                    <span class="block text-blue-100">Behind the Lost and Found System</span>
                </h1>
            </div>

            <div data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                <p class="text-lg sm:text-xl text-blue-100 max-w-2xl mx-auto lg:mx-0">
                    Get to know the passionate developers and designers behind the BCP Lost and Found System,
                    working together to make item recovery efficient and reliable for the Bestlink community.
                </p>
            </div>
        </div>

        <!-- Decorative Circular SVG -->
        <div class="absolute top-0 right-0 transform translate-x-1/3 -translate-y-1/3 opacity-20">
            <svg class="w-72 h-72 text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                <path fill-rule="evenodd"
                    d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                    clip-rule="evenodd" />
            </svg>
        </div>
    </section>



    <!-- Team Section -->
    <section class="py-16 px-6 bg-gray-50">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-extrabold text-blue-700 text-center mb-12" data-aos="fade-up">
                Our Development Team
            </h2>

            <!-- Team Grid: horizontal row on desktop -->
            <div class="flex flex-col sm:flex-row justify-center items-stretch gap-6">

                <!-- Team Member Card -->
                <div class="team-card bg-white rounded-2xl shadow-md p-6 flex flex-col items-center text-center flex-1 max-w-xs hover:shadow-xl transition-shadow duration-300" data-aos="fade-up" data-aos-delay="100">
                    <i class="fas fa-user text-6xl text-blue-600 mb-4"></i>
                    <h3 class="text-md font-semibold text-blue-700">Flamiano</h3>
                    <p class="text-gray-600 text-sm mb-6">(Programmer)</p>

                    <hr class="w-10/12 border-gray-300 mb-4">

                    <!-- Social Links -->
                    <div class="flex justify-center items-center gap-4">
                        <a href="https://web.facebook.com/roel.flamiano.2025" target="_blank" class="text-blue-600 text-3xl hover:text-blue-800">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="https://github.com/Flamiano" target="_blank" class="text-gray-800 text-3xl hover:text-gray-900">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="https://johnroelflamiano2025.vercel.app/" target="_blank" class="text-purple-600 text-3xl hover:text-purple-800">
                            <i class="fas fa-link"></i>
                        </a>
                    </div>
                </div>

                <div class="team-card bg-white rounded-2xl shadow-md p-6 flex flex-col items-center text-center flex-1 max-w-xs hover:shadow-xl transition-shadow duration-300" data-aos="fade-up" data-aos-delay="200">
                    <i class="fas fa-user text-6xl text-blue-600 mb-4"></i>
                    <h3 class="text-md font-semibold text-blue-700">Dadivas</h3>
                    <p class="text-gray-600 text-sm mb-6">Quality Assurance</p>
                    <hr class="w-10/12 border-gray-300 mb-4">

                    <div class="flex justify-center items-center gap-2">
                        <a href="https://web.facebook.com/ShyShyShy224" target="_blank" class="text-blue-600 text-3xl hover:text-blue-800">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="https://github.com/JayMark102223" target="_blank" class="text-gray-800 text-3xl hover:text-gray-900">
                            <i class="fab fa-github"></i>
                        </a>
                    </div>
                </div>

                <div class="team-card bg-white rounded-2xl shadow-md p-6 flex flex-col items-center text-center flex-1 max-w-xs hover:shadow-xl transition-shadow duration-300" data-aos="fade-up" data-aos-delay="400">
                    <i class="fas fa-user text-6xl text-blue-600 mb-4"></i>
                    <h3 class="text-md font-semibold text-blue-700">Realto</h3>
                    <p class="text-gray-600 text-sm mb-6">Support Staff</p>
                    <hr class="w-10/12 border-gray-300 mb-4">
                    <a href="https://web.facebook.com/Magas.Russel.16" target="_blank" class="text-blue-600 text-3xl hover:text-blue-800">
                        <i class="fab fa-facebook"></i>
                    </a>
                </div>

                <div class="team-card bg-white rounded-2xl shadow-md p-6 flex flex-col items-center text-center flex-1 max-w-xs hover:shadow-xl transition-shadow duration-300" data-aos="fade-up" data-aos-delay="500">
                    <i class="fas fa-user text-6xl text-blue-600 mb-4"></i>
                    <h3 class="text-md font-semibold text-blue-700">Barte</h3>
                    <p class="text-gray-600 text-sm mb-6">UI/UX Designer</p>
                    <hr class="w-10/12 border-gray-300 mb-4">
                    <a href="https://web.facebook.com/jomarbarte0411" target="_blank" class="text-blue-600 text-3xl hover:text-blue-800">
                        <i class="fab fa-facebook"></i>
                    </a>
                </div>

                <div class="team-card bg-white rounded-2xl shadow-md p-6 flex flex-col items-center text-center flex-1 max-w-xs hover:shadow-xl transition-shadow duration-300" data-aos="fade-up" data-aos-delay="300">
                    <i class="fas fa-user text-6xl text-blue-600 mb-4"></i>
                    <h3 class="text-md font-semibold text-blue-700">Cañaveral</h3>
                    <p class="text-gray-600 text-sm mb-6">Documentary</p>
                    <hr class="w-10/12 border-gray-300 mb-4">
                    <a href="https://web.facebook.com/viancxa" target="_blank" class="text-blue-600 text-3xl hover:text-blue-800">
                        <i class="fab fa-facebook"></i>
                    </a>
                </div>

            </div>
        </div>
    </section>




    <?php include '../../includes/footer.php'; ?>

    <!-- AOS Init -->
    <script>
        AOS.init();
    </script>
</body>

</html>