<?php
$usingHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
	'httponly' => true,
	'secure' => $usingHttps,
	'samesite' => 'Lax',
	'path' => '/',
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
# Disable Notices

# Is MapleBit installed?
if(!file_exists('assets/config/install/installdone.txt')) {
	header("Location: assets/config/install/install.php");
	exit;
} else {
	# Get Database Information
	require_once("assets/config/database.php");

	# Import Essential Files
	require_once("assets/config/properties.php");
	require_once("assets/config/funcs.php");
	
	# Define $getbase variable
	$getbase = isset($_GET['base']) ? $_GET['base'] : "";

	$slugs = [];
	$slugarray = [];
	$getslug = $mysqli->query("SELECT slug, title, visible from ".$prefix."pages");
	while($fetchslug = $getslug->fetch_assoc()) {
		$slugs[] = $fetchslug['slug'];
		$slugarray[] = array($fetchslug['slug'], $fetchslug['title'], $fetchslug['visible']);
	}

	switch($getbase) {
		case NULL:
		case "main":
			include("sources/structure/header.php");
			include("sources/public/main.php");
			include("sources/structure/footer.php");
			break;
		case "ucp":
			include("sources/structure/header.php");
			include("sources/ucp/main.php");
			include("sources/structure/footer.php");
			break;
		case "admin":
			include("sources/structure/admin/header.php");
			include("sources/admin/main.php");
			break;
		case "gmcp":
			include("sources/structure/header.php");
			include("sources/gmcp/main.php");
			include("sources/structure/footer.php");
			break;
		case "misc":
			include("sources/misc/main.php");
			break;
		default:
			include("sources/structure/header.php");
			include("sources/public/main.php");
			include("sources/structure/footer.php");
			break;
	}
}

$mysqli->close();
?>
