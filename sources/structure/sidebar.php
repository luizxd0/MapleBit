<?php
if (basename($_SERVER['PHP_SELF']) === 'sidebar.php') {
    http_response_code(403);
    exit('403 - Access Forbidden');
}

$onlinePlayers = (int) mysqli_fetch_assoc(
    $mysqli->query('SELECT COUNT(*) AS total FROM accounts WHERE loggedin = 2')
)['total'];
$accountCount = (int) mysqli_fetch_assoc(
    $mysqli->query('SELECT COUNT(*) AS total FROM accounts')
)['total'];
$characterCount = (int) mysqli_fetch_assoc(
    $mysqli->query('SELECT COUNT(*) AS total FROM characters')
)['total'];
?>
<div class="sidebar-card account-card">
    <div class="sidebar-card-title">
        <span class="sidebar-icon"><i class="fa fa-user" aria-hidden="true"></i></span>
        <div>
            <small>Mapler access</small>
            <strong><?php echo isset($_SESSION['id']) ? 'Welcome back' : 'Your account'; ?></strong>
        </div>
    </div>
    <?php if (isset($_SESSION['id'])): ?>
        <nav class="sidebar-links" aria-label="Account links">
            <a href="?base=ucp"><i class="fa fa-th-large" aria-hidden="true"></i> Control panel</a>
            <?php if (isset($_SESSION['admin'])): ?>
                <a href="?base=admin"><i class="fa fa-cog" aria-hidden="true"></i> Admin panel</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['gm']) || isset($_SESSION['admin'])): ?>
                <a href="?base=gmcp"><i class="fa fa-shield" aria-hidden="true"></i> GM panel</a>
            <?php endif; ?>
            <?php if (($_SESSION['pname'] ?? 'checkpname') === 'checkpname'): ?>
                <a href="?base=ucp&amp;page=profname"><i class="fa fa-id-card-o" aria-hidden="true"></i> Set profile name</a>
            <?php else: ?>
                <a href="?base=main&amp;page=members&amp;name=<?php echo rawurlencode($_SESSION['pname']); ?>">
                    <i class="fa fa-id-card-o" aria-hidden="true"></i> My profile
                </a>
            <?php endif; ?>
            <a href="?base=main&amp;page=members"><i class="fa fa-users" aria-hidden="true"></i> Community</a>
            <a href="?base=misc&amp;script=logout"><i class="fa fa-sign-out" aria-hidden="true"></i> Log out</a>
        </nav>
    <?php else: ?>
        <form name="loginform" id="loginform" autocomplete="off" class="maple-login">
            <input type="hidden" id="login_csrf_token"
                   value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="maple-field">
                <i class="fa fa-user" aria-hidden="true"></i>
                <input type="text" name="username" maxlength="12" class="form-control"
                       placeholder="Username" id="username" autocomplete="username" required>
            </div>
            <div class="maple-field">
                <i class="fa fa-lock" aria-hidden="true"></i>
                <input type="password" name="password" maxlength="72" class="form-control"
                       placeholder="Password" id="password" autocomplete="current-password" required>
            </div>
            <button id="login" type="submit" class="btn maple-btn-primary btn-block">Log in</button>
        </form>
        <div id="message"></div>
        <p class="sidebar-helper">New here? <a href="?base=main&amp;page=register">Create an account</a></p>
    <?php endif; ?>
</div>

<div class="sidebar-card world-card">
    <div class="sidebar-card-title">
        <span class="sidebar-icon server-icon"><i class="fa fa-globe" aria-hidden="true"></i></span>
        <div>
            <small>World status</small>
            <strong>Scania</strong>
        </div>
        <span class="live-pill"><span></span> Live</span>
    </div>
    <div class="world-population">
        <div class="population-orb"><strong><?php echo $onlinePlayers; ?></strong><small>online</small></div>
        <div>
            <strong><?php echo $accountCount; ?></strong><span>accounts</span>
            <strong><?php echo $characterCount; ?></strong><span>characters</span>
        </div>
    </div>
    <div class="rate-grid">
        <div><span>EXP</span><strong><?php echo htmlspecialchars((string) $exprate, ENT_QUOTES, 'UTF-8'); ?></strong></div>
        <div><span>Meso</span><strong><?php echo htmlspecialchars((string) $mesorate, ENT_QUOTES, 'UTF-8'); ?></strong></div>
        <div><span>Drop</span><strong><?php echo htmlspecialchars((string) $droprate, ENT_QUOTES, 'UTF-8'); ?></strong></div>
    </div>
    <a class="server-version" href="?base=main&amp;page=download">
        <span>Client version</span><strong>v<?php echo htmlspecialchars((string) $version, ENT_QUOTES, 'UTF-8'); ?></strong>
    </a>
</div>
