import os
import re

folder = r'c:\xampp\htdocs\sppsekolah\walimurid'
dashboard_path = os.path.join(folder, 'dashboard.php')
profil_path = os.path.join(folder, 'profil_siswa.php')

with open(dashboard_path, 'r', encoding='utf-8') as f:
    dashboard = f.read()

# Extract PHP Header up to `// Ambil data wali murid`
php_head_match = re.search(r'(<\?php.*?// --- NOTIFIKASI WALIMURID \(END\) ---)', dashboard, re.DOTALL)
php_head = php_head_match.group(1)

# Add custom profile queries
php_custom = """

// Ambil data wali murid
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_walimurid' AND role = 'walimurid'");
$currentUser = mysqli_fetch_assoc($query_user);

if (!$currentUser) {
    die("Data user tidak ditemukan. Silakan hubungi administrator.");
}

// Ambil semua data siswa yang terhubung dengan akun ini
$query_siswa = mysqli_query($koneksi, "SELECT * FROM siswa WHERE id_walimurid = '$id_walimurid'");
$siswa_list = [];
while($row = mysqli_fetch_assoc($query_siswa)){
    $siswa_list[] = $row;
}

// Fungsi escape
function escape($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}
?>"""

# Extract HTML Head up to <main
html_head_match = re.search(r'(<!DOCTYPE html>.*?)<main', dashboard, re.DOTALL)
html_head = html_head_match.group(1)

# Extract Header (<header...>)
header_match = re.search(r'(<header.*?</header>)', dashboard, re.DOTALL)
val_header = header_match.group(1)
# Update Title
val_header = val_header.replace('Portal Orang Tua -', 'Profil Saya & Siswa -')

# Extract Scripts (from </main> to </html>) including script logic
scripts_match = re.search(r'(    <!-- SCRIPT NOTIFIKASI -->.*</html>)', dashboard, re.DOTALL)
val_scripts = scripts_match.group(1)

# New Profile Main Content
val_main = """
        <div class="p-8">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800">Profil Keluarga</h2>
                <p class="text-gray-500 mt-2 text-lg">Informasi akun Anda dan detail data ananda yang terdaftar di sekolah.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- KARTU PROFIL WALI MURID -->
                <div class="bg-gradient-to-br from-[#1e293b] to-[#334155] rounded-2xl p-6 shadow-xl relative overflow-hidden text-white lg:col-span-1">
                    <!-- Glass dekorasi -->
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-5 rounded-full blur-2xl"></div>
                    <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-green-400 opacity-10 rounded-full blur-2xl"></div>
                    
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="w-24 h-24 bg-gray-600 border-4 border-gray-500 rounded-full flex items-center justify-center shadow-lg mb-4">
                            <i class="fa-solid fa-user-tie text-4xl text-gray-300"></i>
                        </div>
                        <h3 class="text-xl font-bold text-center"><?= escape($currentUser['nama_lengkap']) ?></h3>
                        <p class="text-green-400 text-sm mt-1 mb-6 font-semibold uppercase tracking-widest">Wali Murid</p>
                        
                        <div class="w-full space-y-4">
                            <div class="bg-[#0f172a] bg-opacity-50 p-4 rounded-xl backdrop-blur-sm border border-gray-700">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Username Login</p>
                                <p class="font-medium text-sm flex items-center"><i class="fa-solid fa-fingerprint text-green-400 mr-2"></i> <?= escape($currentUser['username']) ?></p>
                            </div>
                            <div class="bg-[#0f172a] bg-opacity-50 p-4 rounded-xl backdrop-blur-sm border border-gray-700">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Alamat Email</p>
                                <p class="font-medium text-sm flex items-center"><i class="fa-solid fa-envelope text-blue-400 mr-2"></i> <?= escape($currentUser['email']) ?: '<span class="italic text-gray-500">Belum diatur</span>' ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KARTU PROFIL TANGGUNGAN (SISWA) -->
                <div class="lg:col-span-2 space-y-6">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center"><i class="fa-solid fa-graduation-cap text-[#10b981] mr-2"></i> Data Anak / Tanggungan</h3>
                    
                    <?php if (count($siswa_list) > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach($siswa_list as $sw): ?>
                            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:-translate-y-1 transition-transform duration-300 relative group overflow-hidden">
                                <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-bl-[100px] -z-0 transition-colors group-hover:bg-green-100"></div>
                                
                                <div class="relative z-10 flex items-start space-x-4">
                                    <div class="bg-green-100 p-4 rounded-full text-green-600">
                                        <i class="fa-solid fa-child-reaching text-2xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-bold text-gray-800"><?= escape($sw['nama_siswa']) ?></h4>
                                        <div class="inline-block mt-2 px-3 py-1 bg-gray-50 border border-gray-200 text-gray-600 rounded-md text-xs font-bold uppercase">
                                            Kelas <?= escape($sw['kelas']) ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="relative z-10 mt-6 pt-5 border-t border-gray-50 grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Nomor Induk / NISN</p>
                                        <p class="font-bold text-gray-700"><?= escape($sw['nisn']) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-1">Aksi Cepat</p>
                                        <a href="tagihan.php" class="text-sm text-[#10b981] font-bold hover:underline flex items-center"><i class="fa-solid fa-arrow-right mr-1"></i> Cek Tagihan</a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-50 rounded-2xl p-8 border border-yellow-100 text-center flex flex-col items-center">
                            <div class="bg-yellow-100 w-16 h-16 rounded-full flex items-center justify-center text-yellow-600 mb-4">
                                <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
                            </div>
                            <h4 class="text-lg font-bold text-yellow-800 mb-2">Belum Ada Data Siswa Terhubung</h4>
                            <p class="text-yellow-700 text-sm max-w-md mx-auto">Akun wali murid ini belum tertaut dengan data anak mana pun. Silakan hubungi bagian Tata Usaha sekolah untuk melakukan sinkronisasi data.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
"""

full_content = php_head + php_custom + html_head + "<main class=\"flex-1 ml-64 flex flex-col min-w-0\">\n" + val_header + val_main + "</main>\n    </div>\n" + val_scripts

with open(profil_path, 'w', encoding='utf-8') as f:
    f.write(full_content)

print('Generated profil_siswa.php')

# Patch navigation in dashboard.php, tagihan.php, form_banding.php, profil_siswa.php
files = ['dashboard.php', 'tagihan.php', 'form_banding.php', 'profil_siswa.php']
menu_item = """
                <a href="profil_siswa.php"
                    class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-smooth">
                    <i class="fa-solid fa-user-graduate w-6"></i>
                    <span>Profil Siswa</span>
                </a>
"""
# Active menu item for profil_siswa.php
menu_item_active = """
                <a href="profil_siswa.php"
                    class="flex items-center px-4 py-3 bg-[#10b981] text-white rounded-lg font-medium shadow-md transition-smooth">
                    <i class="fa-solid fa-user-graduate w-6"></i>
                    <span>Profil Siswa</span>
                </a>
"""

for fname in files:
    path = os.path.join(folder, fname)
    with open(path, 'r', encoding='utf-8') as f:
        cont = f.read()
    
    if 'profil_siswa.php' not in cont or fname == 'profil_siswa.php':
        # Insert after form_banding.php menu item
        pattern = r'(<a href="form_banding\.php".*?</a>)'
        
        replacement = r'\1' + '\n' + (menu_item_active if fname == 'profil_siswa.php' else menu_item)
        if '<a href="profil_siswa.php"' not in cont:
            cont = re.sub(pattern, replacement, cont, flags=re.DOTALL)
            
        with open(path, 'w', encoding='utf-8') as f:
            f.write(cont)

print('Patched navigation menu')
