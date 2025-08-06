<?php
// === AUTH SETTINGS ===
$valid_username = "admin";
$valid_password = "Advance2025"; // You can change this
$encryption_key = 'my_strong_encryption_key'; // same as in submit.php

// === BASIC LOGIN HANDLER ===
session_start();
if (isset($_POST['username']) && isset($_POST['password'])) {
    if ($_POST['username'] === $valid_username && $_POST['password'] === $valid_password) {
        $_SESSION['authenticated'] = true;
    } else {
        $error = "Invalid username or password.";
    }
}

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true):
?>
<!DOCTYPE html>
<html>
<head><title>Admin Login</title></head>
<body>
  <form method="POST" style="max-width:400px;margin:100px auto;padding:20px;border:1px solid #ccc;border-radius:10px;">
    <h3>Secure Admin Login</h3>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <input type="text" name="username" placeholder="Username" required style="width:100%;padding:10px;margin-bottom:10px;">
    <input type="password" name="password" placeholder="Password" required style="width:100%;padding:10px;margin-bottom:10px;">
    <button type="submit" style="width:100%;padding:10px;background-color:#f9cc00;border:none;">Login</button>
  </form>
</body>
</html>
<?php
exit;
endif;

// === DECRYPT AND DOWNLOAD ===
function decryptData($data, $key) {
    $data = base64_decode($data);
    $ivlen = openssl_cipher_iv_length($cipher = "AES-256-CBC");
    $iv = substr($data, 0, $ivlen);
    $ciphertext = substr($data, $ivlen);
    return openssl_decrypt($ciphertext, $cipher, $key, 0, $iv);
}

if (file_exists('submissions.csv.enc')) {
    $lines = file('submissions.csv.enc');
    $decrypted = '';
    foreach ($lines as $line) {
        $decrypted .= decryptData(trim($line), $encryption_key);
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="submissions.csv"');
    echo $decrypted;
    exit;
} else {
    echo "No data found.";
}
?>
