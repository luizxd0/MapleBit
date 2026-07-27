<?php
if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
    || empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest'
) {
    http_response_code(405);
    exit('invalid');
}

$csrfToken = (string) ($_POST['csrf_token'] ?? '');
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    exit('invalid');
}

if (isset($_SESSION['login_block_until']) && $_SESSION['login_block_until'] > time()) {
    exit('wait%' . ($_SESSION['login_block_until'] - time()));
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
if (!preg_match('/^[A-Za-z0-9]{4,13}$/', $username) || $password === '') {
    exit('invalid');
}

$statement = $mysqli->prepare(
    'SELECT id, name, password, banned, mute, email, webadmin FROM accounts WHERE name = ? LIMIT 1'
);
$statement->bind_param('s', $username);
$statement->execute();
$account = $statement->get_result()->fetch_assoc();

if (!$account || (int) $account['banned'] > 0 || !password_verify($password, $account['password'])) {
    $_SESSION['login_attempts'] = (int) ($_SESSION['login_attempts'] ?? 0) + 1;
    if ($_SESSION['login_attempts'] >= 5) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_block_until'] = time() + 60;
        exit('wait%60');
    }
    exit('invalid');
}

session_regenerate_id(true);
$_SESSION['login_attempts'] = 0;
unset($_SESSION['login_block_until']);

$profileStatement = $mysqli->prepare("SELECT name FROM {$prefix}profile WHERE accountid = ? LIMIT 1");
$profileStatement->bind_param('i', $account['id']);
$profileStatement->execute();
$profile = $profileStatement->get_result()->fetch_assoc();

$_SESSION['id'] = (int) $account['id'];
$_SESSION['name'] = $account['name'];
$_SESSION['mute'] = (int) $account['mute'];
$_SESSION['email'] = $account['email'];
$_SESSION['pname'] = $profile ? $profile['name'] : 'checkpname';

if ((int) $account['webadmin'] === 1) {
    $_SESSION['admin'] = 1;
}

$gmStatement = $mysqli->prepare('SELECT MAX(gm) AS gm FROM characters WHERE accountid = ?');
$gmStatement->bind_param('i', $account['id']);
$gmStatement->execute();
$gmRow = $gmStatement->get_result()->fetch_assoc();
if ($gmRow && (int) $gmRow['gm'] >= (int) $gmlevel) {
    $_SESSION['gm'] = (int) $gmRow['gm'];
}

echo 'success';
