$ErrorActionPreference = 'Stop'
$taskSecurePassword = Read-Host 'Designated test Account 1 password (hidden; verified localhost HTTPS only)' -AsSecureString
$taskPointer = [IntPtr]::Zero
try {
    $taskPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($taskSecurePassword)
    $taskPayload = @{ password = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($taskPointer) } | ConvertTo-Json -Compress
    $taskPayload | & 'D:\xampp\php\php.exe' 'D:\xampp\htdocs\kobi\tests\api-auth-final-acceptance.php'
    if ($LASTEXITCODE -ne 0) { throw 'Final acceptance stopped; do not rerun automatically.' }
} finally {
    if ($taskPointer -ne [IntPtr]::Zero) { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($taskPointer) }
    $taskPayload = $null
    $taskSecurePassword.Dispose()
}
