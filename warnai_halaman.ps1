$files = Get-ChildItem -Path "c:\xampp\htdocs\sppsekolah\admin" -Filter "*.php"

foreach ($f in $files) {
    if ($f.Name -match "dashboard.php|cetak") {
        continue
    }

    $c = Get-Content $f.FullName -Raw

    # 1. Bikin Edit Button Lebih Berwarna (Biru)
    $c = $c -replace 'bg-gray-50 text-gray-500 hover:bg-\[#10B981\]', 'bg-blue-50 text-blue-500 hover:bg-blue-500'
    $c = $c -replace 'bg-gray-50 text-gray-500 hover:bg-\[#2b7a4b\]', 'bg-blue-50 text-blue-500 hover:bg-blue-500'
    
    # 2. Bikin Hapus Button Lebih Berwarna (Merah/Rose)
    $c = $c -replace 'bg-gray-50 text-gray-500 hover:bg-red-500', 'bg-rose-50 text-rose-500 hover:bg-rose-500'

    # 3. Badge kelas & informasi umum (Abu -> Emerald)
    $c = $c -replace 'bg-gray-50 text-gray-600 border border-gray-100', 'bg-emerald-50 text-emerald-700 border border-emerald-100'
    
    # 4. Box Tautan Wali / Detail (Abu -> Indigo)
    $c = $c -replace 'class="bg-gray-50 border border-gray-100 p-2 rounded-lg inline-block"', 'class="bg-indigo-50 border border-indigo-100 p-2 rounded-lg inline-block"'
    
    # 5. Empty State text icon 
    $c = $c -replace 'text-3xl block mb-3 text-gray-300', 'text-3xl block mb-3 text-emerald-300 drop-shadow-sm'
    $c = $c -replace 'text-2xl block mb-2 text-gray-300', 'text-2xl block mb-2 text-emerald-300 drop-shadow-sm'
    $c = $c -replace 'Belum ada data', '<span class="text-emerald-600 font-medium tracking-wide">Belum ada data'
    
    # 6. Modal section / Box form (Abu -> Hijau Muda/Emerald)
    $c = $c -replace 'bg-gray-50 p-4 rounded-xl border border-gray-100', 'bg-emerald-50/50 p-4 rounded-xl border border-emerald-100'
    $c = $c -replace 'text-gray-400 italic text-xs bg-gray-50 py-1 px-2 rounded-md border border-gray-100', 'text-rose-500 italic text-xs bg-rose-50 py-1 px-2 rounded-md border border-rose-100 font-medium'
    
    # 7. Form inputs read-only (Abu -> Emerald Muda)
    $c = $c -replace 'bg-gray-50 text-sm pointer-events-none', 'bg-emerald-50/50 text-emerald-800 font-medium text-sm pointer-events-none'
    
    Set-Content -Path $f.FullName -Value $c -NoNewline
}
