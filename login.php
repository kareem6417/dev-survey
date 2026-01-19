<?php
session_start();


$admin_roles = [
    '7366'   => 'ALL',
    '3839'   => 3,
    '80018648' => 5,
    '897' => 3
];

$allowed_niks = array_keys($admin_roles);

define('API_URL_BASE', 'http://mandiricoal.co.id:1880/master/employee/pernr/');
define('API_KEY', 'ca6cda3462809fc894801c6f84e0cd8ecff93afb');

$error = "";

// Fungsi Call API (Logic Tetap)
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
    if ($err) return ['status' => 'error', 'message' => 'Koneksi API Gagal: ' . $err];
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) return ['status' => 'error', 'message' => 'Respon server bukan JSON valid.'];
    return ['status' => 'success', 'data' => $data];
}

if (isset($_POST['login'])) {
    $nik_input = trim($_POST['nik']);
    $dob_input = $_POST['dob']; 

    if (!in_array($nik_input, $allowed_niks)) {
        // Update wording error message
        $error = "Akses Ditolak. NIK Anda tidak memiliki izin akses Dashboard.";
    } else {
        $apiResult = checkEmployeeApi($nik_input);
        if ($apiResult['status'] === 'success') {
            $json = $apiResult['data'];
            $empData = null;
            
            // Akses data sesuai struktur debug
            if (isset($json['employee']) && is_array($json['employee']) && isset($json['employee'][0])) {
                $empData = $json['employee'][0];
            }

            if (empty($empData)) {
                $error = "Data NIK tidak ditemukan di sistem.";
            } else {
                $api_dob_raw = $empData['GBPAS'] ?? null;
                if ($api_dob_raw) {
                    $formatted_api_dob = DateTime::createFromFormat('Ymd', $api_dob_raw);
                    if ($formatted_api_dob) {
                        $dob_api_clean = $formatted_api_dob->format('Y-m-d');
                        if ($dob_input === $dob_api_clean) {
                            $_SESSION['is_admin_logged_in'] = true;
                            $_SESSION['admin_nik'] = $nik_input;
                            $_SESSION['admin_name'] = $empData['CNAME'] ?? $empData['employee_name'] ?? 'User';
                            
                            $_SESSION['admin_scope'] = $admin_roles[$nik_input]; 
                            
                            header("Location: dashboard.php");
                            exit();
                        } else {
                            $error = "Verifikasi Gagal: Tanggal Lahir tidak sesuai.";
                        }
                    } else {
                        $error = "Format tanggal sistem tidak valid.";
                    }
                } else {
                    $error = "Data ditemukan, tapi informasi tanggal lahir kosong.";
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
    <title>Login Dashboard - IT Survey</title>
    
    <link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bg-pattern {
            background-color: #f8fafc;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="bg-pattern min-h-screen flex items-center justify-center p-6 relative">

    <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-blue-50 to-transparent pointer-events-none"></div>

    <div class="bg-white w-full max-w-[440px] rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-100 relative z-10 overflow-hidden">
        
        <div class="pt-10 pb-6 px-8 text-center bg-white">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 mb-6 ring-1 ring-blue-100 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            </div>
            
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                Dashboard Survey IT
            </h1>
            <p class="text-slate-500 text-sm mt-2 leading-relaxed">
                Silakan verifikasi identitas Anda untuk mengakses laporan survey.
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mx-8 mb-2 bg-red-50 border border-red-100 text-red-600 p-3 rounded-xl text-xs flex items-start gap-2 animate-pulse">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span class="font-medium"><?= $error ?></span>
            </div>
        <?php endif; ?>

        <div class="p-8 pt-2">
            <form method="POST" autocomplete="off" class="space-y-5">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">NIK Karyawan</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-3.5 text-slate-400 group-focus-within:text-blue-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" name="nik" placeholder="Masukan NIK" 
                               class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-semibold text-slate-700 placeholder:text-slate-400"
                               required>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Tanggal Lahir</label>
                    <div class="relative group">
                        <span class="absolute left-4 top-3.5 text-slate-400 group-focus-within:text-blue-600 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </span>
                        <input type="date" name="dob" 
                               class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all font-semibold text-slate-700"
                               required>
                    </div>
                </div>

                <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-500/20 transition-all duration-200 transform hover:-translate-y-0.5 active:scale-[0.98] flex items-center justify-center gap-2 mt-6">
                    <span>Masuk Dashboard</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                </button>
            </form>
        </div>

        <div class="bg-slate-50 p-5 text-center border-t border-slate-100">
            <a href="index.php" class="text-xs font-semibold text-slate-500 hover:text-blue-600 transition-colors inline-flex items-center gap-1.5 group">
                <svg class="group-hover:-translate-x-1 transition-transform" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                Kembali ke Halaman Survey
            </a>
        </div>
    </div>

</body>
</html>