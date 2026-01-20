<?php
// BUFFERING: Mencegah error output sebelum download
ob_start();
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

// ============================================================
// 3. LOGIKA MERGING PERTANYAAN (AGAR TIDAK BERULANG)
// ============================================================

// Ambil pertanyaan (Filter sesuai hak akses/pilihan)
$sqlQ = "SELECT id, question_text FROM questions";
$paramsQ = [];

if ($finalFilter !== 'ALL') {
    $sqlQ .= " WHERE company_id = ?";
    $paramsQ[] = $finalFilter;
}
// Urutkan berdasarkan Teks agar pertanyaan yang sama berkumpul
$sqlQ .= " ORDER BY question_text ASC, id ASC"; 

$stmtQ = $pdo->prepare($sqlQ);
$stmtQ->execute($paramsQ);
$allQuestions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

// MAPPING & HEADER PREPARATION
$questionIdToKeyMap = []; // Map ID Pertanyaan -> Key Unik
$uniqueHeaders = [];      // Daftar Header Unik (Key -> Label Asli)

foreach ($allQuestions as $q) {
    // BERSIHKAN TEKS SEBERSIH-BERSIHNYA
    $raw = $q['question_text'];
    $raw = html_entity_decode($raw);       // Ubah &nbsp; jadi spasi
    $raw = strip_tags($raw);               // Hapus tag HTML
    $raw = preg_replace('/\s+/', ' ', $raw); // Hapus spasi ganda/enter
    $raw = trim($raw);                     // Hapus spasi depan/belakang
    
    // Key Unik (Huruf kecil semua)
    $cleanKey = strtolower($raw); 
    
    // Map ID -> Key (Contoh: ID 10 -> "internet", ID 55 -> "internet")
    $questionIdToKeyMap[$q['id']] = $cleanKey;

    // Simpan Header (Jika belum ada)
    if (!isset($uniqueHeaders[$cleanKey])) {
        // Label Header yang cantik (Huruf besar asli)
        $uniqueHeaders[$cleanKey] = $raw;
    }
}

// KUNCI URUTAN KOLOM (PENTING AGAR TIDAK GESER)
// Kita simpan daftar Key urut sesuai Header yang terbentuk
$finalColumnKeys = array_keys($uniqueHeaders);


// ============================================================
// 4. AMBIL DATA RESPONDEN
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
// 5. OUTPUT FILE EXCEL
// ============================================================
ob_end_clean(); // Bersihkan buffer

$fileName = "Survey_Report_" . date('Y-m-d_His') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$fileName");
header("Pragma: no-cache");
header("Expires: 0");

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

// Loop Header sesuai urutan KUNCI yang sudah kita kunci di atas
foreach ($finalColumnKeys as $key) {
    $label = $uniqueHeaders[$key];
    $shortLabel = strlen($label) > 60 ? substr($label, 0, 57) . '...' : $label;
    echo "<td style='background-color:#e0e7ff; width:150px;'>$shortLabel</td>";
}
echo "</tr>";

// B. ISI DATA
$no = 1;
foreach ($respondents as $resp) {
    echo "<tr>";
    echo "<td>" . $no++ . "</td>";
    
    // --- FIX TANGGAL (Cek semua kemungkinan kolom) ---
    $tgl = '-';
    if (!empty($resp['submitted_at'])) {
        $tgl = $resp['submitted_at'];
    } elseif (!empty($resp['created_at'])) {
        $tgl = $resp['created_at'];
    } elseif (!empty($resp['submission_date'])) {
        $tgl = $resp['submission_date'];
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

    // --- PENGAMBILAN JAWABAN (MENGGUNAKAN LOGIKA MERGE) ---
    
    // 1. Ambil semua jawaban responden ini (ID Pertanyaan => Nilai)
    $stmtAns = $pdo->prepare("SELECT question_id, answer_value FROM answers WHERE respondent_id = ?");
    $stmtAns->execute([$resp['id']]);
    $answersById = $stmtAns->fetchAll(PDO::FETCH_KEY_PAIR); 

    // 2. Petakan Jawaban ke Key Teks
    $respondentAnswersByKey = [];
    foreach ($answersById as $qid => $val) {
        if (isset($questionIdToKeyMap[$qid])) {
            $key = $questionIdToKeyMap[$qid]; // Ubah ID 10 jadi "internet"
            $respondentAnswersByKey[$key] = $val;
        }
    }

    // 3. Isi Kolom sesuai URUTAN KUNCI HEADER (Dijamin Match)
    foreach ($finalColumnKeys as $key) {
        // Cek apakah user punya jawaban untuk Key pertanyaan ini?
        $val = isset($respondentAnswersByKey[$key]) ? $respondentAnswersByKey[$key] : '-';
        
        // Bersihkan enter agar tabel tidak pecah
        $val = str_replace(["\r", "\n"], " ", $val);
        echo "<td>" . htmlspecialchars($val) . "</td>";
    }

    echo "</tr>";
}

echo "</table>";
exit();
?>