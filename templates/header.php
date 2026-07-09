<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 页头模板
 * @License     MIT
 */
$currentDir = __DIR__;
$configPath = dirname($currentDir) . '/config.php';

if (!isset($config)) {
    require_once $configPath;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? htmlspecialchars($title) . ' - ' : ''; ?><?php echo isset($config['site']['name']) ? htmlspecialchars($config['site']['name']) : '自动发卡平台'; ?></title>
    <link rel="icon" href="<?php $isAdminPage = strpos($_SERVER['PHP_SELF'], '/admin/') !== false; echo !empty($config['site']['logo']) ? htmlspecialchars($config['site']['logo']) : ($isAdminPage ? '../templates/image/favicon.ico' : 'templates/image/favicon.ico'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <link rel="stylesheet" href="../assets/fontawesome/css/all.min.css">
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
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                url("../templates/image/bg.jpg");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .navbar {
            background: rgba(15, 15, 20, 0.9);
            border-bottom: 1px solid #ff450050;
        }

        .ui-card {
            background: rgba(20, 20, 30, 0.9);
            border: 1px solid #ff660040;
            border-radius: 4px;
            box-shadow: 0 0 15px rgba(255, 100, 0, 0.15);
        }

        .btn-buy {
            background: linear-gradient(180deg, #ff4500, #cc3700);
            border: 1px solid #ff6600;
            color: #fff;
            font-weight: bold;
            transition: 0.3s;
            border-radius: 4px;
        }

        .btn-buy:hover {
            background: linear-gradient(180deg, #ff6622, #dd4811);
            box-shadow: 0 0 12px rgba(255, 100, 0, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #ff6600;
            color: #ff6600;
            transition: 0.3s;
            border-radius: 4px;
        }

        .btn-outline:hover {
            background: rgba(255, 100, 0, 0.1);
        }

        .ui-input {
            background: #1a1a24;
            border: 1px solid #ff660040;
            color: #fff;
            transition: 0.3s;
            border-radius: 4px;
        }

        .ui-input:focus {
            border-color: #ff6600;
            outline: none;
            box-shadow: 0 0 8px rgba(255, 100, 0, 0.3);
        }

        .alert-danger {
            background: rgba(255, 77, 79, 0.2);
            border: 1px solid rgba(255, 77, 79, 0.4);
            color: #ff4d4f;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .modal {
            background: rgba(20, 20, 30, 0.95);
            border: 1px solid #ff660040;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(255, 100, 0, 0.2);
        }
    </style>
</head>

<body>
    <div class="navbar w-full fixed top-0 z-50">
        <div class="container mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <?php
                $isAdmin = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
                $homeLink = $isAdmin ? '../index.php' : 'index.php';
                ?>
                <a href="<?php echo $homeLink; ?>" class="text-xl font-bold text-orange-500 flex items-center gap-2 hover:text-orange-400 transition-colors">
                    <i class="fa fa-store"></i>
                    <?php echo htmlspecialchars($config['site']['name'] ?? '自动发卡平台'); ?>
                </a>
            </div>
            <?php
            $isAdminLogin = strpos($_SERVER['PHP_SELF'], '/admin/login.php') !== false;
            if (!$isAdminLogin):
            ?>
                <div class="flex items-center gap-6 text-gray-300 text-sm">
                    <?php
                    $orderLink = $isAdmin ? '../order.php' : 'order.php';
                    $adminLink = $isAdmin ? 'login.php' : 'admin/login.php';
                    $adminAccessKey = $config['security']['admin_access_key'] ?? '';
                    if (!empty($adminAccessKey)) {
                        $adminLink .= '?key=' . urlencode($adminAccessKey);
                    }
                    ?>
                    <a href="<?php echo $homeLink; ?>" class="hover:text-orange-400 transition-colors">首页</a>
                    <a href="<?php echo $orderLink; ?>" class="hover:text-orange-400 transition-colors">订单查询</a>
                <?php
                $showAdminLogin = true;
                if (isset($config['security']['show_admin_login'])) {
                    $showAdminLogin = (bool)$config['security']['show_admin_login'];
                } elseif (function_exists('loadConfig')) {
                    $cfg = loadConfig();
                    $showAdminLogin = !empty($cfg['security']['show_admin_login']);
                }
                if ($showAdminLogin):
                ?>
                    <a href="<?php echo $adminLink; ?>" class="text-orange-400 font-medium">管理后台</a>
                <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>