<?php 
require 'config.php'; 
// Ambil daftar perusahaan untuk Dropdown
$stmt = $pdo->query("SELECT * FROM companies ORDER BY name ASC");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Satisfaction Survey - Mandirigroup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f1f5f9; }
        .animate-fade-up { animation: fadeUp 0.5s ease-out forwards; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .focus-pulse:focus { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>

<body x-data="surveyApp()" class="min-h-screen flex flex-col items-center justify-center p-4 bg-slate-100">

    <div x-show="showWelcomeModal" style="display: none;" 
         class="fixed inset-0 z-[100] flex items-center justify-center px-4 py-6"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
             @click="showWelcomeModal = false"></div>

        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-fade-up"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">
            
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-6 py-4 flex justify-between items-center shrink-0">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 p-1.5 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h2 class="text-lg font-bold text-white tracking-wide">Pengantar Survey</h2>
                </div>
                <button @click="showWelcomeModal = false" class="text-white/70 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-6 overflow-y-auto text-slate-600 space-y-4 text-sm leading-relaxed custom-scroll">
                <p><span class="font-bold text-slate-800">Yth. Bapak/Ibu,</span></p>
                <p>Divisi ITE senantiasa berupaya memberikan layanan TI terbaik. Demi meningkatkan kualitas pelayanan dan memahami kebutuhan Anda, kami mengharapkan kesediaan Bapak/Ibu meluangkan waktu sejenak untuk mengisi kuesioner ini.</p>

                <div class="bg-slate-50 border-l-4 border-blue-500 p-4 rounded-r-lg">
                    <p class="font-semibold text-slate-800 mb-1">Tujuan Kuesioner:</p>
                    <ul class="list-disc list-inside space-y-1 ml-1 text-slate-600">
                        <li>Evaluasi performa layanan TI & Literasi Digital.</li>
                        <li>Masukan langsung untuk analisis dan peningkatan layanan.</li>
                        <li>Penyusunan program peningkatan <i>awareness</i> digital di lingkungan Mandirigroup.</li>
                    </ul>
                </div>

                <div class="flex items-start gap-3 bg-slate-50 p-3 rounded-lg border border-slate-100">
                    <svg class="w-5 h-5 text-emerald-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <p class="text-xs text-slate-500">Informasi data pribadi yang Anda berikan akan kami <strong>jaga kerahasiaannya</strong>.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 relative group hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="bg-amber-100 text-amber-600 p-1.5 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
                            </span>
                            <span class="font-bold text-amber-800 text-sm">Doorprize!</span>
                        </div>
                        <p class="text-amber-900/80 text-xs">
                            Hadiah untuk <strong>10 responden beruntung</strong>. Mohon isi data identitas dengan lengkap.
                        </p>
                    </div>

                    <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 relative group hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="bg-rose-100 text-rose-600 p-1.5 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </span>
                            <span class="font-bold text-rose-800 text-sm">Batas Waktu</span>
                        </div>
                        <p class="text-rose-900/80 text-xs">
                            Ditutup pada tanggal:<br>
                            <strong>28 Februari 2025, 17.00 WIB</strong>
                        </p>
                    </div>
                </div>
                <p>Akhir kata, kami sampaikan banyak terima kasih atas kontribusi bapak/ibu dalam pengisian kuisioner. Bantu kami untuk bisa lebih baik membantu anda.
                </p>
                <p>IT Operations Department
                </p>
            </div>

            <div class="bg-slate-50 px-6 py-4 flex justify-end shrink-0 border-t border-slate-200">
                <button @click="showWelcomeModal = false" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-6 rounded-xl shadow-lg shadow-indigo-200 transition-all transform hover:-translate-y-0.5 active:scale-95 flex items-center gap-2 text-sm">
                    <span>Mulai Pengisian</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </div>
        </div>
    </div>
    <div class="w-full max-w-2xl bg-white rounded-3xl shadow-[0_20px_50px_-12px_rgba(0,0,0,0.1)] overflow-hidden border border-slate-200 relative">
        
        <div class="bg-white px-8 py-6 border-b border-slate-100 relative">
            <button x-show="step > 1" 
                    @click="goBack()" 
                    class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded-lg transition-all flex items-center gap-2 text-xs font-bold uppercase tracking-wider group"
                    style="display: none;">
                <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali
            </button>
            <div class="text-center">
                <img src="logo1.png" alt="Logo" class="h-16 mx-auto object-contain hover:scale-105 transition-transform duration-500">
            </div>
        </div>

        <div class="h-1 w-full bg-slate-50">
            <div class="h-full bg-blue-600 transition-all duration-500 ease-out" :style="'width: ' + ((step / 4) * 100) + '%'"></div>
        </div>

        <div class="p-8 sm:p-10 min-h-[350px]">
            
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-slate-800 tracking-tight" x-text="getTitle()"></h1>
                <p class="text-slate-500 text-sm mt-1" x-text="getSubtitle()"></p>
            </div>

            <div x-show="showModal" style="display: none;" 
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
                x-transition.opacity>
                
                <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl transform transition-all"
                    @click.away="showModal = false"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-90 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0">
                    
                    <div class="flex items-center justify-center w-12 h-12 rounded-full mb-4 mx-auto"
                        :class="modalType === 'error' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600'">
                        <svg x-show="modalType === 'error'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <svg x-show="modalType === 'warning'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    
                    <h3 class="text-lg font-bold text-center text-slate-800 mb-2" x-text="modalTitle"></h3>
                    <p class="text-sm text-center text-slate-500 mb-6 leading-relaxed" x-text="modalMessage"></p>
                    
                    <button @click="showModal = false" 
                        class="w-full py-2.5 rounded-xl font-bold transition-colors text-white"
                        :class="modalType === 'error' ? 'bg-red-600 hover:bg-red-700' : 'bg-amber-500 hover:bg-amber-600'">
                        Tutup
                    </button>
                </div>
            </div>

            <div x-show="step === 1" class="animate-fade-up">
                <div class="space-y-6">
                    <div class="relative group">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Unit Bisnis</label>
                        <select x-model="selectedCompanyId" @change="checkCompanyType()" 
                            class="w-full p-4 bg-slate-50 border-2 border-slate-200 rounded-xl appearance-none font-bold text-slate-700 focus:outline-none focus:border-blue-500 focus:bg-white transition-all cursor-pointer shadow-sm group-hover:border-blue-300">
                            <option value="">-- Pilih Perusahaan --</option>
                            <?php foreach ($companies as $comp): ?>
                                <option value="<?php echo $comp['id']; ?>" data-name="<?php echo $comp['name']; ?>">
                                    <?php echo $comp['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-4 bottom-4 pointer-events-none text-slate-400 group-hover:text-blue-500 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <button @click="nextStep()" :disabled="!selectedCompanyId"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-slate-200 disabled:text-slate-400 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-500/30 disabled:shadow-none transition-all transform active:scale-[0.98] mt-4 flex justify-center items-center gap-2">
                        <span>Lanjut</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    </button>
                </div>
            </div>

            <div x-show="step === 2" class="animate-fade-up" style="display:none;">
                <div class="space-y-6">
                    <div>
                        <input type="text" x-model="nikInput" @keydown.enter="searchNik()" placeholder="Contoh: 7366" 
                            class="w-full p-4 border-2 border-slate-200 rounded-xl bg-white focus:border-blue-600 outline-none font-bold text-center text-2xl tracking-widest text-slate-800 placeholder-slate-300 transition-colors focus-pulse">
                    </div>

                    <button @click="searchNik()" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-500/30 transition-all transform active:scale-[0.98] flex justify-center items-center gap-3">
                        <span x-show="!isLoading">Cari Data</span>
                        <span x-show="isLoading">Memproses...</span>
                        <svg x-show="isLoading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                </div>
            </div>

            <div x-show="step === 3" class="animate-fade-up" style="display:none;">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg">
                    <p class="text-sm text-blue-700">
                        Halo <span class="font-bold" x-text="formData.name"></span>, demi keamanan, silakan konfirmasi tanggal lahir Anda.
                    </p>
                </div>

                <div class="space-y-6">
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tanggal Lahir</label>
                        <input type="date" x-model="userDobInput" 
                            class="w-full p-4 bg-slate-50 border-2 border-slate-200 rounded-xl font-bold text-slate-700 focus:outline-none focus:border-blue-500 focus:bg-white transition-all">
                    </div>

                    <p x-show="verifyError" class="text-red-500 text-sm font-medium flex items-center gap-2 animate-pulse">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Tanggal lahir tidak sesuai.
                    </p>

                    <button @click="verifyDob()" :disabled="!userDobInput"
                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-slate-200 disabled:text-slate-400 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-500/30 disabled:shadow-none transition-all transform active:scale-[0.98] flex justify-center items-center gap-2">
                        <span>Verifikasi</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </button>
                </div>
            </div>

            <div x-show="step === 4" class="animate-fade-up" style="display:none;">
                
                <div x-show="mode === 'manual'" class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-6 rounded-r-lg">
                    <p class="text-sm text-amber-700 font-medium">Data NIK Anda belum terdaftar di sistem otomatis kami. Silakan lengkapi data di bawah ini secara manual.</p>
                </div>

                <form action="form.php" method="POST" class="space-y-4">
                    <input type="hidden" name="company_id" x-model="selectedCompanyId">
                    <input type="hidden" name="company_name" x-model="selectedCompanyName">
                    <input type="hidden" name="nik" x-model="formData.nik">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">NIK</label>
                            <input type="text" x-model="formData.nik" readonly class="w-full p-3 bg-slate-100 border border-slate-200 rounded-lg text-slate-500 font-mono text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Perusahaan</label>
                            <input type="text" x-model="selectedCompanyName" readonly class="w-full p-3 bg-slate-100 border border-slate-200 rounded-lg text-slate-500 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Nama Lengkap</label>
                        <input type="text" name="name" x-model="formData.name" :readonly="mode === 'api'" required
                            class="w-full p-3 border border-slate-200 rounded-lg text-slate-800 text-sm focus:border-blue-500 outline-none transition-colors"
                            :class="mode === 'api' ? 'bg-slate-50' : 'bg-white'">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Email Kantor</label>
                        <input type="email" name="email" x-model="formData.email" :readonly="mode === 'api'" required
                            class="w-full p-3 border border-slate-200 rounded-lg text-slate-800 text-sm focus:border-blue-500 outline-none transition-colors"
                            :class="mode === 'api' ? 'bg-slate-50' : 'bg-white'">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Divisi</label>
                        <input type="text" name="division" x-model="formData.division" :readonly="mode === 'api'" required
                            class="w-full p-3 border border-slate-200 rounded-lg text-slate-800 text-sm focus:border-blue-500 outline-none transition-colors"
                            :class="mode === 'api' ? 'bg-slate-50' : 'bg-white'">
                    </div>

                    <button type="submit" :disabled="!isFormValid()"
                        class="w-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold py-4 rounded-xl shadow-lg shadow-emerald-500/30 transition-all transform active:scale-[0.98] mt-6 flex justify-center items-center gap-2">
                        <span>Mulai Isi Survey</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7-7 7"></path></svg>
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 flex justify-center">
            <p class="text-xs text-slate-400 font-semibold tracking-wide">© 2026 IT Operations Department</p>
        </div>
    </div>
    <div class="fixed bottom-4 right-4 opacity-50 hover:opacity-100 transition duration-300">
        <a href="login_admin.php" class="flex items-center gap-2 text-slate-400 hover:text-slate-800 text-xs font-medium bg-white/80 px-3 py-1.5 rounded-full shadow-sm border border-slate-200 backdrop-blur-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
            Dashboard
        </a>
    </div>
    <script>
    function surveyApp() {
        return {
            showWelcomeModal: true, 

            step: 1, 
            mode: 'api', 
            selectedCompanyId: '',
            selectedCompanyName: '',
            nikInput: '',
            userDobInput: '',
            apiDobCheck: '',
            isLoading: false,
            errorMessage: '',
            verifyError: false,
            formData: { nik: '', name: '', email: '', division: '' },
            
            // Modal States (Alert)
            showModal: false,
            modalType: 'error', 
            modalTitle: '',
            modalMessage: '',

            triggerAlert(type, title, message) {
                this.modalType = type;
                this.modalTitle = title;
                this.modalMessage = message;
                this.showModal = true;
            },

            goBack() {
                this.resetForm();
                this.step = 1;
            },

            resetForm() {
                this.nikInput = '';
                this.userDobInput = '';
                this.apiDobCheck = '';
                this.errorMessage = '';
                this.verifyError = false;
                this.formData = { nik: '', name: '', email: '', division: '' };
            },

            getTitle() {
                if(this.step === 1) return "IT Satisfaction Survey";
                if(this.step === 2) return "Pencarian NIK";
                if(this.step === 3) return "Verifikasi Keamanan";
                if(this.step === 4) return "Konfirmasi Data";
            },

            getSubtitle() {
                if(this.step === 1) return "Silakan pilih unit bisnis Anda untuk memulai.";
                if(this.step === 2) return "Masukkan NIK karyawan untuk validasi data.";
                if(this.step === 3) return "Mohon konfirmasi tanggal lahir Anda.";
                if(this.step === 4) return "Pastikan data diri Anda sudah benar.";
            },
            
            checkCompanyType() {
                const select = document.querySelector('select');
                const option = select.options[select.selectedIndex];
                if(option.value === "") {
                    this.selectedCompanyId = "";
                    this.selectedCompanyName = "";
                    return;
                }
                this.selectedCompanyName = option.getAttribute('data-name');
                if (this.selectedCompanyName && this.selectedCompanyName.toLowerCase().includes('mandiriland')) {
                    this.mode = 'manual';
                } else {
                    this.mode = 'api';
                }
            },

            nextStep() {
                if (this.mode === 'manual') {
                    this.formData.nik = '-'; 
                    this.step = 4;
                } else {
                    this.step = 2;
                }
            },

            async checkNik() {
                if (this.nikInput.length < 3) {
                    this.triggerAlert('error', 'NIK Pendek', 'Masukkan NIK minimal 3 digit.');
                    return;
                }

                this.isLoading = true;
                this.errorMessage = "";

                try {
                    const response = await fetch(`https://survey.mandiricoal.co.id/api/check-nik?nik=${this.nikInput}`);
                    const data = await response.json();

                    if (data.status === 'success') {
                        // 1. Data Ditemukan (Entah Lengkap atau Tidak)
                        // Kita masukkan dulu data yang ada ke formData
                        const emp = data.data;

                        this.formData = {
                            nik: this.nikInput,
                            name: emp.employee_name || '', // Jika kosong, set string kosong
                            email: emp.email || '',
                            division: emp.division || '',
                            department: emp.department || '',
                            position: emp.position || ''
                        };
                        
                        // Simpan data pembanding untuk verifikasi DOB nanti
                        this.apiDobCheck = emp.date_of_birth || ''; 
                        this.apiCompanyCode = emp.company_code || '';

                        // Cek kelengkapan data kunci
                        // (Kita anggap lengkap jika DOB, Company, Divisi, dll ada)
                        const isDataComplete = emp.company_code && emp.division && emp.department && emp.position && emp.date_of_birth;

                        if (isDataComplete) {
                            // KASUS A: Data Sempurna -> Mode AUTO
                            this.mode = 'auto';

                            // Auto-select perusahaan berdasarkan kode
                            let foundCompany = this.companies.find(c => c.code === emp.company_code);
                            if (foundCompany) {
                                this.selectedCompanyId = foundCompany.id;
                            }

                            // Lanjut ke verifikasi Tanggal Lahir (Step 3)
                            this.step = 3; 
                            
                        } else {
                            // KASUS B: Data Ada tapi Tidak Lengkap -> Mode MANUAL
                            this.mode = 'manual';
                            
                            // Beri tahu user
                            this.triggerAlert(
                                'warning', 
                                'Data Belum Lengkap', 
                                'Data Anda ditemukan namun belum lengkap. Silakan lengkapi kolom yang kosong.'
                            );

                            // Langsung ke Form Pengisian (Step 4), lewati verifikasi DOB karena mungkin DOB-nya kosong
                            this.step = 4;
                        }

                    } else {
                        // KASUS C: Data Tidak Ditemukan Sama Sekali -> Mode MANUAL Full
                        this.mode = 'manual';
                        
                        // Kosongkan form kecuali NIK
                        this.formData = {
                            nik: this.nikInput,
                            name: '',
                            email: '',
                            division: '',
                            department: '',
                            position: ''
                        };
                        this.selectedCompanyId = '';

                        this.triggerAlert(
                            'info', 
                            'Data Tidak Ditemukan', 
                            'NIK tidak ditemukan di database. Silakan isi data diri Anda secara manual.'
                        );

                        // Langsung ke Form Pengisian (Step 4)
                        this.step = 4;
                    }

                } catch (e) {
                    console.error(e);
                    this.triggerAlert('error', 'Gagal Koneksi', 'Terjadi kesalahan saat menghubungi server.');
                } finally {
                    this.isLoading = false;
                }
            },

            verifyDob() {
                let inputVal = String(this.userDobInput).trim();
                let apiVal = String(this.apiDobCheck).trim();

                if (inputVal === apiVal) {
                    this.step = 4; 
                    this.verifyError = false;
                } else {
                    this.verifyError = true;
                }
            },

            isFormValid() {
                return this.formData.name && this.formData.email && this.selectedCompanyId;
            }
        }
    }
    </script>
</body>
</html>