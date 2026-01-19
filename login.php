<?php
session_start();

$allowed_admins = [
    '7366',
    '3839', 
    '999999' // Pastikan NIK yang Anda pakai login ada di sini!
];

// Ubah ke true jika ingin melihat pesan error detail dari API (Matikan saat production)
$debug_mode = false; 

$error = "";

function callApi($nik) {
    $url = "https://survey.mandiricoal.co.id/api/check-nik?nik=" . $nik;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    
    $output = curl_exec($ch);
    
    if(curl_errno($ch)){
        return ['status' => 'error', 'message' => 'Curl Error: ' . curl_error($ch)];
    }
    
    curl_close($ch);
    return json_decode($output, true);
}

if (isset($_POST['login'])) {
    $nik_input = trim($_POST['nik']);
    $dob_input = $_POST['dob']; // Format browser biasanya YYYY-MM-DD

    // 1. Cek Whitelist
    if (!in_array($nik_input, $allowed_admins)) {
        $error = "Akses Ditolak: NIK $nik_input belum terdaftar di whitelist admin.";
    } else {
        // 2. Panggil API
        $api_data = callApi($nik_input);

        if ($debug_mode) {
            echo "<pre>DEBUG MODE:<br>";
            echo "Input DOB: " . $dob_input . "<br>";
            echo "API Response: "; print_r($api_data);
            echo "</pre>";
            die();
        }

        if (isset($api_data['status']) && $api_data['status'] === 'success') {
            
            // Ambil DOB dari API
            $api_dob = $api_data['data']['date_of_birth'] ?? '';
            
            // Pastikan format tanggal sama-sama YYYY-MM-DD
            // Kadang API memberikan spasi atau format lain, kita potong 10 karakter pertama saja
            $clean_api_dob = substr($api_dob, 0, 10); 

            if ($dob_input === $clean_api_dob) {
                // LOGIN SUKSES
                $_SESSION['is_admin_logged_in'] = true;
                $_SESSION['admin_nik'] = $nik_input;
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Tanggal Lahir Salah. <br>Input: $dob_input <br>Data HR: $clean_api_dob";
            }
        } else {
            $msg = $api_data['message'] ?? 'Tidak ada respon dari server.';
            $error = "Gagal Validasi API: " . $msg;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-sm border-t-4 border-blue-600">
        <h2 class="text-2xl font-bold text-center text-slate-800 mb-6">Login Dashboard</h2>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-xs text-center border border-red-100">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-slate-600 text-sm font-medium mb-1">NIK</label>
                <input type="text" name="nik" placeholder="Masukan NIK Terdaftar" 
                       class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>

            <div class="mb-6">
                <label class="block text-slate-600 text-sm font-medium mb-1">Tanggal Lahir</label>
                <input type="date" name="dob" 
                       class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none" required>
            </div>

            <button type="submit" name="login" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                Masuk Dashboard
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="index.php" class="text-sm text-slate-400 hover:text-blue-600">← Kembali ke Survey</a>
        </div>
    </div>
</body>
</html>