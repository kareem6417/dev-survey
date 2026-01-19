<?php
session_start();
require 'config.php';

// 1. CEK KEAMANAN (Login Check)
if (!isset($_SESSION['is_admin_logged_in']) || $_SESSION['is_admin_logged_in'] !== true) {
    die("Akses Ditolak. Harap login terlebih dahulu.");
}

// 2. CEK HAK AKSES & FILTER
$adminScope = $_SESSION['admin_scope'] ?? 0;   // Hak Akses (ALL atau ID PT)
$filterInput = $_GET['filter_company'] ?? 'ALL'; // Input dari URL

// Logika Validasi Filter
$finalFilter = 'ALL';

if ($adminScope === 'ALL') {
    // Jika Super Admin, ikuti input dari URL (Dropdown)
    $finalFilter = $filterInput;
} else {
    // Jika Admin PT, PAKSA filter sesuai ID perusahaannya (Supaya tidak bisa intip PT lain)
    $finalFilter = $adminScope;
}

// 3. SIAPKAN HEADER FILE EXCEL
$fileName = "Survey_Report_" . date('Y-m-d_His') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$fileName");
header("Pragma: no-cache");
header("Expires: 0");

// 4. SIAPKAN QUERY SESUAI FILTER
$whereClause = "";
$params = [];

// Jika filter bukan ALL, tambahkan WHERE
if ($finalFilter !== 'ALL') {
    $whereClause = "WHERE company_id = ?";
    $params[] = $finalFilter;
}

// Ambil Pertanyaan (Header)
$questions = $pdo->query("SELECT id, question_text FROM questions ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Ambil Responden (Data) - DENGAN FILTER
$sql = "SELECT * FROM respondents $whereClause ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$respondents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- MULAI OUTPUT TABEL (EXCEL) ---
echo "<table border='1'>";

// A. HEADER KOLOM
echo "<tr style='background-color:#f0f0f0; font-weight:bold;'>";
echo "<td>No</td>";
echo "<td>Tanggal Submit</td>";
echo "<td>NIK</td>";
echo "<td>Nama</td>";
echo "<td>Email</td>";
echo "<td>Divisi</td>";
echo "<td>Company</td>";

foreach ($questions as $q) {
    // Pendekkan judul pertanyaan di header agar rapi
    $shortText = strlen($q['question_text']) > 60 ? substr($q['question_text'], 0, 57) . '...' : $q['question_text'];
    echo "<td style='background-color:#e0e7ff;'>[Q{$q['id']}] $shortText</td>";
}
echo "</tr>";

// B. ISI DATA
$no = 1;
foreach ($respondents as $resp) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    echo "<td>" . $resp['submission_date'] . "</td>"; // Sesuaikan nama kolom tanggal di DB Anda (created_at atau submission_date)
    echo "<td>'" . $resp['respondent_nik'] . "</td>"; // Tanda kutip ' agar Excel baca sebagai teks
    echo "<td>" . $resp['respondent_name'] . "</td>";
    echo "<td>" . $resp['respondent_email'] . "</td>";
    echo "<td>" . $resp['respondent_division'] . "</td>";
    
    // Ambil Nama Company (Query langsung biar simpel, walaupun bisa di-JOIN)
    $stmtC = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
    $stmtC->execute([$resp['company_id']]);
    $compName = $stmtC->fetchColumn();
    echo "<td>" . $compName . "</td>";

    // Ambil Jawaban User Ini
    // Mengambil semua jawaban responden ini dalam satu tarikan
    $stmtAns = $pdo->prepare("SELECT question_id, answer_value FROM answers WHERE respondent_id = ?");
    $stmtAns->execute([$resp['id']]);
    $answersRaw = $stmtAns->fetchAll(PDO::FETCH_KEY_PAIR); // Hasil: [question_id => value]

    // Loop sesuai urutan pertanyaan header
    foreach ($questions as $q) {
        $val = isset($answersRaw[$q['id']]) ? $answersRaw[$q['id']] : '-';
        echo "<td>" . htmlspecialchars($val) . "</td>";
    }

    echo "</tr>";
}

echo "</table>";
exit();
?>