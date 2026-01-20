<?php
// 1. BUFFERING: Mencegah error "Headers already sent" atau sampah text
ob_start();
session_start();
require 'config.php';

// 2. CEK KEAMANAN
if (!isset($_SESSION['is_admin_logged_in']) || $_SESSION['is_admin_logged_in'] !== true) {
    die("Akses Ditolak. Harap login terlebih dahulu.");
}

// 3. FILTER & HAK AKSES
$adminScope = $_SESSION['admin_scope'] ?? 0;
$filterInput = $_GET['filter_company'] ?? 'ALL';

$finalFilter = 'ALL';
if ($adminScope === 'ALL') {
    $finalFilter = $filterInput;
} else {
    $finalFilter = $adminScope;
}

// ============================================================
// 4. LOGIKA PERAMPINGAN PERTANYAAN (MERGE QUESTION TEXT)
// ============================================================

// [FIX] Hapus 'sort_order' karena tidak ada di database Anda
$sqlQ = "SELECT id, question_text FROM questions";
$paramsQ = [];

if ($finalFilter !== 'ALL') {
    $sqlQ .= " WHERE company_id = ?";
    $paramsQ[] = $finalFilter;
}
// [FIX] Order by ID saja
$sqlQ .= " ORDER BY id ASC"; 

$stmtQ = $pdo->prepare($sqlQ);
$stmtQ->execute($paramsQ);
$allQuestions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

// MAPPING: ID Pertanyaan -> Key Teks Unik
$questionIdToTextMap = []; 
$uniqueHeaders = []; 

foreach ($allQuestions as $q) {
    // Bersihkan teks agar pertanyaan yang sama dari PT berbeda bisa dianggap satu grup
    $cleanTextRaw = strip_tags($q['question_text']);
    $cleanTextRaw = preg_replace('/\s+/', ' ', $cleanTextRaw); 
    $cleanKey = trim(strtolower($cleanTextRaw)); 
    
    // Label Header Asli
    $headerLabel = trim(strip_tags($q['question_text']));

    // Simpan Mapping
    $questionIdToTextMap[$q['id']] = $cleanKey;

    // Simpan Header Unik (Gunakan Key agar pertanyaan duplicate jadi 1 kolom)
    if (!isset($uniqueHeaders[$cleanKey])) {
        $uniqueHeaders[$cleanKey] = $headerLabel;
    }
}

// ============================================================
// 5. AMBIL DATA RESPONDEN
// ============================================================
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

// ============================================================
// 6. BERSIHKAN BUFFER SEBELUM DOWNLOAD
// ============================================================
// Ini penting! Menghapus semua output (seperti pesan error warning/notice) sebelum kirim file
ob_end_clean(); 

// Header Excel
$fileName = "Survey_Report_" . date('Y-m-d_His') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$fileName");
header("Pragma: no-cache");
header("Expires: 0");

// ============================================================
// 7. OUTPUT TABEL HTML (EXCEL)
// ============================================================
echo "<table border='1'>";

// A. HEADER TABEL
echo "<tr style='background-color:#f0f0f0; font-weight:bold; vertical-align:middle;'>";
echo "<td style='width:40px;'>No</td>";
echo "<td style='width:120px;'>Tanggal Submit</td>";
echo "<td style='width:100px;'>NIK</td>";
echo "<td style='width:200px;'>Nama</td>";
echo "<td style='width:200px;'>Email</td>";
echo "<td style='width:150px;'>Divisi</td>";
echo "<td style='width:150px;'>Perusahaan</td>";

// Loop Header Unik (Sudah Dirampingkan)
foreach ($uniqueHeaders as $key => $label) {
    $shortLabel = strlen($label) > 60 ? substr($label, 0, 57) . '...' : $label;
    echo "<td style='background-color:#e0e7ff; width:150px;'>$shortLabel</td>";
}
echo "</tr>";

// B. ISI DATA
$no = 1;
foreach ($respondents as $resp) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    
    // --- TANGGAL SUBMIT ---
    // Cek prioritas berdasarkan database SQL Anda
    $tgl = '-';
    // Berdasarkan file dev-survey_it (1).sql, nama kolomnya adalah `submission_date`
    if (!empty($resp['submission_date'])) {
        $tgl = $resp['submission_date'];
    } elseif (!empty($resp['submitted_at'])) {
        $tgl = $resp['submitted_at'];
    } elseif (!empty($resp['created_at'])) {
        $tgl = $resp['created_at'];
    }
    echo "<td>" . $tgl . "</td>"; 

    // DATA DIRI
    // Menggunakan tanda kutip ' agar Excel membaca NIK sebagai Text (angka 0 di depan aman)
    echo "<td>'" . ($resp['nik'] ?? '-') . "</td>"; 
    echo "<td>" . ($resp['full_name'] ?? '-') . "</td>"; 
    echo "<td>" . ($resp['email'] ?? '-') . "</td>"; 
    echo "<td>" . ($resp['division'] ?? '-') . "</td>"; 
    
    // NAMA PERUSAHAAN
    $stmtC = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
    $stmtC->execute([$resp['company_id']]);
    $compName = $stmtC->fetchColumn();
    echo "<td>" . $compName . "</td>";

    // --- JAWABAN YANG DIRAMPINGKAN ---
    
    // 1. Ambil jawaban mentah by ID
    $stmtAns = $pdo->prepare("SELECT question_id, answer_value FROM answers WHERE respondent_id = ?");
    $stmtAns->execute([$resp['id']]);
    $answersById = $stmtAns->fetchAll(PDO::FETCH_KEY_PAIR); 

    // 2. Konversi Jawaban: Dari ID menjadi Key Teks
    $answersByKey = [];
    foreach ($answersById as $qid => $val) {
        if (isset($questionIdToTextMap[$qid])) {
            $textKey = $questionIdToTextMap[$qid];
            $answersByKey[$textKey] = $val;
        }
    }

    // 3. Loop Kolom Header Unik
    foreach ($uniqueHeaders as $key => $label) {
        $val = isset($answersByKey[$key]) ? $answersByKey[$key] : '-';
        $val = str_replace(["\r", "\n"], " ", $val);
        echo "<td>" . htmlspecialchars($val) . "</td>";
    }

    echo "</tr>";
}

echo "</table>";
exit();
?>