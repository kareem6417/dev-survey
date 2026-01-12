<?php
require 'config.php';

// Set Header agar browser mendownload file Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Report_IT_Survey_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// 1. Ambil Semua Pertanyaan untuk Header Tabel
$questions = $pdo->query("SELECT id, question_text FROM questions ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Ambil Semua Responden
$respondents = $pdo->query("SELECT * FROM respondents ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// MULAI TABEL HTML (Excel bisa baca tabel HTML sederhana)
echo "<table border='1'>";

// --- HEADER ---
echo "<tr style='background-color:#f0f0f0; font-weight:bold;'>";
echo "<td>No</td>";
echo "<td>Tanggal Submit</td>";
echo "<td>NIK</td>";
echo "<td>Nama</td>";
echo "<td>Email</td>";
echo "<td>Divisi</td>";
echo "<td>Company</td>";

// Loop kolom pertanyaan
foreach ($questions as $q) {
    // Potong teks pertanyaan jika terlalu panjang biar header tidak raksasa
    $shortText = strlen($q['question_text']) > 50 ? substr($q['question_text'], 0, 50) . '...' : $q['question_text'];
    echo "<td style='background-color:#e0e7ff;'>[Q{$q['id']}] $shortText</td>";
}
echo "</tr>";

// --- ISI DATA ---
$no = 1;
foreach ($respondents as $resp) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    echo "<td>" . $resp['created_at'] . "</td>";
    echo "<td>'" . $resp['nik'] . "</td>"; // Tambah kutip agar Excel anggap teks (biar angka 0 di depan tidak hilang)
    echo "<td>" . $resp['full_name'] . "</td>";
    echo "<td>" . $resp['email'] . "</td>";
    echo "<td>" . $resp['division'] . "</td>";
    
    // Ambil Nama Company (bisa join query, tapi ini cara cepat)
    $stmtC = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
    $stmtC->execute([$resp['company_id']]);
    $compName = $stmtC->fetchColumn();
    echo "<td>" . $compName . "</td>";

    // Ambil Jawaban User Ini
    // Kita ambil semua jawaban user ini sekaligus biar efisien
    $stmtAns = $pdo->prepare("SELECT question_id, answer_value FROM answers WHERE respondent_id = ?");
    $stmtAns->execute([$resp['id']]);
    $myAnswers = $stmtAns->fetchAll(PDO::FETCH_KEY_PAIR); // Format: [question_id => answer_value]

    // Loop sesuai urutan kolom pertanyaan
    foreach ($questions as $q) {
        $val = $myAnswers[$q['id']] ?? '-'; // Jika tidak dijawab (skip logic), tulis strip
        echo "<td>" . htmlspecialchars($val) . "</td>";
    }

    echo "</tr>";
}

echo "</table>";
exit;
?>