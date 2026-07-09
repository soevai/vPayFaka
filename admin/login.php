<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 管理员登录
 * @License     MIT
 */
ob_start();
include __DIR__ . '/../functions.php';

if (!checkInstallation()) {
    header('Location: ../install.php');
    exit;
}

foreach ($_COOKIE as $name => $value) {
    if ($name != 'PHPSESSID') {
        setcookie($name, '', time() - 3600, '/');
        unset($_COOKIE[$name]);
    }
}

session_start();

$config = loadConfig();

$accessKey = $config['security']['admin_access_key'] ?? '';
if (!empty($accessKey)) {
    $urlKey = $_GET['key'] ?? '';
    $cookieKey = $_COOKIE['admin_access_key'] ?? '';
    if ($urlKey === $accessKey) {
        setcookie('admin_access_key', $accessKey, time() + 86400 * 30, '/');
        $_COOKIE['admin_access_key'] = $accessKey;
    } elseif ($cookieKey !== $accessKey) {
        header('Location: ../index.php');
        exit;
    }
}

$MAX_LOGIN_ATTEMPTS = 5;
$LOCKOUT_DURATION = 3 * 60;

$error = '';

if (empty($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['login_csrf_token'];

$ip = $_SERVER['REMOTE_ADDR'] ?? '';

$loginAttempts = 0;
$lockoutUntil = 0;

if (isset($_SESSION['login_attempts'][$ip])) {
    $loginAttempts = $_SESSION['login_attempts'][$ip];
} elseif (file_exists(__DIR__ . '/../data/login_attempts.txt')) {
    $attemptsData = json_decode(file_get_contents(__DIR__ . '/../data/login_attempts.txt'), true);
    if (isset($attemptsData[$ip])) {
        $loginAttempts = $attemptsData[$ip]['attempts'] ?? 0;
        $lockoutUntil = $attemptsData[$ip]['lockout_until'] ?? 0;
    }
}

if (isset($_SESSION['lockout_until'][$ip])) {
    $lockoutUntil = $_SESSION['lockout_until'][$ip];
}


if (time() >= $lockoutUntil) {
    $lockoutUntil = 0;
}

$remainingTime = 0;
if (time() < $lockoutUntil) {
    $remainingTime = ceil(($lockoutUntil - time()) / 60);
    $error = "账户已被锁定，请 {$remainingTime} 分钟后再试";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken = $_POST['csrf_token'] ?? '';
    if (empty($submittedToken) || !hash_equals($_SESSION['login_csrf_token'], $submittedToken)) {
        $error = '表单已过期，请刷新页面后重试';
    } else {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $storedUsername = $config['admin']['username'] ?? '';
    $storedPasswordHash = $config['admin']['password'] ?? '';

    if (empty($storedUsername) || empty($storedPasswordHash)) {
        $error = "管理员账号密码未配置";
    } elseif ($username !== $storedUsername) {
        $loginAttempts++;
        $_SESSION['login_attempts'][$ip] = $loginAttempts;
        if ($loginAttempts >= $MAX_LOGIN_ATTEMPTS) {
            $_SESSION['lockout_until'][$ip] = time() + $LOCKOUT_DURATION;
            $error = "登录失败次数过多，账户已被锁定3分钟";
        } else {
            $remainingAttempts = $MAX_LOGIN_ATTEMPTS - $loginAttempts;
            $error = "用户名或密码错误，还剩 {$remainingAttempts} 次尝试机会";
        }
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details, created_at, ip) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([0, 'login_failed', "管理员登录失败: 用户名或密码错误 (第{$loginAttempts}次尝试)", date('Y-m-d H:i:s'), $ip]);
    } elseif (!password_verify($password, $storedPasswordHash)) {
        $loginAttempts++;
        $_SESSION['login_attempts'][$ip] = $loginAttempts;
        if ($loginAttempts >= $MAX_LOGIN_ATTEMPTS) {
            $_SESSION['lockout_until'][$ip] = time() + $LOCKOUT_DURATION;
            $error = "登录失败次数过多，账户已被锁定3分钟";
        } else {
            $remainingAttempts = $MAX_LOGIN_ATTEMPTS - $loginAttempts;
            $error = "用户名或密码错误，还剩 {$remainingAttempts} 次尝试机会";
        }
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details, created_at, ip) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([0, 'login_failed', "管理员登录失败: 用户名或密码错误 (第{$loginAttempts}次尝试)", date('Y-m-d H:i:s'), $ip]);
    } else {

        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['admin_id'] = 1;
        $_SESSION['login_attempts'][$ip] = 0;
        $_SESSION['lockout_until'][$ip] = 0;

        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, details, created_at, ip) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([1, 'login', '管理员登录成功', date('Y-m-d H:i:s'), $ip]);

        session_write_close();
        header('Location: index.php');
        exit;
    }
    }
}

include __DIR__ . '/../templates/header.php';
?>

<div class="pt-20">
    <div class="container mx-auto px-4">
        <div class="max-w-md mx-auto">
            <div class="ui-card p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-orange-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa fa-user-circle text-orange-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-orange-400">管理员登录</h3>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert-danger">
                        <i class="fa fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error ?? ''); ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <div class="mb-4">
                        <label class="block text-gray-400 text-sm mb-2">用户名</label>
                        <input type="text" name="username" class="ui-input w-full px-4 py-3" required placeholder="请输入用户名">
                    </div>
                    <div class="mb-6">
                        <label class="block text-gray-400 text-sm mb-2">密码</label>
                        <input type="password" name="password" class="ui-input w-full px-4 py-3" required placeholder="请输入密码">
                    </div>
                    <button type="submit" class="btn-buy w-full py-3 font-medium">登录</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>