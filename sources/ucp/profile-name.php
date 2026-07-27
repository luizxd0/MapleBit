<?php
if (basename($_SERVER['PHP_SELF']) === 'profile-name.php') {
    http_response_code(403);
    exit('403 - Access Forbidden');
}

if (($_SESSION['pname'] ?? 'checkpname') !== 'checkpname') {
    echo '<div class="alert alert-danger">Oops! Looks like you already have a profile name!</div>';
    return;
}

$message = '';
$submittedName = trim((string) ($_POST['name'] ?? ''));

if (isset($_POST['create'])) {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $message = '<div class="alert alert-danger">Your form expired. Please try again.</div>';
    } elseif (!preg_match('/^[A-Za-z0-9]{4,16}$/', $submittedName)) {
        $message = '<div class="alert alert-danger">Profile names must be 4–16 letters or numbers.</div>';
    } elseif (strcasecmp($submittedName, 'checkpname') === 0) {
        $message = '<div class="alert alert-danger">Please choose a different profile name.</div>';
    } else {
        $check = $mysqli->prepare(
            "SELECT id FROM {$prefix}profile WHERE name = ? OR accountid = ? LIMIT 1"
        );
        $accountId = (int) $_SESSION['id'];
        $check->bind_param('si', $submittedName, $accountId);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $message = '<div class="alert alert-danger">That profile name is already in use.</div>';
        } else {
            $insert = $mysqli->prepare(
                "INSERT INTO {$prefix}profile (accountid, name) VALUES (?, ?)"
            );
            $insert->bind_param('is', $accountId, $submittedName);
            if ($insert->execute()) {
                $_SESSION['pname'] = $submittedName;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                echo '<div class="alert alert-success">The profile name has been created! '
                    . 'You can now go to the community page and edit your public profile.</div>';
                return;
            }
            $message = '<div class="alert alert-danger">The profile could not be created. Please try again.</div>';
        }
    }
}

echo $message;
?>

<h2 class="text-left">Set Profile Name</h2><hr/>
Once you've created a profile, other people can view your biography, character, and so on. Note that none of your private information will be shown.<br/>
Please pick a name <i>other</i> than your LoginID!<br/><br/>

<b>Steps:</b><br/>
<b>1.</b> Insert your desired profile name and click submit.<br/>
<b>2.</b> If the name is taken, you will be notified. If not, your profile will be created.<br/>
<b>3.</b> Afterwards you can go to the community menu and edit your profile information.<br/><br/>

<form method="post">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    <input type="text" name="name" placeholder="Profile Name" maxlength="16" class="form-control"
           value="<?php echo htmlspecialchars($submittedName, ENT_QUOTES, 'UTF-8'); ?>"><br/>
    <input type="submit" name="create" class="btn btn-primary" value="Submit &raquo;">
</form>
<br/>
