<?php
session_start();

$allowed_admins = [
    '7366',
    '3839', 
    '999999'
];

$error = "";

// Fungsi Pembantu: Panggil API pakai cURL (Lebih Stabil)
function callApi($nik) {
    $url = "https://survey.mandiricoal.co.id/api/check-nik?nik=" . $nik;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
    // Trik: Bypass SSL jika sertifikat server belum update (Opsional, tapi membantu)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    // Trik: Menyamar jadi Browser agar tidak diblokir API
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    $output = curl_exec($ch);
    
    if(curl_errno($ch)){
        // Jika error koneksi cURL
        return ['status' => 'error', 'message' => 'Koneksi API Gagal: ' . curl_error($ch)];
    }
    
    curl_close($ch);
    return json_decode($output, true);
}


if (isset($_POST['login'])) {
    $nik_input = trim($_POST['nik']);
    $dob_input = $_POST['dob']; // YYYY-MM-DD

    // 1. Cek Whitelist NIK
    if (!in_array($nik_input, $allowed_admins)) {
        $error = "Akses Ditolak: NIK $nik_input tidak terdaftar sebagai Admin.";
    } else {
        // 2. Panggil API dengan cURL
        $api_data = callApi($nik_input);

        // Debugging (Kalau masih gagal, hilangkan komentar baris di bawah ini untuk melihat isi respon)
        // var_dump($api_data); die(); 

        if (isset($api_data['status']) && $api_data['status'] === 'success') {
            
            // 3. Ambil Tanggal Lahir dari API
            // Pastikan format dari API adalah YYYY-MM-DD
            $api_dob = $api_data['data']['date_of_birth'] ?? ''; 

            // 4. Bandingkan
            if ($dob_input === $api_dob) {
                // LOGIN SUKSES
                $_SESSION['is_admin_logged_in'] = true;
                $_SESSION['admin_nik'] = $nik_input;
                $_SESSION['admin_name'] = $api_data['data']['employee_name'];
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Verifikasi Gagal: Tanggal lahir tidak sesuai dengan data HR.";
            }
        } else {
            // Error dari API (misal data tidak ditemukan atau koneksi timeout)
            $msg = $api_data['message'] ?? 'Data NIK tidak ditemukan di sistem API.';
            $error = "Gagal Validasi: " . $msg;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Verifikasi Data</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-sm border-t-4 border-blue-600">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-slate-800">Admin Dashboard</h2>
            <p class="text-slate-500 text-sm mt-1">Verifikasi Identitas Anda</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-xs text-center border border-red-100">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label class="block text-slate-600 text-sm font-medium mb-1">NIK (Nomor Induk Karyawan)</label>
                <input type="text" name="nik" placeholder="Contoh: 123456" 
                       class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition" 
                       required autocomplete="off">
            </div>

            <div class="mb-6">
                <label class="block text-slate-600 text-sm font-medium mb-1">Tanggal Lahir</label>
                <input type="date" name="dob" 
                       class="w-full border border-slate-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none transition" 
                       required>
            </div>

            <button type="submit" name="login" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium shadow-md">
                Verifikasi & Masuk
            </button>
        </form>
        
        <div class="mt-6 text-center border-t pt-4">
            <a href="index.php" class="text-slate-400 text-sm hover:text-slate-600 transition">
                &larr; Kembali ke Survey
            </a>
        </div>
    </div>
</body>
</html>