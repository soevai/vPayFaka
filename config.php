<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 配置文件
 * @License     MIT
 */
if (!defined('FAKA_VERSION')) {
    define('FAKA_VERSION', '1.0.0');
}

if (!function_exists('getCurrentUrl')) {
    function getCurrentUrl()
    {
        $baseUrl = getenv('VPAY_BASE_URL');
        if ($baseUrl) {
            return rtrim($baseUrl, '/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}

$currentUrl = getCurrentUrl();

$defaultConfigItems = require __DIR__ . '/config/defaults.php';
$defaultConfig = [];
foreach ($defaultConfigItems as $item) {
    $defaultConfig[$item['key']] = $item['value'];
}

$dbConfig = $defaultConfig;

try {
    require_once __DIR__ . '/functions.php';
    $dbConfig = getSiteConfig();
    if (empty($dbConfig)) {
        $dbConfig = $defaultConfig;
    }
} catch (Exception $e) {
}


$siteName = $dbConfig['site_name'] ?? $defaultConfig['site_name'];
$siteTitle = $dbConfig['site_title'] ?? $defaultConfig['site_title'];
$siteDescription = $dbConfig['site_description'] ?? $defaultConfig['site_description'];
$siteKeywords = $dbConfig['site_keywords'] ?? $defaultConfig['site_keywords'];
$siteLogo = $dbConfig['site_logo'] ?? $defaultConfig['site_logo'];
$siteFooter = $dbConfig['site_footer'] ?? $defaultConfig['site_footer'];
$videoUrl = $dbConfig['video_url'] ?? $defaultConfig['video_url'];
$videoTitle = $dbConfig['video_title'] ?? $defaultConfig['video_title'];
$noticeContent = $dbConfig['notice_content'] ?? $defaultConfig['notice_content'];
$payApiUrl = $dbConfig['pay_apiUrl'] ?? $defaultConfig['pay_apiUrl'];
$paySecretKey = $dbConfig['pay_secretKey'] ?? $defaultConfig['pay_secretKey'];
$mailApiUrl = $dbConfig['mail_apiUrl'] ?? $defaultConfig['mail_apiUrl'];
$mailApiSecret = $dbConfig['mail_apiSecret'] ?? $defaultConfig['mail_apiSecret'];
$mailSmtpHost = $dbConfig['mail_smtpHost'] ?? $defaultConfig['mail_smtpHost'];
$mailSmtpPort = $dbConfig['mail_smtpPort'] ?? $defaultConfig['mail_smtpPort'];
$mailSmtpUser = $dbConfig['mail_smtpUser'] ?? $defaultConfig['mail_smtpUser'];
$mailSmtpPass = $dbConfig['mail_smtpPass'] ?? $defaultConfig['mail_smtpPass'];
$mailFromName = $dbConfig['mail_fromName'] ?? $defaultConfig['mail_fromName'];
$adminUsername = $dbConfig['admin_username'] ?? $defaultConfig['admin_username'];
$adminPassword = $dbConfig['admin_password'] ?? $defaultConfig['admin_password'];
$kfName = $dbConfig['kf_name'] ?? $defaultConfig['kf_name'];
$kfQq = $dbConfig['kf_qq'] ?? $defaultConfig['kf_qq'];
$kfWechat = $dbConfig['kf_wechat'] ?? $defaultConfig['kf_wechat'];
$kfPhone = $dbConfig['kf_phone'] ?? $defaultConfig['kf_phone'];
$kfEmail = $dbConfig['kf_email'] ?? $defaultConfig['kf_email'];
$showAdminLogin = isset($dbConfig['show_admin_login']) ? (int)$dbConfig['show_admin_login'] : 1;

$config = [
    'site' => [
        'name' => $siteName,
        'title' => $siteTitle,
        'description' => $siteDescription,
        'keywords' => $siteKeywords,
        'logo' => $siteLogo,
        'footer' => $siteFooter,
        'video' => [
            'url' => $videoUrl,
            'title' => $videoTitle,
        ],
        'notice' => [
            'content' => $noticeContent,
        ],
    ],
    'pay' => [
        'apiUrl' => $payApiUrl,
        'secretKey' => $paySecretKey,
        'notifyUrl' => $currentUrl . '/notify.php',
        'returnUrl' => $currentUrl . '/return.php',
    ],
    'database' => [
        'type' => 'mysql',
        'path' => __DIR__ . '/data/',
    ],
    'admin' => [
        'username' => $adminUsername,
        'password' => $adminPassword,
    ],
    'kf' => [
        'name' => $kfName ?: '在线客服',
        'qq' => $kfQq,
        'wechat' => $kfWechat,
        'phone' => $kfPhone,
        'email' => $kfEmail,
    ],
    'mail' => [
        'apiUrl' => $mailApiUrl ?: $currentUrl . '/send_mail.php',
        'apiSecret' => $mailApiSecret,
        'smtpHost' => $mailSmtpHost,
        'smtpPort' => $mailSmtpPort ?: '465',
        'smtpUser' => $mailSmtpUser,
        'smtpPass' => $mailSmtpPass,
        'fromName' => $mailFromName ?: 'vPay Faka',
    ],
    'ssl' => [
        'verify' => (bool)($dbConfig['ssl_verify'] ?? false),
    ],
    'security' => [
        'show_admin_login' => $showAdminLogin,
        'admin_access_key' => $dbConfig['admin_access_key'] ?? '',
    ],
];

return $config;
