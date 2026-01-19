<?php
session_start();

// 1. Cek Sesi Login
if (!isset($_SESSION['is_admin_logged_in']) || $_SESSION['is_admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require 'config.php';

// 2. Ambil Info User & Hak Akses
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminScope = $_SESSION['admin_scope'] ?? 0; // 'ALL' atau ID Company (3, 5, dll)

// 3. Logika Filter Query
$currentFilter = $adminScope;
if ($adminScope === 'ALL' && isset($_GET['filter_company']) && $_GET['filter_company'] !== 'ALL') {
    $currentFilter = $_GET['filter_company'];
}

// Persiapkan potongan SQL WHERE
$whereClause = ""; 
$params = [];

if ($currentFilter !== 'ALL') {
    $whereClause = " WHERE company_id = ? ";
    $params = [$currentFilter];
}

// -----------------------------------------------------------
// 4. QUERY DATA
// -----------------------------------------------------------

// A. Total Responden
$sqlTotal = "SELECT COUNT(*) FROM respondents" . $whereClause;
$stmt = $pdo->prepare($sqlTotal);
$stmt->execute($params);
$totalRespondents = $stmt->fetchColumn();

// B. Chart Statistik Perusahaan
$sqlStats = "SELECT company_id, COUNT(*) as count FROM respondents " . $whereClause . " GROUP BY company_id";
$stmtStats = $pdo->prepare($sqlStats);
$stmtStats->execute($params);
$companyStatsRaw = $stmtStats->fetchAll(PDO::FETCH_KEY_PAIR);

// Ambil Daftar Perusahaan
$companies = $pdo->query("SELECT id, name, code FROM companies")->fetchAll(PDO::FETCH_ASSOC);

// --- [BARU] Buat Mapping ID ke Nama PT untuk Banner ---
$companyNamesMap = [];
foreach ($companies as $c) {
    // Kita simpan: ID => Nama PT (contoh: 3 => 'PT. Maritim Prima Mandiri')
    $companyNamesMap[$c['id']] = $c['name']; 
}

// Siapkan Data Chart
$companyLabels = [];
$companyData = [];
$companyColors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e'];

foreach ($companies as $comp) {
    if ($currentFilter === 'ALL' || $currentFilter == $comp['id']) {
        $companyLabels[] = $comp['code'] ?: $comp['name'];
        $companyData[] = $companyStatsRaw[$comp['id']] ?? 0;
    }
}

// C. Data Jawaban
$sqlQ = "SELECT * FROM questions";
$paramsQ = [];

if ($currentFilter !== 'ALL') {
    $sqlQ .= " WHERE company_id = ?";
    $paramsQ[] = $currentFilter;
}

$sqlQ .= " ORDER BY id ASC";

$stmtQ = $pdo->prepare($sqlQ);
$stmtQ->execute($paramsQ);
$questions = $stmtQ->fetchAll(PDO::FETCH_ASSOC);
function getAnswerStats($pdo, $question_id, $filterCompanyId) {
    $sql = "SELECT a.answer_value, COUNT(*) as count 
            FROM answers a 
            JOIN respondents r ON a.respondent_id = r.id 
            WHERE a.question_id = ?";
    $queryParams = [$question_id];

    if ($filterCompanyId !== 'ALL') {
        $sql .= " AND r.company_id = ?";
        $queryParams[] = $filterCompanyId;
    }

    $sql .= " GROUP BY a.answer_value";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($queryParams);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Survey - <?= htmlspecialchars($adminName) ?></title>
    <link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }</style>
</head>
<body>

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between h-auto md:h-16 py-3 md:py-0 items-center gap-4">
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <div class="bg-blue-600 text-white p-2 rounded-lg shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-slate-800 leading-none">Dashboard Survey IT</h1>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Halo, <?= htmlspecialchars($adminName) ?> 
                            <?php if($adminScope === 'ALL'): ?>
                                <span class="bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded text-[10px] font-bold ml-1">SUPER ADMIN</span>
                            <?php else: ?>
                                <span class="bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded text-[10px] font-bold ml-1">ADMIN PT</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <?php if ($adminScope === 'ALL'): ?>
                <form method="GET" class="w-full md:w-auto flex items-center">
                    <div class="relative w-full md:w-64">
                        <select name="filter_company" onchange="this.form.submit()" class="w-full appearance-none bg-slate-50 border border-slate-200 text-slate-700 py-2 pl-4 pr-8 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer font-medium">
                            <option value="ALL" <?= $currentFilter === 'ALL' ? 'selected' : '' ?>>Semua Perusahaan</option>
                            <?php foreach ($companies as $comp): ?>
                                <option value="<?= $comp['id'] ?>" <?= $currentFilter == $comp['id'] ? 'selected' : '' ?>>
                                    <?= $comp['code'] ?: $comp['name'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
                <div class="flex items-center w-full md:w-auto justify-end gap-2">
                    <a href="export.php?filter_company=<?= $currentFilter ?>" target="_blank" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors shadow-sm shadow-green-200">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                        Export Excel
                    </a>
                    <a href="logout.php" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-100">
                        Keluar
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    </a>
                </div>                
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <?php if ($currentFilter !== 'ALL'): ?>
            <div class="bg-blue-50 border border-blue-100 text-blue-700 px-4 py-3 rounded-xl mb-6 flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <p class="font-bold text-sm">Menampilkan Data Terfilter</p>
                    <p class="text-xs mt-1 opacity-80">
                        Anda sedang melihat hasil survey khusus untuk: 
                        <strong>
                            <?= isset($companyNamesMap[$currentFilter]) ? htmlspecialchars($companyNamesMap[$currentFilter]) : $currentFilter ?>
                        </strong>
                        <?php if($adminScope !== 'ALL') echo "(Akses Terbatas)"; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Total Responden</p>
                    <h2 class="text-4xl font-bold text-slate-800"><?php echo number_format($totalRespondents); ?></h2>
                    <p class="text-xs text-slate-400 mt-2">
                        <?= ($currentFilter === 'ALL') ? 'Semua Perusahaan' : (isset($companyNamesMap[$currentFilter]) ? $companyNamesMap[$currentFilter] : 'Perusahaan Terpilih') ?>
                    </p>
                </div>
                <div class="bg-blue-50 p-4 rounded-xl text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100">
                <h3 class="text-sm font-bold text-slate-700 mb-4 uppercase tracking-wide">Partisipasi per Perusahaan</h3>
                <div class="h-48">
                    <canvas id="companyChart"></canvas>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-200 my-8"></div>

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-slate-800">Analisa Jawaban</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($questions as $q): 
                $stats = getAnswerStats($pdo, $q['id'], $currentFilter);
                $chartId = "chart_" . $q['id'];
                $labels = array_keys($stats);
                $values = array_values($stats);
                $type = $q['input_type']; 
            ?>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                <div class="mb-4 h-16 overflow-hidden">
                    <?php 
                        $cleanQuestion = strip_tags($q['question_text']); 
                    ?>
                    <h4 class="text-sm font-semibold text-slate-700 line-clamp-2" title="<?= htmlspecialchars($cleanQuestion) ?>">
                        <?= htmlspecialchars($cleanQuestion) ?>
                    </h4>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 mt-1 inline-block uppercase font-bold tracking-wider">
                        <?= $type ?>
                    </span>
                </div>

                <div class="relative h-48 w-full">
                    <?php if ($type == 'text'): ?>
                        <div class="h-full overflow-y-auto bg-slate-50 p-3 rounded text-xs text-slate-600 space-y-2 border border-slate-100">
                            <?php if(empty($stats)): ?>
                                <p class="text-center italic text-slate-400 mt-10">Belum ada jawaban text.</p>
                            <?php else: ?>
                                <ul class="list-disc pl-4 space-y-1">
                                    <?php foreach($stats as $ansText => $count): ?>
                                        <li>
                                            <span class="font-medium text-slate-800">"<?= htmlspecialchars($ansText) ?>"</span>
                                            <span class="text-slate-400 ml-1">(<?= $count ?>)</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <canvas id="<?= $chartId ?>"></canvas>
                        <script>
                            (function(){
                                const ctx = document.getElementById('<?= $chartId ?>').getContext('2d');
                                const type = '<?= $type ?>';
                                const chartType = (type === 'rating_10' || type === 'checkbox') ? 'bar' : 'doughnut';
                                
                                new Chart(ctx, {
                                    type: chartType,
                                    data: {
                                        labels: <?php echo json_encode($labels); ?>,
                                        datasets: [{
                                            label: 'Jumlah',
                                            data: <?php echo json_encode($values); ?>,
                                            backgroundColor: [
                                                '#3b82f6', '#10b981', '#f59e0b', '#ef4444', 
                                                '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6'
                                            ],
                                            borderWidth: 0,
                                            borderRadius: 4
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: { 
                                                display: chartType === 'doughnut',
                                                position: 'bottom',
                                                labels: { boxWidth: 10, font: { size: 10 } }
                                            }
                                        },
                                        scales: chartType === 'bar' ? {
                                            y: { beginAtZero: true, grid: { display: false } },
                                            x: { grid: { display: false } }
                                        } : {}
                                    }
                                });
                            })();
                        </script>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        const ctxComp = document.getElementById('companyChart').getContext('2d');
        new Chart(ctxComp, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($companyLabels); ?>,
                datasets: [{
                    label: 'Responden',
                    data: <?php echo json_encode($companyData); ?>,
                    backgroundColor: <?php echo json_encode($companyColors); ?>,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>