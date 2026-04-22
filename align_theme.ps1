$files = Get-ChildItem -Path "c:\xampp\htdocs\sppsekolah\admin" -Filter "*.php"

foreach ($f in $files) {
    $c = Get-Content $f.FullName -Raw
    
    # 1. SIDEBAR BACKGROUND
    # Fix sidebar container
    $c = $c -replace '<aside class="w-\[260px\] bg-\[#22C55E\]', '<aside class="w-[260px] bg-[#1C2434]'
    $c = $c -replace '<aside class="w-\[260px\] bg-\[#10B981\]', '<aside class="w-[260px] bg-[#1C2434]'

    # 2. LOGO TEXT (Admin Panel -> SaaS Panel with color)
    $c = $c -replace 'text-gray-500 mt-0.5">Admin Panel</p>', 'text-[#10B981] mt-0.5 font-bold">SaaS Panel</p>'

    # 3. SIDEBAR NAVIGATION
    # Active item backgrounds
    $c = $c -replace 'bg-\[#059669\] text-white', 'bg-[#10B981] text-white shadow-sm'
    # Remove borders from sidebar items
    $c = $c -replace 'border-l-\[3px\] border-white', ''
    $c = $c -replace 'border-l-\[3px\] border-transparent', ''
    # Sidebar hover
    $c = $c -replace 'hover:bg-\[#16A34A\] hover:text-', 'hover:bg-white/5 hover:text-'

    # 4. BUTTONS & ACCENTS
    # Main buttons bg
    $c = $c -replace 'bg-\[#22C55E\]', 'bg-[#10B981]'
    # Button hover
    $c = $c -replace 'hover:bg-\[#16A34A\]', 'hover:bg-[#059669]'

    # 5. HEADER BADGE (Admin Username)
    # Right now it's: 
    # <div class="flex items-center text-xs text-gray-500">
    #      <i class="fa-regular fa-circle-user mr-1.5 text-gray-400"></i>
    #      <?= $nama_admin; ? >
    # </div>
    # Let's replace it with the badge style:
    $badgeOld = '(?s)<div class="flex items-center text-xs text-gray-500">\s*<i class="fa-regular fa-circle-user mr-1.5 text-gray-400"></i>\s*<\?= \$nama_admin; \?>\s*</div>'
    $badgeNew = '<div class="flex items-center bg-[#f0fdf4] text-[#166534] px-4 py-1.5 rounded-full text-xs font-semibold mr-2 border border-green-100"> Admin: <?= $nama_admin; ?> </div>'
    $c = $c -replace $badgeOld, $badgeNew

    Set-Content -Path $f.FullName -Value $c -NoNewline
}
