<?php
session_start();
$password = "Advance2025";

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if (isset($_POST['password']) && $_POST['password'] === $password) {
        $_SESSION['loggedin'] = true;
    } else {
        echo '<!DOCTYPE html><html><head><title>Admin Login</title><style>
        body { background: #f4f4f4; font-family: Arial; padding: 50px; }
        .login-box { background: #fff; max-width: 400px; margin: auto; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin-top: 10px; font-size: 16px; }
        button { background: #f9cc00; border: none; padding: 10px; margin-top: 15px; width: 100%; font-weight: bold; font-size: 16px; cursor: pointer; }
        </style></head><body>
        <div class="login-box">
        <h2>Admin Login</h2>
        <form method="post">
            <input type="password" name="password" placeholder="Enter password">
            <button type="submit">Login</button>
        </form>
        </div></body></html>';
        exit;
    }
}

function decryptData($data, $key) {
    $data = base64_decode($data);
    $ivlen = openssl_cipher_iv_length("AES-256-CBC");
    $iv = substr($data, 0, $ivlen);
    $ciphertext_raw = substr($data, $ivlen);
    return openssl_decrypt($ciphertext_raw, "AES-256-CBC", $key, 0, $iv);
}

function encryptData($data, $key) {
    $ivlen = openssl_cipher_iv_length("AES-256-CBC");
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($data, "AES-256-CBC", $key, 0, $iv);
    return base64_encode($iv . $ciphertext_raw);
}

$encryption_key = 'my_strong_encryption_key';
$data_file = 'submissions.csv.enc';
$entries = [];

if (file_exists($data_file)) {
    $lines = file($data_file, FILE_IGNORE_NEW_LINES);
    $header = str_getcsv(array_shift($lines));
    foreach ($lines as $line) {
        $decrypted = decryptData($line, $encryption_key);
        if ($decrypted) {
            $entries[] = str_getcsv($decrypted);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $idsToDelete = $_POST['delete'];
    $filtered = array_filter($entries, fn($entry) => !in_array($entry[12], $idsToDelete));
    $fp = fopen($data_file, 'w');
    fwrite($fp, implode(",", $header) . "\n");
    foreach ($filtered as $entry) {
        fwrite($fp, encryptData(implode(",", $entry), $encryption_key) . "\n");
    }
    fclose($fp);
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f2f6fc; padding: 30px; }
        h2 { text-align: center; margin-bottom: 20px; }
        .panel { background: #fff; padding: 20px; border-radius: 10px; max-width: 1100px; margin: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; overflow-x: auto; display: block; }
        th, td { border: 1px solid #ccc; padding: 10px; font-size: 13px; text-align: left; white-space: nowrap; }
        th { background: #f9cc00; color: #000; position: sticky; top: 0; z-index: 1; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        button { background: #f44336; color: #fff; padding: 10px 15px; border: none; margin-top: 20px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #d32f2f; }
        input[type="checkbox"] { transform: scale(1.2); }
        .overflow-x { overflow-x: auto; }
    </style>
    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete selected entries?");
        }
    </script>
</head>
<body>
<div class="panel">
    <h2>Admin Panel – Full Submission Data</h2>
    <form method="post" onsubmit="return confirmDelete();">
        <div class="overflow-x">
        <table>
            <thead>
            <tr>
                <th>Select</th>
                <?php foreach ($header as $col) echo "<th>$col</th>"; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($entries as $entry): ?>
            <tr>
                <td><input type="checkbox" name="delete[]" value="<?= htmlspecialchars($entry[12]) ?>"></td>
                <?php foreach ($entry as $col) echo "<td>" . nl2br(htmlspecialchars($col)) . "</td>"; ?>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <button type="submit">Delete Selected</button>
    </form>
</div>
</body>
</html>