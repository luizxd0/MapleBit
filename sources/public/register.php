<?php
if (basename($_SERVER["PHP_SELF"] ?? "") === "register.php") {
    http_response_code(403);
    die("403 - Access Forbidden");
}

if (isset($_SESSION['id'])) {
    echo '<meta http-equiv="refresh" content="0; url=?base=ucp">';
    return;
}

$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
$localDevelopment = getenv('MAPLE_LOCAL_DEV') === '1'
    && in_array($remoteAddress, ['127.0.0.1', '::1'], true);
$captchaConfigured = !empty($recaptcha_public) && !empty($recaptcha_private);
$registrationErrors = [];
$registrationComplete = false;

if (!$localDevelopment && !$captchaConfigured) {
    echo '<div class="alert alert-danger">Registration is disabled until reCAPTCHA is configured.</div>';
    return;
}

if (isset($_POST['submit'])) {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $email = trim((string) ($_POST['email'] ?? ''));
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $registrationErrors[] = 'Your session expired. Reload the page and try again.';
    }
    if (!preg_match('/^[A-Za-z0-9]{4,13}$/', $username)) {
        $registrationErrors[] = 'Username must contain 4-13 letters or numbers.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        $registrationErrors[] = 'Password must contain 8-72 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 45) {
        $registrationErrors[] = 'Enter a valid email address of at most 45 characters.';
    }

    if (!$localDevelopment && $captchaConfigured) {
        require_once "assets/libs/recaptcha/autoload.php";
        $recaptcha = new \ReCaptcha\ReCaptcha($recaptcha_private);
        $captchaResponse = $recaptcha->verify(
            (string) ($_POST['g-recaptcha-response'] ?? ''),
            $remoteAddress
        );
        if (!$captchaResponse->isSuccess()) {
            $registrationErrors[] = 'reCAPTCHA verification failed.';
        }
    }

    if (!$registrationErrors) {
        $duplicate = $mysqli->prepare(
            'SELECT name, email FROM accounts WHERE name = ? OR email = ? LIMIT 1'
        );
        $duplicate->bind_param('ss', $username, $email);
        $duplicate->execute();
        $existing = $duplicate->get_result()->fetch_assoc();
        if ($existing) {
            $registrationErrors[] = strcasecmp($existing['name'], $username) === 0
                ? 'That username is already registered.'
                : 'That email address is already registered.';
        }
    }

    if (!$registrationErrors) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $ipAddress = getRealIpAddr();
        $birthday = '1990-01-01';
        $insert = $mysqli->prepare(
            'INSERT INTO accounts (name, password, ip, email, birthday) VALUES (?, ?, ?, ?, ?)'
        );
        $insert->bind_param('sssss', $username, $hashedPassword, $ipAddress, $email, $birthday);
        $insert->execute();
        $registrationComplete = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

if ($registrationErrors) {
    echo '<div class="alert alert-danger">';
    foreach ($registrationErrors as $error) {
        echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '<br>';
    }
    echo '</div>';
}

if ($registrationComplete) {
    echo '<div class="alert alert-success"><b>Success!</b> Your account is ready. You can now sign in here or in the v83 client.</div>';
    return;
}
?>
<h2 class="text-left">Registration</h2><hr/>
<?php if ($localDevelopment): ?>
    <div class="alert alert-info">Local development mode: reCAPTCHA is bypassed only for loopback requests.</div>
<?php endif; ?>
<form action="?base=main&amp;page=register" method="POST" id="register">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    <div class="form-group">
        <label for="inputUsername">Username</label>
        <input type="text" name="username" maxlength="13" class="form-control" id="inputUsername" autocomplete="username" placeholder="Username" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
    </div>
    <div class="form-group">
        <label for="inputPassword">Password</label>
        <input type="password" name="password" maxlength="72" class="form-control" id="inputPassword" autocomplete="new-password" placeholder="Password" required>
    </div>
    <div class="form-group">
        <label for="inputEmail">Email</label>
        <input type="email" name="email" maxlength="45" class="form-control" id="inputEmail" autocomplete="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
    </div>
    <?php if (!$localDevelopment && $captchaConfigured): ?>
        <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptcha_public, ENT_QUOTES, 'UTF-8'); ?>"></div><br/>
    <?php endif; ?>
    <input type="submit" class="btn btn-primary" name="submit" value="Register &raquo;">
</form>
