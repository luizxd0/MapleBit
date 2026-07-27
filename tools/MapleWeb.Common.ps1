Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Get-MapleYamlValue {
    param(
        [Parameter(Mandatory)][string]$Content,
        [Parameter(Mandatory)][string]$Name
    )

    $match = [regex]::Match(
        $Content,
        "(?m)^\s+$([regex]::Escape($Name)):\s*(?<value>[^\r\n#]+)"
    )
    if (-not $match.Success) {
        throw "Unable to find '$Name' in the MapleServer configuration."
    }

    return $match.Groups['value'].Value.Trim().Trim('"').Trim("'")
}

function Import-MapleWebEnvironment {
    param(
        [Parameter(Mandatory)][string]$ServerRoot,
        [int]$Port = 8080
    )

    $configPath = Join-Path $ServerRoot 'config.yaml'
    if (-not (Test-Path -LiteralPath $configPath)) {
        throw "MapleServer configuration was not found: $configPath"
    }

    $yaml = Get-Content -LiteralPath $configPath -Raw
    $dbUrl = Get-MapleYamlValue -Content $yaml -Name 'DB_URL_FORMAT'
    $dbNameMatch = [regex]::Match($dbUrl, '/(?<name>[A-Za-z0-9_]+)(?:\?|$)')

    $env:MAPLE_DB_HOST = Get-MapleYamlValue -Content $yaml -Name 'DB_HOST'
    if ($env:MAPLE_DB_HOST -eq 'localhost') {
        $env:MAPLE_DB_HOST = '127.0.0.1'
    }
    $env:MAPLE_DB_USER = Get-MapleYamlValue -Content $yaml -Name 'DB_USER'
    $env:MAPLE_DB_PASS = Get-MapleYamlValue -Content $yaml -Name 'DB_PASS'
    $env:MAPLE_DB_NAME = if ($dbNameMatch.Success) { $dbNameMatch.Groups['name'].Value } else { 'cosmic' }
    $env:MAPLE_DB_PORT = '3306'
    $env:MAPLE_DB_PREFIX = 'bit_'
    $env:MAPLE_LOCAL_DEV = '1'
    $env:MAPLE_SITE_URL = "http://127.0.0.1:$Port/"
}

function Get-MaplePhpPath {
    $repositoryRoot = Split-Path -Parent $PSScriptRoot
    $phpPath = Join-Path $repositoryRoot '.runtime\php\php.exe'
    if (-not (Test-Path -LiteralPath $phpPath)) {
        throw "The MapleWeb PHP runtime was not found: $phpPath"
    }
    return $phpPath
}

