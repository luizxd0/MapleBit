[CmdletBinding()]
param(
    [string]$ServerRoot = 'C:\MapleServer',
    [ValidateRange(1024, 65535)][int]$Port = 8080,
    [switch]$Background
)

. (Join-Path $PSScriptRoot 'tools\MapleWeb.Common.ps1')
Import-MapleWebEnvironment -ServerRoot $ServerRoot -Port $Port
$phpPath = Get-MaplePhpPath
$marker = Join-Path $PSScriptRoot 'assets\config\install\installdone.txt'

if (-not (Test-Path -LiteralPath $marker)) {
    & (Join-Path $PSScriptRoot 'Install-MapleWeb.ps1') -ServerRoot $ServerRoot -Port $Port
}

$existingListener = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if ($existingListener) {
    throw "Port $Port is already in use by process $($existingListener[0].OwningProcess)."
}

$arguments = @('-S', "127.0.0.1:$Port", '-t', $PSScriptRoot)
if ($Background) {
    $process = Start-Process `
        -FilePath $phpPath `
        -ArgumentList $arguments `
        -WorkingDirectory $PSScriptRoot `
        -WindowStyle Hidden `
        -RedirectStandardOutput (Join-Path $PSScriptRoot '.mapleweb.log') `
        -RedirectStandardError (Join-Path $PSScriptRoot '.mapleweb-error.log') `
        -PassThru
    Set-Content -LiteralPath (Join-Path $PSScriptRoot '.mapleweb.pid') -Value $process.Id
    Write-Output "MapleWeb started at http://127.0.0.1:$Port/ (PID $($process.Id))."
    return
}

Write-Output "MapleWeb is running at http://127.0.0.1:$Port/. Press Ctrl+C to stop it."
& $phpPath @arguments

