$files = Get-ChildItem -Path "c:\xampp\htdocs\sppsekolah\admin" -Filter "*.php"
foreach ($f in $files) {
    $content = Get-Content $f.FullName -Raw
    
    # Sidebar Background
    $content = $content -replace 'bg-\[#1C2434\]', 'bg-[#10B981]'
    
    # Sidebar Active & Hover
    $content = $content -replace 'bg-\[#333A48\]', 'bg-[#059669]'
    $content = $content -replace 'hover:bg-\[#333A48\]', 'hover:bg-[#059669]'

    # Buttons
    $content = $content -replace 'bg-\[#10B981\]', 'bg-[#22C55E]'
    
    # Text (Keep text same emerald or change to green)
    $content = $content -replace 'text-\[#10B981\]', 'text-[#22C55E]'
    
    # Button Hover
    $content = $content -replace 'hover:bg-\[#059669\]', 'hover:bg-[#16A34A]'
    
    Set-Content -Path $f.FullName -Value $content -NoNewline
}
