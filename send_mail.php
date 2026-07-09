<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 邮件发送服务
 * @License     MIT
 */

declare(strict_types=1);

$CFG = [
  'ALLOW_HTTP'        => true,
  'CORS_ALLOW_ORIGIN' => '',
  'ALLOWED_IPS'       => '',
  'MAIL_API_SECRET'   => '',
  'SMTP_HOST'         => '',
  'SMTP_PORT'         => 465,
  'SMTP_USER'         => '',
  'SMTP_PASS'         => '',
  'MAIL_FROM'         => '',
  'MAIL_FROM_NAME'    => '',
  'MAIL_TO'           => '',
];

try {
  require __DIR__ . '/functions.php';
  $dbConfig = getSiteConfig();

  if (!empty($dbConfig['mail_apiSecret'])) {
    $CFG['MAIL_API_SECRET'] = $dbConfig['mail_apiSecret'];
  }
  if (!empty($dbConfig['mail_smtpHost'])) {
    $CFG['SMTP_HOST'] = $dbConfig['mail_smtpHost'];
  }
  if (!empty($dbConfig['mail_smtpPort'])) {
    $CFG['SMTP_PORT'] = (int)$dbConfig['mail_smtpPort'];
  }
  if (!empty($dbConfig['mail_smtpUser'])) {
    $CFG['SMTP_USER'] = $dbConfig['mail_smtpUser'];
  }
  if (!empty($dbConfig['mail_smtpPass'])) {
    $CFG['SMTP_PASS'] = $dbConfig['mail_smtpPass'];
  }
  if (!empty($dbConfig['mail_fromName'])) {
    $CFG['MAIL_FROM_NAME'] = $dbConfig['mail_fromName'];
  }
} catch (Exception $e) {
}

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=UTF-8');

set_error_handler(function ($sev, $msg, $file, $line) {
  if (error_reporting() === 0) return false;
  error_log("send_mail.php error: $msg in $file:$line");
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => '提交失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
  exit;
});
set_exception_handler(function ($e) {
  error_log("send_mail.php exception: " . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => '提交失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
  exit;
});

function ensure_dir(string $dir, int $mode = 0700): void
{
  if (is_dir($dir)) return;
  if (file_exists($dir) && !is_dir($dir)) throw new Exception("Path exists and not dir: $dir");
  if (!@mkdir($dir, $mode, true) && !is_dir($dir)) throw new Exception("Failed to create dir: $dir");
}

function header_get(string $name): ?string
{
  $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
  return $_SERVER[$key] ?? null;
}

function hash_equals_safe(string $a, string $b): bool
{
  if (function_exists('hash_equals')) return hash_equals($a, $b);
  if (strlen($a) !== strlen($b)) return false;
  $x = 0;
  for ($i = 0; $i < strlen($a); $i++) $x |= ord($a[$i]) ^ ord($b[$i]);
  return $x === 0;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(200);
  echo json_encode(['ok' => false, 'error' => '提交失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
  exit;
}

if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
  http_response_code(200);
  echo json_encode(['ok' => false, 'error' => '提交失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
  exit;
}

$rawBody = file_get_contents('php://input') ?: '';
$data = json_decode($rawBody, true);
if (!is_array($data)) {
  http_response_code(200);
  echo json_encode(['ok' => false, 'error' => '提交失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
  exit;
}

$msg = trim((string)($data['msg'] ?? ''));
if ($msg === '') {
  http_response_code(200);
  echo json_encode(['ok' => false, 'error' => '提交失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
  exit;
}

$secret = (string)$CFG['MAIL_API_SECRET'];

if (empty($secret)) {
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => '邮件API未配置，请在后台设置API密钥'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sigHex = header_get('X-Signature');
if (!$sigHex) {
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => '提交失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ts    = header_get('X-Timestamp');
$nonce = header_get('X-Nonce');
if (!$ts || !$nonce) {
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => '提交失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!ctype_digit($ts)) {
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => '提交失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
    exit;
}
if (abs(time() - (int)$ts) > 120) {
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => '提交失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
    exit;
}
$macHex = bin2hex(hash_hmac('sha256', $ts . '.' . $nonce . '.' . $rawBody, $secret, true));
if (!hash_equals_safe($macHex, strtolower($sigHex))) {
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => '提交失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
    exit;
}

require __DIR__ . '/PHPMailer/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host       = (string)$CFG['SMTP_HOST'];
  $mail->SMTPAuth   = true;
  $mail->Username   = (string)$CFG['SMTP_USER'];
  $mail->Password   = (string)$CFG['SMTP_PASS'];
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  $mail->Port       = (int)$CFG['SMTP_PORT'];
  $mail->CharSet    = 'UTF-8';

  $from = !empty($CFG['MAIL_FROM']) ? (string)$CFG['MAIL_FROM'] : (string)$CFG['SMTP_USER'];
  $fromName = (string)$CFG['MAIL_FROM_NAME'];

  $to = !empty($data['to']) ? (string)$data['to'] : (string)$CFG['MAIL_TO'];

  if (empty($from) || empty($to)) {
    echo json_encode(['ok' => false, 'error' => '邮件配置未完成'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $mail->setFrom($from, $fromName);

  $toEmails = array_filter(array_map('trim', explode(',', $to)));
  foreach ($toEmails as $email) {
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $mail->addAddress($email);
    }
  }

  $mail->Subject = '订单通知';

  $isHtml = isset($data['isHtml']) && $data['isHtml'] === true;
  if ($isHtml) {
    $mail->isHTML(true);
    $mail->Body    = $msg;
    $mail->AltBody = strip_tags($msg);
  } else {
    $mail->Body    = nl2br(htmlspecialchars($msg));
    $mail->AltBody = $msg;
  }

  $mail->send();
  echo json_encode(['ok' => true, 'message' => '邮件发送成功'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
  echo json_encode(['ok' => false, 'error' => '邮件发送失败，请稍后重试'], JSON_UNESCAPED_UNICODE);
}
