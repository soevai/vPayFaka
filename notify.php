<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 支付通知处理
 * @License     MIT
 */
include __DIR__ . '/functions.php';

$config = loadConfig();

$payId = $_GET['payId'] ?? $_POST['payId'] ?? '';
$orderId = $_GET['orderId'] ?? $_POST['orderId'] ?? '';
$param = $_GET['param'] ?? $_POST['param'] ?? '';
$type = $_GET['type'] ?? $_POST['type'] ?? 0;
$price = $_GET['price'] ?? $_POST['price'] ?? 0;
$reallyPrice = $_GET['reallyPrice'] ?? $_POST['reallyPrice'] ?? 0;
$sign = $_GET['sign'] ?? $_POST['sign'] ?? '';
$timestamp = $_GET['timestamp'] ?? $_POST['timestamp'] ?? 0;

$REQUEST_EXPIRE_TIME = 300;

$nonceDir = sys_get_temp_dir() . '/vpay_nonces';
if (!is_dir($nonceDir)) {
    @mkdir($nonceDir, 0700, true);
}

function processOrderPayment($payId, $orderId, $param, $reallyPrice) {
    global $config;
    
    $order = null;
    $orderPayId = '';
    if (!empty($payId)) {
        $order = getOrderByPayId($payId);
        $orderPayId = $payId;
    } elseif (!empty($orderId)) {
        $order = getOrderByOrderId($orderId);
        $orderPayId = $order['payId'] ?? '';
    } elseif (!empty($param)) {
        $order = getOrderByOrderId($param);
        $orderPayId = $order['payId'] ?? '';
    }

    if (!$order) {
        echo 'fail';
        exit;
    }

    if ($order['status'] == 'completed') {
        echo 'success';
        exit;
    }

    $orderPrice = (float)($order['reallyPrice'] ?: $order['price']);
    $callbackPrice = (float)$reallyPrice;

    if ($callbackPrice > 0 && $callbackPrice < $orderPrice - 0.01) {
        echo 'fail';
        exit;
    }

    return ['order' => $order, 'orderPayId' => $orderPayId, 'orderPrice' => $orderPrice];
}

if (empty($sign)) {
    error_log("notify.php: Missing sign parameter from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    echo 'fail';
    exit;
}

$currentTime = time();

if (!$timestamp || abs($currentTime - $timestamp) > $REQUEST_EXPIRE_TIME) {
    error_log("notify.php: Missing or expired timestamp from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    echo 'fail';
    exit;
}

$key = $config['pay']['secretKey'];
$signData = $payId . $param . $type . $price . $reallyPrice . $key;
if ($timestamp) {
    $signData = $timestamp . $signData;
}
$expectedSign = md5($signData);

if ($sign !== $expectedSign) {
    error_log("notify.php: Invalid sign from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ", expected: $expectedSign, got: $sign");
    echo 'fail';
    exit;
}

$nonceKey = $payId . '_' . $timestamp;
$nonceFile = $nonceDir . '/' . md5($nonceKey);
if (file_exists($nonceFile)) {
    error_log("notify.php: Replay attack detected for payId: $payId from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    echo 'fail';
    exit;
}

@file_put_contents($nonceFile, time());

if (mt_rand(0, 99) === 0) {
    $expireTime = time() - ($REQUEST_EXPIRE_TIME * 2);
    foreach (@glob($nonceDir . '/*') as $f) {
        if (filemtime($f) < $expireTime) {
            @unlink($f);
        }
    }
}

$result = processOrderPayment($payId, $orderId, $param, $reallyPrice);
$order = $result['order'];
$orderPayId = $result['orderPayId'];
$orderPrice = $result['orderPrice'];

$pdo = getDB();

$cards = takeCards($order['product_id'], $order['quantity'], false, $order['sku']);
if ($cards === null || empty($cards)) {
    $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
    $stmt->execute([$order['quantity'], $order['product_id']]);

    restoreSkuStock($order['product_id'], $order['sku'], $order['quantity']);

    updateOrder($orderPayId, [
        'status' => 'failed',
        'state' => -1,
        'paid_at' => date('Y-m-d H:i:s'),
        'reallyPrice' => $orderPrice,
    ]);
    echo 'fail';
    exit;
}

updateOrder($orderPayId, [
    'status' => 'completed',
    'state' => 1,
    'paid_at' => date('Y-m-d H:i:s'),
    'reallyPrice' => $orderPrice,
    'cards' => $cards,
]);

$stmt = $pdo->prepare("UPDATE products SET sales = sales + ? WHERE id = ?");
$stmt->execute([$order['quantity'], $order['product_id']]);


sendPaymentMail($order);

echo 'success';
exit;
