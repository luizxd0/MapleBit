[CmdletBinding()]
param(
    [string]$ServerRoot = 'C:\MapleServer',
    [int]$Port = 8080
)

. (Join-Path $PSScriptRoot 'tools\MapleWeb.Common.ps1')
Import-MapleWebEnvironment -ServerRoot $ServerRoot -Port $Port
$phpPath = Get-MaplePhpPath

& $phpPath (Join-Path $PSScriptRoot 'tools\configure.php')
if ($LASTEXITCODE -ne 0) {
    throw "MapleWeb database configuration failed with exit code $LASTEXITCODE."
}

