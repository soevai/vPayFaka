<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-07-09
 * @Description vPay 定时任务入口（无需 Session，使用静态密钥认证）
 * @License     MIT
 *
 * 用法：
 *   curl http://你的域名/cron.php?secret=你的密钥
 *
 * 密钥优先级：环境变量 VPAY_CRON_SECRET > 数据库 admin_access_key
 */

require_once __DIR__ . '/functions.php';

// 验证密钥
$cronSecret = getenv('VPAY_CRON_SECRET');
if (empty($cronSecret)) {
    try {
        $dbConfig = getSiteConfig();
        $cronSecret = $dbConfig['admin_access_key'] ?? '';
    } catch (Exception $e) {
        $cronSecret = '';
    }
}

if (empty($cronSecret)) {
    http_response_code(403);
    die('Cron secret not configured. Set VPAY_CRON_SECRET or admin_access_key.');
}

$inputSecret = $_GET['secret'] ?? '';
if (!hash_equals($cronSecret, $inputSecret)) {
    http_response_code(403);
    die('Invalid secret.');
}

// 执行超时订单取消
$count = cancelTimeoutOrders();
echo 'OK - Cancelled ' . $count . ' timeout orders';
