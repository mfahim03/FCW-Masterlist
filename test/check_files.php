<?php
echo "<h2>File Structure Check</h2>";

$files = [
    'db.php',
    'passport.php',
    'config/fetchPassport.php',
    'mail/passportExpiry.php',
    'vendor/autoload.php',
    'vendor/phpmailer/phpmailer/src/PHPMailer.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file - EXISTS<br>";
    } else {
        echo "❌ $file - NOT FOUND<br>";
    }
}

echo "<br><h2>Current Directory:</h2>";
echo __DIR__ . "<br>";

echo "<br><h2>Mail Directory Contents:</h2>";
if (is_dir('mail')) {
    $mailFiles = scandir('mail');
    foreach ($mailFiles as $file) {
        if ($file != '.' && $file != '..') {
            echo "- $file<br>";
        }
    }
} else {
    echo "❌ mail/ directory not found!";
}
?>