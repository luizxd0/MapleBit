<?php
if (basename($_SERVER['PHP_SELF']) === 'buynx.php') {
    http_response_code(403);
    exit('403 - Access Forbidden');
}

$accountId = (int) $_SESSION['id'];
$message = '';

if (isset($_POST['buyNX'])) {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    $characterId = filter_var($_POST['selChar'] ?? null, FILTER_VALIDATE_INT);
    $packageId = filter_var($_POST['selPack'] ?? null, FILTER_VALIDATE_INT);

    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $message = '<div class="alert alert-danger">Your form expired. Please try again.</div>';
    } elseif (!$characterId) {
        $message = '<div class="alert alert-danger">Select a character to pay for the NX.</div>';
    } elseif (!$packageId) {
        $message = '<div class="alert alert-danger">Select the NX package you want to buy.</div>';
    } else {
        try {
            $mysqli->begin_transaction();

            $account = $mysqli->prepare('SELECT loggedin FROM accounts WHERE id = ? FOR UPDATE');
            $account->bind_param('i', $accountId);
            $account->execute();
            $accountRow = $account->get_result()->fetch_assoc();
            if (!$accountRow) {
                throw new RuntimeException('Your account could not be found.');
            }
            if ((int) $accountRow['loggedin'] > 0) {
                throw new RuntimeException('Log out of the game before exchanging mesos for NX.');
            }

            $character = $mysqli->prepare(
                'SELECT id, name, meso FROM characters WHERE id = ? AND accountid = ? FOR UPDATE'
            );
            $character->bind_param('ii', $characterId, $accountId);
            $character->execute();
            $characterRow = $character->get_result()->fetch_assoc();
            if (!$characterRow) {
                throw new RuntimeException('Select one of your own characters.');
            }

            $package = $mysqli->prepare(
                "SELECT id, meso, nx FROM {$prefix}buynx WHERE id = ? FOR UPDATE"
            );
            $package->bind_param('i', $packageId);
            $package->execute();
            $packageRow = $package->get_result()->fetch_assoc();
            if (!$packageRow) {
                throw new RuntimeException('That NX package is no longer available.');
            }
            if ((int) $characterRow['meso'] < (int) $packageRow['meso']) {
                throw new RuntimeException('That character does not have enough mesos.');
            }

            $mesoCost = (int) $packageRow['meso'];
            $nxAmount = (int) $packageRow['nx'];
            $deduct = $mysqli->prepare(
                'UPDATE characters SET meso = meso - ? WHERE id = ? AND accountid = ?'
            );
            $deduct->bind_param('iii', $mesoCost, $characterId, $accountId);
            $deduct->execute();

            $validNxColumns = ['nxCredit', 'maplePoint', 'nxPrepaid'];
            if (!in_array($colnx, $validNxColumns, true)) {
                throw new RuntimeException('The website NX column is not configured correctly.');
            }
            $credit = $mysqli->prepare("UPDATE accounts SET {$colnx} = {$colnx} + ? WHERE id = ?");
            $credit->bind_param('ii', $nxAmount, $accountId);
            $credit->execute();

            $mysqli->commit();
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $message = '<div class="alert alert-success">You purchased <b>'
                . number_format($nxAmount) . ' NX</b> for <b>' . number_format($mesoCost)
                . ' mesos</b>. The mesos were taken from <b>'
                . htmlspecialchars($characterRow['name'], ENT_QUOTES, 'UTF-8')
                . '</b>.</div>';
        } catch (Throwable $error) {
            $mysqli->rollback();
            $message = '<div class="alert alert-danger">'
                . htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }
}

$charactersStatement = $mysqli->prepare(
    'SELECT id, name, meso FROM characters WHERE accountid = ? ORDER BY name'
);
$charactersStatement->bind_param('i', $accountId);
$charactersStatement->execute();
$characters = $charactersStatement->get_result()->fetch_all(MYSQLI_ASSOC);
$packages = $mysqli->query(
    "SELECT id, meso, nx FROM {$prefix}buynx ORDER BY meso, nx"
)->fetch_all(MYSQLI_ASSOC);

echo $message;
?>
<form name="buynx" method="post">
    <input type="hidden" name="csrf_token"
           value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    <h2 class="text-left">Buy NX</h2><hr/>
    <h4>Select a Character <small>(must have enough mesos)</small></h4>
    <?php if (!$characters): ?>
        <div class="alert alert-danger">Oops! You don't have any characters!</div>
    <?php else: ?>
        <?php foreach ($characters as $character): ?>
            <?php $characterInputId = 'character-' . (int) $character['id']; ?>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="selChar"
                       value="<?php echo (int) $character['id']; ?>"
                       id="<?php echo $characterInputId; ?>">
                <label class="form-check-label" for="<?php echo $characterInputId; ?>">
                    <?php echo htmlspecialchars($character['name'], ENT_QUOTES, 'UTF-8'); ?>
                    (<?php echo number_format((int) $character['meso']); ?> mesos)
                </label>
            </div>
        <?php endforeach; ?>
        <hr/><h4>Select a Package</h4>
        <?php if (!$packages): ?>
            <div class="alert alert-danger">Oops! Looks like there are no NX packages available right now!</div>
        <?php else: ?>
            <?php foreach ($packages as $package): ?>
                <?php $packageInputId = 'package-' . (int) $package['id']; ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="selPack"
                           value="<?php echo (int) $package['id']; ?>"
                           id="<?php echo $packageInputId; ?>">
                    <label class="form-check-label" for="<?php echo $packageInputId; ?>">
                        <?php echo number_format((int) $package['nx']); ?> NX for
                        <?php echo number_format((int) $package['meso']); ?> mesos
                    </label>
                </div>
            <?php endforeach; ?>
            <br/><input type="submit" name="buyNX" value="Buy NX &raquo;" class="btn btn-primary">
        <?php endif; ?>
    <?php endif; ?>
</form><br/>
