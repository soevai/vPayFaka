<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 支付返回处理
 * @License     MIT
 */
include __DIR__ . '/functions.php';

$config = loadConfig();

$payId = $_GET['payId'] ?? '';
$orderId = $_GET['orderId'] ?? '';
$param = $_GET['param'] ?? '';
$type = $_GET['type'] ?? 0;
$price = $_GET['price'] ?? 0;
$reallyPrice = $_GET['reallyPrice'] ?? 0;
$sign = $_GET['sign'] ?? '';
$timestamp = $_GET['timestamp'] ?? 0;

$REQUEST_EXPIRE_TIME = 300;
$currentTime = time();

if (!$timestamp || abs($currentTime - $timestamp) > $REQUEST_EXPIRE_TIME) {
    $title = '支付完成';
    include __DIR__ . '/templates/header.php';
?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card-box p-5">
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fa fa-times-circle text-danger" style="font-size: 48px;"></i>
                        </div>
                        <h5 class="fw-bold mb-3 text-danger">请求已过期</h5>
                        <p class="text-muted mb-4">请返回重新操作</p>
                        <a href="index.php" class="btn btn-primary">返回首页</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include __DIR__ . '/templates/footer.php';
    exit;
}

if (empty($sign)) {
    header('Location: index.php');
    exit;
}

$key = $config['pay']['secretKey'];
$signData = $payId . $param . $type . $price . $reallyPrice . $key;
if ($timestamp) {
    $signData = $timestamp . $signData;
}
$expectedSign = md5($signData);

if ($sign !== $expectedSign) {
    $title = '支付完成';
    include __DIR__ . '/templates/header.php';
?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card-box p-5">
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fa fa-times-circle text-danger" style="font-size: 48px;"></i>
                        </div>
                        <h5 class="fw-bold mb-3 text-danger">签名验证失败</h5>
                        <p class="text-muted mb-4">请返回重新操作</p>
                        <a href="index.php" class="btn btn-primary">返回首页</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include __DIR__ . '/templates/footer.php';
    exit;
}

$order = null;
$orderParam = '';

if (!empty($payId)) {
    $order = getOrderByPayId($payId);
    $orderParam = 'payId=' . urlencode($payId);
} elseif (!empty($orderId)) {
    $order = getOrderByOrderId($orderId);
    $orderParam = 'orderId=' . urlencode($orderId);
}

if ($order && $order['status'] == 'completed') {
    header('Location: index.php?' . $orderParam);
    exit;
}

$title = '支付完成';
include __DIR__ . '/templates/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card-box p-5">
                <div class="text-center py-5">
                    <div class="mb-4">
                        <div class="loading mx-auto" style="width: 32px; height: 32px;"></div>
                    </div>
                    <h5 class="fw-bold mb-3" style="color: var(--warning-color);">订单处理中</h5>
                    <p class="text-muted mb-4">请稍等，正在处理您的订单</p>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'index.php<?php echo !empty($orderParam) ? '?' . $orderParam : ''; ?>';
                        }, 2000);
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>