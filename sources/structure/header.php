<?php
if (basename($_SERVER['PHP_SELF']) === 'header.php') {
    http_response_code(403);
    exit('403 - Access Forbidden');
}

$currentBase = (string) ($_GET['base'] ?? 'main');
$currentPage = (string) ($_GET['page'] ?? '');
$isHomepage = $currentBase === 'main' && $currentPage === '';
$safeServerName = htmlspecialchars($servername, ENT_QUOTES, 'UTF-8');
$safeSiteUrl = htmlspecialchars($siteurl, ENT_QUOTES, 'UTF-8');
$bannerValue = (string) ($banner ?? '');
$backgroundValue = (string) ($background ?? '');
$backgroundColorValue = (string) ($bgcolor ?? '');
$backgroundRepeatValue = (string) ($bgrepeat ?? '');
$headerOnline = (int) mysqli_fetch_assoc(
    $mysqli->query('SELECT COUNT(*) AS total FROM accounts WHERE loggedin = 2')
)['total'];
$heroStyle = $bannerValue !== ''
    ? ' style="background-image: linear-gradient(90deg, rgba(18, 49, 69, .92), rgba(18, 49, 69, .2)), url(\''
        . htmlspecialchars($bannerValue, ENT_QUOTES, 'UTF-8') . '\')"'
    : '';

$navIsActive = static function (string $page) use ($currentBase, $currentPage): string {
    if ($currentBase !== 'main') {
        return '';
    }
    return ($page === 'home' && $currentPage === '') || $currentPage === $page
        ? ' active'
        : '';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $safeServerName; ?> — a local GMS v83 MapleStory test world.">
    <title><?php echo $safeServerName; ?></title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link href="assets/css/<?php echo htmlspecialchars($theme, ENT_QUOTES, 'UTF-8'); ?>.min.css" rel="stylesheet">
    <link href="assets/css/font-awesome.min.css" rel="stylesheet">
    <link href="assets/css/addon.css" rel="stylesheet">
    <?php if ($backgroundValue !== '' || $backgroundColorValue !== ''): ?>
        <style>
            body {
                <?php if ($backgroundValue !== ''): ?>background-image: url('<?php echo htmlspecialchars($backgroundValue, ENT_QUOTES, 'UTF-8'); ?>');<?php endif; ?>
                <?php if ($backgroundColorValue !== ''): ?>background-color: #<?php echo htmlspecialchars($backgroundColorValue, ENT_QUOTES, 'UTF-8'); ?>;<?php endif; ?>
                <?php if ($backgroundRepeatValue !== ''): ?>background-repeat: <?php echo htmlspecialchars($backgroundRepeatValue, ENT_QUOTES, 'UTF-8'); ?>;<?php endif; ?>
                <?php if (!empty($bgcenter)): ?>background-position: center;<?php endif; ?>
                <?php if (!empty($bgfixed)): ?>background-attachment: fixed;<?php endif; ?>
                <?php if (!empty($bgcover)): ?>background-size: cover;<?php endif; ?>
            }
        </style>
    <?php endif; ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="<?php echo $isHomepage ? 'maple-home' : 'maple-inner'; ?>">
<div class="maple-backdrop" aria-hidden="true"></div>
<div class="site-shell">
    <header class="maple-hero <?php echo $isHomepage ? 'maple-hero-home' : 'maple-hero-compact'; ?>"<?php echo $heroStyle; ?>>
        <div class="maple-hero-glow" aria-hidden="true"></div>
        <div class="maple-hero-content">
            <div class="maple-kicker"><span class="status-pulse"></span> Local GMS v83 world</div>
            <h1><?php echo $safeServerName; ?></h1>
            <p>Classic adventures, a living solo world, and a fresh place to build.</p>
            <?php if ($isHomepage): ?>
                <div class="hero-actions">
                    <a class="btn maple-btn-primary" href="?base=main&amp;page=download">
                        <i class="fa fa-download" aria-hidden="true"></i> Get the client
                    </a>
                    <?php if (!isset($_SESSION['id'])): ?>
                        <a class="btn maple-btn-ghost" href="?base=main&amp;page=register">Create account</a>
                    <?php else: ?>
                        <a class="btn maple-btn-ghost" href="?base=ucp">Open control panel</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="hero-world-status">
            <span class="world-status-dot"></span>
            <div>
                <strong><?php echo $headerOnline; ?> online</strong>
                <small>Scania · <?php echo htmlspecialchars((string) $exprate, ENT_QUOTES, 'UTF-8'); ?> EXP</small>
            </div>
        </div>
    </header>

    <nav class="navbar navbar-expand-lg maple-navbar">
        <a class="navbar-brand" href="?base=main">
            <span class="maple-mark" aria-hidden="true">🍁</span>
            <span><?php echo $safeServerName; ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa fa-bars" aria-hidden="true"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item<?php echo $navIsActive('home'); ?>"><a class="nav-link" href="?base=main">Home</a></li>
                <?php if (!isset($_SESSION['id'])): ?>
                    <li class="nav-item<?php echo $navIsActive('register'); ?>"><a class="nav-link" href="?base=main&amp;page=register">Register</a></li>
                <?php endif; ?>
                <li class="nav-item<?php echo $navIsActive('download'); ?>"><a class="nav-link" href="?base=main&amp;page=download">Download</a></li>
                <li class="nav-item<?php echo $navIsActive('rankings'); ?>"><a class="nav-link" href="?base=main&amp;page=rankings">Rankings</a></li>
                <li class="nav-item<?php echo $navIsActive('members'); ?>"><a class="nav-link" href="?base=main&amp;page=members">Community</a></li>
                <li class="nav-item<?php echo $navIsActive('vote'); ?>"><a class="nav-link" href="?base=main&amp;page=vote">Vote</a></li>
                <?php if ($forumurl !== '' && $forumurl !== '#'): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars($forumurl, ENT_QUOTES, 'UTF-8'); ?>">Forums</a></li>
                <?php endif; ?>
                <?php foreach ($slugarray as $page): ?>
                    <?php if ($page[2]): ?>
                        <li class="nav-item<?php echo $navIsActive($page[0]); ?>">
                            <a class="nav-link" href="?base=main&amp;page=<?php echo rawurlencode($page[0]); ?>">
                                <?php echo htmlspecialchars($page[1], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <?php if (isset($_SESSION['id'])): ?>
                <ul class="navbar-nav maple-user-nav">
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" role="button" data-toggle="dropdown"
                           aria-haspopup="true" aria-expanded="false">
                            <img src="<?php echo htmlspecialchars(get_gravatar($_SESSION['email'], 40), ENT_QUOTES, 'UTF-8'); ?>"
                                 alt="" class="user-avatar">
                            <?php echo htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <?php if ($_SESSION['pname'] === 'checkpname'): ?>
                                <a class="dropdown-item" href="?base=ucp&amp;page=profname">Set profile name</a>
                            <?php else: ?>
                                <a class="dropdown-item" href="?base=main&amp;page=members&amp;name=<?php echo rawurlencode($_SESSION['pname']); ?>">My profile</a>
                            <?php endif; ?>
                            <a class="dropdown-item" href="?base=ucp">Control panel</a>
                            <a class="dropdown-item" href="?base=ucp&amp;page=charfix">Character fix</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="?base=misc&amp;script=logout">Log out</a>
                        </div>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </nav>

    <main class="maple-page">
        <div class="row maple-layout">
            <aside class="col-lg-3 maple-sidebar">
                <?php include 'sources/structure/sidebar.php'; ?>
            </aside>
            <section class="col-lg-9 maple-content">
