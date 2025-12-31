<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendPassportExpiryAlert($employeesExpiringSoon) {
    if (empty($employeesExpiringSoon)) {
        return false;
    }

    $mail = new PHPMailer;

    $mail->SMTPDebug = 0;
    $mail->isSMTP();  
    $mail->Host = '10.23.1.228';
    $mail->SMTPAuth = false;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 25;
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    $mail->SMTPAutoTLS = false;
    $mail->SMTPSecure = false;

    $mail->Username = 'bizalert.Noreply@my.alps.com';
    $mail->Password = 'Abc12345678';

    // Recipients
    $mail->setFrom('bizalert.Noreply@my.alps.com', 'FCW Passport Expiry Alert');
    $mail->addAddress('fahim.mfza@outlook.com', '');
    // Add more recipients if needed
    // $mail->addAddress('hr@my.alps.com', 'HR Manager');
    // $mail->addCC('email@.com', 'position');

    $pageLink = "http://10.23.6.223:106";

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Passport Expiry Alert - ' . count($employeesExpiringSoon) . ' Employee(s) Expiring Soon';

    $emailBody = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
            .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f8f9fa; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; background-color: white; }
            th { background-color: #343a40; color: white; padding: 12px; text-align: left; border: 1px solid #ddd; }
            td { padding: 12px; border: 1px solid #ddd; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            .footer { margin-top: 20px; padding: 15px; background-color: #343a40; color: white; text-align: center; font-size: 12px; }
            .warning { color: #ffc107; font-weight: bold; }
            .expired { color: #dc3545; font-weight: bold; }
            .alert-icon { font-size: 48px; margin-bottom: 10px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>Passport Expiry Alert</h2>
        </div>
        <div>
            <p>Page link: <a href=" '. $pageLink . ' " target="_blank">http://10.23.6.223:106</a></p>
        </div>
        <div class="content">
            <p>The following employee(s) have passport(s) expiring within 12 months or already expired:</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Employee No</th>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th>Nationality</th>
                        <th>Passport Number</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($employeesExpiringSoon as $employee) {
        $statusClass = $employee['status'] === 'Expired' ? 'expired' : 'warning';
        $emailBody .= '
                    <tr>
                        <td>' . htmlspecialchars($employee['employee_no']) . '</td>
                        <td>' . htmlspecialchars($employee['name']) . '</td>
                        <td>' . htmlspecialchars($employee['department']) . '</td>
                        <td>' . htmlspecialchars($employee['nationality']) . '</td>
                        <td>' . htmlspecialchars($employee['passport_no']) . '</td>
                        <td>' . htmlspecialchars($employee['expiry_date']) . '</td>
                        <td class="' . $statusClass . '">' . htmlspecialchars($employee['status']) . '</td>
                    </tr>';
    }
    
    $emailBody .= '
                </tbody>
            </table>
        </div>
        <div class="footer">
            <p>This is an automated message from FCW Masterlist System.</p>
            <p>&copy; 2025 Alps Electric (Malaysia) Sdn Bhd</p>
        </div>
    </body>
    </html>';

    $mail->Body = $emailBody;
    
    // Plain text version
    $altBody = "Passport Expiry Alert\n\n";
    $altBody .= "The following employee(s) have passport(s) expiring within 12 months or already expired:\n\n";
    foreach ($employeesExpiringSoon as $employee) {
        $altBody .= "Employee No: {$employee['employee_no']}\n";
        $altBody .= "Name: {$employee['name']}\n";
        $altBody .= "Department: {$employee['department']}\n";
        $altBody .= "Passport No: {$employee['passport_no']}\n";
        $altBody .= "Expiry Date: {$employee['expiry_date']}\n";
        $altBody .= "Status: {$employee['status']}\n\n";
    }
    $mail->AltBody = $altBody;

    // Send email
    if ($mail->send()) {
        error_log("Passport expiry alert email sent successfully");
        return true;
    } else {
        error_log("Failed to send passport alert email: " . $mail->ErrorInfo);
        return false;
    }
}
?>