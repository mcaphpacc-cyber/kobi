$ErrorActionPreference = 'Stop'
$taskSecurePassword = Read-Host 'KOBI Account 1 password (hidden; sent only to verified localhost HTTPS)' -AsSecureString
$taskPointer = [IntPtr]::Zero
try {
    $taskPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($taskSecurePassword)
    $taskPayload = @{ password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($taskPointer) } | ConvertTo-Json -Compress
    $taskPayload | & 'D:\xampp\php\php.exe' 'D:\xampp\htdocs\kobi\tests\api-auth-production-smoke.php'
    if ($LASTEXITCODE -ne 0) { throw 'API smoke stopped; do not rerun automatically.' }
} finally {
    if ($taskPointer -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($taskPointer) }
    $taskPayload = $null
    $taskSecurePassword.Dispose()
}
