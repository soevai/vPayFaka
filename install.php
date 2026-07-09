<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 安装程序
 * @License     MIT
 */
require_once __DIR__ . '/functions.php';
$dbConfig = require __DIR__ . '/config/database.php';

define('INSTALL_LOCK_FILE', __DIR__ . '/data/installed.lock');

function createPDOConnection($dbConfig)
{
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

$installSuccess = false;
$installError = '';
$steps = [];
$isReinstall = false;

$alreadyInstalled = false;
try {
    $checkPdo = createPDOConnection($dbConfig);
    $stmt = $checkPdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([$dbConfig['database']]);
    if ($stmt->rowCount() > 0) {
        $checkPdo->exec("USE `{$dbConfig['database']}`");
        $stmt = $checkPdo->query("SHOW TABLES LIKE 'site_config'");
        $alreadyInstalled = $stmt->rowCount() > 0;
    }
} catch (PDOException $e) {
}

if ($alreadyInstalled && file_exists(INSTALL_LOCK_FILE)) {
    $forceReinstall = isset($_GET['force']) && $_GET['force'] === 'reinstall';

    if (!$forceReinstall) {
        header('Location: index.php');
        exit;
    }

    $reinstallError = '';
    $reinstallVerified = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reinstall_password'])) {
        $inputPassword = $_POST['reinstall_password'] ?? '';
        try {

            $stmt = $checkPdo->query("SELECT `value` FROM `site_config` WHERE `key` = 'admin_password'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $storedHash = $row['value'] ?? '';

            if (!empty($storedHash) && password_verify($inputPassword, $storedHash)) {
                $reinstallVerified = true;
                $isReinstall = true;

                @unlink(INSTALL_LOCK_FILE);
            } else {
                $reinstallError = '管理员密码错误，验证失败';
            }
        } catch (PDOException $e) {
            $reinstallError = '数据库连接失败，无法验证密码';
        }
    }

    if (!$reinstallVerified) {
        ?>
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>安全验证 - vPay Faka</title>
            <link rel="stylesheet" href="assets/css/tailwind.min.css">
            <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    background: #0f0f14; font-family: "Microsoft YaHei", sans-serif; color: #fff;
                    min-height: 100vh; display: flex; align-items: center; justify-content: center;
                    background-image: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), url("templates/image/bg.jpg");
                    background-size: cover; background-position: center;
                }
                .ui-card {
                    background: rgba(20,20,30,0.95); border: 1px solid #ff660040;
                    border-radius: 8px; box-shadow: 0 0 30px rgba(255,100,0,0.2);
                    padding: 2rem; max-width: 440px; width: 90%;
                }
                .ui-input {
                    background: rgba(255,255,255,0.05); border: 1px solid rgba(255,102,0,0.3);
                    border-radius: 4px; color: #fff; padding: 10px 16px; width: 100%;
                    transition: all 0.3s ease; font-size: 14px;
                }
                .ui-input:focus { outline: none; border-color: #ff6600; box-shadow: 0 0 10px rgba(255,102,0,0.3); }
                .btn { padding: 10px 24px; border-radius: 4px; font-size: 14px; font-weight: 500;
                    cursor: pointer; transition: all 0.3s ease; display: inline-flex;
                    align-items: center; gap: 8px; justify-content: center; }
                .btn-primary { background: linear-gradient(135deg, #f97316, #ea580c); color: #fff; border: none; width: 100%; }
                .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(249,115,22,0.4); }
                .btn-cancel { background: transparent; color: #f97316; border: 1px solid #f97316; width: 100%; }
                .btn-cancel:hover { background: rgba(249,115,22,0.1); }
                .alert-danger { background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.4);
                    border-radius: 6px; padding: 12px 16px; margin-bottom: 16px; color: #fca5a5; font-size: 14px; }
                .alert-warning { background: rgba(251,191,36,0.15); border: 1px solid rgba(251,191,36,0.4);
                    border-radius: 6px; padding: 12px 16px; margin-bottom: 16px; color: #fde68a; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="ui-card">
                <div class="text-center mb-6">
                    <i class="fa-solid fa-shield-halved text-orange-500 text-4xl mb-3 block"></i>
                    <h2 class="text-xl font-bold text-orange-400">安全验证</h2>
                    <p class="text-gray-400 text-sm mt-2">系统检测到已安装，重新安装将<strong class="text-red-400">清除所有数据</strong></p>
                </div>

                <?php if (!empty($reinstallError)): ?>
                    <div class="alert-danger">
                        <i class="fa-solid fa-circle-exclamation mr-2"></i><?php echo htmlspecialchars($reinstallError); ?>
                    </div>
                <?php endif; ?>

                <div class="alert-warning">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i>
                    重新安装将删除所有商品、订单和卡密数据！请输入<strong>当前管理员密码</strong>以确认操作。
                </div>

                <form method="post" class="space-y-4">
                    <div>
                        <label class="block text-gray-400 text-sm mb-2">管理员密码</label>
                        <input type="password" name="reinstall_password" class="ui-input"
                               placeholder="请输入当前管理员密码" required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-unlock"></i> 验证并重新安装
                    </button>
                    <a href="index.php" class="btn btn-cancel" style="display: flex;">
                        <i class="fa-solid fa-arrow-left"></i> 取消，返回首页
                    </a>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

if (!$installSuccess) {
    try {
        if (!$pdo) {
            $pdo = createPDOConnection($dbConfig);
        }

        $steps[] = ['name' => '连接数据库', 'status' => 'success', 'msg' => '数据库连接成功'];

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['database']}` DEFAULT CHARACTER SET {$dbConfig['charset']} COLLATE {$dbConfig['collation']}");
        $steps[] = ['name' => '创建数据库', 'status' => 'success', 'msg' => "数据库 `{$dbConfig['database']}` 创建成功"];

        $pdo = new PDO(
            "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
            $dbConfig['username'],
            $dbConfig['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `products` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT,
                `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
                `original_price` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
                `stock` INT NOT NULL DEFAULT '0',
                `category` VARCHAR(100) NOT NULL,
                `sales` INT NOT NULL DEFAULT '0',
                `icon` VARCHAR(255) DEFAULT '',
                `image` VARCHAR(255) DEFAULT '',
                `skus` TEXT DEFAULT NULL,
                `status` TINYINT(1) NOT NULL DEFAULT '1',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $steps[] = ['name' => '创建商品表', 'status' => 'success', 'msg' => 'products 表创建成功'];

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `orders` (
                `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
                `payId` VARCHAR(50) UNIQUE NOT NULL,
                `orderId` VARCHAR(50) UNIQUE NOT NULL,
                `product_id` INT NOT NULL,
                `product_name` VARCHAR(255) NOT NULL,
                `price` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
                `reallyPrice` DECIMAL(10,2) DEFAULT NULL,
                `payType` TINYINT(1) DEFAULT '1',
                `cards` TEXT,
                `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
                `state` TINYINT(1) DEFAULT '0',
                `payUrl` VARCHAR(500) DEFAULT NULL,
                `timeOut` VARCHAR(10) DEFAULT NULL,
                `account` VARCHAR(50) DEFAULT NULL,
                `quantity` VARCHAR(10) DEFAULT '1',
                `sku` VARCHAR(100) DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                `paid_at` DATETIME DEFAULT NULL,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `ip` VARCHAR(45) DEFAULT NULL,
                INDEX `idx_payId` (`payId`),
                INDEX `idx_orderId` (`orderId`),
                INDEX `idx_status` (`status`),
                INDEX `idx_ip` (`ip`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $steps[] = ['name' => '创建订单表', 'status' => 'success', 'msg' => 'orders 表创建成功'];

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `cards` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `product_id` INT NOT NULL,
                `sku` VARCHAR(100) DEFAULT NULL,
                `card_code` VARCHAR(255) NOT NULL,
                `used` TINYINT(1) NOT NULL DEFAULT '0',
                `order_id` BIGINT DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `used_at` DATETIME DEFAULT NULL,
                INDEX `idx_product_id` (`product_id`),
                INDEX `idx_used` (`used`),
                INDEX `idx_sku` (`sku`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $steps[] = ['name' => '创建卡密表', 'status' => 'success', 'msg' => 'cards 表创建成功'];

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `site_config` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `key` VARCHAR(100) UNIQUE NOT NULL,
                `value` TEXT,
                `description` VARCHAR(255) DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_key` (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $steps[] = ['name' => '创建配置表', 'status' => 'success', 'msg' => 'site_config 表创建成功'];

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admin_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `admin_id` INT NOT NULL,
                `action` VARCHAR(100) NOT NULL,
                `details` TEXT,
                `created_at` DATETIME NOT NULL,
                `ip` VARCHAR(45) NOT NULL,
                INDEX `idx_admin_id` (`admin_id`),
                INDEX `idx_action` (`action`),
                INDEX `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $steps[] = ['name' => '创建日志表', 'status' => 'success', 'msg' => 'admin_logs 表创建成功'];

        $configItems = require __DIR__ . '/config/defaults.php';

        foreach ($configItems as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO `site_config` (`key`, `value`, `description`)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$item['key'], $item['value'], $item['description']]);
        }
        $steps[] = ['name' => '初始化配置', 'status' => 'success', 'msg' => '系统配置初始化成功'];

        $stmt = $pdo->prepare("
            INSERT INTO `products` (`name`, `description`, `price`, `original_price`, `stock`, `category`, `sales`, `icon`, `image`, `status`, `created_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP
        ");
        $stmt->execute(['vPay Demo', '这是一个测试用的虚拟商品，用于演示自动发卡功能', 0.01, 10000, 0, '虚拟商品', 0, 'templates/image/bg.jpg', '', 1, date('Y-m-d H:i:s')]);
        $steps[] = ['name' => '创建演示商品', 'status' => 'success', 'msg' => '演示商品创建成功'];

        $installSuccess = true;

        $lockDir = dirname(INSTALL_LOCK_FILE);
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0700, true);
        }
        @file_put_contents(INSTALL_LOCK_FILE, date('Y-m-d H:i:s') . ' | installed');
    } catch (PDOException $e) {
        $installError = $e->getMessage();
        $steps[] = ['name' => '安装失败', 'status' => 'error', 'msg' => $installError];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - vPay Faka</title>
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0f0f14;
            font-family: "Microsoft YaHei", sans-serif;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)),
                url("templates/image/bg.jpg");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .ui-card {
            background: rgba(20, 20, 30, 0.95);
            border: 1px solid #ff660040;
            border-radius: 8px;
            box-shadow: 0 0 30px rgba(255, 100, 0, 0.2);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
        }

        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo i {
            font-size: 3rem;
            color: #f97316;
            margin-bottom: 0.5rem;
        }

        .logo h1 {
            color: #f97316;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .steps-container {
            max-height: 200px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #f97316 rgba(255, 102, 0, 0.2);
        }

        .steps-container::-webkit-scrollbar {
            width: 6px;
        }

        .steps-container::-webkit-scrollbar-track {
            background: rgba(255, 102, 0, 0.1);
            border-radius: 3px;
        }

        .steps-container::-webkit-scrollbar-thumb {
            background: #f97316;
            border-radius: 3px;
        }

        .steps-container::-webkit-scrollbar-thumb:hover {
            background: #ea580c;
        }

        .step-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #ff660020;
            transition: all 0.3s ease;
        }

        .step-item:last-child {
            border-bottom: none;
        }

        .step-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .step-icon.success {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .step-icon.error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .step-icon.pending {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
        }

        .step-content {
            flex: 1;
            min-width: 0;
        }

        .step-title {
            font-size: 0.9rem;
            color: #e5e7eb;
            font-weight: 500;
        }

        .step-msg {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .result-section {
            margin-top: 1.5rem;
            text-align: center;
            padding: 1.5rem;
            border-radius: 8px;
        }

        .result-section.success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .result-section.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .result-icon {
            font-size: 3rem;
            margin-bottom: 0.75rem;
        }

        .result-icon.success {
            color: #22c55e;
        }

        .result-icon.error {
            color: #ef4444;
        }

        .result-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .result-title.success {
            color: #22c55e;
        }

        .result-title.error {
            color: #ef4444;
        }

        .result-desc {
            font-size: 0.875rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            margin: 0 0.5rem;
        }

        .btn i {
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #fff;
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: #f97316;
            border: 1px solid #f97316;
        }

        .btn-outline:hover {
            background: rgba(249, 115, 22, 0.1);
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="ui-card">
        <div class="logo">
            <i class="fa-solid fa-store"></i>
            <h1>vPay Faka</h1>
            <p class="text-gray-500 text-sm mt-1">自动发卡系统安装</p>
        </div>

        <div class="steps-container">
            <?php foreach ($steps as $step): ?>
                <div class="step-item">
                    <div class="step-icon <?php echo $step['status']; ?>">
                        <?php if ($step['status'] === 'success'): ?>
                            <i class="fa-solid fa-check"></i>
                        <?php elseif ($step['status'] === 'error'): ?>
                            <i class="fa-solid fa-x"></i>
                        <?php else: ?>
                            <i class="fa-solid fa-spinner spinner"></i>
                        <?php endif; ?>
                    </div>
                    <div class="step-content">
                        <div class="step-title"><?php echo htmlspecialchars($step['name']); ?></div>
                        <div class="step-msg"><?php echo htmlspecialchars($step['msg']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($installSuccess): ?>
            <div class="result-section success">
                <div class="result-icon success"><i class="fa-solid fa-check-circle"></i></div>
                <div class="result-title success">安装成功</div>
                <div class="result-desc">系统已成功安装，您可以开始使用了</div>
                <div class="flex justify-center gap-2">
                    <a href="index.php" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> 返回首页</a>
                    <a href="admin/login.php" class="btn btn-outline"><i class="fa-solid fa-sign-in"></i> 后台登录</a>
                </div>
            </div>
        <?php elseif ($installError): ?>
            <div class="result-section error">
                <div class="result-icon error"><i class="fa-solid fa-exclamation-circle"></i></div>
                <div class="result-title error">安装失败</div>
                <div class="result-desc"><?php echo htmlspecialchars($installError); ?></div>
                <button onclick="location.reload()" class="btn btn-primary"><i class="fa-solid fa-refresh"></i> 重试安装</button>
            </div>
        <?php else: ?>
            <div class="result-section">
                <div class="result-icon pending"><i class="fa-solid fa-spinner spinner"></i></div>
                <div class="result-title" style="color: #f97316;">正在检测...</div>
                <div class="result-desc">系统正在检测安装状态，请稍候...</div>
            </div>
            <script>
                setTimeout(function() {
                    location.reload();
                }, 1000);
            </script>
        <?php endif; ?>
    </div>
</body>

</html>