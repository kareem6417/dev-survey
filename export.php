<?php
session_start();
require 'config.php';

// 1. CEK KEAMANAN
if (!isset($_SESSION['is_admin_logged_in']) || $_SESSION['is_admin_logged_in'] !== true) {
    die("Akses Ditolak. Harap login terlebih dahulu.");
}

// 2. FILTER & HAK AKSES
$adminScope = $_SESSION['admin_scope'] ?? 0;
$filterInput = $_GET['filter_company'] ?? 'ALL';

$finalFilter = 'ALL';
if ($adminScope === 'ALL') {
    $finalFilter = $filterInput;
} else {
    $finalFilter = $adminScope;
}

// 3. HEADER EXCEL
$fileName = "Survey_Report_" . date('Y-m-d_His') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$fileName");
header("Pragma: no-cache");
header("Expires: 0");

// 4. AMBIL PERTANYAAN (DIFILTER SESUAI PT)
$sqlQ = "SELECT id, question_text FROM questions";
$paramsQ = [];

if ($finalFilter !== 'ALL') {
    $sqlQ .= " WHERE company_id = ?";
    $paramsQ[] = $finalFilter;
}
$sqlQ .= " ORDER BY id ASC";

$stmtQ = $pdo->prepare($sqlQ);
$stmtQ->execute($paramsQ);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

// 5. AMBIL RESPONDEN (DIFILTER SESUAI PT)
$sqlR = "SELECT * FROM respondents";
$paramsR = [];

if ($finalFilter !== 'ALL') {
    $sqlR .= " WHERE company_id = ?";
    $paramsR[] = $finalFilter;
}
$sqlR .= " ORDER BY id DESC";

$stmtR = $pdo->prepare($sqlR);
$stmtR->execute($paramsR);
$respondents = $stmtR->fetchAll(PDO::FETCH_ASSOC);

// --- OUTPUT TABEL EXCEL ---
echo "<table border='1'>";

// A. HEADER TABEL
echo "<tr style='background-color:#f0f0f0; font-weight:bold;'>";
echo "<td>No</td>";
echo "<td>Tanggal Submit</td>";
echo "<td>NIK</td>";
echo "<td>Nama</td>";
echo "<td>Email</td>";
echo "<td>Divisi</td>";
echo "<td>Perusahaan</td>";

foreach ($questions as $q) {
    $cleanText = strip_tags($q['question_text']);
    $shortText = strlen($cleanText) > 60 ? substr($cleanText, 0, 57) . '...' : $cleanText;
    echo "<td style='background-color:#e0e7ff;'>[Q{$q['id']}] $shortText</td>";
}
echo "</tr>";

// B. ISI DATA
$no = 1;
foreach ($respondents as $resp) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    
    // --- [FIX] TANGGAL SUBMIT (Sesuai info Anda: submitted_at) ---
    echo "<td>" . ($resp['submitted_at'] ?? '-') . "</td>"; 

    // DATA DIRI (Sesuai Database SQL)
    echo "<td>'" . ($resp['nik'] ?? '-') . "</td>"; 
    echo "<td>" . ($resp['full_name'] ?? '-') . "</td>"; 
    echo "<td>" . ($resp['email'] ?? '-') . "</td>"; 
    echo "<td>" . ($resp['division'] ?? '-') . "</td>"; 
    
    // NAMA PERUSAHAAN
    $stmtC = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
    $stmtC->execute([$resp['company_id']]);
    $compName = $stmtC->fetchColumn();
    echo "<td>" . $compName . "</td>";

    // JAWABAN
    $stmtAns = $pdo->prepare("SELECT question_id, answer_value FROM answers WHERE respondent_id = ?");
    $stmtAns->execute([$resp['id']]);
    $answersRaw = $stmtAns->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($questions as $q) {
        $qid = $q['id'];
        $val = isset($answersRaw[$qid]) ? $answersRaw[$qid] : '-';
        $val = str_replace(["\r", "\n"], " ", $val); // Hapus enter biar rapi
        echo "<td>" . htmlspecialchars($val) . "</td>";
    }

    echo "</tr>";
}

echo "</table>";
exit();
?>