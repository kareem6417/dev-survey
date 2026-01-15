<?php
// config.php
$host = 'localhost';
$db   = 'dev-survey_it';
$user = 'admin-dev';
$pass = 'Jam10pagi#';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

function searchEmployeeByNIK($nik) {
    // Simulasi Data Dummy
    $mockData = [
        '7366' => ['name' => 'Ilham Nuril', 'email' => 'ilham.nuril@mandirigroup.net', 'company' => 'PT. Mandiri Intiperkasa', 'company_id' => 1, 'division' => 'ITE'],
        '499' => ['name' => 'Akhmad Sekhu', 'email' => 'akhmad.sekhu@gmail.com', 'company' => 'PT. Mandiri Intiperkasa', 'company_id' => 2, 'division' => 'HRGA'],
    ];

    return isset($mockData[$nik]) ? $mockData[$nik] : null;
}
?>