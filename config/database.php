<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-07-09
 * @Description vPay 数据库配置（支持环境变量覆盖）
 * @License     MIT
 *
 * 优先级：环境变量 > 默认值
 * 可在服务器上设置以下环境变量覆盖默认值：
 *   VPAY_DB_HOST, VPAY_DB_PORT, VPAY_DB_NAME, VPAY_DB_USER, VPAY_DB_PASS
 */

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (!empty($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

return [
    'host' => getenv('VPAY_DB_HOST') ?: '127.0.0.1',
    'port' => getenv('VPAY_DB_PORT') ?: 3306,
    'database' => getenv('VPAY_DB_NAME') ?: 'vpay',
    'username' => getenv('VPAY_DB_USER') ?: 'vpay',
    'password' => getenv('VPAY_DB_PASS') ?: 'vpay@2085',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
