<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require $root . '/assets/config/database.php';

$requiredGameTables = ['accounts', 'characters', 'inventoryitems', 'guilds'];
foreach ($requiredGameTables as $table) {
    $statement = $mysqli->prepare(
        'SELECT COUNT(*) AS table_count FROM information_schema.tables '
        . 'WHERE table_schema = DATABASE() AND table_name = ?'
    );
    $statement->bind_param('s', $table);
    $statement->execute();
    $row = $statement->get_result()->fetch_assoc();
    if ((int) $row['table_count'] !== 1) {
        throw new RuntimeException("The Cosmic table '{$table}' is missing.");
    }
}

$propertiesTable = $prefix . 'properties';
$check = $mysqli->prepare(
    'SELECT COUNT(*) AS table_count FROM information_schema.tables '
    . 'WHERE table_schema = DATABASE() AND table_name = ?'
);
$check->bind_param('s', $propertiesTable);
$check->execute();
$row = $check->get_result()->fetch_assoc();

if ((int) $row['table_count'] !== 1) {
    $_GET['install'] = 3;
    $_SERVER['PHP_SELF'] = 'tools/configure.php';
    ob_start();
    include $root . '/assets/config/install/install.php';
    ob_end_clean();
    while ($mysqli->more_results() && $mysqli->next_result()) {
        // Drain every result produced by mysqli_multi_query in the legacy installer.
    }
}

$siteUrl = getenv('MAPLE_SITE_URL') ?: 'http://127.0.0.1:8080/';
$serverName = getenv('MAPLE_SERVER_NAME') ?: 'SoloMapling';
$homepage = '<h2>Welcome to SoloMapling</h2>'
    . '<p>The local GMS v83 test server and website are online.</p>';
$downloadUrl = $siteUrl . '?base=main&page=download';
$forumUrl = '#';
$expRate = getenv('MAPLE_EXP_RATE') ?: '1x';
$mesoRate = getenv('MAPLE_MESO_RATE') ?: '1x';
$dropRate = getenv('MAPLE_DROP_RATE') ?: '1x';

$update = $mysqli->prepare(
    "UPDATE {$prefix}properties SET "
    . 'name = ?, type = 0, client = ?, server = ?, version = 83, forumurl = ?, '
    . 'siteurl = ?, exprate = ?, mesorate = ?, droprate = ?, gmlevel = 3, '
    . "flood = 1, floodint = 5, theme = 'bootstrap', nav = 1, "
    . "colnx = 'nxCredit', colvp = 'votepoints', homecontent = ?"
);
$update->bind_param(
    'sssssssss',
    $serverName,
    $downloadUrl,
    $downloadUrl,
    $forumUrl,
    $siteUrl,
    $expRate,
    $mesoRate,
    $dropRate,
    $homepage
);
$update->execute();

$marker = $root . '/assets/config/install/installdone.txt';
if (file_put_contents($marker, "Installed by tools/configure.php\n") === false) {
    throw new RuntimeException("Unable to create the installer lock file: {$marker}");
}

$counts = [];
foreach (['accounts', 'characters'] as $table) {
    $result = $mysqli->query("SELECT COUNT(*) AS total FROM {$table}");
    $counts[$table] = (int) $result->fetch_assoc()['total'];
}

echo "MapleWeb configured successfully.\n";
echo "Database: {$host['database']} on {$host['hostname']}:{$host['port']}\n";
echo "Accounts: {$counts['accounts']}; Characters: {$counts['characters']}\n";

