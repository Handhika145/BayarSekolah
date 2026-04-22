$superadminPattern = '(?s)(<a href="#" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-all opacity-50 cursor-not-allowed" title="Segera Hadir">)'
$superadminReplacement = @"
<a href="terms.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-all">
                <i class="fa-solid fa-file-contract w-6"></i> Terms of Service
            </a>
            `$1
"@

Get-ChildItem -Path c:\xampp\htdocs\sppsekolah\superadmin\*.php -Exclude "terms.php" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    if ($content -notmatch 'href="terms\.php"') {
        $newContent = $content -replace $superadminPattern, $superadminReplacement
        Set-Content -Path $_.FullName -Value $newContent
        Write-Output "Updated $_.Name"
    }
}

$adminPattern = '(?s)(</a>\s*</nav>\s*</aside>)'
$adminReplacement = @"
</a>
            <a href="terms.php" class="flex items-center px-4 py-3 hover:bg-gray-800 hover:text-white rounded-lg font-medium transition-colors">
                <i class="fa-solid fa-file-contract w-6"></i> Terms of Service
            </a>
        </nav>
    </aside>
"@

Get-ChildItem -Path c:\xampp\htdocs\sppsekolah\admin\*.php -Exclude "terms.php", "export_excel.php" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    if ($content -notmatch 'href="terms\.php"') {
        $newContent = $content -replace $adminPattern, $adminReplacement
        Set-Content -Path $_.FullName -Value $newContent
        Write-Output "Updated $_.Name"
    }
}
