<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer | Lost and Found System</title>
</head>

<body>
    <footer class="bg-gray-900 text-white px-6 md:px-16 py-12 border-t border-gray-700">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-start">
            <!-- LEFT -->
            <div class="flex flex-col gap-6">
                <div class="flex items-center gap-4">
                    <?php
                    $basePath = "";
                    if (strpos($_SERVER['PHP_SELF'], '/pages/footer/') !== false) {
                        $basePath = "../../";
                    } elseif (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
                        $basePath = "../";
                    } else {
                        $basePath = "./";
                    }
                    ?>
                    <img src="<?php echo $basePath; ?>assets/bcp-logo.png" alt="BCP Logo" class="w-12 h-12 rounded-lg">
                    <h1 class="text-2xl font-extrabold tracking-wide text-white">BCP Lost & Found</h1>
                </div>

                <p class="text-sm text-gray-300 leading-relaxed max-w-lg">
                    The BCP Lost and Found System provides a centralized, efficient, and transparent platform for reporting and retrieving lost items within BCP.
                </p>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-6 text-sm pt-2">
                    <!-- About -->
                    <div>
                        <h2 class="font-semibold mb-2">About</h2>
                        <ul class="space-y-1">
                            <li><a href="<?php echo $basePath; ?>pages/about.php" class="hover:text-blue-400">Our Story</a></li>
                            <li><a href="<?php echo $basePath; ?>pages/footer/teams.php" class="hover:text-blue-400">Team</a></li>
                            <li><a href="<?php echo $basePath; ?>pages/reports.php" class="hover:text-blue-400">Services</a></li>
                        </ul>
                    </div>

                    <!-- Legal -->
                    <div>
                        <h2 class="font-semibold mb-2">Legal</h2>
                        <ul class="space-y-1">
                            <li><a href="<?php echo $basePath; ?>pages/footer/privacy_policy.php" class="hover:text-blue-400">Privacy Policy</a></li>
                            <li><a href="<?php echo $basePath; ?>pages/footer/terms_conditions.php" class="hover:text-blue-400">Terms & Conditions</a></li>
                        </ul>
                    </div>

                    <!-- Support -->
                    <div>
                        <h2 class="font-semibold mb-2">Support</h2>
                        <ul class="space-y-1">
                            <li><a href="<?php echo $basePath; ?>pages/contact.php" class="hover:text-blue-400">Contact Us</a></li>
                            <li><a href="<?php echo $basePath; ?>pages/footer/faqs.php" class="hover:text-blue-400">FAQs</a></li>
                        </ul>
                    </div>
                </div>

                <p class="text-xs text-gray-400 pt-4">&copy; <?php echo date('Y'); ?> Bestlink College of the Philippines. All rights reserved.</p>
            </div>

            <!-- Map -->
            <div class="w-full h-[300px]">
                <iframe src="https://www.google.com/maps/embed?pb=!4v1752911672294!6m8!1m7!1stKVhwnDAMUbOWsTee6Yitg!2m2!1d14.72662985390938!2d121.0373473614249!3f254.22949!4f0!5f0.7820865974627469 "
                    class=" w-full h-full rounded-lg border-0" loading="lazy"></iframe>
            </div>
        </div>
    </footer>
</body>

</html>