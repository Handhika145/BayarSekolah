$files = Get-ChildItem -Path "c:\xampp\htdocs\sppsekolah\admin" -Filter "*.php"
foreach ($f in $files) {
    $content = Get-Content $f.FullName -Raw
    $content = $content -replace 'bg-\[#1b633c\]', 'bg-[#1C2434]'
    $content = $content -replace 'bg-\[#398c58\]/80', 'bg-[#333A48]'
    $content = $content -replace 'bg-\[#398c58\]/40', 'bg-[#333A48]'
    $content = $content -replace 'bg-\[#398c58\]', 'bg-[#10B981]'
    $content = $content -replace 'hover:bg-\[#2b7a4b\]', 'hover:bg-[#059669]'
    $content = $content -replace 'text-\[#398c58\]', 'text-[#10B981]'
    Set-Content -Path $f.FullName -Value $content -NoNewline
}
