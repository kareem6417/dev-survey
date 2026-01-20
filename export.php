<?php
// 1. BERSIHKAN BUFFER & SESSION
ob_start();
session_start();
require 'config.php';

// 2. CEK KEAMANAN
if (!isset($_SESSION['is_admin_logged_in']) || $_SESSION['is_admin_logged_in'] !== true) {
    die("Akses Ditolak. Harap login terlebih dahulu.");
}

// 3. FILTER & HAK AKSES
$adminScope = $_SESSION['admin_scope'] ?? 0;   // 'ALL' atau ID Company
$filterInput = $_GET['filter_company'] ?? 'ALL';

// Tentukan Scope Akhir
$finalFilter = 'ALL';
if ($adminScope === 'ALL') {
    $finalFilter = $filterInput; // Super Admin bisa pilih ALL atau spesifik
} else {
    $finalFilter = $adminScope;  // Admin PT terkunci di PT-nya sendiri
}

// 4. SIAPKAN DAFTAR PERUSAHAAN (Untuk dijadikan Sheet)
$sqlComp = "SELECT * FROM companies";
$paramsComp = [];

// Jika filter spesifik (bukan ALL), hanya ambil 1 perusahaan itu saja
if ($finalFilter !== 'ALL') {
    $sqlComp .= " WHERE id = ?";
    $paramsComp[] = $finalFilter;
}
$sqlComp .= " ORDER BY id ASC";

$stmtComp = $pdo->prepare($sqlComp);
$stmtComp->execute($paramsComp);
$companies = $stmtComp->fetchAll(PDO::FETCH_ASSOC);

// Bersihkan buffer sebelum kirim header
ob_end_clean();

// 5. HEADER DOWNLOAD XML EXCEL
$fileName = "Survey_Report_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$fileName\"");
header("Pragma: no-cache");
header("Expires: 0");

// ==============================================================================
// 6. STRUKTUR XML EXCEL (Mendukung Multi Sheet)
// ==============================================================================
echo '<?xml version="1.0"?>';
echo '<?mso-application progid="Excel.Sheet"?>';
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
 <Styles>
  <Style ss:ID="Default" ss:Name="Normal">
   <Alignment ss:Vertical="Bottom"/>
   <Borders/>
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000"/>
   <Interior/>
   <NumberFormat/>
   <Protection/>
  </Style>
  <Style ss:ID="HeaderStyle">
   <Font ss:FontName="Calibri" x:Family="Swiss" ss:Size="11" ss:Color="#000000" ss:Bold="1"/>
   <Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/>
   <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
  </Style>
  <Style ss:ID="DateStyle">
   <NumberFormat ss:Format="Short Date"/>
  </Style>
 </Styles>

<?php
// ==============================================================================
// 7. LOOPING SHEET PER PERUSAHAAN
// ==============================================================================
foreach ($companies as $company) {
    $companyId = $company['id'];
    // Nama Sheet (Bersihkan karakter yang dilarang Excel: \ / ? * [ ] :)
    $sheetName = preg_replace('/[\\\\\/:\*\?\[\]]/', '', $company['code'] ?: $company['name']);
    $sheetName = substr($sheetName, 0, 30); // Maksimal 31 karakter
    
    // --- AMBIL PERTANYAAN KHUSUS PT INI ---
    // Ini kuncinya: Pertanyaan MIP tidak akan muncul di Sheet MPM
    $stmtQ = $pdo->prepare("SELECT id, question_text FROM questions WHERE company_id = ? ORDER BY id ASC");
    $stmtQ->execute([$companyId]);
    $questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

    // --- AMBIL RESPONDEN KHUSUS PT INI ---
    $stmtR = $pdo->prepare("SELECT * FROM respondents WHERE company_id = ? ORDER BY id DESC");
    $stmtR->execute([$companyId]);
    $respondents = $stmtR->fetchAll(PDO::FETCH_ASSOC);

    // Mulai Worksheet
    echo '<Worksheet ss:Name="' . htmlspecialchars($sheetName) . '">';
    echo '<Table>';

    // --- A. HEADER BARIS (Judul Kolom) ---
    echo '<Row>';
    // Kolom Statis
    $headers = ['No', 'Tanggal Submit', 'NIK', 'Nama', 'Email', 'Divisi'];
    foreach ($headers as $h) {
        echo '<Cell ss:StyleID="HeaderStyle"><Data ss:Type="String">' . $h . '</Data></Cell>';
    }
    // Kolom Pertanyaan Dinamis
    foreach ($questions as $q) {
        $qText = strip_tags($q['question_text']); // Bersihkan HTML
        $qText = htmlspecialchars($qText); // Escape XML
        echo '<Cell ss:StyleID="HeaderStyle"><Data ss:Type="String">' . $qText . '</Data></Cell>';
    }
    echo '</Row>';

    // --- B. ISI DATA RESPONDEN ---
    $no = 1;
    foreach ($respondents as $resp) {
        echo '<Row>';
        
        // 1. No
        echo '<Cell><Data ss:Type="Number">' . $no++ . '</Data></Cell>';
        
        // 2. Tanggal (Prioritas: submitted_at -> created_at -> date)
        $tgl = $resp['submitted_at'] ?? $resp['created_at'] ?? $resp['date'] ?? '';
        echo '<Cell><Data ss:Type="String">' . $tgl . '</Data></Cell>';

        // 3. NIK (String agar nol di depan tidak hilang)
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($resp['nik'] ?? '-') . '</Data></Cell>';

        // 4. Nama
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($resp['full_name'] ?? '-') . '</Data></Cell>';

        // 5. Email
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($resp['email'] ?? '-') . '</Data></Cell>';

        // 6. Divisi
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($resp['division'] ?? '-') . '</Data></Cell>';

        // --- AMBIL JAWABAN UNTUK ROW INI ---
        $stmtAns = $pdo->prepare("SELECT question_id, answer_value FROM answers WHERE respondent_id = ?");
        $stmtAns->execute([$resp['id']]);
        $answers = $stmtAns->fetchAll(PDO::FETCH_KEY_PAIR); // [question_id => jawaban]

        // Loop Jawaban sesuai Urutan Pertanyaan Header Sheet Ini
        foreach ($questions as $q) {
            $val = isset($answers[$q['id']]) ? $answers[$q['id']] : '-';
            // Bersihkan karakter kontrol XML yang invalid
            $val = htmlspecialchars($val); 
            echo '<Cell><Data ss:Type="String">' . $val . '</Data></Cell>';
        }

        echo '</Row>';
    }

    echo '</Table>';
    echo '</Worksheet>';
}
?>
</Workbook>