$pattern = '(?s)(<a href="laporan\.php".*?>)'
$replacement = @"
<a href="m_rincian.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-colors">
                <i class="fa-solid fa-list-check w-6"></i> Master Rincian Biaya
            </a>
            `$1
"@

Get-ChildItem -Path c:\xampp\htdocs\sppsekolah\admin\*.php -Exclude "m_rincian.php", "export_excel.php" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    if ($content -notmatch 'href="m_rincian\.php"') {
        $newContent = $content -replace $pattern, $replacement
        Set-Content -Path $_.FullName -Value $newContent
        Write-Output "Updated $_.Name"
    }
}
