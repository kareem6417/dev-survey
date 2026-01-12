<?php
require 'config.php';

// --- 1. LOGIKA AUTH SEDERHANA (Opsional: Ganti dengan login sistem Anda) ---
// if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

// --- 2. AMBIL DATA RINGKASAN ---
// Total Responden
$stmt = $pdo->query("SELECT COUNT(*) FROM respondents");
$totalRespondents = $stmt->fetchColumn();

// Responden per Perusahaan
$stmt = $pdo->query("SELECT company_id, COUNT(*) as count FROM respondents GROUP BY company_id");
$companyStatsRaw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [id => count]

// Ambil Nama Perusahaan agar label rapi
$companies = $pdo->query("SELECT id, name, code FROM companies")->fetchAll(PDO::FETCH_ASSOC);
$companyLabels = [];
$companyData = [];
$companyColors = ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f97316', '#eab308', '#22c55e'];

foreach ($companies as $comp) {
    $companyLabels[] = $comp['code'] ?: $comp['name'];
    $companyData[] = $companyStatsRaw[$comp['id']] ?? 0;
}

// --- 3. AMBIL DATA PERTANYAAN & JAWABAN ---
$questions = $pdo->query("SELECT * FROM questions ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fungsi Helper untuk memproses jawaban
function getQuestionStats($pdo, $q_id, $type) {
    $stmt = $pdo->prepare("SELECT answer_value FROM answers WHERE question_id = ?");
    $stmt->execute([$q_id]);
    $answers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stats = [];
    
    if ($type == 'checkbox') {
        // Khusus Checkbox: Pecah "FICO, HR" menjadi item terpisah
        foreach ($answers as $ans) {
            $items = explode(',', $ans);
            foreach ($items as $item) {
                $item = trim($item);
                if (!isset($stats[$item])) $stats[$item] = 0;
                $stats[$item]++;
            }
        }
    } elseif ($type == 'text') {
        // Khusus Text: Ambil 5 terbaru saja untuk preview
        $stats = array_slice($answers, 0, 5); 
    } else {
        // Rating & Yes/No
        $stats = array_count_values($answers);
    }
    
    // Sort keys agar rapi (Rating 1-10 berurut)
    if ($type == 'rating_10') ksort($stats);
    
    return $stats;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Survey Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bg-mesh {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
            background-attachment: fixed;
        }
        .card { background: rgba(255, 255, 255, 0.95); border-radius: 1rem; border: 1px solid rgba(255,255,255,0.1); }
    </style>
</head>
<body class="bg-mesh min-h-screen text-slate-800 pb-20">

    <nav class="bg-slate-900/80 backdrop-blur-md border-b border-white/10 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img src="logo1.png" class="h-8 w-auto"> 
                <span class="text-white font-bold text-lg">IT Service Dashboard</span>
            </div>
            <a href="export.php" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download Report (Excel)
            </a>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-10 space-y-8">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card p-6 flex flex-col justify-between shadow-lg shadow-indigo-500/10">
                <div>
                    <p class="text-slate-500 text-sm font-medium uppercase tracking-wider">Total Responden</p>
                    <h2 class="text-4xl font-bold text-slate-800 mt-2"><?php echo number_format($totalRespondents); ?></h2>
                </div>
                <div class="mt-4 text-xs text-green-600 bg-green-100 w-max px-2 py-1 rounded-full font-bold">
                    Live Data
                </div>
            </div>

            <div class="card p-6 md:col-span-2 shadow-lg shadow-indigo-500/10">
                <p class="text-slate-500 text-sm font-medium uppercase tracking-wider mb-4">Partisipasi per Entitas</p>
                <div class="h-48">
                    <canvas id="companyChart"></canvas>
                </div>
            </div>
        </div>

        <h3 class="text-white text-xl font-bold mt-12 mb-6 border-l-4 border-indigo-500 pl-4">Analisis Jawaban per Pertanyaan</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($questions as $q): 
                $dataStats = getQuestionStats($pdo, $q['id'], $q['input_type']);
                
                // Jika data kosong, skip atau tampilkan info
                if (empty($dataStats) && $q['input_type'] != 'text') continue;
            ?>
            
            <div class="card p-6 shadow-md hover:shadow-xl transition-shadow duration-300">
                <div class="flex justify-between items-start mb-4">
                    <span class="bg-indigo-100 text-indigo-700 text-xs font-bold px-2 py-1 rounded">Q<?php echo $q['id']; ?></span>
                    <span class="text-slate-400 text-xs uppercase"><?php echo $q['input_type']; ?></span>
                </div>
                <h4 class="text-slate-800 font-semibold mb-4 text-sm leading-relaxed min-h-[3rem]">
                    <?php echo $q['question_text']; ?>
                </h4>

                <div class="relative w-full">
                    <?php if ($q['input_type'] === 'text'): ?>
                        <ul class="space-y-2 max-h-48 overflow-y-auto pr-2">
                            <?php if(empty($dataStats)): ?>
                                <li class="text-slate-400 italic text-sm">Belum ada jawaban text.</li>
                            <?php else: ?>
                                <?php foreach ($dataStats as $ansText): ?>
                                    <li class="bg-slate-50 p-3 rounded-lg text-sm text-slate-600 border border-slate-100">
                                        "<?php echo htmlspecialchars($ansText); ?>"
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>

                    <?php else: ?>
                        <div class="h-48">
                            <canvas id="chart_<?php echo $q['id']; ?>"></canvas>
                        </div>
                        <script>
                            (function(){
                                const ctx = document.getElementById('chart_<?php echo $q['id']; ?>').getContext('2d');
                                const type = '<?php echo $q['input_type']; ?>';
                                const labels = <?php echo json_encode(array_keys($dataStats)); ?>;
                                const data = <?php echo json_encode(array_values($dataStats)); ?>;
                                
                                // Config warna
                                let bgColors = type === 'yes_no' ? ['#10b981', '#ef4444'] : '#6366f1';
                                let chartType = type === 'yes_no' ? 'doughnut' : 'bar';
                                
                                // Khusus Checkbox jadi Horizontal Bar
                                if (type === 'checkbox') {
                                    chartType = 'bar';
                                }

                                new Chart(ctx, {
                                    type: chartType,
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            label: 'Jumlah',
                                            data: data,
                                            backgroundColor: bgColors,
                                            borderRadius: 5,
                                            borderWidth: 0
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        indexAxis: type === 'checkbox' ? 'y' : 'x', // Checkbox horizontal
                                        plugins: {
                                            legend: { display: type === 'yes_no' },
                                            tooltip: {
                                                callbacks: {
                                                    label: function(context) {
                                                        return context.raw + ' User';
                                                    }
                                                }
                                            }
                                        },
                                        scales: type !== 'yes_no' ? {
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