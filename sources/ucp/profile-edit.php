<?php
if (basename($_SERVER['PHP_SELF']) === 'profile-edit.php') {
    http_response_code(403);
    exit('403 - Access Forbidden');
}

if (($_SESSION['pname'] ?? 'checkpname') === 'checkpname') {
    echo '<div class="alert alert-danger">You must assign a profile name before you can edit your public profile.</div>';
    return;
}

$accountId = (int) $_SESSION['id'];
$profileStatement = $mysqli->prepare("SELECT * FROM {$prefix}profile WHERE accountid = ?");
$profileStatement->bind_param('i', $accountId);
$profileStatement->execute();
$profile = $profileStatement->get_result()->fetch_assoc();

if (!$profile) {
    echo '<div class="alert alert-danger">Your profile could not be found.</div>';
    return;
}

$characterStatement = $mysqli->prepare(
    'SELECT id, name FROM characters WHERE accountid = ? ORDER BY name'
);
$characterStatement->bind_param('i', $accountId);
$characterStatement->execute();
$characters = $characterStatement->get_result()->fetch_all(MYSQLI_ASSOC);

$message = '';
if (isset($_POST['edit'])) {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    $mainCharacter = filter_var($_POST['mainchar'] ?? null, FILTER_VALIDATE_INT);
    $realName = trim(strip_tags((string) ($_POST['realname'] ?? '')));
    $age = filter_var($_POST['age'] ?? null, FILTER_VALIDATE_INT);
    $country = trim(strip_tags((string) ($_POST['country'] ?? '')));
    $motto = trim(strip_tags((string) ($_POST['motto'] ?? '')));
    $favoriteJob = trim(strip_tags((string) ($_POST['favjob'] ?? '')));
    $about = trim((string) ($_POST['text'] ?? ''));

    $ownedCharacterIds = array_map(static fn (array $character): int => (int) $character['id'], $characters);
    $countries = getCountries();
    $jobs = getJobNames(true);
    $errors = [];

    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = 'Your form expired. Please try again.';
    }
    if ($characters && (!$mainCharacter || !in_array((int) $mainCharacter, $ownedCharacterIds, true))) {
        $errors[] = 'Select one of your own characters as the main character.';
    }
    if ($realName !== '' && (!preg_match("/^[\\p{L} .'-]{1,50}$/u", $realName))) {
        $errors[] = 'Real name contains unsupported characters.';
    }
    if ($age === false || $age < 7 || $age > 99) {
        $errors[] = 'Age must be between 7 and 99.';
    }
    if (!in_array($country, $countries, true)) {
        $errors[] = 'Select a valid country.';
    }
    if (mb_strlen($motto) > 100) {
        $errors[] = 'Motto must be 100 characters or fewer.';
    }
    if (!in_array($favoriteJob, $jobs, true)) {
        $errors[] = 'Select a valid favorite job.';
    }
    if (mb_strlen(strip_tags($about)) > 200) {
        $errors[] = 'About Me must be 200 characters or fewer.';
    }

    if ($errors) {
        $message = '<div class="alert alert-danger">' . implode('<br/>', array_map(
            static fn (string $error): string => htmlspecialchars($error, ENT_QUOTES, 'UTF-8'),
            $errors
        )) . '</div>';
        $profile = array_merge($profile, [
            'mainchar' => $mainCharacter ?: null,
            'realname' => $realName,
            'age' => $age ?: null,
            'country' => $country,
            'motto' => $motto,
            'favjob' => $favoriteJob,
            'text' => $about,
        ]);
    } else {
        $mainCharacterId = $characters ? (int) $mainCharacter : null;
        $update = $mysqli->prepare(
            "UPDATE {$prefix}profile SET mainchar = ?, realname = ?, age = ?, "
            . 'country = ?, motto = ?, favjob = ?, text = ? WHERE accountid = ?'
        );
        $update->bind_param(
            'isissssi',
            $mainCharacterId,
            $realName,
            $age,
            $country,
            $motto,
            $favoriteJob,
            $about,
            $accountId
        );
        $update->execute();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $message = '<div class="alert alert-success">Your public profile has been updated.<br/>'
            . '<a class="alert-link" href="?base=main&amp;page=members&amp;name='
            . rawurlencode($_SESSION['pname']) . '">View your profile &raquo;</a></div>';
        $profile = array_merge($profile, [
            'mainchar' => $mainCharacterId,
            'realname' => $realName,
            'age' => $age,
            'country' => $country,
            'motto' => $motto,
            'favjob' => $favoriteJob,
            'text' => $about,
        ]);
    }
}

$escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
echo $message;
?>
<script src="assets/libs/cksimple/ckeditor.js"></script>
<h2 class="text-left">My Profile</h2><hr/>
<form method="post" role="form">
    <input type="hidden" name="csrf_token" value="<?php echo $escape($_SESSION['csrf_token']); ?>">
    <b>Profile Name:</b> <?php echo $escape($profile['name']); ?>
    <div class="form-group">
        <?php if ($characters): ?>
            <label for="mainChar">Main Character:</label>
            <select name="mainchar" class="form-control" id="mainChar">
                <?php foreach ($characters as $character): ?>
                    <option value="<?php echo (int) $character['id']; ?>"
                        <?php echo (int) $profile['mainchar'] === (int) $character['id'] ? 'selected' : ''; ?>>
                        <?php echo $escape($character['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <hr/><div class="alert alert-danger">You don't have any characters!</div><hr/>
        <?php endif; ?>
    </div>
    <div class="form-group">
        <label for="realName">Real Name:</label>
        <input type="text" class="form-control" name="realname" id="realName" maxlength="50"
               value="<?php echo $escape($profile['realname']); ?>">
    </div>
    <div class="form-group">
        <label for="myAge">Age:</label>
        <select name="age" class="form-control" id="myAge">
            <?php for ($candidateAge = 7; $candidateAge <= 99; $candidateAge++): ?>
                <option value="<?php echo $candidateAge; ?>"
                    <?php echo (int) $profile['age'] === $candidateAge ? 'selected' : ''; ?>>
                    <?php echo $candidateAge; ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="inputCountry">Country:</label>
        <select id="inputCountry" name="country" class="form-control">
            <?php foreach (getCountries() as $candidateCountry): ?>
                <option value="<?php echo $escape($candidateCountry); ?>"
                    <?php echo $candidateCountry === ($profile['country'] ?? '') ? 'selected' : ''; ?>>
                    <?php echo $escape($candidateCountry); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="inputMotto">Motto:</label>
        <input type="text" class="form-control" name="motto" id="inputMotto" maxlength="100"
               value="<?php echo $escape($profile['motto']); ?>">
    </div>
    <div class="form-group">
        <label for="favJob">Favorite Job:</label>
        <select name="favjob" class="form-control" id="favJob">
            <?php foreach (getJobNames(true) as $job): ?>
                <option value="<?php echo $escape($job); ?>"
                    <?php echo $job === ($profile['favjob'] ?? '') ? 'selected' : ''; ?>>
                    <?php echo $escape($job); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="textCount">About Me:</label>
        <textarea name="text" style="height:200px" maxlength="200" class="form-control"
                  id="textCount"><?php echo $escape($profile['text']); ?></textarea>
    </div>
    <p id="counter">Characters left: 200</p>
    <div class="alert alert-info">Please keep in mind that all of this information will be public.</div>
    <input type="submit" name="edit" value="Update &raquo;" class="btn btn-primary">
</form>
<script>
    if (document.getElementById('textCount')) {
        CKEDITOR.replace('textCount');
        CKEDITOR.instances.textCount.on('key', function () {
            var left = 200 - CKEDITOR.instances.textCount.getData().replace(/<[^>]*>/g, '').length;
            document.getElementById('counter').textContent = 'Characters left: ' + left;
        });
    }
</script>
