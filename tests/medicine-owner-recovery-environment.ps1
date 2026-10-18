param([Parameter(Mandatory=$true)][ValidateSet('start','stop')][string]$Action)
$ErrorActionPreference = 'Stop'
$ownerEnvironment = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '.medicine-owner-recovery'))
if ($ownerEnvironment -ne 'D:\xampp\htdocs\kobi\tests\.medicine-owner-recovery') { throw 'Unexpected isolated target' }
$ownerDataArgument = '--datadir=D:/xampp/htdocs/kobi/tests/.medicine-owner-recovery/data'
if ($Action -eq 'start') {
    if (Test-Path -LiteralPath $ownerEnvironment) { throw 'Fresh datadir required' }
    New-Item -ItemType Directory -Path $ownerEnvironment | Out-Null
    Set-Content -LiteralPath (Join-Path $ownerEnvironment 'instance-id.txt') -Value ([Guid]::NewGuid().ToString())
    & D:\xampp\mysql\bin\mysql_install_db.exe "--datadir=$ownerEnvironment\data" --port=3342
    if ($LASTEXITCODE -ne 0) { throw 'Isolated initialization failed' }
    $ownerProcess = Start-Process -FilePath D:\xampp\mysql\bin\mysqld.exe -ArgumentList '--no-defaults','--basedir=D:/xampp/mysql',$ownerDataArgument,'--port=3342','--bind-address=127.0.0.1','--innodb-use-native-aio=0','--innodb-flush-method=normal','--character-set-server=utf8mb4','--collation-server=utf8mb4_general_ci','--sql-mode=NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION','--pid-file=D:/xampp/htdocs/kobi/tests/.medicine-owner-recovery/server.pid','--log-error=D:/xampp/htdocs/kobi/tests/.medicine-owner-recovery/server.err' -WindowStyle Hidden -PassThru
    for ($ownerAttempt=0; $ownerAttempt -lt 15; $ownerAttempt++) {
        & D:\xampp\mysql\bin\mysqladmin.exe --no-defaults --host=127.0.0.1 --port=3342 --user=root ping 2>$null | Out-Null
        if ($LASTEXITCODE -eq 0) { "Disposable instance ready: PID $($ownerProcess.Id)"; exit 0 }
        Start-Sleep -Milliseconds 500
    }
    throw 'Isolated startup unavailable; do not restore'
}
if (!(Test-Path -LiteralPath $ownerEnvironment)) { throw 'No isolated environment to stop' }
$ownerPid = [int](Get-Content -LiteralPath (Join-Path $ownerEnvironment 'server.pid'))
$ownerRunning = Get-CimInstance Win32_Process -Filter "ProcessId=$ownerPid"
if (!$ownerRunning -or $ownerRunning.Name -ne 'mysqld.exe' -or $ownerRunning.CommandLine -notlike "*$ownerDataArgument*" -or $ownerRunning.CommandLine -notlike '*--port=3342*') { throw 'Isolated process identity mismatch' }
& D:\xampp\mysql\bin\mysqladmin.exe --no-defaults --host=127.0.0.1 --port=3342 --user=root shutdown
if ($LASTEXITCODE -ne 0) { throw 'Isolated shutdown request failed' }
for ($ownerWait=0; $ownerWait -lt 20; $ownerWait++) {
    if (!(Get-CimInstance Win32_Process -Filter "ProcessId=$ownerPid")) { break }
    Start-Sleep -Milliseconds 500
}
if (Get-CimInstance Win32_Process -Filter "ProcessId=$ownerPid") {
    $ownerStillRunning = Get-CimInstance Win32_Process -Filter "ProcessId=$ownerPid"
    if ($ownerStillRunning.CommandLine -notlike "*$ownerDataArgument*" -or $ownerStillRunning.CommandLine -notlike '*--port=3342*') { throw 'Process identity changed; stop cleanup' }
    Stop-Process -Id $ownerPid -Force
    for ($ownerWait=0; $ownerWait -lt 20; $ownerWait++) {
        if (!(Get-CimInstance Win32_Process -Filter "ProcessId=$ownerPid")) { break }
        Start-Sleep -Milliseconds 500
    }
}
if (Get-CimInstance Win32_Process -Filter "ProcessId=$ownerPid") { throw 'Isolated process remains; no deletion' }
$ownerItems = @(Get-Item -LiteralPath $ownerEnvironment) + @(Get-ChildItem -LiteralPath $ownerEnvironment -Recurse -Force)
if ($ownerItems | Where-Object { $_.Attributes -band [IO.FileAttributes]::ReparsePoint }) { throw 'Reparsepoint cleanup denied' }
$ownerArchive = Join-Path (Split-Path $PSScriptRoot -Parent) 'storage\logs\medicine321a-owner-recovery'
$ownerInstance = (Get-Content -LiteralPath (Join-Path $ownerEnvironment 'instance-id.txt')).Trim()
Copy-Item -LiteralPath (Join-Path $ownerEnvironment 'server.err') -Destination (Join-Path $ownerArchive "fidelity-server-$ownerInstance.err")
Remove-Item -LiteralPath $ownerEnvironment -Recurse -Force
"Verified isolated process stopped; datadir removed: $ownerInstance"
