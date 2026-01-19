<?php
session_start();

// Cek Sesi
if (!isset($_SESSION['is_admin_logged_in']) || $_SESSION['is_admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require 'config.php';

// Ambil Nama Admin dari sesi (jika ada)
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Total Responden
$stmt = $pdo->query("SELECT COUNT(*) FROM respondents");
$totalRespondents = $stmt->fetchColumn();

// Responden per Perusahaan
$stmt = $pdo->query("SELECT company_id, COUNT(*) as count FROM respondents GROUP BY company_id");
$companyStatsRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Ambil Nama Perusahaan
$companies = $pdo->query("SELECT id, name, code FROM companies")->fetchAll(PDO::FETCH_ASSOC);
$companyLabels = [];
$companyData = [];
$companyColors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e'];

foreach ($companies as $comp) {
    $companyLabels[] = $comp['code'] ?: $comp['name'];
    $companyData[] = $companyStatsRaw[$comp['id']] ?? 0;
}

// Ambil Data Pertanyaan
$questions = $pdo->query("SELECT * FROM questions ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fungsi Helper Persentase
function getAnswerStats($pdo, $question_id) {
    $stmt = $pdo->prepare("SELECT answer_value, COUNT(*) as count FROM answers WHERE question_id = ? GROUP BY answer_value");
    $stmt->execute([$question_id]);
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Hasil Survey</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }</style>
</head>
<body>

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-600 text-white p-2 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-slate-800 leading-none">IT Survey Dashboard</h1>
                        <p class="text-xs text-slate-500 mt-0.5">Welcome, <?= htmlspecialchars($adminName) ?></p>
                    </div>
                </div>

                <div class="flex items-center">
                    <a href="logout.php" class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors border border-red-100">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Keluar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Total Responden</p>
                    <h2 class="text-4xl font-bold text-slate-800"><?php echo number_format($totalRespondents); ?></h2>
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

        <h2 class="text-2xl font-bold text-slate-800 mb-6">Analisa Jawaban</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($questions as $q): 
                $stats = getAnswerStats($pdo, $q['id']);
                $chartId = "chart_" . $q['id'];
                
                // Siapkan data chart
                $labels = array_keys($stats);
                $values = array_values($stats);
                $type = $q['input_type']; 
            ?>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow">
                <div class="mb-4 h-16 overflow-hidden">
                    <h4 class="text-sm font-semibold text-slate-700 line-clamp-2" title="<?= htmlspecialchars($q['question_text']) ?>">
                        <?= htmlspecialchars($q['question_text']) ?>
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