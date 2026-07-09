<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 核心函数库
 * @License     MIT
 */
if (session_status() === PHP_SESSION_NONE) {
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams['lifetime'] ?? 0,
        'path'     => $cookieParams['path'] ?? '/',
        'domain'   => $cookieParams['domain'] ?? '',
        'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; frame-src 'self'; connect-src 'self'; font-src 'self'");
}

if (!function_exists('curlOptions')) {
    function curlOptions($ch, $config = null)
    {
        if ($config === null) {
            $config = loadConfig();
        }
        $sslVerify = isset($config['ssl']['verify']) ? $config['ssl']['verify'] : true;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $sslVerify ? 2 : 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }
}

function e($data, $flags = ENT_QUOTES)
{
    return htmlspecialchars($data ?? '', $flags, 'UTF-8');
}

function js($data)
{
    return json_encode($data);
}



function loadConfig()
{
    return include __DIR__ . '/config.php';
}

function checkInstallation()
{
    try {
        $dbConfig = include __DIR__ . '/config/database.php';

        $pdo = new PDO(
            "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}",
            $dbConfig['username'],
            $dbConfig['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
        $stmt->execute([$dbConfig['database']]);
        if ($stmt->rowCount() == 0) {
            return false;
        }

        $pdo->exec("USE `{$dbConfig['database']}`");

        $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
        if ($stmt->rowCount() == 0) {
            return false;
        }

        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function isAdmin()
{
    if (session_status() == PHP_SESSION_NONE) {
        return false;
    }
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}

function requireAdmin()
{
    if (!isAdmin()) {
        header('Content-Type: application/json');
        echo json_encode(['code' => -403, 'msg' => '无权访问，请先登录']);
        exit;
    }
}

function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function loadJsonFile($filename)
{
    $config = loadConfig();
    $path = $config['database']['path'] . $filename;
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    return json_decode($content, true) ?: [];
}

function saveJsonFile($filename, $data)
{
    $config = loadConfig();

    $filename = basename($filename);
    if (empty($filename) || strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        error_log("Invalid filename attempt: " . ($filename ?? 'empty'));
        return false;
    }

    $path = $config['database']['path'] . $filename;
    return file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function getDB()
{
    static $pdo = null;
    if ($pdo === null) {
        $dbConfig = require __DIR__ . '/config/database.php';
        try {
            $pdo = new PDO(
                "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
                $dbConfig['username'],
                $dbConfig['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            error_log("数据库连接失败: " . $e->getMessage());
            die("服务器内部错误，请稍后重试");
        }
    }
    return $pdo;
}

function getProducts()
{
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM products WHERE status = 1 ORDER BY id ASC");
    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        if (!empty($product['skus'])) {
            $product['skus'] = json_decode($product['skus'], true) ?: [];
        } else {
            $product['skus'] = [];
        }

        if (!empty($product['skus']) && is_array($product['skus'])) {
            foreach ($product['skus'] as &$sku) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND sku = ? AND used = 0");
                $stmt->execute([$product['id'], $sku['name']]);
                $sku['stock'] = (int)$stmt->fetchColumn();
            }
            $product['stock'] = array_sum(array_column($product['skus'], 'stock'));
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND used = 0");
            $stmt->execute([$product['id']]);
            $product['stock'] = (int)$stmt->fetchColumn();
        }
    }

    return $products;
}

function getProductById($id)
{
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        if (!empty($product['skus'])) {
            $product['skus'] = json_decode($product['skus'], true) ?: [];
        } else {
            $product['skus'] = [];
        }

        if (!empty($product['skus']) && is_array($product['skus'])) {
            foreach ($product['skus'] as &$sku) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND sku = ? AND used = 0");
                $stmt->execute([$product['id'], $sku['name']]);
                $sku['stock'] = (int)$stmt->fetchColumn();
            }
            $product['stock'] = array_sum(array_column($product['skus'], 'stock'));
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND used = 0");
            $stmt->execute([$product['id']]);
            $product['stock'] = (int)$stmt->fetchColumn();
        }
    }

    return $product;
}

function getCardsByProductId($productId, $page = 0, $pageSize = 10, $includeUsed = false, $sku = '')
{
    $pdo = getDB();
    $sql = "SELECT id, card_code, sku, used FROM cards WHERE product_id = ?";
    $params = [$productId];

    if (!$includeUsed) {
        $sql .= " AND used = 0";
    }

    if ($sku !== null && $sku !== '') {
        $sql .= " AND sku = ?";
        $params[] = $sku;
    } elseif ($sku === '') {
        $sql .= " AND (sku = '' OR sku IS NULL)";
    }

    if ($page > 0) {
        $offset = ($page - 1) * $pageSize;
        $sql .= " LIMIT " . intval($pageSize) . " OFFSET " . intval($offset);
    } elseif ($pageSize > 0) {
        $sql .= " LIMIT " . intval($pageSize);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getCardsCountByProductId($productId, $includeUsed = false, $sku = '')
{
    $pdo = getDB();
    if ($includeUsed) {
        $sql = "SELECT COUNT(*) FROM cards WHERE product_id = ?";
    } else {
        $sql = "SELECT COUNT(*) FROM cards WHERE product_id = ? AND used = 0";
    }

    $params = [$productId];

    if ($sku !== null && $sku !== '') {
        $sql .= " AND sku = ?";
        $params[] = $sku;
    } elseif ($sku === '') {
        $sql .= " AND (sku = '' OR sku IS NULL)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}
function takeCards($productId, $quantity, $returnWithIds = false, $sku = '')
{
    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        $productId = intval($productId);
        $quantity = intval($quantity);

        if ($sku !== null && $sku !== '') {
            $stmt = $pdo->prepare("SELECT id, card_code FROM cards WHERE product_id = ? AND sku = ? AND used = 0 ORDER BY id ASC LIMIT " . $quantity . " FOR UPDATE");
            $stmt->execute([$productId, $sku]);
        } elseif ($sku === '') {
            $stmt = $pdo->prepare("SELECT id, card_code FROM cards WHERE product_id = ? AND (sku = '' OR sku IS NULL) AND used = 0 ORDER BY id ASC LIMIT " . $quantity . " FOR UPDATE");
            $stmt->execute([$productId]);
        } else {
            $stmt = $pdo->prepare("SELECT id, card_code FROM cards WHERE product_id = ? AND used = 0 ORDER BY id ASC LIMIT " . $quantity . " FOR UPDATE");
            $stmt->execute([$productId]);
        }
        $cards = $stmt->fetchAll();

        if (count($cards) < $quantity) {
            $pdo->rollBack();
            return null;
        }

        $cardIds = array_column($cards, 'id');
        $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
        $stmt = $pdo->prepare("UPDATE cards SET used = 1 WHERE id IN ($placeholders)");
        $stmt->execute($cardIds);

        $pdo->commit();

        if ($returnWithIds) {
            return [
                'ids' => $cardIds,
                'codes' => array_column($cards, 'card_code')
            ];
        }

        return array_column($cards, 'card_code');
    } catch (Exception $e) {
        $pdo->rollBack();
        return null;
    }
}

function takeCardsIgnoreStock($productId, $quantity)
{
    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        $productId = intval($productId);
        $quantity = intval($quantity);

        $stmt = $pdo->prepare("SELECT id, card_code FROM cards WHERE product_id = ? AND used = 0 ORDER BY id ASC LIMIT " . $quantity . " FOR UPDATE");
        $stmt->execute([$productId]);
        $cards = $stmt->fetchAll();

        $actualCount = count($cards);
        if ($actualCount == 0) {
            $pdo->rollBack();
            return null;
        }

        $cardIds = array_column($cards, 'id');
        $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
        $stmt = $pdo->prepare("UPDATE cards SET used = 1 WHERE id IN ($placeholders)");
        $stmt->execute($cardIds);

        $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$actualCount, $productId]);

        $pdo->commit();
        return array_column($cards, 'card_code');
    } catch (Exception $e) {
        $pdo->rollBack();
        return null;
    }
}

function reissueCards($productId, $quantity)
{
    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        $productId = intval($productId);
        $quantity = intval($quantity);

        $stmt = $pdo->prepare("SELECT id, card_code FROM cards WHERE product_id = ? AND used = 0 ORDER BY id ASC LIMIT " . $quantity . " FOR UPDATE");
        $stmt->execute([$productId]);
        $cards = $stmt->fetchAll();

        $actualCount = count($cards);
        if ($actualCount == 0) {
            $pdo->rollBack();
            return null;
        }

        $cardIds = array_column($cards, 'id');
        $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
        $stmt = $pdo->prepare("UPDATE cards SET used = 1 WHERE id IN ($placeholders)");
        $stmt->execute($cardIds);

        $pdo->commit();
        return array_column($cards, 'card_code');
    } catch (Exception $e) {
        $pdo->rollBack();
        return null;
    }
}

function addCards($productId, $newCards, $sku = '')
{
    $pdo = getDB();
    $pdo->beginTransaction();
    try {
        if ($sku !== null && $sku !== '') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND sku = ? AND used = 0");
            $stmt->execute([$productId, $sku]);
        } elseif ($sku === '') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND (sku = '' OR sku IS NULL) AND used = 0");
            $stmt->execute([$productId]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND used = 0");
            $stmt->execute([$productId]);
        }
        $oldCount = (int)$stmt->fetchColumn();

        if ($sku !== null && $sku !== '') {
            $stmt = $pdo->prepare("DELETE FROM cards WHERE product_id = ? AND sku = ? AND used = 0");
            $stmt->execute([$productId, $sku]);
        } elseif ($sku === '') {
            $stmt = $pdo->prepare("DELETE FROM cards WHERE product_id = ? AND (sku = '' OR sku IS NULL) AND used = 0");
            $stmt->execute([$productId]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM cards WHERE product_id = ? AND used = 0");
            $stmt->execute([$productId]);
        }

        $addedCount = 0;
        foreach ($newCards as $card) {
            $card = trim($card);
            if (empty($card)) continue;

            if (!empty($sku)) {
                $stmt = $pdo->prepare("INSERT INTO cards (product_id, sku, card_code, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$productId, $sku, $card]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cards (product_id, card_code, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$productId, $card]);
            }
            $addedCount++;
        }

        $stockChange = $addedCount - $oldCount;
        if ($stockChange != 0) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$stockChange, $productId]);
        }

        $pdo->commit();
        return $addedCount;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function updateProductStock($productId, $change)
{
    $pdo = getDB();
    $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
    return $stmt->execute([$change, $productId]);
}

function createOrder($data)
{
    $pdo = getDB();
    $cards = isset($data['cards']) ? json_encode($data['cards']) : '[]';
    $stmt = $pdo->prepare("
        INSERT INTO orders (payId, orderId, product_id, product_name, price, reallyPrice, payType, cards, status, state, payUrl, timeOut, account, quantity, sku, created_at, paid_at, ip)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['payId'],
        $data['orderId'] ?? '',
        $data['product_id'],
        $data['product_name'],
        $data['price'],
        $data['reallyPrice'] ?? $data['price'],
        $data['payType'] ?? 1,
        $cards,
        $data['status'] ?? 'pending',
        $data['state'] ?? 0,
        $data['payUrl'] ?? '',
        $data['timeOut'] ?? 5,
        $data['account'] ?? '',
        $data['quantity'] ?? 1,
        $data['sku'] ?? '',
        date('Y-m-d H:i:s'),
        $data['paid_at'] ?? null,
        $data['ip'] ?? ''
    ]);

    return [
        'id' => $pdo->lastInsertId(),
        'payId' => $data['payId'],
        'orderId' => $data['orderId'] ?? '',
        'product_id' => $data['product_id'],
        'product_name' => $data['product_name'],
        'price' => $data['price'],
        'payType' => $data['payType'] ?? 1,
        'cards' => [],
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'paid_at' => null,
        'payUrl' => $data['payUrl'] ?? '',
        'reallyPrice' => $data['reallyPrice'] ?? $data['price'],
        'date' => $data['date'] ?? time(),
        'timeOut' => $data['timeOut'] ?? 5,
        'state' => $data['state'] ?? 0,
        'account' => $data['account'] ?? '',
        'quantity' => $data['quantity'] ?? 1,
    ];
}

function getOrderByPayId($payId)
{
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE payId = ?");
    $stmt->execute([$payId]);
    $order = $stmt->fetch();
    if ($order) {
        $order['cards'] = json_decode($order['cards'], true) ?: [];
    }
    return $order;
}

function getOrderById($id)
{
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if ($order) {
        $order['cards'] = json_decode($order['cards'], true) ?: [];
    }
    return $order;
}

function getOrderByOrderId($orderId)
{
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE orderId = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    if ($order) {
        $order['cards'] = json_decode($order['cards'], true) ?: [];
    }
    return $order;
}

function updateOrder($payId, $data)
{
    $pdo = getDB();
    $sets = [];
    $params = [];

    $allowedColumns = [
        'orderId', 'product_id', 'product_name', 'price', 'reallyPrice',
        'payType', 'cards', 'status', 'state', 'payUrl', 'timeOut',
        'account', 'quantity', 'sku', 'paid_at', 'ip'
    ];

    if (isset($data['cards'])) {
        $data['cards'] = json_encode($data['cards']);
    }

    foreach ($data as $key => $value) {
        if (!in_array($key, $allowedColumns, true)) {
            continue;
        }
        $sets[] = "$key = ?";
        $params[] = $value;
    }

    if (empty($sets)) {
        return getOrderByPayId($payId);
    }

    $params[] = $payId;

    $stmt = $pdo->prepare("UPDATE orders SET " . implode(', ', $sets) . " WHERE payId = ?");
    $stmt->execute($params);

    return getOrderByPayId($payId);
}

function cancelTimeoutOrders()
{
    $pdo = getDB();
    $timeoutMinutes = 15;

    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE status = 'pending' 
        AND created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    $stmt->execute([$timeoutMinutes]);
    $timeoutOrders = $stmt->fetchAll();

    foreach ($timeoutOrders as $order) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$order['quantity'], $order['product_id']]);

            restoreSkuStock($order['product_id'], $order['sku'], $order['quantity']);

            $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE payId = ?");
            $stmt->execute([$order['payId']]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }

    return count($timeoutOrders);
}

function restoreSkuStock($productId, $sku, $quantity)
{
    if (empty($sku) || $quantity <= 0) {
        return;
    }

    $skuName = $sku;
    if (strpos($sku, '_') !== false) {
        $parts = explode('_', $sku, 2);
        if (count($parts) == 2) {
            $skuName = $parts[1];
        }
    }

    if (empty($skuName)) {
        return;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT skus FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product || empty($product['skus'])) {
        return;
    }

    $skus = is_array($product['skus']) ? $product['skus'] : json_decode($product['skus'], true);
    if (!is_array($skus)) {
        return;
    }

    foreach ($skus as &$s) {
        if ($s['name'] === $skuName) {
            $s['stock'] = max(0, ($s['stock'] ?? 0) + $quantity);
            break;
        }
    }

    $stmt = $pdo->prepare("UPDATE products SET skus = ? WHERE id = ?");
    $stmt->execute([json_encode($skus), $productId]);
}

function reduceSkuStock($productId, $sku, $quantity)
{
    if (empty($sku) || $quantity <= 0) {
        return;
    }

    $skuName = $sku;
    if (strpos($sku, '_') !== false) {
        $parts = explode('_', $sku, 2);
        if (count($parts) == 2) {
            $skuName = $parts[1];
        }
    }

    if (empty($skuName)) {
        return;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT skus FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product || empty($product['skus'])) {
        return;
    }

    $skus = is_array($product['skus']) ? $product['skus'] : json_decode($product['skus'], true);
    if (!is_array($skus)) {
        return;
    }

    foreach ($skus as &$s) {
        if ($s['name'] === $skuName) {
            $s['stock'] = max(0, ($s['stock'] ?? 0) - $quantity);
            break;
        }
    }

    $stmt = $pdo->prepare("UPDATE products SET skus = ? WHERE id = ?");
    $stmt->execute([json_encode($skus), $productId]);
}

function createPaymentOrder($config, $productId, $payType = 1, $account = '', $quantity = 1, $sku = '', $productData = null)
{
    $pdo = getDB();

    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            return ['code' => -1, 'msg' => '商品不存在'];
        }

        $price = $product['price'];

        $skuName = $sku;
        if (!empty($sku) && strpos($sku, '_') !== false) {
            $parts = explode('_', $sku, 2);
            if (count($parts) == 2) {
                $skuName = $parts[1];
            }
        }

        if (!empty($skuName)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND sku = ? AND used = 0");
            $stmt->execute([$productId, $skuName]);
            $availableStock = (int)$stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND used = 0");
            $stmt->execute([$productId]);
            $availableStock = (int)$stmt->fetchColumn();
        }

        if (!empty($skuName) && !empty($product['skus'])) {
            $skus = is_array($product['skus']) ? $product['skus'] : json_decode($product['skus'], true);
            if (is_array($skus)) {
                foreach ($skus as $s) {
                    if ($s['name'] == $skuName) {
                        $price = $s['price'] ?? $product['price'];
                        break;
                    }
                }
            }
        }

        if ($availableStock <= 0) {
            return ['code' => -1, 'msg' => '库存不足'];
        }

        if ($quantity > $availableStock) {
            return ['code' => -1, 'msg' => '购买数量不能超过库存'];
        }
    } catch (Exception $e) {
        return ['code' => -1, 'msg' => '库存检查失败，请稍后重试'];
    }

    $totalPrice = $price * $quantity;
    $payId = strtoupper(bin2hex(random_bytes(8)));
    $sign = md5($payId . $productId . $payType . $totalPrice . $config['pay']['secretKey']);

    $data = [
        'payId' => $payId,
        'type' => $payType,
        'price' => $totalPrice,
        'sign' => $sign,
        'param' => $productId,
        'notifyUrl' => $config['pay']['notifyUrl'],
        'returnUrl' => $config['pay']['returnUrl'],
        'isHtml' => 0,
        'quantity' => $quantity,
    ];

    $url = $config['pay']['apiUrl'] . '/createOrder?' . http_build_query($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curlOptions($ch, $config);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("[vPay] cURL error: $error | URL: $url");
        return ['code' => -1, 'msg' => '支付接口调用失败: ' . $error];
    }

    if ($httpCode != 200) {
        error_log("[vPay] HTTP $httpCode from: $url");
        return ['code' => -1, 'msg' => '支付接口HTTP错误(' . $httpCode . ')'];
    }

    $result = json_decode($response, true);
    if (!$result) {
        $errorInfo = htmlspecialchars(substr($response, 0, 200));
        return ['code' => -1, 'msg' => '支付接口返回非JSON数据'];
    }

    if ($result['code'] == 1) {
        $payUrl = $result['data']['payUrl'] ?? '';

        $pdo = getDB();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$productId]);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND used = 0");
            $stmt->execute([$productId]);
            $currentAvailable = (int)$stmt->fetchColumn();

            if ($currentAvailable < $quantity) {
                $pdo->rollBack();
                $result['code'] = -1;
                $result['msg'] = '库存不足';
                return $result;
            }

            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$quantity, $productId]);

            if (!empty($skuName)) {
                reduceSkuStock($productId, $skuName, $quantity);
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            createOrder([
                'payId' => $payId,
                'orderId' => $result['data']['orderId'],
                'product_id' => $productId,
                'product_name' => $product['name'],
                'price' => $totalPrice,
                'payType' => $payType,
                'account' => $account,
                'quantity' => $quantity,
                'sku' => $skuName,
                'status' => 'pending',
                'payUrl' => $payUrl,
                'reallyPrice' => $result['data']['reallyPrice'] ?? $totalPrice,
                'date' => $result['data']['date'] ?? time(),
                'timeOut' => $result['data']['timeOut'] ?? 5,
                'state' => $result['data']['state'] ?? 0,
                'ip' => $ip
            ]);

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $result['code'] = -1;
            $result['msg'] = '订单创建失败，请稍后重试';
            return $result;
        }
    }

    return $result;
}

function verifySign($config, $data)
{
    $key = $config['pay']['secretKey'];
    $sign = md5($data['payId'] . $data['param'] . $data['type'] . $data['price'] . $data['reallyPrice'] . $key);
    return $sign === $data['sign'];
}

function getCategories()
{
    $products = getProducts();
    $categories = ['全部'];
    foreach ($products as $product) {
        if (!in_array($product['category'], $categories)) {
            $categories[] = $product['category'];
        }
    }
    return $categories;
}

function addProduct($data)
{
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO products (name, description, price, original_price, stock, category, sales, icon, image, skus, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['name'],
        $data['description'],
        $data['price'],
        $data['original_price'],
        $data['stock'],
        $data['category'],
        0,
        $data['icon'] ?? '',
        $data['image'] ?? '',
        $data['skus'] ?? '[]',
        1,
        date('Y-m-d H:i:s')
    ]);
    return $pdo->lastInsertId();
}

function updateProduct($id, $data)
{
    $pdo = getDB();
    $sets = [];
    $params = [];

    foreach ($data as $key => $value) {
        if (in_array($key, ['name', 'description', 'price', 'original_price', 'category', 'icon', 'image', 'skus', 'status'])) {
            $sets[] = "$key = ?";
            $params[] = $value;
        }
    }

    $params[] = $id;

    $stmt = $pdo->prepare("UPDATE products SET " . implode(', ', $sets) . " WHERE id = ?");
    $stmt->execute($params);
    return $stmt->rowCount() > 0;
}

function deleteProduct($id)
{
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}

function getAllOrders($status = 'all', $keyword = '', $page = 0, $pageSize = 10)
{
    $pdo = getDB();
    $sql = "SELECT * FROM orders";
    $params = [];
    $conditions = [];

    if ($status != 'all') {
        $conditions[] = "status = ?";
        $params[] = $status;
    }

    if (!empty($keyword)) {
        $conditions[] = "(payId LIKE ? OR orderId LIKE ? OR product_name LIKE ? OR account LIKE ?)";
        $keywordParam = '%' . $keyword . '%';
        $params[] = $keywordParam;
        $params[] = $keywordParam;
        $params[] = $keywordParam;
        $params[] = $keywordParam;
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    $sql .= " ORDER BY created_at DESC";

    if ($page > 0) {
        $offset = ($page - 1) * $pageSize;
        $sql .= " LIMIT " . intval($pageSize) . " OFFSET " . intval($offset);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $orders = $stmt->fetchAll();
    foreach ($orders as &$order) {
        $order['cards'] = json_decode($order['cards'], true) ?: [];
    }
    return $orders;
}

function getSiteConfig($key = null, $default = null)
{
    $config = [];

    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT `key`, `value` FROM site_config");
        while ($row = $stmt->fetch()) {
            $config[$row['key']] = $row['value'];
        }
    } catch (Exception $e) {
    }

    if (empty($config)) {
        $defaultConfigItems = require __DIR__ . '/config/defaults.php';
        foreach ($defaultConfigItems as $item) {
            $config[$item['key']] = $item['value'];
        }
    }

    if ($key === null) {
        return $config;
    }

    return isset($config[$key]) ? $config[$key] : $default;
}

function updateSiteConfig($key, $value)
{
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO site_config (`key`, `value`)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
    ");
    return $stmt->execute([$key, $value]);
}

function checkPurchaseLimit($ip, $productId = null, $timeWindow = 3600, $maxOrders = 5)
{
    $pdo = getDB();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM orders 
        WHERE created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        AND ip = ?
    ");
    $stmt->execute([$timeWindow, $ip]);
    $count = $stmt->fetchColumn();

    if ($count >= $maxOrders) {
        return false;
    }

    if ($productId) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM orders 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            AND ip = ?
            AND product_id = ?
        ");
        $stmt->execute([$timeWindow, $ip, $productId]);
        $productCount = $stmt->fetchColumn();

        if ($productCount >= 2) {
            return false;
        }
    }

    return true;
}

function addAdminLog($action, $details = '')
{
    if (!isAdmin()) {
        return false;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, details, created_at, ip)
        VALUES (?, ?, ?, ?, ?)
    ");

    $adminId = $_SESSION['admin_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    return $stmt->execute([$adminId, $action, $details, date('Y-m-d H:i:s'), $ip]);
}

function sendPaymentMail($order)
{
    $config = loadConfig();
    $mailConfig = $config['mail'] ?? [];

    $userEmail = $order['account'] ?? '';

    if (empty($userEmail)) {
        return false;
    }

    if (empty($mailConfig['apiUrl']) || empty($mailConfig['apiSecret'])) {
        return false;
    }

    $ts = time();
    $nonce = bin2hex(random_bytes(16));

    $templatePath = __DIR__ . '/templates/mail/order_notify.html';
    if (file_exists($templatePath)) {
        $html = file_get_contents($templatePath);
        
        $baseUrl = getenv('VPAY_BASE_URL');
        if ($baseUrl) {
            $currentUrl = rtrim($baseUrl, '/');
        } else {
            $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
        }
        $html = str_replace('../image/', $currentUrl . '/templates/image/', $html);
        
        $html = str_replace([
            '{{orderId}}',
            '{{product_name}}',
            '{{quantity}}',
            '{{reallyPrice}}',
            '{{created_at}}',
            '{{account}}'
        ], [
            htmlspecialchars($order['orderId']),
            htmlspecialchars($order['product_name']),
            htmlspecialchars($order['quantity']),
            htmlspecialchars($order['reallyPrice']),
            htmlspecialchars($order['created_at']),
            htmlspecialchars($order['account'])
        ], $html);
    } else {
        $html = "订单号：{$order['orderId']}\n商品名称：{$order['product_name']}\n购买数量：{$order['quantity']}\n订单金额：¥{$order['reallyPrice']}\n支付状态：已完成\n下单时间：{$order['created_at']}\n用户账号：{$order['account']}";
    }

    $isHtml = file_exists($templatePath);
    $rawBody = json_encode(['msg' => $html, 'isHtml' => $isHtml, 'to' => $userEmail], JSON_UNESCAPED_UNICODE);
    $signData = $ts . '.' . $nonce . '.' . $rawBody;
    $signature = bin2hex(hash_hmac('sha256', $signData, $mailConfig['apiSecret'], true));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $mailConfig['apiUrl']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Timestamp: ' . $ts,
        'X-Nonce: ' . $nonce,
        'X-Signature: ' . $signature
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return false;
    }

    $result = json_decode($response, true);
    return $result['ok'] ?? false;
}
