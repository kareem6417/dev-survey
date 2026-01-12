<?php
// handler.php
require 'config.php';
header('Content-Type: application/json');

// Matikan error reporting agar JSON tidak rusak oleh warning PHP
error_reporting(0);
ini_set('display_errors', 0);

$action = $_GET['action'] ?? '';

// =============================================================
// 1. BAGIAN SEARCH NIK (INI SUDAH BENAR & SESUAI CODE ANDA)
// =============================================================
if ($action == 'search_nik') {
    $nik = $_GET['nik'] ?? '';

    // API CONFIG
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

    if ($err) {
        echo json_encode(['status' => 'error', 'message' => 'Koneksi API Gagal.']);
        exit;
    }

    $data = json_decode($response, true);

    if (isset($data['employee']) && count($data['employee']) > 0) {
        $userData = $data['employee'][0];
        
        $companyNameFromApi = isset($userData['BUTXT']) ? trim($userData['BUTXT']) : (isset($userData['ABKTX']) ? trim($userData['ABKTX']) : 'Unknown Company');
        
        // Parsing Tanggal Lahir (GBPAS)
        $dobRaw = isset($userData['GBPAS']) ? trim($userData['GBPAS']) : ''; 
        $dobFormatted = '';
        $cleanDate = preg_replace('/[^0-9]/', '', $dobRaw);

        if (strlen($cleanDate) === 8) {
            $y = substr($cleanDate, 0, 4);
            $m = substr($cleanDate, 4, 2);
            $d = substr($cleanDate, 6, 2);
            $dobFormatted = "$y-$m-$d"; 
        } 

        // Cek Database Perusahaan
        $stmt = $pdo->prepare("SELECT id FROM companies WHERE name = ? LIMIT 1");
        $stmt->execute([$companyNameFromApi]);
        $companyDB = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($companyDB) {
            $companyId = $companyDB['id'];
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO companies (name, code) VALUES (?, ?)");
            $codeDummy = strtoupper(substr(str_replace(' ', '', $companyNameFromApi), 0, 5)); 
            $stmtInsert->execute([$companyNameFromApi, $codeDummy]);
            $companyId = $pdo->lastInsertId();
        }

        $result = [
            'name' => $userData['CNAME'] ?? '',
            'email' => $userData['UMAIL'] ?? '',
            'division' => $userData['ORGTX'] ?? '',
            'company_name' => $companyNameFromApi,
            'company_id' => $companyId,
            'dob_check' => $dobFormatted
        ];

        echo json_encode(['status' => 'success', 'data' => $result]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'NIK tidak ditemukan.']);
    }
    exit;
}

// =============================================================
// 2. BAGIAN SUBMIT (SAYA PERBAIKI DI SINI UTK CHECKBOX)
// =============================================================
if ($action == 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        // Simpan Data Responden
        $stmt = $pdo->prepare("INSERT INTO respondents (nik, full_name, email, division, company_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['nik'], 
            $input['name'], 
            $input['email'], 
            $input['division'], 
            $input['company_id'] ?: null
        ]);
        $respondent_id = $pdo->lastInsertId();

        // Simpan Jawaban
        $stmtAnswer = $pdo->prepare("INSERT INTO answers (respondent_id, question_id, answer_value, respondent_nik, respondent_name, respondent_email, respondent_company, respondent_division) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $companyName = $input['company_name'] ?? '-';

        foreach ($input['answers'] as $q_id => $val) {
            
            // --- [FIX] Logika Penanganan Checkbox ---
            // Jika jawaban berupa Array (misal user pilih FICO dan MM), gabungkan jadi string.
            if (is_array($val)) {
                $final_val = implode(', ', $val); // Hasil: "FICO, MM"
            } else {
                $final_val = $val; // Hasil: "Ya" atau "Sangat Baik"
            }
            // ----------------------------------------

            $stmtAnswer->execute([
                $respondent_id, 
                $q_id, 
                $final_val, // Gunakan variabel yang sudah diperbaiki
                $input['nik'], 
                $input['name'], 
                $input['email'], 
                $companyName, 
                $input['division']
            ]);
        }
        
        $pdo->commit();
        echo json_encode(['status' => 'success']);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>