<?php
// cek_data.php
header('Content-Type: application/json');

// Masukkan salah satu NIK yang Anda gunakan untuk tes (misal: 7367 atau 7363)
$nik = $_GET['nik'] ?? '7367'; 

$apiUrl = "http://mandiricoal.co.id:1880/master/employee/pernr/" . $nik;
$apiKey = "ca6cda3462809fc894801c6f84e0cd8ecff93afb";

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HTTPHEADER => array("api_key: " . $apiKey),
));

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

echo "<h1>DATA MENTAH DARI API (NIK: $nik)</h1>";
echo "<p>Silakan copy semua teks di dalam kotak di bawah ini dan kirimkan ke saya:</p>";

if ($err) {
    echo "Error: " . $err;
} else {
    // Tampilkan data mentah agar kita bisa cari tanggal lahirnya ngumpet dimana
    echo "<textarea style='width:100%; height:400px; font-family:monospace; padding:10px;'>";
    echo $response;
    echo "</textarea>";
    
    // Bantuan visual format JSON
    echo "<pre>" . print_r(json_decode($response, true), true) . "</pre>";
}
?>