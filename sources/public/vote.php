<?php
if (basename($_SERVER['PHP_SELF']) === 'vote.php') {
    http_response_code(403);
    exit('403 - Access Forbidden');
}

$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$message = '';
$voteDestination = null;
$accountId = isset($_SESSION['id']) ? (int) $_SESSION['id'] : 0;
$accountName = isset($_SESSION['name']) ? (string) $_SESSION['name'] : '';
$selectedSiteId = filter_var($_POST['votingsite'] ?? null, FILTER_VALIDATE_INT);
$validNxColumns = ['nxCredit', 'maplePoint', 'nxPrepaid'];
$validVpColumns = ['votepoints'];

if (isset($_POST['submit'])) {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!$accountId) {
        $message = '<div class="alert alert-danger">Sign in before voting so rewards reach the correct account.</div>';
    } elseif (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $message = '<div class="alert alert-danger">Your form expired. Reload the page and try again.</div>';
    } elseif (!$selectedSiteId) {
        $message = '<div class="alert alert-danger">Choose a voting site.</div>';
    } elseif (!in_array($colnx, $validNxColumns, true) || !in_array($colvp, $validVpColumns, true)) {
        $message = '<div class="alert alert-danger">Vote rewards are not configured for this server.</div>';
    } else {
        try {
            $mysqli->begin_transaction();

            $accountStatement = $mysqli->prepare(
                'SELECT id, name, loggedin FROM accounts WHERE id = ? FOR UPDATE'
            );
            $accountStatement->bind_param('i', $accountId);
            $accountStatement->execute();
            $account = $accountStatement->get_result()->fetch_assoc();
            if (!$account) {
                throw new RuntimeException('Your account could not be found.');
            }
            if ((int) $account['loggedin'] > 0) {
                throw new RuntimeException('Log out of the game before collecting vote rewards.');
            }

            $siteStatement = $mysqli->prepare(
                "SELECT id, name, link, gnx, gvp, waittime FROM {$prefix}vote WHERE id = ? FOR UPDATE"
            );
            $siteStatement->bind_param('i', $selectedSiteId);
            $siteStatement->execute();
            $site = $siteStatement->get_result()->fetch_assoc();
            if (!$site) {
                throw new RuntimeException('That voting site is no longer available.');
            }

            $recordStatement = $mysqli->prepare(
                "SELECT id, date FROM {$prefix}votingrecords "
                . 'WHERE account = ? AND siteid = ? ORDER BY date DESC LIMIT 1 FOR UPDATE'
            );
            $recordStatement->bind_param('si', $accountName, $selectedSiteId);
            $recordStatement->execute();
            $record = $recordStatement->get_result()->fetch_assoc();

            $now = time();
            $waitTime = max(0, (int) $site['waittime']);
            $nextVoteAt = $record ? (int) $record['date'] + $waitTime : 0;
            if ($record && $now < $nextVoteAt) {
                $remaining = $nextVoteAt - $now;
                $hours = intdiv($remaining, 3600);
                $minutes = max(1, (int) ceil(($remaining % 3600) / 60));
                throw new RuntimeException(
                    'You can vote on ' . $site['name'] . ' again in '
                    . ($hours > 0 ? $hours . 'h ' : '') . $minutes . 'm.'
                );
            }

            if ($record) {
                $updateRecord = $mysqli->prepare(
                    "UPDATE {$prefix}votingrecords SET ip = ?, date = ?, times = times + 1 WHERE id = ?"
                );
                $recordId = (int) $record['id'];
                $updateRecord->bind_param('sii', $ipaddress, $now, $recordId);
                $updateRecord->execute();
            } else {
                $insertRecord = $mysqli->prepare(
                    "INSERT INTO {$prefix}votingrecords (siteid, ip, account, date, times) "
                    . 'VALUES (?, ?, ?, ?, 1)'
                );
                $insertRecord->bind_param('issi', $selectedSiteId, $ipaddress, $accountName, $now);
                $insertRecord->execute();
            }

            $nxReward = max(0, (int) $site['gnx']);
            $vpReward = max(0, (int) $site['gvp']);
            $rewardStatement = $mysqli->prepare(
                "UPDATE accounts SET {$colvp} = {$colvp} + ?, {$colnx} = {$colnx} + ? WHERE id = ?"
            );
            $rewardStatement->bind_param('iii', $vpReward, $nxReward, $accountId);
            $rewardStatement->execute();
            $mysqli->commit();

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $voteDestination = (string) $site['link'];
            $message = '<div class="alert alert-success"><strong>Reward added!</strong> '
                . number_format($nxReward) . ' NX and ' . number_format($vpReward)
                . ' vote point' . ($vpReward === 1 ? '' : 's')
                . ' were credited to ' . $escape($accountName) . '.</div>';
        } catch (Throwable $error) {
            $mysqli->rollback();
            $message = '<div class="alert alert-danger">'
                . $escape($error->getMessage()) . '</div>';
        }
    }
}

$sitesResult = $mysqli->query(
    "SELECT id, name, link, gnx, gvp, waittime FROM {$prefix}vote ORDER BY name"
);
$voteSites = $sitesResult ? $sitesResult->fetch_all(MYSQLI_ASSOC) : [];

$lastVotes = [];
if ($accountId && $voteSites) {
    $lastVoteStatement = $mysqli->prepare(
        "SELECT siteid, MAX(date) AS last_vote FROM {$prefix}votingrecords WHERE account = ? GROUP BY siteid"
    );
    $lastVoteStatement->bind_param('s', $accountName);
    $lastVoteStatement->execute();
    foreach ($lastVoteStatement->get_result()->fetch_all(MYSQLI_ASSOC) as $lastVote) {
        $lastVotes[(int) $lastVote['siteid']] = (int) $lastVote['last_vote'];
    }
}

$availableSiteIds = [];
$currentTime = time();
foreach ($voteSites as $site) {
    $siteId = (int) $site['id'];
    $lastVoteAt = $lastVotes[$siteId] ?? 0;
    if (!$lastVoteAt || $currentTime >= $lastVoteAt + (int) $site['waittime']) {
        $availableSiteIds[] = $siteId;
    }
}
$defaultSiteId = in_array($selectedSiteId, $availableSiteIds, true)
    ? $selectedSiteId
    : ($availableSiteIds[0] ?? 0);
?>
<div class="vote-heading">
    <div>
        <span>Support the world</span>
        <h2 class="text-left">Vote &amp; earn rewards</h2>
        <p>Help SoloMapling get noticed and collect a small thank-you for your account.</p>
    </div>
    <span class="vote-gift"><i class="fa fa-gift" aria-hidden="true"></i></span>
</div>

<?php echo $message; ?>

<?php if ($voteDestination !== null): ?>
    <div class="vote-success-action">
        <a class="btn maple-btn-primary" href="<?php echo $escape($voteDestination); ?>"
           rel="noopener noreferrer">
            Continue to voting site <i class="fa fa-external-link" aria-hidden="true"></i>
        </a>
        <small>Your reward has already been recorded; the site cooldown now applies.</small>
    </div>
<?php elseif (!$voteSites): ?>
    <div class="vote-empty">
        <span><i class="fa fa-flag-o" aria-hidden="true"></i></span>
        <h3>Voting is not configured yet</h3>
        <p>An administrator can add a server-listing URL from Admin Panel → Vote Configuration.</p>
    </div>
<?php elseif (!$accountId): ?>
    <div class="vote-empty">
        <span><i class="fa fa-user-circle-o" aria-hidden="true"></i></span>
        <h3>Sign in to vote</h3>
        <p>Use the account panel on this page, then choose a voting site to collect rewards securely.</p>
    </div>
<?php else: ?>
    <form method="post" class="vote-form">
        <input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['csrf_token']); ?>">
        <div class="vote-site-grid">
            <?php foreach ($voteSites as $site): ?>
                <?php
                $siteId = (int) $site['id'];
                $lastVoteAt = $lastVotes[$siteId] ?? 0;
                $availableAt = $lastVoteAt + (int) $site['waittime'];
                $available = in_array($siteId, $availableSiteIds, true);
                ?>
                <label class="vote-site-card <?php echo $available ? '' : 'on-cooldown'; ?>">
                    <input type="radio" name="votingsite" value="<?php echo $siteId; ?>"
                        <?php echo $defaultSiteId === $siteId ? 'checked' : ''; ?>
                        <?php echo $available ? '' : 'disabled'; ?>>
                    <span class="vote-radio"><i class="fa fa-check" aria-hidden="true"></i></span>
                    <span class="vote-site-copy">
                        <small><?php echo $available ? 'Ready to vote' : 'Cooldown active'; ?></small>
                        <strong><?php echo $escape($site['name']); ?></strong>
                        <em>
                            <?php echo number_format((int) $site['gnx']); ?> NX ·
                            <?php echo number_format((int) $site['gvp']); ?> VP
                        </em>
                    </span>
                    <span class="vote-timer">
                        <?php if ($available): ?>
                            Every <?php echo max(1, round((int) $site['waittime'] / 3600)); ?>h
                        <?php else: ?>
                            <?php echo date('H:i', $availableAt); ?>
                        <?php endif; ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="vote-submit-row">
            <div>
                <strong>Voting as <?php echo $escape($accountName); ?></strong>
                <small>You must be logged out of the game.</small>
            </div>
            <button type="submit" name="submit" class="btn maple-btn-primary"
                    <?php echo $availableSiteIds ? '' : 'disabled'; ?>>
                <?php echo $availableSiteIds ? 'Claim reward &amp; vote' : 'All sites on cooldown'; ?>
                <i class="fa fa-angle-right" aria-hidden="true"></i>
            </button>
        </div>
    </form>
<?php endif; ?>
