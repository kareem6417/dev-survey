<?php
session_start();

$allowed_niks = [
    '7366',
    '3839', 
];


define('API_URL_BASE', 'http://mandiricoal.co.id:1880/master/employee/pernr/');
define('API_KEY', 'ca6cda3462809fc894801c6f84e0cd8ecff93afb');

$error = "";

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
    
    // Validasi JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['status' => 'error', 'message' => 'Respon server bukan JSON valid.'];
    }

    return ['status' => 'success', 'data' => $data];
}

// LOGIC LOGIN
if (isset($_POST['login'])) {
    $nik_input = trim($_POST['nik']);
    $dob_input = $_POST['dob']; // Format: YYYY-MM-DD

    // 1. Cek Input Kosong
    if (empty($nik_input) || empty($dob_input)) {
        $error = "NIK dan Tanggal Lahir wajib diisi.";
    } 
    // 2. Cek Whitelist
    elseif (!in_array($nik_input, $allowed_niks)) {
        $error = "Akses Ditolak. NIK $nik_input tidak memiliki izin akses Dashboard.";
    } 
    else {
        // 3. Panggil API
        $apiResult = checkEmployeeApi($nik_input);

        if ($apiResult['status'] === 'success') {
            $json = $apiResult['data']; // Ini adalah seluruh JSON response

            // Cek status dari body response API (biasanya ada field status atau data)
            // Struktur biasanya: { status: 'success', data: { ... } } atau langsung { ...data... }
            
            // Kita coba cari object datanya
            $empData = null;
            if (isset($json['data'])) {
                $empData = $json['data'];
            } else {
                // Fallback jika API langsung mengembalikan data karyawan tanpa wrapper 'data'
                $empData = $json;
            }

            if (empty($empData)) {
                $error = "Data NIK tidak ditemukan di sistem API.";
            } else {
                // 4. Ambil Tanggal Lahir (Cari berbagai kemungkinan nama field)
                $api_dob = $empData['date_of_birth'] ?? $empData['birthDate'] ?? $empData['tgl_lahir'] ?? null;

                if ($api_dob) {
                    try {
                        // Normalisasi Tanggal
                        $dateInput = new DateTime($dob_input);
                        $dateApi   = new DateTime($api_dob);

                        // Bandingkan
                        if ($dateInput->format('Y-m-d') === $dateApi->format('Y-m-d')) {
                            // --- LOGIN SUKSES ---
                            $_SESSION['is_admin_logged_in'] = true;
                            $_SESSION['admin_nik'] = $nik_input;
                            $_SESSION['admin_name'] = $empData['employee_name'] ?? $empData['name'] ?? 'Admin';
                            
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            // Tampilkan error detail untuk debugging user
                            $error = "Tanggal lahir salah. <br><small>Input: $dob_input <br>Data Sistem: " . $dateApi->format('Y-m-d') . "</small>";
                        }
                    } catch (Exception $e) {
                        $error = "Format tanggal sistem tidak valid: " . $api_dob;
                    }
                } else {
                    $error = "Data ditemukan, tapi field Tanggal Lahir kosong.";
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
    <title>Dashboard Login - IT Survey</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white w-full max-w-[400px] p-8 rounded-2xl shadow-xl border border-slate-200">
        
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-blue-50 text-blue-600 mb-4 ring-1 ring-blue-100">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Dashboard Login</h1>
            <p class="text-slate-500 text-sm mt-1">Verifikasi identitas untuk mengakses data</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border border-red-100 text-red-600 p-3 rounded-lg text-sm mb-6 flex items-start gap-2">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            
            <div class="mb-5">
                <label class="block text-sm font-semibold text-slate-700 mb-2">NIK Karyawan</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input type="text" name="nik" placeholder="Masukan NIK" 
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-slate-800 font-medium placeholder:text-slate-400" 
                           required>
                </div>
            </div>

            <div class="mb-8">
                <label class="block text-sm font-semibold text-slate-700 mb-2">Tanggal Lahir</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                    <input type="date" name="dob" 
                           class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition text-slate-800 font-medium" 
                           required>
                </div>
                <p class="text-xs text-slate-400 mt-1 text-right italic">*Sesuai data HC/SAP</p>
            </div>

            <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition-all duration-200 transform active:scale-[0.98] shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2">
                <span>Masuk Dashboard</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            </button>
        </form>

        <div class="mt-8 text-center pt-6 border-t border-slate-100">
            <a href="index.php" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-blue-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Kembali ke Survey
            </a>
        </div>
    </div>

</body>
</html>