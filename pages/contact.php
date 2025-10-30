<?php
session_start();
// NOTE: Assuming db.php is not strictly needed for this page unless you fetch logged-in user data.
// If you are fetching data from a database, uncomment the line below:
// require '../config/db.php'; 

// Load PHPMailer
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 💡 CONFIGURATION: CHANGE THESE VALUES!
$recipient_email = 'your-admin-email@example.com'; // <--- 🚨 Change this to your actual email address
$recipient_name = 'Lost and Found Admin';          // <--- Recipient Name
$smtp_username = 'viavanta.web@gmail.com';          // <--- Your Gmail/SMTP username
$smtp_password = 'qsqoycanowkvgzxw';              // <--- Your App Password (NOT your regular password!)
// 💡 END CONFIGURATION

// --- PHP Contact Form Submission Handler ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['name'])) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $_SESSION['alert_error'] = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // The HTML input type="email" provides basic client-side validation, 
        // but PHP handles the secure server-side validation.
        $_SESSION['alert_error'] = "Invalid email format. Please check the address.";
    } else {
        $mail = new PHPMailer(true);

        try {
            // Server settings (Your SMTP Configuration)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_username;
            $mail->Password   = $smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom($smtp_username, 'Lost and Found Contact Form');
            $mail->addAddress($recipient_email, $recipient_name);
            $mail->addReplyTo($email, $name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'NEW CONTACT FORM MESSAGE: ' . $name;

            // Visually appealing email body (Unchanged from previous response)
            $email_body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;'>
                <div style='background-color: #0047b3; color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>New Contact Inquiry</h2>
                </div>
                <div style='padding: 20px;'>
                    <p style='margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px;'>You have received a new message from the Lost and Found Contact Form.</p>
                    
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; width: 30%; color: #0047b3;'>Name:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #eee;'>" . htmlspecialchars($name) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border-bottom: 1px solid #eee; font-weight: bold; color: #0047b3;'>Email:</td>
                            <td style='padding: 8px; border-bottom: 1px solid #eee;'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td>
                        </tr>
                    </table>

                    <h3 style='margin-top: 20px; color: #0047b3;'>Message:</h3>
                    <div style='background-color: #f8f8f8; padding: 15px; border-radius: 5px; border-left: 3px solid #0047b3; white-space: pre-wrap;'>
                        " . htmlspecialchars($message) . "
                    </div>

                    <p style='margin-top: 30px; text-align: center; color: #777; font-size: 0.9em;'>Please use the 'Reply' button in your email client to respond to the user.</p>
                </div>
            </div>";

            $mail->Body = $email_body;
            $mail->AltBody = "New Contact Form Message from " . $name . " (" . $email . ")\n\nMessage:\n" . $message;

            $mail->send();
            $_SESSION['alert_success'] = "Thank you! Your message has been sent successfully. We will get back to you shortly.";
        } catch (Exception $e) {
            $_SESSION['alert_error'] = "Message could not be sent. Mailer Error: " . $mail->ErrorInfo;
        }
    }
    // Redirect to clear POST data and display message using SweetAlert in the client side
    header("Location: contact.php");
    exit();
}
// Removed the display_session_message PHP function as it's replaced by JS/SweetAlert.
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact | Lost and Found System</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../assets/bcp-logo.png" />
    <link rel="stylesheet" href="../node_modules/@fortawesome/fontawesome-free/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * {
            font-family: "Poppins", sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50">

    <?php include '../includes/navbar.php'; ?>

    <section class="relative bg-gradient-to-r from-blue-700 via-blue-800 to-blue-900 text-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 lg:px-12 py-32 sm:py-40 relative z-10 text-center lg:text-left">
            <div data-aos="fade-up" data-aos-duration="800">
                <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold leading-tight mb-4">
                    <span class="block">Get in Touch With Us</span>
                    <span class="block text-blue-100">We’re Here to Help You</span>
                </h1>
            </div>

            <div data-aos="fade-up" data-aos-delay="200" data-aos-duration="1000">
                <p class="text-lg sm:text-xl text-blue-100 max-w-2xl mx-auto lg:mx-0">
                    Have questions or concerns about your lost or found items? Reach out to us and our team will assist you as soon as possible.
                </p>
            </div>
        </div>

        <div class="absolute top-0 right-0 transform translate-x-1/4 -translate-y-1/4 opacity-10">
            <svg class="w-72 h-72 text-blue-200" fill="none" viewBox="0 0 512 512">
                <path fill="currentColor"
                    d="M487.4 315.7l-37.5-21.6c2.2-14.4 2.2-29.3 0-43.7l37.5-21.6c9.1-5.3 13-16.4 9.1-26.4-11.6-29.3-29.2-56-51.2-78l-1.4-1.4c-7.2-7.2-18-9.2-26.7-4.4l-37.5 21.6c-11.3-9.3-23.8-17.2-37.3-23.3V60.1c0-10.5-6.8-19.8-16.7-22.7-31.3-9.5-64.9-11-97.2 0-9.9 2.9-16.7 12.2-16.7 22.7v43.4c-13.5 6.1-26 14-37.3 23.3l-37.5-21.6c-8.7-4.9-19.5-2.8-26.7 4.4l-1.4 1.4c-22 22-39.6 48.7-51.2 78-3.9 10-0.1 21.1 9.1 26.4l37.5 21.6c-2.2 14.4-2.2 29.3 0 43.7l-37.5 21.6c-9.1 5.3-13 16.4-9.1 26.4 11.6 29.3 29.2 56 51.2 78l1.4 1.4c7.2 7.2 18 9.2 26.7 4.4l37.5-21.6c11.3 9.3 23.8 17.2 37.3 23.3v43.4c0 10.5 6.8 19.8 16.7 22.7 31.3 9.5 64.9 11 97.2 0 9.9-2.9 16.7-12.2 16.7-22.7v-43.4c13.5-6.1 26-14 37.3-23.3l37.5 21.6c8.7 4.9 19.5 2.8 26.7-4.4l1.4-1.4c22-22 39.6-48.7 51.2-78 3.9-10 .1-21.1-9.1-26.4zM256 336c-44.2 0-80-35.8-80-80s35.8-80 80-80 80 35.8 80 80-35.8 80-80 80z" />
            </svg>
        </div>
    </section>

    <section class="py-16 bg-white overflow-hidden">
        <div class="max-w-6xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-10 items-start">

            <div data-aos="fade-right" data-aos-duration="1000">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-800 mb-6">Contact Information</h2>
                <p class="text-gray-600 mb-6 leading-relaxed">
                    Whether you found a lost item or are searching for something important, don’t hesitate to contact us.
                    Our staff will respond promptly to your inquiries and reports.
                </p>

                <ul class="space-y-4 text-gray-700">
                    <li class="flex items-center gap-3">
                        <i class="fas fa-map-marker-alt text-blue-600 text-lg"></i>
                        <span>Bestlink College of the Philippines, Quirino Highway, Quezon City</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-envelope text-blue-600 text-lg"></i>
                        <a href="mailto:support@bcp.edu.ph" class="hover:text-blue-700">support@bcp.edu.ph</a>
                    </li>
                    <li class="flex items-center gap-3">
                        <i class="fas fa-phone-alt text-blue-600 text-lg"></i>
                        <span>(+63) 912 345 6789</span>
                    </li>
                </ul>

                <div class="mt-8 rounded-lg overflow-hidden shadow-lg" data-aos="fade-up">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3859.510479149356!2d121.04038697510182!3d14.735177685762092!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b1dc97e2e6d9%3A0xd84a9b9786d0cc3a!2sBestlink%20College%20of%20the%20Philippines!5e0!3m2!1sen!2sph!4v1700000000000"
                        width="100%" height="250" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>

            <div data-aos="fade-left" data-aos-duration="1000" class="bg-blue-50 rounded-2xl p-8 shadow-lg">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Send Us a Message</h2>

                <form action="contact.php" method="POST" class="space-y-4">
                    <div>
                        <label for="name" class="block text-gray-700 font-medium mb-1">Full Name</label>
                        <input type="text" id="name" name="name" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="email" class="block text-gray-700 font-medium mb-1">Email</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="message" class="block text-gray-700 font-medium mb-1">Message</label>
                        <textarea id="message" name="message" rows="5" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full bg-blue-700 hover:bg-blue-800 text-white font-semibold py-3 rounded-lg shadow-md transition-all duration-300">
                        <i class="fas fa-paper-plane mr-2"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true,
            duration: 1000
        });

        // SWEETALERT2 INTEGRATION SCRIPT
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['alert_success'])): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Message Sent! 🎉',
                    text: '<?php echo htmlspecialchars($_SESSION['alert_success']); ?>',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition'
                    }
                });
                <?php unset($_SESSION['alert_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['alert_error'])): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: '<?php echo htmlspecialchars($_SESSION['alert_error']); ?>',
                    confirmButtonText: 'Try Again',
                    customClass: {
                        confirmButton: 'bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition'
                    }
                });
                <?php unset($_SESSION['alert_error']); ?>
            <?php endif; ?>
        });
    </script>

</body>

</html>