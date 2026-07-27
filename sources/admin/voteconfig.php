<?php
if (basename($_SERVER['PHP_SELF']) === 'voteconfig.php') {
    http_response_code(403);
    exit('403 - Access Forbidden');
}

$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$action = (string) ($_GET['action'] ?? 'list');
$siteId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$message = '';
$validNxColumns = ['nxCredit', 'maplePoint', 'nxPrepaid'];

$csrfIsValid = static function (): bool {
    $provided = (string) ($_POST['csrf_token'] ?? '');
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $provided);
};

$validateSite = static function (array $input): array {
    $name = trim(strip_tags((string) ($input['sitename'] ?? '')));
    $link = trim((string) ($input['votelink'] ?? ''));
    $nx = filter_var($input['nx'] ?? null, FILTER_VALIDATE_INT);
    $vp = filter_var($input['vp'] ?? null, FILTER_VALIDATE_INT);
    $hours = filter_var($input['wait'] ?? null, FILTER_VALIDATE_INT);
    $errors = [];

    if (mb_strlen($name) < 2 || mb_strlen($name) > 45) {
        $errors[] = 'Site name must be 2–45 characters.';
    }
    $validExternalUrl = filter_var($link, FILTER_VALIDATE_URL)
        && in_array(strtolower((string) parse_url($link, PHP_URL_SCHEME)), ['http', 'https'], true);
    $validLocalUrl = getenv('MAPLE_LOCAL_DEV') === '1'
        && (str_starts_with($link, '?') || str_starts_with($link, '/'));
    if (!$validExternalUrl && !$validLocalUrl) {
        $errors[] = 'Enter a valid HTTPS voting URL.';
    }
    if ($nx === false || $nx < 0 || $nx > 100000000) {
        $errors[] = 'NX reward must be between 0 and 100,000,000.';
    }
    if ($vp === false || $vp < 0 || $vp > 1000000) {
        $errors[] = 'Vote-point reward must be between 0 and 1,000,000.';
    }
    if ($hours === false || $hours < 1 || $hours > 720) {
        $errors[] = 'Cooldown must be between 1 and 720 hours.';
    }

    return [
        'errors' => $errors,
        'name' => $name,
        'link' => $link,
        'nx' => $nx === false ? 0 : $nx,
        'vp' => $vp === false ? 0 : $vp,
        'hours' => $hours === false ? 6 : $hours,
    ];
};

if ($action === 'columns' && isset($_POST['submit'])) {
    $nextNxColumn = (string) ($_POST['colnx'] ?? '');
    if (!$csrfIsValid()) {
        $message = '<div class="alert alert-danger">Your form expired. Please try again.</div>';
    } elseif (!in_array($nextNxColumn, $validNxColumns, true)) {
        $message = '<div class="alert alert-danger">Choose a supported Cosmic NX column.</div>';
    } else {
        $votePointColumn = 'votepoints';
        $update = $mysqli->prepare(
            "UPDATE {$prefix}properties SET colnx = ?, colvp = ?"
        );
        $update->bind_param('ss', $nextNxColumn, $votePointColumn);
        $update->execute();
        $colnx = $nextNxColumn;
        $colvp = $votePointColumn;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $message = '<div class="alert alert-success">Vote reward columns updated.</div>';
    }
}

if (($action === 'add' || $action === 'edit') && isset($_POST['submit'])) {
    $siteInput = $validateSite($_POST);
    if (!$csrfIsValid()) {
        $siteInput['errors'][] = 'Your form expired. Please try again.';
    }
    if ($action === 'edit' && !$siteId) {
        $siteInput['errors'][] = 'Invalid voting-site ID.';
    }

    if ($siteInput['errors']) {
        $message = '<div class="alert alert-danger">'
            . implode('<br>', array_map($escape, $siteInput['errors'])) . '</div>';
    } else {
        $waitSeconds = (int) $siteInput['hours'] * 3600;
        if ($action === 'add') {
            $save = $mysqli->prepare(
                "INSERT INTO {$prefix}vote (name, link, gnx, gvp, waittime) VALUES (?, ?, ?, ?, ?)"
            );
            $save->bind_param(
                'ssiii',
                $siteInput['name'],
                $siteInput['link'],
                $siteInput['nx'],
                $siteInput['vp'],
                $waitSeconds
            );
        } else {
            $save = $mysqli->prepare(
                "UPDATE {$prefix}vote SET name = ?, link = ?, gnx = ?, gvp = ?, waittime = ? WHERE id = ?"
            );
            $save->bind_param(
                'ssiiii',
                $siteInput['name'],
                $siteInput['link'],
                $siteInput['nx'],
                $siteInput['vp'],
                $waitSeconds,
                $siteId
            );
        }
        $save->execute();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $message = '<div class="alert alert-success">Voting site '
            . ($action === 'add' ? 'added' : 'updated') . ' successfully.</div>';
        $action = 'list';
    }
}

if ($action === 'delete' && isset($_POST['confirm_delete'])) {
    if (!$csrfIsValid()) {
        $message = '<div class="alert alert-danger">Your form expired. Please try again.</div>';
    } elseif (!$siteId) {
        $message = '<div class="alert alert-danger">Invalid voting-site ID.</div>';
    } else {
        $mysqli->begin_transaction();
        try {
            $deleteRecords = $mysqli->prepare("DELETE FROM {$prefix}votingrecords WHERE siteid = ?");
            $deleteRecords->bind_param('i', $siteId);
            $deleteRecords->execute();
            $deleteSite = $mysqli->prepare("DELETE FROM {$prefix}vote WHERE id = ?");
            $deleteSite->bind_param('i', $siteId);
            $deleteSite->execute();
            $mysqli->commit();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $message = '<div class="alert alert-success">Voting site deleted.</div>';
            $action = 'list';
        } catch (Throwable $error) {
            $mysqli->rollback();
            $message = '<div class="alert alert-danger">The voting site could not be deleted.</div>';
        }
    }
}

echo $message;

if ($action === 'add' || ($action === 'edit' && $siteId)) {
    $site = [
        'name' => '',
        'link' => '',
        'gnx' => 1000,
        'gvp' => 1,
        'waittime' => 21600,
    ];
    if ($action === 'edit') {
        $siteStatement = $mysqli->prepare(
            "SELECT name, link, gnx, gvp, waittime FROM {$prefix}vote WHERE id = ?"
        );
        $siteStatement->bind_param('i', $siteId);
        $siteStatement->execute();
        $site = $siteStatement->get_result()->fetch_assoc();
        if (!$site) {
            echo '<div class="alert alert-danger">Voting site not found.</div>';
            return;
        }
    }
    if (isset($siteInput)) {
        $site = [
            'name' => $siteInput['name'],
            'link' => $siteInput['link'],
            'gnx' => $siteInput['nx'],
            'gvp' => $siteInput['vp'],
            'waittime' => (int) $siteInput['hours'] * 3600,
        ];
    }
?>
    <h2 class="text-left"><?php echo $action === 'add' ? 'Add' : 'Edit'; ?> Voting Site</h2><hr>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['csrf_token']); ?>">
        <div class="form-group">
            <label for="linkName">Site name</label>
            <input name="sitename" type="text" maxlength="45" class="form-control" id="linkName"
                   value="<?php echo $escape($site['name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="voteLink">Voting URL</label>
            <input name="votelink" type="url" class="form-control" id="voteLink"
                   placeholder="https://example.com/vote/your-listing"
                   value="<?php echo $escape($site['link']); ?>" required>
        </div>
        <div class="row">
            <div class="form-group col-md-4">
                <label for="nxGiven">NX reward</label>
                <input name="nx" type="number" min="0" max="100000000" class="form-control" id="nxGiven"
                       value="<?php echo (int) $site['gnx']; ?>" required>
            </div>
            <div class="form-group col-md-4">
                <label for="vpGiven">Vote points</label>
                <input name="vp" type="number" min="0" max="1000000" class="form-control" id="vpGiven"
                       value="<?php echo (int) $site['gvp']; ?>" required>
            </div>
            <div class="form-group col-md-4">
                <label for="waitTime">Cooldown (hours)</label>
                <input name="wait" type="number" min="1" max="720" class="form-control" id="waitTime"
                       value="<?php echo max(1, (int) round((int) $site['waittime'] / 3600)); ?>" required>
            </div>
        </div>
        <button type="submit" name="submit" class="btn btn-primary">Save voting site</button>
        <a href="?base=admin&amp;page=voteconfig" class="btn btn-outline-secondary">Cancel</a>
    </form>
<?php
    return;
}

if ($action === 'delete' && $siteId) {
    $siteStatement = $mysqli->prepare("SELECT name FROM {$prefix}vote WHERE id = ?");
    $siteStatement->bind_param('i', $siteId);
    $siteStatement->execute();
    $site = $siteStatement->get_result()->fetch_assoc();
?>
    <h2 class="text-left">Delete Voting Site</h2><hr>
    <?php if (!$site): ?>
        <div class="alert alert-danger">Voting site not found.</div>
    <?php else: ?>
        <div class="alert alert-warning">
            Delete <strong><?php echo $escape($site['name']); ?></strong> and its cooldown records?
        </div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['csrf_token']); ?>">
            <button type="submit" name="confirm_delete" class="btn btn-danger">Delete voting site</button>
            <a href="?base=admin&amp;page=voteconfig" class="btn btn-outline-secondary">Cancel</a>
        </form>
    <?php endif; ?>
<?php
    return;
}

$sites = $mysqli->query(
    "SELECT id, name, link, gnx, gvp, waittime FROM {$prefix}vote ORDER BY name"
)->fetch_all(MYSQLI_ASSOC);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div><h2 class="text-left">Vote Configuration</h2><small>Manage listing URLs, rewards, and cooldowns.</small></div>
    <a href="?base=admin&amp;page=voteconfig&amp;action=add" class="btn btn-primary">Add voting site</a>
</div>
<hr>
<?php if (!$sites): ?>
    <div class="alert alert-info">No voting sites are configured yet.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Site</th><th>NX</th><th>VP</th><th>Cooldown</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($sites as $site): ?>
                <tr>
                    <td><a href="<?php echo $escape($site['link']); ?>" rel="noopener noreferrer"><?php echo $escape($site['name']); ?></a></td>
                    <td><?php echo number_format((int) $site['gnx']); ?></td>
                    <td><?php echo number_format((int) $site['gvp']); ?></td>
                    <td><?php echo round((int) $site['waittime'] / 3600, 1); ?>h</td>
                    <td>
                        <a href="?base=admin&amp;page=voteconfig&amp;action=edit&amp;id=<?php echo (int) $site['id']; ?>">Edit</a>
                        ·
                        <a class="text-danger" href="?base=admin&amp;page=voteconfig&amp;action=delete&amp;id=<?php echo (int) $site['id']; ?>">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<hr>
<h3>Reward Columns</h3>
<form method="post" action="?base=admin&amp;page=voteconfig&amp;action=columns" class="mt-3">
    <input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['csrf_token']); ?>">
    <div class="row align-items-end">
        <div class="form-group col-md-5">
            <label for="colNX">NX account column</label>
            <select name="colnx" class="form-control" id="colNX">
                <?php foreach ($validNxColumns as $column): ?>
                    <option value="<?php echo $column; ?>" <?php echo $colnx === $column ? 'selected' : ''; ?>>
                        <?php echo $column; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-5">
            <label for="colVP">Vote-point account column</label>
            <input value="votepoints" class="form-control" id="colVP" disabled>
        </div>
        <div class="form-group col-md-2">
            <button type="submit" name="submit" class="btn btn-primary btn-block">Save</button>
        </div>
    </div>
</form>
