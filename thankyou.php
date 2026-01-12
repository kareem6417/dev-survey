<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - IT Satisfaction Survey</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* BACKGROUND MESH (Sama seperti Form) */
        .bg-mesh {
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
            background-attachment: fixed;
        }

        /* CARD STYLE (Glassmorphism) */
        .pro-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        /* ANIMASI POP-IN */
        .animate-pop { animation: popIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; transform: scale(0.95); }
        @keyframes popIn { to { opacity: 1; transform: scale(1); } }

        /* DEKORASI CONFETTI */
        .confetti { position: absolute; width: 10px; height: 10px; background-color: #f00; animation: fall linear infinite; opacity: 0; }
        @keyframes fall {
            0% { transform: translateY(-100px) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
    </style>
</head>
<body class="bg-mesh min-h-screen flex flex-col items-center justify-center relative overflow-hidden">

    <header class="absolute top-0 inset-x-0 z-10 py-6 px-6">
        <div class="max-w-5xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-4">
                <img src="logo1.png" alt="Logo" class="h-10 w-auto object-contain drop-shadow-md">
                <div class="hidden sm:block">
                    <h1 class="font-bold text-lg text-white tracking-tight">IT Satisfaction Survey</h1>
                </div>
            </div>
        </div>
    </header>

    <main class="w-full px-4 relative z-20">
        <div class="max-w-md mx-auto pro-card p-10 text-center animate-pop relative overflow-hidden">
            
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>

            <div class="w-24 h-24 bg-green-50 text-green-500 rounded-3xl flex items-center justify-center mx-auto mb-8 shadow-inner border border-green-100 transform rotate-3 hover:rotate-0 transition-transform duration-500">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
            </div>

            <h1 class="text-3xl font-extrabold text-slate-800 mb-4 tracking-tight">Terima Kasih!</h1>
            
            <p class="text-slate-500 text-base leading-relaxed mb-10">
                Jawaban survei Anda telah berhasil kami simpan. Masukan Anda sangat berharga untuk peningkatan layanan IT Mandirigroup ke depannya.
            </p>

            <div class="space-y-4">
                <a href="index.php" class="group relative block w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold py-4 rounded-2xl shadow-lg shadow-indigo-500/30 transform hover:-translate-y-1 transition-all duration-300">
                    <span class="relative z-10 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Kembali ke Halaman Utama
                    </span>
                </a>
                
                <p class="text-xs text-slate-400 pt-4">IT Operation Support &copy; <?php echo date('Y'); ?></p>
            </div>
        </div>
    </main>

    <script>
        function createConfetti() {
            const colors = ['#6366f1', '#3b82f6', '#ec4899', '#10b981'];
            const container = document.body;
            
            for(let i=0; i<30; i++) {
                const conf = document.createElement('div');
                conf.classList.add('confetti');
                conf.style.left = Math.random() * 100 + 'vw';
                conf.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                conf.style.animationDuration = (Math.random() * 2 + 2) + 's';
                conf.style.top = '-10px';
                container.appendChild(conf);
                
                // Hapus elemen setelah animasi selesai agar tidak berat
                setTimeout(() => { conf.remove(); }, 4000);
            }
        }
        // Jalankan saat load
        window.onload = createConfetti;
    </script>
</body>
</html>