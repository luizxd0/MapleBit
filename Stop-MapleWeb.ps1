[CmdletBinding()]
param()

$pidPath = Join-Path $PSScriptRoot '.mapleweb.pid'
if (-not (Test-Path -LiteralPath $pidPath)) {
    Write-Output 'MapleWeb does not have a recorded background process.'
    return
}

$webProcessId = [int] (Get-Content -LiteralPath $pidPath -Raw).Trim()
$process = Get-Process -Id $webProcessId -ErrorAction SilentlyContinue
if ($process -and $process.ProcessName -eq 'php') {
    Stop-Process -Id $webProcessId
    Write-Output "Stopped MapleWeb process $webProcessId."
} else {
    Write-Output "Recorded process $webProcessId is no longer a MapleWeb PHP process."
}
Remove-Item -LiteralPath $pidPath -Force

