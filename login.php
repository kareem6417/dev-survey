<?php
session_start();

// ==========================================
// 1. CONFIG: WHITELIST (DAFTAR IZIN)
// ==========================================
// PENTING: TAMBAHKAN NIK ANDA DI SINI!
$allowed_niks = [
    '7366',
    '3839', 
    '123456', // <--- GANTI INI DENGAN NIK ANDA SENDIRI
    '999999'
];

// ==========================================
// 2. CONFIG: API
// ==========================================
define('API_URL_BASE', 'http://mandiricoal.co.id:1880/master/employee/pernr/');
define('API_KEY', 'ca6cda3462809fc894801c6f84e0cd8ecff93afb');

$error = "";
$debug_info = "";

// Fungsi Call API
function checkEmployeeApi($nik) {
    $url = API_URL_BASE . $nik;
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => array("api_key: " . API_KEY),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        return ['status' => 'error', 'message' => 'Koneksi API Gagal: ' . $err];
    }

    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['status' => 'error', 'message' => 'Respon server bukan JSON valid.'];
    }

    return ['status' => 'success', 'data' => $data];
}

// LOGIC LOGIN
if (isset($_POST['login'])) {
    $nik_input = trim($_POST['nik']);
    $dob_input = $_POST['dob']; // Format: YYYY-MM-DD

    // 1. Cek Whitelist
    if (!in_array($nik_input, $allowed_niks)) {
        $error = "Akses Ditolak. NIK <b>$nik_input</b> tidak terdaftar di Whitelist (login.php baris 9).";
    } 
    else {
        // 2. Panggil API
        $apiResult = checkEmployeeApi($nik_input);

        if ($apiResult['status'] === 'success') {
            $json = $apiResult['data'];
            
            // Cek struktur data (Wrapper 'data' atau langsung)
            $empData = $json['data'] ?? $json;

            if (empty($empData)) {
                $error = "NIK ada di whitelist, tapi API tidak mengembalikan data karyawan.";
            } else {
                // 3. Cari Field Tanggal Lahir (Debugging Otomatis)
                $api_dob = $empData['date_of_birth'] ?? $empData['birthDate'] ?? $empData['tgl_lahir'] ?? $empData['birth_date'] ?? null;

                // --- DEBUG INFO (Akan muncul jika gagal) ---
                $debug_info = "
                    <div class='mt-4 p-3 bg-slate-100 border border-slate-300 rounded text-xs text-left font-mono overflow-auto'>
                        <strong>[DEBUGGER]</strong><br>
                        Input NIK: $nik_input<br>
                        Input DOB: $dob_input<br>
                        API DOB Found: " . ($api_dob ? $api_dob : '<span class="text-red-600">KOSONG</span>') . "<br>
                        <hr class='my-2 border-slate-300'>
                        <strong>Data Mentah API:</strong><br>
                        <pre>" . print_r($empData, true) . "</pre>
                    </div>
                ";

                if ($api_dob) {
                    try {
                        $dateInput = new DateTime($dob_input);
                        $dateApi   = new DateTime($api_dob);

                        if ($dateInput->format('Y-m-d') === $dateApi->format('Y-m-d')) {
                            // SUKSES
                            $_SESSION['is_admin_logged_in'] = true;
                            $_SESSION['admin_nik'] = $nik_input;
                            $_SESSION['admin_name'] = $empData['employee_name'] ?? $empData['name'] ?? 'Admin';
                            
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $error = "Tanggal Lahir Tidak Cocok.";
                        }
                    } catch (Exception $e) {
                        $error = "Format tanggal API tidak valid: " . $api_dob;
                    }
                } else {
                    $error = "Data API ditemukan, tapi field Tanggal Lahir kosong.";
                }
            }
        } else {
            $error = $apiResult['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white w-full max-w-[450px] p-8 rounded-2xl shadow-xl border border-slate-200">
        
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Dashboard Login</h1>
            <p class="text-slate-500 text-sm mt-1">Verifikasi Identitas</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-100 text-red-600 p-4 rounded-lg text-sm mb-6">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Login Gagal
                </div>
                <?= $error ?>
                
                <?= $debug_info ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-700 mb-2">NIK</label>
                <input type="text" name="nik" placeholder="Masukan NIK" class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition font-medium text-slate-800" required>
            </div>

            <div class="mb-8">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Lahir</label>
                <input type="date" name="dob" class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-lg focus:bg-white focus:ring-2 focus:ring-blue-500 outline-none transition font-medium text-slate-800" required>
            </div>

            <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition shadow-lg shadow-blue-500/30 flex justify-center items-center gap-2">
                Masuk Dashboard
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            </button>
        </form>

        <div class="mt-8 text-center pt-6 border-t border-slate-100">
            <a href="index.php" class="text-sm font-medium text-slate-500 hover:text-blue-600 transition">Kembali ke Survey</a>
        </div>
    </div>

</body>
</html>