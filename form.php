<?php
require 'config.php'; 

// 1. Cek akses POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// 2. AMBIL INPUT
$input_company = $_POST['company_id'] ?? null; 

// 3. LOGIC PINTAR: CEK KE TABEL COMPANIES
$final_company_id = null;
$final_company_name = $_POST['company_name'] ?? '';

if ($input_company) {
    $stmt = $pdo->prepare("SELECT id, name, code FROM companies WHERE id = ? OR code = ? LIMIT 1");
    $stmt->execute([$input_company, $input_company]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $final_company_id = $data['id']; 
        if (empty($final_company_name)) {
            $final_company_name = $data['name'];
        }
    }
}

// 4. SUSUN DATA USER
$user = [
    'nik' => $_POST['nik'] ?? null,
    'name' => $_POST['name'] ?? 'User',
    'email' => $_POST['email'] ?? '',
    'division' => $_POST['division'] ?? '',
    'company_id' => $final_company_id,
    'company_name' => $final_company_name
];

// 5. AMBIL PERTANYAAN
$stmt = $pdo->prepare("SELECT * FROM questions WHERE company_id IS NULL OR company_id = ? ORDER BY id ASC");
$stmt->execute([$final_company_id]);
$questionsDB = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. FORMAT PERTANYAAN
$questions = [];
$mainCounter = 0; 
$subCounter = 0;  
$lastParentNum = 0; 

foreach ($questionsDB as $q) {
    $isChild = !empty($q['dependency_id']);
    
    if (!$isChild) {
        $mainCounter++;
        $subCounter = 0;
        $displayNumber = "$mainCounter";
        $lastParentNum = $mainCounter; 
    } else {
        $subCounter++;
        $displayNumber = "$lastParentNum.$subCounter";
    }

    $optionsArray = [];
    if (!empty($q['options'])) {
        $optionsArray = array_map('trim', explode(',', $q['options']));
    }

    $questions[$q['id']] = [
        'id' => $q['id'],
        'text' => $q['question_text'],
        'type' => $q['input_type'], 
        'options' => $optionsArray,
        'number' => $displayNumber,
        'is_child' => $isChild,
        'dependency_id' => !empty($q['dependency_id']) ? $q['dependency_id'] : null,
        'dependency_value' => !empty($q['dependency_value']) ? $q['dependency_value'] : null,
        'label_min' => !empty($q['label_min']) ? $q['label_min'] : 'Sangat Buruk',
        'label_max' => !empty($q['label_max']) ? $q['label_max'] : 'Sangat Baik'
    ];
}
?>

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Satisfaction Survey</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
                    colors: { darkCard: '#1e293b', darkBg: '#0f172a' }
                }
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Background Mesh */
        .bg-mesh-light {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,96%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,90%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,90%,1) 0, transparent 50%);
            background-attachment: fixed;
        }
        .bg-mesh-dark {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,20%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,20%,1) 0, transparent 50%);
            background-attachment: fixed;
        }

        .pro-card {
            border-radius: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
        }
        
        .rating-circle {
            transition: all 0.2s ease;
            width: 100%; aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
            border-radius: 9999px; font-weight: 700; 
        }
        .rating-circle:hover { transform: scale(1.1); }

        .fade-in-up { animation: fadeInUp 0.5s ease-out forwards; opacity: 0; transform: translateY(15px); }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        
        /* Progress Bar di Header */
        .progress-container { position: fixed; top: 0; left: 0; width: 100%; height: 5px; z-index: 110; background: rgba(0,0,0,0.05); }
        .progress-bar { height: 100%; background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899); transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1); }
        
        /* Tooltip Arrow */
        .tooltip-arrow::before {
            content: ""; position: absolute; top: -5px; right: 10px;
            border-width: 0 5px 5px 5px; border-style: solid; border-color: transparent transparent #1e293b transparent;
        }
    </style>
</head>

<body x-data="themeHandler()" :class="isDark ? 'bg-mesh-dark text-slate-200' : 'bg-mesh-light text-slate-800'" class="antialiased min-h-screen transition-colors duration-300">

    <div class="progress-container">
        <div class="progress-bar" id="smartProgressBar" style="width: 0%"></div>
    </div>

    <header class="fixed top-0 inset-x-0 z-40 transition-all duration-300" 
        :class="scrolled ? (isDark ? 'bg-slate-900/80 border-slate-700' : 'bg-white/80 border-slate-200') + ' backdrop-blur-md shadow-sm border-b py-3' : 'bg-transparent py-6'"
        x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 20)">
        
        <div class="max-w-5xl mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <img src="logo1.png" alt="Logo" class="h-10 w-auto object-contain drop-shadow-md">
                <div class="hidden sm:block">
                    <h1 class="font-bold text-lg leading-tight tracking-tight" :class="isDark ? 'text-white' : 'text-slate-800'">
                        IT Satisfaction Survey
                    </h1>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                
                <div class="relative group">
                    <button @click="toggleTheme()" class="p-2 rounded-full transition-colors duration-200 relative z-10" 
                        :class="isDark ? 'bg-slate-800 text-yellow-400 hover:bg-slate-700 ring-1 ring-slate-700' : 'bg-white text-slate-600 hover:bg-slate-100 shadow-sm border border-slate-200'">
                        <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>

                    <div class="absolute right-0 top-full mt-2 w-32 px-2 py-1.5 bg-slate-800 text-white text-xs text-center rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none shadow-lg tooltip-arrow z-20">
                        Ganti Tema (Gelap/Terang)
                    </div>
                </div>

                <div class="hidden sm:flex items-center gap-3 pl-4 pr-1.5 py-1.5 rounded-full border transition-all"
                    :class="isDark ? 'border-slate-700 bg-slate-800' : 'border-white bg-white/60 shadow-sm'">
                    <div class="text-right">
                        <p class="text-xs font-bold" :class="isDark ? 'text-slate-200' : 'text-slate-700'"><?php echo explode(' ', $user['name'])[0]; ?></p>
                        <p class="text-[10px]" :class="isDark ? 'text-slate-400' : 'text-slate-500'"><?php echo htmlspecialchars($user['division']); ?></p>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-sm font-extrabold text-white shadow-sm">
                        <?php echo substr($user['name'], 0, 1); ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="pt-32 pb-24 px-4" x-data="surveyForm()">
        <div class="max-w-3xl mx-auto space-y-6">

            <div class="pro-card p-8 sm:p-10 text-center relative overflow-hidden fade-in-up shadow-xl" 
                 :class="isDark ? 'bg-slate-800 border border-slate-700' : 'bg-white border border-slate-100'"
                 style="animation-delay: 0.05s;">
                <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>
                <div class="relative z-10">
                    <h2 class="text-2xl sm:text-3xl font-bold mb-3 tracking-tight" :class="isDark ? 'text-white' : 'text-slate-800'">
                        Halo, <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-500"><?php echo htmlspecialchars($user['name']); ?></span> 👋
                    </h2>
                    <p class="leading-relaxed max-w-xl mx-auto text-base" :class="isDark ? 'text-slate-400' : 'text-slate-500'">
                        Pendapat Anda sangat berharga. Mohon isi survey ini dengan objektif untuk unit 
                        <span class="font-bold text-indigo-500"><?php echo htmlspecialchars($user['company_name']); ?></span>.
                    </p>
                </div>
            </div>

            <?php if (empty($questions)): ?>
                <div class="pro-card p-12 text-center fade-in-up" :class="isDark ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-100'">
                    <h3 class="font-bold text-2xl mb-3" :class="isDark ? 'text-white' : 'text-slate-800'">Survey Belum Tersedia</h3>
                    <a href="index.php" class="inline-flex items-center gap-2 bg-slate-800 text-white font-bold py-3 px-8 rounded-xl hover:bg-slate-900 transition">Kembali</a>
                </div>
            <?php else: ?>

                <form @submit.prevent="submitAll()" class="space-y-6">
                    <?php $delay = 0.15; ?>
                    <?php foreach ($questions as $id => $q): ?>
                    
                    <div id="q-card-<?php echo $id; ?>" 
                        class="pro-card p-6 sm:p-8 fade-in-up relative group transition-all duration-300 border" 
                        :class="isDark ? 'bg-slate-800 border-slate-700 hover:border-slate-600' : 'bg-white border-slate-100 hover:shadow-lg'"
                        style="animation-delay: <?php echo $delay; ?>s;"
                        
                        x-data="{
                            showQuestion: true, 
                            parentId: <?php echo json_encode($q['dependency_id']); ?>,
                            triggerVal: '<?php echo $q['dependency_value']; ?>',

                            init() {
                                if (this.parentId) {
                                    this.showQuestion = false; 
                                    this.$watch(`$store.answersStore.answers[${this.parentId}]`, (val) => {
                                        this.showQuestion = (val == this.triggerVal);
                                        // PENTING: Saat hidden/show berubah, Hitung ulang Progress Bar!
                                        if (!this.showQuestion) {
                                            delete $store.answersStore.answers[<?php echo $id; ?>];
                                        }
                                        $dispatch('recalc-progress');
                                    });
                                }
                            }
                        }"
                        x-show="showQuestion" 
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                    >
                        <div class="absolute left-0 top-6 bottom-6 w-1 rounded-r-full transition-opacity duration-300 opacity-0 group-hover:opacity-100"
                             :class="isDark ? 'bg-indigo-400' : 'bg-indigo-500'"></div>

                        <div class="flex gap-4 sm:gap-6 <?php echo $q['is_child'] ? 'ml-0 sm:ml-8 pl-4 border-l-2' : ''; ?>"
                             :class="isDark ? 'border-slate-700' : 'border-indigo-50'">
                            
                            <div class="flex-shrink-0">
                                <div class="flex flex-col items-center justify-center w-14 h-14 rounded-2xl font-bold text-lg border transition-colors"
                                    :class="isDark 
                                        ? (<?php echo $q['is_child'] ? "'bg-slate-900 text-slate-500 border-slate-700'" : "'bg-indigo-600 text-white border-transparent shadow-lg shadow-indigo-900/20'" ?>)
                                        : (<?php echo $q['is_child'] ? "'bg-slate-50 text-slate-400 border-transparent'" : "'bg-indigo-600 text-white shadow-lg shadow-indigo-200 border-transparent'" ?>)">
                                    <?php echo $q['number']; ?>
                                </div>
                            </div>

                            <div class="flex-grow pt-1">
                                <h3 class="text-lg font-bold leading-snug mb-6" :class="isDark ? 'text-slate-100' : 'text-slate-800'">
                                    <?php echo $q['text']; ?>
                                </h3>

                                <div class="w-full" @change="$dispatch('recalc-progress')">
                                    <?php if ($q['type'] == 'yes_no'): ?>
                                        <div class="grid grid-cols-2 gap-4 max-w-sm">
                                            <label class="cursor-pointer group">
                                                <input type="radio" name="q_<?php echo $id; ?>" value="Ya" x-model="$store.answersStore.answers[<?php echo $id; ?>]" class="sr-only">
                                                <div class="w-full py-3 px-4 rounded-xl flex items-center justify-center gap-3 border transition-all"
                                                     :class="isDark 
                                                        ? ($store.answersStore.answers[<?php echo $id; ?>] == 'Ya' ? 'bg-emerald-900/30 border-emerald-500 text-emerald-400' : 'bg-slate-900 border-slate-700 text-slate-400 hover:bg-slate-800')
                                                        : ($store.answersStore.answers[<?php echo $id; ?>] == 'Ya' ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50')">
                                                    <span class="font-bold">Ya</span>
                                                </div>
                                            </label>
                                            <label class="cursor-pointer group">
                                                <input type="radio" name="q_<?php echo $id; ?>" value="Tidak" x-model="$store.answersStore.answers[<?php echo $id; ?>]" class="sr-only">
                                                <div class="w-full py-3 px-4 rounded-xl flex items-center justify-center gap-3 border transition-all"
                                                     :class="isDark 
                                                        ? ($store.answersStore.answers[<?php echo $id; ?>] == 'Tidak' ? 'bg-red-900/30 border-red-500 text-red-400' : 'bg-slate-900 border-slate-700 text-slate-400 hover:bg-slate-800')
                                                        : ($store.answersStore.answers[<?php echo $id; ?>] == 'Tidak' ? 'bg-red-50 border-red-500 text-red-700' : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50')">
                                                    <span class="font-bold">Tidak</span>
                                                </div>
                                            </label>
                                        </div>
                                    
                                    <?php elseif ($q['type'] == 'checkbox'): ?>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-init="if (!($store.answersStore.answers[<?php echo $id; ?>] instanceof Array)) $store.answersStore.answers[<?php echo $id; ?>] = []">
                                            <?php foreach ($q['options'] as $opt): ?>
                                                <label class="cursor-pointer group relative">
                                                    <input type="checkbox" value="<?php echo htmlspecialchars($opt); ?>" x-model="$store.answersStore.answers[<?php echo $id; ?>]" class="sr-only">
                                                    <div class="w-full py-3.5 px-5 rounded-xl flex items-center gap-3 transition-all duration-200 border"
                                                         :class="isDark
                                                            ? ($store.answersStore.answers[<?php echo $id; ?>] && $store.answersStore.answers[<?php echo $id; ?>].includes('<?php echo htmlspecialchars($opt); ?>') ? 'border-indigo-500 bg-indigo-900/30 text-indigo-300' : 'border-slate-700 bg-slate-900 text-slate-400 hover:bg-slate-800')
                                                            : ($store.answersStore.answers[<?php echo $id; ?>] && $store.answersStore.answers[<?php echo $id; ?>].includes('<?php echo htmlspecialchars($opt); ?>') ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50')">
                                                        <div class="w-5 h-5 rounded border flex items-center justify-center transition-all duration-200"
                                                             :class="isDark ? ($store.answersStore.answers[<?php echo $id; ?>].includes('<?php echo htmlspecialchars($opt); ?>') ? 'bg-indigo-500 border-indigo-500' : 'border-slate-600 bg-slate-800') : ($store.answersStore.answers[<?php echo $id; ?>].includes('<?php echo htmlspecialchars($opt); ?>') ? 'bg-indigo-500 border-indigo-500' : 'border-slate-300 bg-white')">
                                                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="$store.answersStore.answers[<?php echo $id; ?>].includes('<?php echo htmlspecialchars($opt); ?>')"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                                        </div>
                                                        <span class="font-medium"><?php echo htmlspecialchars($opt); ?></span>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                    <?php elseif ($q['type'] == 'rating_10'): ?>
                                        <div class="grid grid-cols-5 sm:grid-cols-10 gap-2 sm:gap-3">
                                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                                <label class="cursor-pointer group relative">
                                                    <input type="radio" name="q_<?php echo $id; ?>" value="<?php echo $i; ?>" x-model="$store.answersStore.answers[<?php echo $id; ?>]" class="sr-only">
                                                    <div class="rating-circle border-2" 
                                                         :class="isDark
                                                            ? ($store.answersStore.answers[<?php echo $id; ?>] == <?php echo $i; ?> ? 'bg-indigo-500 border-indigo-500 text-white' : 'border-slate-700 bg-slate-900 text-slate-500 hover:border-indigo-400 hover:text-indigo-400')
                                                            : ($store.answersStore.answers[<?php echo $id; ?>] == <?php echo $i; ?> ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-200 bg-slate-50 text-slate-400 hover:border-blue-400 hover:text-blue-500')">
                                                        <?php echo $i; ?>
                                                    </div>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="flex justify-between mt-2 text-xs font-bold uppercase tracking-wider px-1" :class="isDark ? 'text-slate-500' : 'text-slate-400'">
                                            <span><?php echo $q['label_min']; ?></span>
                                            <span><?php echo $q['label_max']; ?></span>
                                        </div>

                                    <?php elseif ($q['type'] == 'text'): ?>
                                        <textarea x-model="$store.answersStore.answers[<?php echo $id; ?>]" rows="3" 
                                            class="w-full rounded-xl p-4 text-sm resize-none focus:ring-2 focus:ring-indigo-500 outline-none transition-all border"
                                            :class="isDark ? 'bg-slate-900 border-slate-700 text-white placeholder-slate-600' : 'bg-slate-50 border-slate-200 text-slate-800 placeholder-slate-400'"
                                            placeholder="Tulis jawaban Anda di sini..."></textarea>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php $delay += 0.08; ?>
                    <?php endforeach; ?>

                    <div class="pt-8 flex justify-center pb-12">
                        <button type="submit" :disabled="isSubmitting" 
                            class="group relative w-full sm:w-auto min-w-[280px] bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white text-base font-bold py-4 px-10 rounded-2xl shadow-xl shadow-indigo-500/30 transform hover:-translate-y-1 transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed overflow-hidden">
                            <div class="flex items-center justify-center gap-3 relative z-10">
                                <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-show="!isSubmitting">KIRIM SURVEY SEKARANG</span>
                                <span x-show="isSubmitting">Memproses...</span>
                            </div>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // 1. Theme Handler
        function themeHandler() {
            return {
                isDark: false,
                init() {
                    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                        this.isDark = true;
                    }
                },
                toggleTheme() {
                    this.isDark = !this.isDark;
                    localStorage.theme = this.isDark ? 'dark' : 'light';
                }
            }
        }

        // 2. Alpine Store Init
        document.addEventListener('alpine:init', () => {
            Alpine.store('answersStore', { answers: {} });
        });

        // 3. MAIN LOGIC
        function surveyForm() {
            return {
                isSubmitting: false,
                userData: <?php echo json_encode($user); ?>,
                questionsData: <?php echo json_encode($questions); ?>,

                init() {
                    // Pantau perubahan jawaban untuk update progress bar
                    this.$watch('$store.answersStore.answers', () => {
                        this.calculateProgress();
                    });
                    // Event listener manual
                    window.addEventListener('recalc-progress', () => {
                        this.calculateProgress();
                    });
                    
                    // Hitung progress saat pertama kali load (pasti 0%)
                    this.calculateProgress();
                },

                // --- REVISI RUMUS PROGRESS BAR ---
                calculateProgress() {
                    const answers = Alpine.store('answersStore').answers;
                    
                    let visibleQuestionsCount = 0; // Penyebut (Denominator)
                    let filledQuestionsCount = 0;  // Pembilang (Numerator)

                    for (const [id, q] of Object.entries(this.questionsData)) {
                        
                        // 1. Cek Visibility
                        let isVisible = true;
                        if (q.dependency_id) {
                            // Jika induk belum dijawab ATAU jawaban tidak sesuai pemicu -> Sembunyi
                            // Saat awal load, answers[induk] pasti undefined, jadi otomatis isVisible = false
                            if (answers[q.dependency_id] !== q.dependency_value) {
                                isVisible = false;
                            }
                        }

                        // 2. Hitung Hanya yang Tampil
                        if (isVisible) {
                            visibleQuestionsCount++; // Tambah total soal yang harus dikerjakan saat ini

                            const ans = answers[id];
                            // Cek apakah sudah diisi (tidak null/kosong)
                            const isFilled = (ans !== undefined && ans !== null && ans !== "" && !(Array.isArray(ans) && ans.length === 0));
                            
                            if (isFilled) {
                                filledQuestionsCount++;
                            }
                        }
                    }

                    // 3. Kalkulasi Persentase
                    let percent = 0;
                    if (visibleQuestionsCount > 0) {
                        percent = Math.round((filledQuestionsCount / visibleQuestionsCount) * 100);
                    }

                    // Update UI
                    const bar = document.getElementById('smartProgressBar');
                    if(bar) bar.style.width = percent + '%';
                },

                async submitAll() {
                    const submittedAnswers = Alpine.store('answersStore').answers;
                    let firstMissingId = null;
                    let missingCount = 0;

                    // Validasi Wajib Isi (Hanya yang Visible)
                    for (const [id, q] of Object.entries(this.questionsData)) {
                        let isVisible = true;
                        if (q.dependency_id) {
                            if (submittedAnswers[q.dependency_id] !== q.dependency_value) isVisible = false;
                        }

                        if (isVisible) {
                            const answer = submittedAnswers[id];
                            const isEmpty = (answer === undefined || answer === null || answer === "" || (Array.isArray(answer) && answer.length === 0));

                            if (isEmpty) {
                                if (!firstMissingId) firstMissingId = id;
                                missingCount++;
                                const el = document.getElementById('q-card-' + id);
                                if(el) {
                                    el.classList.add('ring-2', 'ring-red-500', 'bg-red-50', 'dark:bg-red-900/20');
                                    setTimeout(() => el.classList.remove('ring-2', 'ring-red-500', 'bg-red-50', 'dark:bg-red-900/20'), 3000);
                                }
                            }
                        }
                    }

                    if (missingCount > 0) {
                        alert(`Mohon lengkapi ${missingCount} pertanyaan yang belum diisi.`);
                        if (firstMissingId) {
                            const el = document.getElementById('q-card-' + firstMissingId);
                            if(el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        return;
                    }

                    this.isSubmitting = true;
                    const payload = { ...this.userData, answers: submittedAnswers };

                    try {
                        const res = await fetch('handler.php?action=submit', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(payload)
                        });

                        const text = await res.text(); 
                        let json;
                        try { json = JSON.parse(text); } catch (e) { throw new Error("Respon server tidak valid."); }

                        if (json.status === 'success') {
                            window.location.href = 'thankyou.php';
                        } else {
                            alert("Gagal menyimpan: " + (json.message || "Unknown Error"));
                        }

                    } catch(e) { 
                        console.error(e);
                        alert("Terjadi kesalahan saat mengirim data.");
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            }
        }
    </script>
</body>
</html>