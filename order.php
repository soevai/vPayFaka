<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 订单查询页面
 * @License     MIT
 */
$orderId = $_GET['orderId'] ?? '';
$payId = $_GET['payId'] ?? '';

$redirectUrl = 'index.php';
if ($orderId) {
    $redirectUrl .= '?orderId=' . urlencode($orderId);
} elseif ($payId) {
    $redirectUrl .= '?orderId=' . urlencode($payId);
}

header("Location: $redirectUrl");
exit;
