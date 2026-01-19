<?php
session_start();

$allowed_admins = [
    '7366', 
    '3839',  
];

$error = "";

if (isset($_POST['login'])) {
    $nik_input = trim($_POST['nik']);
    $dob_input = $_POST['dob'];

    if (!in_array($nik_input, $allowed_admins)) {
        $error = "Akses Ditolak: NIK Anda tidak terdaftar sebagai Admin.";
    } else {
        $api_url = "https://survey.mandiricoal.co.id/api/check-nik?nik=" . $nik_input;
        
        $response = @file_get_contents($api_url); 
        
        if ($response === FALSE) {
            $error = "Gagal menghubungi server data karyawan.";
        } else {
            $data = json_decode($response, true);

            // 3. Validasi Respon API
            if (isset($data['status']) && $data['status'] === 'success') {
                $api_dob = $data['data']['date_of_birth'];

                // 4. Bandingkan Tanggal Lahir
                if ($dob_input === $api_dob) {
                    // LOGIN SUKSES
                    $_SESSION['is_admin_logged_in'] = true;
                    $_SESSION['admin_nik'] = $nik_input;
                    $_SESSION['admin_name'] = $data['data']['employee_name'];
                    
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Verifikasi Gagal: Tanggal lahir tidak sesuai.";
                }
            } else {
                $error = "Data NIK tidak ditemukan di sistem.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Login - Verifikasi Data</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-sm border-t-4 border-blue-600">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-slate-800">Dashboard</h2>
            <p class="text-slate-500 text-sm mt-1">Verifikasi Identitas Anda</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm text-center border border-red-100">
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
                <p class="text-xs text-slate-400 mt-1">*Sesuai dari data SAP</p>
            </div>

            <button type="submit" name="login" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium shadow-md hover:shadow-lg">
                Verifikasi & Masuk
            </button>
        </form>
        
        <div class="mt-6 text-center border-t pt-4">
            <a href="index.php" class="text-slate-400 text-sm hover:text-slate-600 transition flex items-center justify-center gap-1">
                <span>&larr;</span> Kembali ke Survey
            </a>
        </div>
    </div>
</body>
</html>