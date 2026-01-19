<?php
session_start();

// ==========================================
// 1. SETTING ADMIN (WHITELIST)
// ==========================================
$allowed_admins = [
    '7366',
    '3839', 
    '999999',
    '123456' // Masukkan NIK Anda di sini
];

// SETTING API BARU
define('API_URL', 'http://mandiricoal.co.id:1880/master/employee/pernr/');
define('API_KEY', 'ca6cda3462809fc894801c6f84e0cd8ecff93afb');

$error = "";

// Fungsi Panggil API Internal
function callApi($nik) {
    $url = API_URL . $nik;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Timeout 10 detik
    
    // Setting Header API Key
    // Catatan: Biasanya nama headernya 'x-api-key' atau 'api-key'. 
    // Jika gagal, coba ganti 'x-api-key' menjadi 'Authorization' atau 'key'.
    $headers = [
        "x-api-key: " . API_KEY,
        "Content-Type: application/json"
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $output = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if(curl_errno($ch)){
        return ['status' => 'error', 'message' => 'Koneksi Gagal: ' . curl_error($ch)];
    }
    
    curl_close($ch);

    // Cek apakah data ditemukan (HTTP 200)
    if ($httpCode !== 200) {
        return ['status' => 'error', 'message' => "API Error (HTTP $httpCode)"];
    }
    
    // Decode JSON
    $data = json_decode($output, true);
    
    // Cek apakah JSON valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['status' => 'error', 'message' => 'Format respon API bukan JSON valid.'];
    }

    return ['status' => 'success', 'data' => $data];
}

if (isset($_POST['login'])) {
    $nik_input = trim($_POST['nik']);
    $dob_input = $_POST['dob']; // Input user (YYYY-MM-DD)

    if (empty($nik_input) || empty($dob_input)) {
        $error = "NIK dan Tanggal Lahir wajib diisi.";
    } 
    elseif (!in_array($nik_input, $allowed_admins)) {
        $error = "Akses Ditolak. NIK Anda tidak terdaftar sebagai Admin.";
    } 
    else {
        // Panggil API
        $result = callApi($nik_input);

        if ($result['status'] === 'success') {
            $empData = $result['data'];
            
            // LOGIC PENCARIAN TANGGAL LAHIR (Otomatis)
            // Kita cari field yang namanya mengandung unsur tanggal
            $api_dob = null;
            $possible_keys = ['date_of_birth', 'birthDate', 'tgl_lahir', 'birth_date', 'tanggal_lahir', 'dob'];
            
            // 1. Cek berdasarkan key umum
            foreach ($possible_keys as $key) {
                if (!empty($empData[$key])) {
                    $api_dob = $empData[$key];
                    break;
                }
            }
            
            // 2. Jika fieldnya ada di dalam object 'data' lagi (nested)
            if (!$api_dob && isset($empData['data'])) {
                foreach ($possible_keys as $key) {
                    if (!empty($empData['data'][$key])) {
                        $api_dob = $empData['data'][$key];
                        break;
                    }
                }
            }

            // DEBUG MODE: Hapus komentar "//" di bawah jika Login masih gagal 
            // untuk melihat apa nama field tanggal lahir yang benar dari API.
            /*
            echo "<pre>";
            echo "Input User: " . $dob_input . "<br>";
            echo "Data API Mentah:<br>";
            print_r($empData);
            die();
            */

            if ($api_dob) {
                // Normalisasi Tanggal (Ubah semua ke format Y-m-d)
                try {
                    $dateInput = new DateTime($dob_input);
                    $dateApi   = new DateTime($api_dob);
                    
                    if ($dateInput->format('Y-m-d') === $dateApi->format('Y-m-d')) {
                        // LOGIN SUKSES
                        $_SESSION['is_admin_logged_in'] = true;
                        $_SESSION['admin_nik'] = $nik_input;
                        // Ambil nama (prioritas field 'name' atau 'employee_name')
                        $name = $empData['employee_name'] ?? $empData['name'] ?? $empData['nama'] ?? 'Admin';
                        $_SESSION['admin_name'] = $name;
                        
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $error = "Verifikasi Gagal. Tanggal lahir tidak sesuai.";
                    }
                } catch (Exception $e) {
                    $error = "Format tanggal dari API tidak dikenali.";
                }
            } else {
                $error = "Data ditemukan, tapi field Tanggal Lahir kosong/tidak ada.";
            }

        } else {
            $error = "Gagal mengambil data karyawan. " . $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin IT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4 bg-[url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070&auto=format&fit=crop')] bg-cover bg-center bg-no-repeat bg-fixed">
    
    <div class="absolute inset-0 bg-black/60 z-0"></div>

    <div class="glass-panel w-full max-w-md p-8 rounded-2xl shadow-2xl relative z-10">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-blue-600/10 text-blue-600 mb-4 ring-4 ring-blue-50">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Admin Portal</h1>
            <p class="text-slate-500 text-sm mt-1">Silakan verifikasi identitas Anda</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg text-sm mb-6 flex items-start gap-3 animate-pulse">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="mb-5">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">NIK Karyawan</label>
                <div class="relative group">
                    <span class="absolute left-3 top-3 text-slate-400 group-focus-within:text-blue-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input type="text" name="nik" placeholder="Masukan NIK" 
                           class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm placeholder:text-slate-300 text-slate-700 font-semibold"
                           required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tanggal Lahir</label>
                <div class="relative group">
                    <span class="absolute left-3 top-3 text-slate-400 group-focus-within:text-blue-600 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                    <input type="date" name="dob" 
                           class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition shadow-sm text-slate-700 font-semibold"
                           required>
                </div>
            </div>

            <button type="submit" name="login" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-blue-500/30 transition-all duration-200 transform active:scale-[0.98]">
                Masuk Dashboard
            </button>
        </form>

        <div class="mt-8 text-center">
            <a href="index.php" class="text-sm font-medium text-slate-500 hover:text-slate-800 transition flex items-center justify-center gap-2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Kembali ke Halaman Survey
            </a>
        </div>
    </div>
</body>
</html>