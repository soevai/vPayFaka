<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay API接口控制器
 * @License     MIT
 */
session_start();
include __DIR__ . '/functions.php';

$config = loadConfig();
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

$csrfRequiredActions = [
    'createOrder', 'updateSiteConfig', 'addProduct', 'updateProduct',
    'deleteProduct', 'addCards', 'batchDeleteCards', 'deleteOrder',
    'updateOrder', 'resetConfig', 'completeOrder', 'batchDeleteProducts',
    'testTakeCard', 'reissueCards', 'cancelTimeoutOrders',
    'autoCancelTimeoutOrders', 'resetStock'
];

$csrfToken = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $csrfToken = $_GET['csrf_token'] ?? '';
}

if (in_array($action, $csrfRequiredActions)) {
    if (!verifyCSRFToken($csrfToken)) {
        echo json_encode(['code' => -403, 'msg' => 'CSRF验证失败，请刷新页面后重试']);
        exit;
    }
}

switch ($action) {
    case 'createOrder':
        $productId = (int)($_POST['productId'] ?? 0);
        $payType = (int)($_POST['payType'] ?? 1);
        $account = trim($_POST['account'] ?? '');
        $quantity = (int)($_POST['quantity'] ?? 1);
        $sku = trim($_POST['sku'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        if ($productId <= 0) {
            echo json_encode(['code' => -1, 'msg' => '无效的商品ID']);
            break;
        }

        if ($quantity <= 0 || $quantity > 10) {
            echo json_encode(['code' => -1, 'msg' => '购买数量必须在1-10之间']);
            break;
        }

        if (!empty($account) && !filter_var($account, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['code' => -1, 'msg' => '请输入有效的邮箱地址']);
            break;
        }

        if (!checkPurchaseLimit($ip, $productId)) {
            echo json_encode(['code' => -1, 'msg' => '购买频率过高，请稍后再试']);
            break;
        }

        $product = getProductById($productId);
        if (!$product) {
            echo json_encode(['code' => -1, 'msg' => '商品不存在']);
            break;
        }


        $skuName = $sku;
        if (!empty($sku) && strpos($sku, '_') !== false) {
            $parts = explode('_', $sku, 2);
            if (count($parts) == 2) {
                $skuName = $parts[1];
            }
        }


        $pdo = getDB();

        if (!empty($skuName)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND sku = ? AND used = 0");
            $stmt->execute([$productId, $skuName]);
            $actualCardCount = (int)$stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND used = 0");
            $stmt->execute([$productId]);
            $actualCardCount = (int)$stmt->fetchColumn();
        }

        $availableStock = $actualCardCount;
        if (!empty($skuName) && !empty($product['skus'])) {
            $skus = is_array($product['skus']) ? $product['skus'] : json_decode($product['skus'], true);
            if (is_array($skus)) {
                foreach ($skus as $s) {
                    if ($s['name'] == $skuName) {
                        break;
                    }
                }
            }
        }

        if ($availableStock < $quantity) {
            echo json_encode(['code' => -1, 'msg' => '库存不足']);
            break;
        }

        $result = createPaymentOrder($config, $productId, $payType, $account, $quantity, $sku, $product);
        echo json_encode($result);
        break;

    case 'checkOrder':
        $orderId = $_GET['orderId'] ?? '';

        if (empty($orderId)) {
            echo json_encode(['code' => -1, 'msg' => '缺少订单号']);
            break;
        }

        $localOrder = getOrderByOrderId($orderId);
        if ($localOrder) {
            if ($localOrder['status'] == 'pending' && strtotime($localOrder['created_at']) < time() - 15 * 60) {
                updateOrder($localOrder['payId'], [
                    'status' => 'failed',
                    'state' => 0,
                    'paid_at' => null
                ]);

                $product = getProductById($localOrder['product_id']);
                if ($product) {
                    updateProductStock($localOrder['product_id'], $localOrder['quantity']);
                }
                $localOrder = getOrderByOrderId($orderId);
            }

            if ($localOrder['status'] == 'completed') {
                echo json_encode([
                    'code' => 1,
                    'msg' => 'success',
                    'data' => [
                        'orderId' => $orderId,
                        'state' => 1,
                        'isLocal' => true,
                        'order' => $localOrder
                    ]
                ]);
                break;
            }
        }

        $url = $config['pay']['apiUrl'] . '/getOrder?orderId=' . urlencode($orderId);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curlOptions($ch, $config);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            echo json_encode(['code' => -1, 'msg' => '网络错误']);
            break;
        }

        $result = json_decode($response, true);
        if ($result === null && $response !== '') {
            echo json_encode(['code' => -1, 'msg' => '解析错误']);
            break;
        }

        if ($result && $result['code'] === 1) {
            if (!isset($result['data']['state'])) {
                $result['data']['state'] = 1;
            }

            if ($result['data']['state'] >= 1 && $localOrder && $localOrder['status'] != 'completed') {
                $cards = takeCards($localOrder['product_id'], $localOrder['quantity'], false, $localOrder['sku']);

                if ($cards === null || empty($cards)) {
                    $pdo = getDB();
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                    $stmt->execute([$localOrder['quantity'], $localOrder['product_id']]);

                    restoreSkuStock($localOrder['product_id'], $localOrder['sku'], $localOrder['quantity']);

                    error_log("[PAYMENT] takeCards failed for payId: {$localOrder['payId']}, product_id: {$localOrder['product_id']}, quantity: {$localOrder['quantity']}");

                    updateOrder($localOrder['payId'], [
                        'status' => 'failed',
                        'state' => -1,
                        'cards' => [],
                        'paid_at' => date('Y-m-d H:i:s')
                    ]);

                    $localOrder = getOrderByOrderId($orderId);
                    echo json_encode(['code' => 1, 'msg' => 'success', 'data' => $localOrder]);
                    break;
                }

                $orderPrice = (float)($localOrder['reallyPrice'] ?: $localOrder['price']);
                $callbackPrice = (float)($result['data']['reallyPrice'] ?? 0);

                if ($callbackPrice > 0 && ($callbackPrice < $orderPrice && abs($callbackPrice - $orderPrice) > 0.01)) {
                    error_log("[PAYMENT] Price mismatch for payId: {$localOrder['payId']}, orderPrice: $orderPrice, callbackPrice: $callbackPrice");

                    $pdo = getDB();
                    $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                    $stmt->execute([$localOrder['quantity'], $localOrder['product_id']]);
                    restoreSkuStock($localOrder['product_id'], $localOrder['sku'], $localOrder['quantity']);

                    updateOrder($localOrder['payId'], [
                        'status' => 'failed',
                        'state' => -2,
                        'paid_at' => date('Y-m-d H:i:s'),
                        'cards' => [],
                        'reallyPrice' => $callbackPrice,
                    ]);

                    $localOrder = getOrderByOrderId($orderId);
                    echo json_encode(['code' => 1, 'msg' => 'success', 'data' => $localOrder]);
                    break;
                }

                $verifiedPrice = $orderPrice;

                updateOrder($localOrder['payId'], [
                    'status' => 'completed',
                    'state' => 1,
                    'paid_at' => date('Y-m-d H:i:s'),
                    'reallyPrice' => $verifiedPrice,
                    'cards' => $cards,
                ]);

                $pdo = getDB();
                $stmt = $pdo->prepare("UPDATE products SET sales = sales + ? WHERE id = ?");
                $stmt->execute([$localOrder['quantity'], $localOrder['product_id']]);

                $localOrder = getOrderByOrderId($orderId);
                $result['data']['isLocal'] = true;
                $result['data']['order'] = $localOrder;
            }

            echo json_encode($result);
        } else {
            echo json_encode(['code' => -1, 'msg' => '查询失败']);
        }
        break;

    case 'getProductById':
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo json_encode(['code' => -1, 'msg' => '无效的商品ID']);
            break;
        }
        $product = getProductById($id);
        if ($product) {
            echo json_encode(['code' => 1, 'msg' => 'success', 'data' => $product]);
        } else {
            echo json_encode(['code' => -1, 'msg' => '商品不存在']);
        }
        break;

    case 'getProducts':
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        $pageSize = isset($_GET['pageSize']) ? intval($_GET['pageSize']) : 10;

        $pdo = getDB();

        if ($page > 0) {
            $offset = ($page - 1) * $pageSize;

            $stmt = $pdo->query("SELECT COUNT(*) FROM products");
            $total = $stmt->fetchColumn();
            $pages = ceil($total / $pageSize);

            $stmt = $pdo->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bindParam(1, $pageSize, PDO::PARAM_INT);
            $stmt->bindParam(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll();
        } else {
            $stmt = $pdo->query("SELECT * FROM products WHERE status = 1 ORDER BY created_at DESC");
            $products = $stmt->fetchAll();
        }

        foreach ($products as &$product) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND used = 0");
            $stmt->execute([$product['id']]);
            $product['stock'] = (int)$stmt->fetchColumn();

            if (!empty($product['skus'])) {
                $product['skus'] = json_decode($product['skus'], true) ?: [];
            } else {
                $product['skus'] = [];
            }
        }

        if ($page > 0) {
            echo json_encode([
                'code' => 1,
                'msg' => 'success',
                'data' => $products,
                'total' => $total,
                'page' => $page,
                'pages' => $pages
            ]);
        } else {
            echo json_encode(['code' => 1, 'msg' => 'success', 'data' => $products]);
        }
        break;

    case 'getCategories':
        $products = getProducts();
        $categories = array_unique(array_column($products, 'category'));
        $categories = array_filter($categories);
        echo json_encode(['code' => 1, 'msg' => 'success', 'data' => array_values($categories)]);
        break;

    case 'getCards':
        requireAdmin();
        $productId = (int)($_GET['productId'] ?? 0);
        $sku = $_GET['sku'] ?? '';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
        $pageSize = 15;

        $total = getCardsCountByProductId($productId, false, $sku);
        $pages = $page > 0 ? ceil($total / $pageSize) : 1;

        $cards = getCardsByProductId($productId, $page, $pageSize, false, $sku);

        echo json_encode([
            'code' => 1,
            'msg' => 'success',
            'data' => $cards,
            'total' => $total,
            'page' => $page,
            'pages' => $pages
        ]);
        break;

    case 'getCardsGroupBySku':
        $productId = (int)($_GET['productId'] ?? 0);

        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT sku, COUNT(*) as count FROM cards WHERE product_id = ? AND used = 0 GROUP BY sku");
        $stmt->execute([$productId]);
        $result = $stmt->fetchAll();

        $skuCounts = [];
        foreach ($result as $row) {
            $skuCounts[] = [
                'sku' => $row['sku'] ?: '无SKU',
                'count' => (int)$row['count']
            ];
        }

        echo json_encode(['code' => 1, 'msg' => 'success', 'data' => $skuCounts]);
        break;

    case 'getOrders':
        requireAdmin();
        $status = $_GET['status'] ?? 'all';
        $keyword = $_GET['keyword'] ?? '';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $pageSize = 10;

        $pdo = getDB();
        $countSql = "SELECT COUNT(*) FROM orders";
        $countParams = [];
        $countConditions = [];

        if ($status != 'all') {
            $countConditions[] = "status = ?";
            $countParams[] = $status;
        }

        if (!empty($keyword)) {
            $countConditions[] = "(payId LIKE ? OR orderId LIKE ? OR product_name LIKE ? OR account LIKE ?)";
            $keywordParam = '%' . $keyword . '%';
            $countParams[] = $keywordParam;
            $countParams[] = $keywordParam;
            $countParams[] = $keywordParam;
            $countParams[] = $keywordParam;
        }

        if (!empty($countConditions)) {
            $countSql .= " WHERE " . implode(' AND ', $countConditions);
        }

        $stmt = $pdo->prepare($countSql);
        $stmt->execute($countParams);
        $total = $stmt->fetchColumn();
        $pages = ceil($total / $pageSize);

        $orders = getAllOrders($status, $keyword, $page, $pageSize);

        echo json_encode([
            'code' => 1,
            'msg' => 'success',
            'data' => $orders,
            'total' => $total,
            'page' => $page,
            'pages' => $pages
        ]);
        break;

    case 'getOrder':
        $orderId = $_GET['orderId'] ?? '';
        $payId = $_GET['payId'] ?? '';

        if (empty($orderId) && empty($payId)) {
            echo json_encode(['code' => -1, 'msg' => '缺少订单号']);
            break;
        }

        $order = null;
        if (!empty($orderId)) {
            $order = getOrderByOrderId($orderId);
        }
        if (!$order && !empty($payId)) {
            $order = getOrderByPayId($payId);
        }

        if ($order) {
            $cardsEmpty = false;
            if (empty($order['cards']) || !is_array($order['cards']) || count($order['cards']) == 0) {
                $cardsEmpty = true;
            } else {
                $hasValidCards = false;
                foreach ($order['cards'] as $card) {
                    if (!empty($card)) {
                        $hasValidCards = true;
                        break;
                    }
                }
                $cardsEmpty = !$hasValidCards;
            }

            if ($order['status'] == 'completed' && $cardsEmpty) {
                $pdo = getDB();

                $cards = reissueCards($order['product_id'], intval($order['quantity']));
                if ($cards !== null && !empty($cards)) {
                    updateOrder($order['payId'], [
                        'cards' => $cards,
                    ]);

                    error_log("[ORDER] reissue cards for orderId: {$orderId}, payId: {$payId}, cards count: " . count($cards));

                    if (!empty($orderId)) {
                        $order = getOrderByOrderId($orderId);
                    } else {
                        $order = getOrderByPayId($payId);
                    }
                } else {
                    error_log("[ORDER] reissue cards failed for orderId: {$orderId}, payId: {$payId}");
                }
            }

            echo json_encode(['code' => 1, 'msg' => 'success', 'data' => $order]);
        } else {
            echo json_encode(['code' => -1, 'msg' => '订单不存在']);
        }
        break;

    case 'searchOrdersByAccount':
        $account = $_GET['account'] ?? '';

        if (empty($account)) {
            echo json_encode(['code' => -1, 'msg' => '缺少接收邮箱']);
            break;
        }

        if (!filter_var($account, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['code' => -1, 'msg' => '无效的邮箱格式']);
            break;
        }

        $rateLimitFile = sys_get_temp_dir() . '/vpay_search_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
        $rateLimitData = @file_get_contents($rateLimitFile);
        $rateLimit = $rateLimitData ? json_decode($rateLimitData, true) : ['count' => 0, 'time' => 0];
        if (time() - $rateLimit['time'] > 60) {
            $rateLimit = ['count' => 0, 'time' => time()];
        }
        $rateLimit['count']++;
        if ($rateLimit['count'] > 3) {
            echo json_encode(['code' => -1, 'msg' => '查询频率过高，请稍后再试']);
            break;
        }
        @file_put_contents($rateLimitFile, json_encode($rateLimit));

        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT * FROM orders
            WHERE account = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$account]);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $order['cards'] = json_decode($order['cards'], true) ?: [];
            if ($order['status'] != 'completed') {
                $order['cards'] = [];
            }
        }

        echo json_encode(['code' => 1, 'msg' => 'success', 'data' => $orders]);
        break;

    case 'completeOrder':
        requireAdmin();
        $orderId = $_POST['orderId'] ?? '';

        if (empty($orderId)) {
            echo json_encode(['code' => -1, 'msg' => '缺少订单号']);
            break;
        }

        $order = getOrderByOrderId($orderId);
        if (!$order) {
            echo json_encode(['code' => -1, 'msg' => '订单不存在']);
            break;
        }

        if ($order['status'] == 'completed') {
            echo json_encode(['code' => -1, 'msg' => '订单已完成']);
            break;
        }

        $pdo = getDB();

        $cards = takeCards($order['product_id'], $order['quantity'], false, $order['sku']);
        if ($cards === null || empty($cards)) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$order['quantity'], $order['product_id']]);

            restoreSkuStock($order['product_id'], $order['sku'], $order['quantity']);

            echo json_encode(['code' => -1, 'msg' => '卡密不足，无法完成订单']);
            break;
        }

        updateOrder($order['payId'], [
            'status' => 'completed',
            'state' => 1,
            'paid_at' => date('Y-m-d H:i:s'),
            'cards' => $cards,
        ]);

        $stmt = $pdo->prepare("UPDATE products SET sales = sales + ? WHERE id = ?");
        $stmt->execute([$order['quantity'], $order['product_id']]);

        if (!empty($order['sku'])) {
            $skuName = $order['sku'];
            if (strpos($order['sku'], '_') !== false) {
                $parts = explode('_', $order['sku'], 2);
                if (count($parts) == 2) {
                    $skuName = $parts[1];
                }
            }

            $stmt = $pdo->prepare("SELECT skus FROM products WHERE id = ?");
            $stmt->execute([$order['product_id']]);
            $product = $stmt->fetch();

            if ($product && !empty($product['skus'])) {
                $skus = is_array($product['skus']) ? $product['skus'] : json_decode($product['skus'], true);
                if (is_array($skus)) {
                    foreach ($skus as &$sku) {
                        if ($sku['name'] == $skuName) {
                            $sku['stock'] = max(0, ($sku['stock'] ?? 0) - $order['quantity']);
                            break;
                        }
                    }
                    $stmt = $pdo->prepare("UPDATE products SET skus = ? WHERE id = ?");
                    $stmt->execute([json_encode($skus), $order['product_id']]);
                }
            }
        }


        sendPaymentMail($order);

        $adminId = $_SESSION['admin_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, details, created_at, ip)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, 'complete_order', '手动完成订单: ' . $orderId, date('Y-m-d H:i:s'), $ip]);

        echo json_encode(['code' => 1, 'msg' => '订单已完成']);
        break;

    case 'addProduct':
        requireAdmin();
        $newProduct = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => (float)$_POST['price'],
            'original_price' => (float)$_POST['original_price'],
            'stock' => 0,
            'category' => $_POST['category'],
            'icon' => $_POST['icon'],
            'skus' => $_POST['skus'] ?? '[]',
        ];
        addProduct($newProduct);

        $adminId = $_SESSION['admin_id'] ?? 0;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, details, created_at, ip)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$adminId, 'add_product', '添加商品: ' . $newProduct['name'], date('Y-m-d H:i:s'), $ip]);

        echo json_encode(['code' => 1, 'msg' => '添加成功']);
        break;

    case 'editProduct':
        requireAdmin();
        $productId = $_POST['id'];
        $updateData = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => (float)$_POST['price'],
            'original_price' => (float)$_POST['original_price'],
            'category' => $_POST['category'],
            'icon' => $_POST['icon'],
            'skus' => $_POST['skus'] ?? '[]',
        ];
        updateProduct($productId, $updateData);
        echo json_encode(['code' => 1, 'msg' => '修改成功']);
        break;

    case 'deleteProduct':
        requireAdmin();
        $productId = $_POST['id'];
        deleteProduct($productId);
        echo json_encode(['code' => 1, 'msg' => '删除成功']);
        break;

    case 'batchDeleteProducts':
        requireAdmin();
        $ids = $_POST['ids'];
        $idArray = array_map('intval', explode(',', $ids));

        $pdo = getDB();
        $placeholders = implode(',', array_fill(0, count($idArray), '?'));

        $stmt = $pdo->prepare("DELETE FROM products WHERE id IN ($placeholders)");
        $stmt->execute($idArray);

        $stmt = $pdo->prepare("DELETE FROM cards WHERE product_id IN ($placeholders)");
        $stmt->execute($idArray);

        echo json_encode(['code' => 1, 'msg' => '批量删除成功']);
        break;

    case 'batchDeleteCards':
        requireAdmin();
        $items = $_POST['items'];
        $itemArray = explode(',', $items);

        $pdo = getDB();
        $pdo->beginTransaction();

        try {
            foreach ($itemArray as $item) {
                list($productId, $sku) = explode('_', $item, 2);
                $productId = intval($productId);
                $sku = urldecode($sku);

                if (!empty($sku)) {
                    $stmt = $pdo->prepare("DELETE FROM cards WHERE product_id = ? AND sku = ?");
                    $stmt->execute([$productId, $sku]);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM cards WHERE product_id = ? AND sku = ''");
                    $stmt->execute([$productId]);
                }
            }

            $pdo->commit();
            echo json_encode(['code' => 1, 'msg' => '批量删除成功']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['code' => -1, 'msg' => '删除失败']);
        }
        break;

    case 'addCards':
        requireAdmin();
        $productId = $_POST['product_id'];
        $cardsText = $_POST['cards'];
        $sku = $_POST['sku'] ?? '';
        $cards = array_filter(array_map('trim', explode("\n", $cardsText)));
        addCards($productId, $cards, $sku);
        echo json_encode(['code' => 1, 'msg' => '保存成功']);
        break;

    case 'getDashboard':
        requireAdmin();
        $products = getProducts();
        $orders = getAllOrders('all');
        $completedOrders = array_filter($orders, function ($o) {
            return $o['status'] == 'completed';
        });
        $pendingOrders = array_filter($orders, function ($o) {
            return $o['status'] == 'pending';
        });

        $page = (int)($_GET['page'] ?? 1);
        $pageSize = 5;
        $totalOrders = count($orders);
        $totalPages = max(1, ceil($totalOrders / $pageSize));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $pageSize;
        $recentOrders = array_slice(array_reverse($orders), $offset, $pageSize);

        $salesTrend = [];
        $pdo = getDB();
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i day"));
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = ? AND status = 'completed'");
            $stmt->execute([$date]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $salesTrend[] = [
                'date' => date('m-d', strtotime("-$i day")),
                'count' => (int)($result['count'] ?? 0)
            ];
        }

        $productSales = [];
        $totalSalesAll = array_sum(array_column($products, 'sales')) ?: 1;
        foreach ($products as $product) {
            $productSales[] = [
                'name' => $product['name'],
                'sales' => $product['sales'],
                'percentage' => round(($product['sales'] / $totalSalesAll) * 100, 1)
            ];
        }
        usort($productSales, function ($a, $b) {
            return $b['sales'] - $a['sales'];
        });
        $topProducts = array_slice($productSales, 0, 5);

        echo json_encode([
            'code' => 1,
            'msg' => 'success',
            'data' => [
                'productCount' => count($products),
                'completedCount' => count($completedOrders),
                'pendingCount' => count($pendingOrders),
                'totalSales' => array_sum(array_column($products, 'sales')),
                'products' => $products,
                'recentOrders' => $recentOrders,
                'salesTrend' => $salesTrend,
                'productSales' => $topProducts,
                'pagination' => [
                    'page' => $page,
                    'pageSize' => $pageSize,
                    'totalPages' => $totalPages,
                    'totalOrders' => $totalOrders
                ]
            ]
        ]);
        break;

    case 'getSiteConfig':
        $dbConfig = getSiteConfig();
        $mailConfigured = !empty($dbConfig['mail_smtpHost']) && !empty($dbConfig['mail_smtpUser']) && !empty($dbConfig['mail_smtpPass']);
        $siteConfig = [
            'announcement' => $dbConfig['notice_content'] ?? '',
            'video_title' => $dbConfig['video_title'] ?? '',
            'video_url' => $dbConfig['video_url'] ?? '',
            'mail_enabled' => $mailConfigured,
            'show_admin_login' => isset($dbConfig['show_admin_login']) ? (int)$dbConfig['show_admin_login'] : 1,

            'site' => [
                'name' => $dbConfig['site_name'] ?? '',
                'title' => $dbConfig['site_title'] ?? '',
                'description' => $dbConfig['site_description'] ?? '',
                'keywords' => $dbConfig['site_keywords'] ?? '',
                'logo' => $dbConfig['site_logo'] ?? '',
                'footer' => $dbConfig['site_footer'] ?? ''
            ]
        ];
        echo json_encode([
            'code' => 1,
            'msg' => 'success',
            'data' => $siteConfig
        ]);
        break;

    case 'testTakeCard':
        requireAdmin();
        $productId = (int)($_GET['productId'] ?? 0);
        if ($productId <= 0) {
            echo json_encode(['code' => -1, 'msg' => '无效的商品ID']);
            break;
        }
        $cards = takeCardsIgnoreStock($productId, 1);
        if ($cards !== null && !empty($cards)) {
            echo json_encode(['code' => 1, 'msg' => 'success', 'data' => $cards]);
        } else {
            echo json_encode(['code' => -1, 'msg' => '发卡失败，可能卡密不足']);
        }
        break;

    case 'reissueCards':
        requireAdmin();
        $payId = $_GET['payId'] ?? '';
        $productId = (int)($_GET['productId'] ?? 0);
        $quantity = (int)($_GET['quantity'] ?? 1);

        if (empty($payId) || $productId <= 0) {
            echo json_encode(['code' => -1, 'msg' => '参数错误']);
            break;
        }

        $cards = reissueCards($productId, $quantity);
        if ($cards !== null && !empty($cards)) {
            updateOrder($payId, ['cards' => $cards]);
            echo json_encode(['code' => 1, 'msg' => 'success', 'data' => $cards]);
        } else {
            echo json_encode(['code' => -1, 'msg' => '补发失败，可能卡密不足']);
        }
        break;

    case 'resetStock':
        requireAdmin();
        $pdo = getDB();
        $stmt = $pdo->query("SELECT id FROM products");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $updatedCount = 0;
        foreach ($products as $product) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cards WHERE product_id = ? AND used = 0");
            $stmt->execute([$product['id']]);
            $cardCount = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmt->execute([$cardCount, $product['id']]);
            $updatedCount++;
        }

        echo json_encode(['code' => 1, 'msg' => "已同步 $updatedCount 个商品的库存"]);
        break;

    case 'cancelTimeoutOrders':
        requireAdmin();
        $count = cancelTimeoutOrders();
        echo json_encode(['code' => 1, 'msg' => "成功取消 $count 个超时订单"]);
        break;

    case 'autoCancelTimeoutOrders':
        requireAdmin();
        $count = cancelTimeoutOrders();
        echo json_encode(['code' => 1, 'msg' => "成功取消 $count 个超时订单"]);
        break;

    case 'getAdminLogs':
        requireAdmin();
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $pageSize = 10;
        $offset = ($page - 1) * $pageSize;

        $pdo = getDB();

        $stmt = $pdo->query("SELECT COUNT(*) FROM admin_logs");
        $total = $stmt->fetchColumn();
        $pages = ceil($total / $pageSize);

        $stmt = $pdo->prepare("SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bindParam(1, $pageSize, PDO::PARAM_INT);
        $stmt->bindParam(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();

        echo json_encode(array(
            'code' => 1,
            'msg' => 'success',
            'data' => $logs,
            'total' => $total,
            'page' => $page,
            'pages' => $pages
        ));
        break;

    case 'getAdminConfig':
        requireAdmin();
        $dbConfig = getSiteConfig();
        $adminConfig = [
            'announcement' => $dbConfig['notice_content'] ?? '',
            'video_title' => $dbConfig['video_title'] ?? '',
            'video_url' => $dbConfig['video_url'] ?? '',
            'show_admin_login' => isset($dbConfig['show_admin_login']) ? (int)$dbConfig['show_admin_login'] : 1,
            'ssl_verify' => isset($dbConfig['ssl_verify']) ? (int)$dbConfig['ssl_verify'] : 0,
            'site' => [
                'name' => $dbConfig['site_name'] ?? '',
                'title' => $dbConfig['site_title'] ?? '',
                'description' => $dbConfig['site_description'] ?? '',
                'keywords' => $dbConfig['site_keywords'] ?? '',
                'logo' => $dbConfig['site_logo'] ?? '',
                'footer' => $dbConfig['site_footer'] ?? ''
            ],
            'pay' => [
                'apiUrl' => $dbConfig['pay_apiUrl'] ?? '',
                'secretKey' => $dbConfig['pay_secretKey'] ?? '',
            ],
            'mail' => [
                'apiSecret' => $dbConfig['mail_apiSecret'] ?? '',
                'smtpHost' => $dbConfig['mail_smtpHost'] ?? '',
                'smtpPort' => $dbConfig['mail_smtpPort'] ?? '',
                'smtpUser' => $dbConfig['mail_smtpUser'] ?? '',
                'smtpPass' => $dbConfig['mail_smtpPass'] ?? '',
                'fromName' => $dbConfig['mail_fromName'] ?? ''
            ],
            'admin' => [
                'username' => $dbConfig['admin_username'] ?? '',
                'access_key' => $dbConfig['admin_access_key'] ?? '',
                // 'password' => $dbConfig['admin_password'] ?? '',
            ],
            'kf' => [
                'name' => $dbConfig['kf_name'] ?? '',
                'qq' => $dbConfig['kf_qq'] ?? '',
                'wechat' => $dbConfig['kf_wechat'] ?? '',
                'phone' => $dbConfig['kf_phone'] ?? '',
                'email' => $dbConfig['kf_email'] ?? ''
            ]
        ];
        echo json_encode([
            'code' => 1,
            'msg' => 'success',
            'data' => $adminConfig
        ]);
        break;

    case 'updateSiteConfig':
        requireAdmin();
        $announcement = strip_tags($_POST['announcement'] ?? '');
        $video_url = $_POST['video_url'] ?? '';
        $video_title = $_POST['video_title'] ?? '';
        $site_name = htmlspecialchars($_POST['site_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $site_title = htmlspecialchars($_POST['site_title'] ?? '', ENT_QUOTES, 'UTF-8');
        $site_description = htmlspecialchars($_POST['site_description'] ?? '', ENT_QUOTES, 'UTF-8');
        $site_keywords = htmlspecialchars($_POST['site_keywords'] ?? '', ENT_QUOTES, 'UTF-8');
        $site_logo = htmlspecialchars($_POST['site_logo'] ?? '', ENT_QUOTES, 'UTF-8');
        $site_footer = htmlspecialchars($_POST['site_footer'] ?? '', ENT_QUOTES, 'UTF-8');
        $pay_apiUrl = htmlspecialchars($_POST['pay_apiUrl'] ?? '', ENT_QUOTES, 'UTF-8');
        $pay_secretKey = $_POST['pay_secretKey'] ?? '';
        $mail_apiSecret = $_POST['mail_apiSecret'] ?? '';
        $mail_smtpHost = htmlspecialchars($_POST['mail_smtpHost'] ?? '', ENT_QUOTES, 'UTF-8');
        $mail_smtpPort = htmlspecialchars($_POST['mail_smtpPort'] ?? '', ENT_QUOTES, 'UTF-8');
        $mail_smtpUser = htmlspecialchars($_POST['mail_smtpUser'] ?? '', ENT_QUOTES, 'UTF-8');
        $mail_smtpPass = $_POST['mail_smtpPass'] ?? '';
        $mail_fromName = htmlspecialchars($_POST['mail_fromName'] ?? '', ENT_QUOTES, 'UTF-8');
        $admin_username = htmlspecialchars($_POST['admin_username'] ?? '', ENT_QUOTES, 'UTF-8');
        $admin_password = $_POST['admin_password'] ?? '';
        $admin_access_key = trim($_POST['admin_access_key'] ?? '');
        $kf_name = htmlspecialchars($_POST['kf_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $kf_qq = htmlspecialchars($_POST['kf_qq'] ?? '', ENT_QUOTES, 'UTF-8');
        $kf_wechat = htmlspecialchars($_POST['kf_wechat'] ?? '', ENT_QUOTES, 'UTF-8');
        $kf_phone = htmlspecialchars($_POST['kf_phone'] ?? '', ENT_QUOTES, 'UTF-8');
        $kf_email = htmlspecialchars($_POST['kf_email'] ?? '', ENT_QUOTES, 'UTF-8');

        updateSiteConfig('notice_content', $announcement);
        updateSiteConfig('video_url', $video_url);
        updateSiteConfig('video_title', $video_title);
        updateSiteConfig('site_name', $site_name);
        updateSiteConfig('site_title', $site_title);
        updateSiteConfig('site_description', $site_description);
        updateSiteConfig('site_keywords', $site_keywords);
        updateSiteConfig('site_logo', $site_logo);
        updateSiteConfig('site_footer', $site_footer);
        updateSiteConfig('pay_apiUrl', $pay_apiUrl);
        updateSiteConfig('pay_secretKey', $pay_secretKey);
        updateSiteConfig('mail_apiSecret', $mail_apiSecret);
        updateSiteConfig('mail_smtpHost', $mail_smtpHost);
        updateSiteConfig('mail_smtpPort', $mail_smtpPort);
        updateSiteConfig('mail_smtpUser', $mail_smtpUser);
        updateSiteConfig('mail_smtpPass', $mail_smtpPass);
        updateSiteConfig('mail_fromName', $mail_fromName);
        updateSiteConfig('admin_username', $admin_username);
        if (!empty($admin_password)) {
            if (strlen($admin_password) < 6) {
                echo json_encode(['code' => -1, 'msg' => '管理员密码至少需要6个字符']);
                exit;
            }
            updateSiteConfig('admin_password', password_hash($admin_password, PASSWORD_DEFAULT));
        }
        updateSiteConfig('kf_name', $kf_name);
        updateSiteConfig('kf_qq', $kf_qq);
        updateSiteConfig('kf_wechat', $kf_wechat);
        updateSiteConfig('kf_phone', $kf_phone);
        updateSiteConfig('kf_email', $kf_email);
        $showAdminLogin = isset($_POST['show_admin_login']) ? (int)$_POST['show_admin_login'] : 1;
        updateSiteConfig('show_admin_login', (string)$showAdminLogin);
        updateSiteConfig('admin_access_key', $admin_access_key);
        $sslVerify = ($_POST['ssl_verify'] ?? '0') === '1' ? '1' : '0';
        updateSiteConfig('ssl_verify', $sslVerify);

        echo json_encode(['code' => 1, 'msg' => '更新成功']);
        break;

    case 'resetConfig':
        requireAdmin();
        $defaultConfigItems = require __DIR__ . '/config/defaults.php';
        $protectedKeys = ['admin_username', 'admin_password', 'admin_access_key'];

        $pdo = getDB();
        foreach ($defaultConfigItems as $item) {
            if (in_array($item['key'], $protectedKeys, true)) {
                continue;
            }
            $stmt = $pdo->prepare("
                INSERT INTO `site_config` (`key`, `value`, `description`)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$item['key'], $item['value'], $item['description']]);
        }

        addAdminLog('reset_config', '重置系统配置到默认值（管理员账号密码已保留）');
        echo json_encode(['code' => 1, 'msg' => '配置已重置为默认值，管理员账号密码保持不变']);
        break;

    default:
        echo json_encode(['code' => -1, 'msg' => '无效的action']);
}
