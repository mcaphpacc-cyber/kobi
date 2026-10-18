param([Parameter(ValueFromRemainingArguments = $true)][string[]]$FlutterArguments)
$ErrorActionPreference = 'Stop'
$mobileRoot = Split-Path -Parent $PSScriptRoot
$toolingRoot = Join-Path (Split-Path -Parent $mobileRoot) '.tooling'
$flutterEntry = Join-Path $toolingRoot 'flutter\bin\flutter.bat'
if (-not (Test-Path -LiteralPath $flutterEntry)) {
    throw 'Install the approved Flutter SDK in mobile/.tooling/flutter or use your own Flutter command from the mobile project directory.'
}
$names = @('PUB_CACHE','GRADLE_USER_HOME','JAVA_HOME','ANDROID_HOME','ANDROID_SDK_ROOT','ANDROID_USER_HOME','TEMP','TMP','DASH__SUPPRESS_ANALYTICS','CI')
$previous = @{}
foreach ($name in $names) { $previous[$name] = [Environment]::GetEnvironmentVariable($name,'Process') }
try {
    $env:PUB_CACHE = Join-Path $toolingRoot 'pub-cache'
    $env:GRADLE_USER_HOME = Join-Path $toolingRoot 'gradle'
    $javaFolder = Get-ChildItem -LiteralPath (Join-Path $toolingRoot 'java') -Directory -Filter 'jdk-*' -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($javaFolder) { $env:JAVA_HOME = $javaFolder.FullName }
    if (Test-Path -LiteralPath (Join-Path $toolingRoot 'android-sdk')) {
        $env:ANDROID_HOME = Join-Path $toolingRoot 'android-sdk'
        $env:ANDROID_SDK_ROOT = $env:ANDROID_HOME
    }
    $env:ANDROID_USER_HOME = Join-Path $toolingRoot 'android-user'
    $env:TEMP = Join-Path $toolingRoot 'temp'
    $env:TMP = $env:TEMP
    New-Item -ItemType Directory -Path $env:TEMP -Force | Out-Null
    $env:DASH__SUPPRESS_ANALYTICS = 'true'
    $env:CI = 'true'
    Push-Location -LiteralPath $mobileRoot
    try { & $flutterEntry --suppress-analytics @FlutterArguments; $resultCode = $LASTEXITCODE }
    finally { Pop-Location }
} finally {
    foreach ($name in $names) { [Environment]::SetEnvironmentVariable($name,$previous[$name],'Process') }
}
exit $resultCode
