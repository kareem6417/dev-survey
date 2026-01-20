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

// ============================================================
// 4. LOGIKA PERAMPINGAN PERTANYAAN (MERGE QUESTION TEXT)
// ============================================================

// Ambil semua pertanyaan yang relevan
$sqlQ = "SELECT id, question_text, sort_order FROM questions";
$paramsQ = [];

if ($finalFilter !== 'ALL') {
    $sqlQ .= " WHERE company_id = ?";
    $paramsQ[] = $finalFilter;
}
$sqlQ .= " ORDER BY sort_order ASC, id ASC"; // Urutkan biar rapi

$stmtQ = $pdo->prepare($sqlQ);
$stmtQ->execute($paramsQ);
$allQuestions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

// ARRAY MAP: KUNCI UTAMA PERBAIKAN
// Kita akan memetakan: ID Pertanyaan -> Teks Pertanyaan yang Bersih
// Dan membuat daftar Header Unik (agar tidak berulang walaupun beda PT)
$questionIdToTextMap = []; 
$uniqueHeaders = []; 

foreach ($allQuestions as $q) {
    // 1. Bersihkan teks (hapus HTML tags, spasi berlebih, lowercase biar seragam)
    //    Contoh: "  Apakah Internet <b>Lancar</b>? " -> "apakah internet lancar?"
    $cleanTextRaw = strip_tags($q['question_text']);
    $cleanTextRaw = preg_replace('/\s+/', ' ', $cleanTextRaw); // Hapus spasi ganda
    $cleanKey = trim(strtolower($cleanTextRaw)); // Key untuk pencocokan (huruf kecil)
    
    // Label Header Asli (Huruf besar/kecil asli untuk tampilan Excel)
    $headerLabel = trim(strip_tags($q['question_text']));

    // Simpan Mapping ID -> Key
    $questionIdToTextMap[$q['id']] = $cleanKey;

    // Simpan Header Unik (Gunakan Key agar pertanyaan sama dari PT beda jadi 1 kolom)
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
// 6. OUTPUT TABEL EXCEL
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

// Loop Header Pertanyaan yang SUDAH DIRAMPINGKAN (Unique)
foreach ($uniqueHeaders as $key => $label) {
    // Potong label kalau kepanjangan biar Excel gak jelek
    $shortLabel = strlen($label) > 60 ? substr($label, 0, 57) . '...' : $label;
    echo "<td style='background-color:#e0e7ff; width:150px;'>$shortLabel</td>";
}
echo "</tr>";

// B. ISI DATA
$no = 1;
foreach ($respondents as $resp) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    
    // --- [PERBAIKAN 1] TANGGAL SUBMIT ---
    // Cek prioritas: submitted_at -> created_at -> date -> -
    $tgl = '-';
    if (!empty($resp['submitted_at'])) {
        $tgl = $resp['submitted_at'];
    } elseif (!empty($resp['created_at'])) {
        $tgl = $resp['created_at'];
    }
    echo "<td>" . $tgl . "</td>"; 

    // DATA DIRI
    echo "<td>'" . ($resp['nik'] ?? '-') . "</td>"; 
    echo "<td>" . ($resp['full_name'] ?? '-') . "</td>"; 
    echo "<td>" . ($resp['email'] ?? '-') . "</td>"; 
    echo "<td>" . ($resp['division'] ?? '-') . "</td>"; 
    
    // NAMA PERUSAHAAN
    $stmtC = $pdo->prepare("SELECT name FROM companies WHERE id = ?");
    $stmtC->execute([$resp['company_id']]);
    $compName = $stmtC->fetchColumn();
    echo "<td>" . $compName . "</td>";

    // --- [PERBAIKAN 2] JAWABAN YANG DIRAMPINGKAN ---
    
    // 1. Ambil jawaban mentah by ID
    $stmtAns = $pdo->prepare("SELECT question_id, answer_value FROM answers WHERE respondent_id = ?");
    $stmtAns->execute([$resp['id']]);
    $answersById = $stmtAns->fetchAll(PDO::FETCH_KEY_PAIR); // [ID_10 => 'Ya', ID_11 => 'Baik']

    // 2. Konversi Jawaban: Dari ID menjadi Teks Pertanyaan (Key)
    $answersByKey = [];
    foreach ($answersById as $qid => $val) {
        // Cek ID pertanyaan ini punya teks apa (mapping yang kita buat di atas)
        if (isset($questionIdToTextMap[$qid])) {
            $textKey = $questionIdToTextMap[$qid];
            $answersByKey[$textKey] = $val;
        }
    }

    // 3. Loop Kolom Header Unik & Isi Datanya
    foreach ($uniqueHeaders as $key => $label) {
        // Cek apakah responden ini punya jawaban untuk Teks Pertanyaan ini?
        $val = isset($answersByKey[$key]) ? $answersByKey[$key] : '-';
        
        // Bersihkan enter/newline
        $val = str_replace(["\r", "\n"], " ", $val);
        echo "<td>" . htmlspecialchars($val) . "</td>";
    }

    echo "</tr>";
}

echo "</table>";
exit();
?>