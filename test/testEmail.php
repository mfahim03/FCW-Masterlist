<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer autoloader
require '../vendor/autoload.php'; // Adjust path if needed

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = '10.23.1.228';     // Hostname
    $mail->Port = 25;                // Port number (25 for non-auth internal servers)
    $mail->SMTPAuth = false;         // No SMTP authentication
    $mail->SMTPAutoTLS = false;
    $mail->SMTPSecure = false;       // No encryption

    $mail->setFrom('bizalert.Noreply@my.alps.com', 'FCW Masterlist Test');
    $mail->addAddress('fahim.mfza@outlook.com', 'Fahim'); // Replace with your email

    $mail->isHTML(true);
    $mail->Subject = 'Test Email from PHPMailer';
    $mail->Body    = 'This is a <b>test email</b> sent from PHPMailer.';
    $mail->AltBody = 'This is a plain-text version of the test email.';

    if ($mail->send()) {
        echo 'Email sent successfully.';
    } else {
        echo 'Email not sent.';
    }

} catch (Exception $e) {
    echo "Message could not be sent. Error: {$mail->ErrorInfo}";
}
?>
