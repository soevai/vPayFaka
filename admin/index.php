<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 管理后台主页面
 * @License     MIT
 */
ob_start();

require_once '../functions.php';

if (!checkInstallation()) {
    header('Location: ../install.php');
    exit;
}

foreach ($_COOKIE as $name => $value) {
    if ($name != 'PHPSESSID') {
        setcookie($name, '', time() - 3600, '/');
        unset($_COOKIE[$name]);
    }
}

session_start();

$SESSION_TIMEOUT = 120 * 60;

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    $redirect = 'login.php';
    if (!empty($_GET['key'])) {
        $redirect .= '?key=' . urlencode($_GET['key']);
    }
    header('Location: ' . $redirect);
    exit;
}

if (isset($_SESSION['login_time']) && time() - $_SESSION['login_time'] > $SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    $redirect = 'login.php';
    if (!empty($_GET['key'])) {
        $redirect .= '?key=' . urlencode($_GET['key']);
    }
    header('Location: ' . $redirect);
    exit;
}

$_SESSION['login_time'] = time();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';
$config = loadConfig();
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - <?php echo htmlspecialchars($config['site']['name']); ?></title>
    <link rel="icon" href="<?php echo !empty($config['site']['logo']) ? htmlspecialchars($config['site']['logo']) : '../templates/image/favicon.ico'; ?>" type="image/x-icon">
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

        .ui-card {
            background: rgba(26, 26, 36, 0.9);
            border: 1px solid rgba(255, 102, 0, 0.2);
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(255, 102, 0, 0.1);
        }

        .btn-buy {
            background: linear-gradient(135deg, #ff6600, #ff4500);
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-buy:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 102, 0, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: #ff6600;
            border: 1px solid #ff6600;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-outline:hover {
            background: rgba(255, 102, 0, 0.1);
        }

        .ui-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 102, 0, 0.3);
            border-radius: 4px;
            color: #fff;
            transition: all 0.3s ease;
        }

        .ui-input:focus {
            outline: none;
            border-color: #ff6600;
            box-shadow: 0 0 10px rgba(255, 102, 0, 0.3);
        }

        select.ui-input {
            padding-right: 2rem;
            appearance: none;
            background-color: #2a2a35;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23ff6600' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            border: 1px solid #444;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        select.ui-input:hover {
            border-color: #ff6600;
        }

        select.ui-input:focus {
            border-color: #ff6600;
            box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.2);
        }

        select.ui-input option {
            background: #2a2a35;
            color: #fff;
            padding: 8px 12px;
            border-bottom: 1px solid #333;
        }

        select.ui-input option:hover {
            background: #ff6600;
            color: #fff;
        }

        .custom-checkbox {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }

        .custom-checkbox input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .custom-checkbox .checkmark {
            width: 18px;
            height: 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 102, 0, 0.3);
            border-radius: 4px;
            transition: all 0.3s ease;
            position: relative;
        }

        .custom-checkbox:hover .checkmark {
            border-color: #ff6600;
            background: rgba(255, 102, 0, 0.1);
        }

        .custom-checkbox input[type="checkbox"]:checked~.checkmark {
            background: linear-gradient(135deg, #ff6600, #ff4500);
            border-color: #ff6600;
        }

        .custom-checkbox .checkmark::after {
            content: "";
            position: absolute;
            display: none;
            left: 6px;
            top: 2px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .custom-checkbox input[type="checkbox"]:checked~.checkmark::after {
            display: block;
        }

        .product-checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
        }

        .modal {
            background: rgba(20, 20, 30, 0.95);
            border: 1px solid rgba(255, 102, 0, 0.3);
            border-radius: 8px;
            animation: modalFadeIn 0.3s ease-out;
        }

        .modal.closing {
            animation: modalFadeOut 0.2s ease-out forwards;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes modalFadeOut {
            from {
                opacity: 1;
                transform: scale(1) translateY(0);
            }

            to {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }
        }

        .animate-modal-in {
            animation: modalFadeIn 0.3s ease-out;
        }

        .animate-modal-out {
            animation: modalFadeOut 0.2s ease-out forwards;
        }

        .modal-backdrop {
            animation: backdropFadeIn 0.3s ease-out;
        }

        .modal-backdrop.closing {
            animation: backdropFadeOut 0.2s ease-out forwards;
        }

        @keyframes backdropFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes backdropFadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        #content {
            transition: opacity 0.3s ease-in-out;
        }

        #content.fade-out {
            opacity: 0;
        }

        #content.fade-in {
            opacity: 1;
        }

        .content-wrapper {
            animation: contentFadeIn 0.3s ease-out;
        }

        @keyframes contentFadeIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .navbar {
            background: rgba(15, 15, 20, 0.95);
            border-bottom: 1px solid rgba(255, 102, 0, 0.2);
        }

        .nav-item.active {
            background: rgba(255, 102, 0, 0.2);
            color: #ff6600;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        ::-webkit-scrollbar-track {
            background: #1a1a24;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #ff6600, #cc4400);
            border-radius: 4px;
            border: 1px solid #ff8800;
        }

        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #ff8800, #dd5500);
            box-shadow: 0 0 10px rgba(255, 102, 0, 0.5);
        }

        .toast-container {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideInRight 0.3s ease-out;
            max-width: 400px;
        }

        .toast-success {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .toast-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .toast-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .toast i {
            font-size: 18px;
        }

        .toast-message {
            flex: 1;
            font-size: 14px;
            color: #fff;
        }

        .toast-close {
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .toast-close:hover {
            opacity: 1;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }

            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }

        body {
            scrollbar-width: thin;
            scrollbar-color: #ff6600 #1a1a24;
        }
    </style>
</head>

<body>
    <div class="toast-container" id="toastContainer"></div>
    <div class="navbar w-full fixed top-0 z-50">
        <div class="container mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="../index.php" class="text-xl font-bold text-orange-500 flex items-center gap-2 hover:text-orange-400 transition-colors">
                    <i class="fa fa-store"></i>
                    <?php echo htmlspecialchars($config['site']['name']); ?>
                </a>
                <div class="hidden md:flex gap-6 text-gray-300 text-sm">
                    <span class="text-orange-400 font-medium">管理后台</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="logout.php" class="btn-outline px-4 py-2 text-sm">退出登录</a>
            </div>
        </div>
    </div>

    <div class="pt-20">
        <div class="container mx-auto px-4 pb-20">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="lg:col-span-1">
                    <div class="ui-card p-4">
                        <div class="space-y-2">
                            <div class="nav-item active block py-2 px-3 rounded cursor-pointer" onclick="showDashboard()">仪表盘</div>
                            <div class="nav-item block py-2 px-3 text-gray-400 rounded cursor-pointer" onclick="showProducts()">商品管理</div>
                            <div class="nav-item block py-2 px-3 text-gray-400 rounded cursor-pointer" onclick="showCards()">卡密管理</div>
                            <div class="nav-item block py-2 px-3 text-gray-400 rounded cursor-pointer" onclick="showOrders()">订单管理</div>
                            <div class="nav-item block py-2 px-3 text-gray-400 rounded cursor-pointer" onclick="showLogs()">操作日志</div>
                            <div class="nav-item block py-2 px-3 text-gray-400 rounded cursor-pointer" onclick="showSiteConfig()">系统设置</div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-3">
                    <div id="content">
                        <div class="ui-card p-4">
                            <div class="text-center py-10 text-gray-400">
                                <i class="fa fa-spinner fa-spin text-3xl mb-3"></i>
                                <div>加载中...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 添加商品弹窗 -->
    <div id="addProductModal" class="modal-backdrop fixed inset-0 bg-black/70 hidden items-center justify-center z-50">
        <div class="modal w-full max-w-lg p-6 mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-orange-400">添加商品</h3>
                <span onclick="closeModal('addProductModal')" class="text-gray-400 text-xl cursor-pointer hover:text-orange-400">×</span>
            </div>
            <form id="addProductForm">
                <input type="hidden" id="addProductId">
                <div class="space-y-3">
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">封面</label>
                        <div class="flex items-center gap-2">
                            <input type="text" id="addIcon" class="ui-input flex-1 px-3 py-2" value="" placeholder="请输入封面图片URL">
                            <button type="button" class="px-3 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 text-sm rounded" onclick="clearIconInput()">清除</button>
                        </div>
                        <div class="mt-2" id="addIconPreview">
                            <img id="addIconImg" src="" alt="封面预览" class="w-16 h-16 object-cover rounded hidden" onerror="this.style.display='none'">
                            <span id="addIconPlaceholder" class="text-gray-500 text-sm">暂无预览</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">商品名称</label>
                        <input type="text" id="addName" class="ui-input w-full px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">描述</label>
                        <textarea id="addDescription" class="ui-input w-full px-3 py-2" rows="2"></textarea>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">价格</label>
                        <input type="number" id="addPrice" class="ui-input w-full px-3 py-2" step="0.01" min="0" required>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">原价</label>
                        <input type="number" id="addOriginalPrice" class="ui-input w-full px-3 py-2" step="0.01" min="0">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">分类</label>
                        <input type="text" id="addCategory" class="ui-input w-full px-3 py-2" value="默认分类">
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">SKU规格（可选）</label>
                        <div class="border border-gray-700 rounded p-3">
                            <div id="skuList" class="space-y-2 mb-2 max-h-48 overflow-y-auto"></div>
                            <button type="button" class="w-full py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 text-sm rounded" onclick="addSkuRow()">+ 添加规格</button>
                        </div>
                        <p class="text-gray-500 text-xs mt-1">每个规格一行，格式：名称|价格（库存由卡密数量自动计算）</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" class="btn-outline px-4 py-2 text-sm" onclick="closeModal('addProductModal')">取消</button>
                    <button type="submit" class="btn-buy px-4 py-2 text-sm">保存</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 添加/编辑卡密弹窗 -->
    <div id="addCardsModal" class="modal-backdrop fixed inset-0 bg-black/70 hidden items-center justify-center z-50">
        <div class="modal w-full max-w-lg p-6 mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-orange-400">管理卡密</h3>
                <span onclick="closeModal('addCardsModal')" class="text-gray-400 text-xl cursor-pointer hover:text-orange-400">×</span>
            </div>
            <form id="addCardsForm">
                <div class="space-y-3">
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">选择商品</label>
                        <select id="addCardsProductId" class="ui-input w-full px-3 py-2" required></select>
                    </div>
                    <div id="addCardsSkuSection" style="display: none;">
                        <label class="block text-gray-400 text-sm mb-1">选择SKU（可选）</label>
                        <select id="addCardsSku" class="ui-input w-full px-3 py-2">
                            <option value="">无SKU</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-400 text-sm mb-1">卡密列表（每行一个）</label>
                        <textarea id="addCardsText" class="ui-input w-full px-3 py-2" rows="10" placeholder="卡密1&#10;卡密2&#10;卡密3"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" class="btn-outline px-4 py-2 text-sm" onclick="closeModal('addCardsModal')">取消</button>
                    <button type="submit" class="btn-buy px-4 py-2 text-sm">保存</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 查看卡密弹窗 -->
    <div id="viewCardsModal" class="modal-backdrop fixed inset-0 bg-black/70 hidden items-start justify-center z-50 pt-8">
        <div class="modal w-full max-w-2xl p-6 mx-4 max-h-[80vh] overflow-hidden flex flex-col">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-orange-400">卡密列表</h3>
                <span onclick="closeModal('viewCardsModal')" class="text-gray-400 text-xl cursor-pointer hover:text-orange-400">×</span>
            </div>
            <div class="flex-1 overflow-auto" id="cardsList">
                <div class="text-center py-5 text-gray-400">加载中...</div>
            </div>
        </div>
    </div>

    <!-- 订单详情弹窗 -->
    <div id="orderDetailModal" class="modal-backdrop fixed inset-0 bg-black/70 hidden items-center justify-center z-50">
        <div class="modal w-full max-w-lg p-6 mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-orange-400">订单详情</h3>
                <span onclick="closeModal('orderDetailModal')" class="text-gray-400 text-xl cursor-pointer hover:text-orange-400">×</span>
            </div>
            <div id="orderDetailContent">
                <div class="text-center py-5 text-gray-400">加载中...</div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = localStorage.getItem('adminCurrentPage') || 'dashboard';
        let productsData = [];

        function savePage(page) {
            currentPage = page;
            localStorage.setItem('adminCurrentPage', page);
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function setContent(html, callback) {
            const content = document.getElementById('content');
            content.style.opacity = '0';

            setTimeout(() => {
                content.innerHTML = `<div class="content-wrapper">${html}</div>`;
                content.style.opacity = '1';

                setTimeout(() => {
                    if (typeof callback === 'function') {
                        callback();
                    }
                }, 100);
            }, 150);
        }

        function showDashboard() {
            savePage('dashboard');
            updateNav();
            loadDashboard();
        }

        function showProducts() {
            savePage('products');
            updateNav();
            loadProducts();
        }

        function showCards() {
            savePage('cards');
            updateNav();
            loadCards();
        }

        function showOrders() {
            savePage('orders');
            updateNav();
            loadOrders('all');
        }

        function updateNav() {
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
                item.classList.remove('text-gray-400');
            });
            document.querySelectorAll('.nav-item').forEach(item => {
                if (item.textContent.trim() === getNavText(currentPage)) {
                    item.classList.add('active');
                } else {
                    item.classList.add('text-gray-400');
                }
            });
        }

        function getNavText(page) {
            const map = {
                'dashboard': '仪表盘',
                'products': '商品管理',
                'cards': '卡密管理',
                'orders': '订单管理',
                'logs': '操作日志',
                'siteconfig': '系统设置'
            };
            return map[page] || '';
        }

        function showLogs() {
            savePage('logs');
            updateNav();
            loadLogs();
        }

        function showSiteConfig() {
            savePage('siteconfig');
            updateNav();
            loadSiteConfig();
        }

        function loadSiteConfig() {
            fetch('../api.php?action=getAdminConfig')
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1) {
                        const config = data.data;
                        let html = `
                        <div class="ui-card p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-xl font-bold text-orange-400">系统设置</h2>
                            </div>
                            <form id="siteConfigForm" class="space-y-6">
                                <div class="border-b border-orange-500/30 pb-4">
                                    <h3 class="text-orange-400 text-sm mb-3">网站信息</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">网站名称</label>
                                            <input type="text" id="configSiteName" class="ui-input w-full px-3 py-2" value="${config.site?.name || ''}" placeholder="网站名称">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">网站标题</label>
                                            <input type="text" id="configSiteTitle" class="ui-input w-full px-3 py-2" value="${config.site?.title || ''}" placeholder="网站标题">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">网站Logo</label>
                                            <input type="text" id="configSiteLogo" class="ui-input w-full px-3 py-2" value="${config.site?.logo || ''}" placeholder="网站Logo图标">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">网站底部版权</label>
                                            <input type="text" id="configSiteFooter" class="ui-input w-full px-3 py-2" value="${config.site?.footer || ''}" placeholder="网站底部版权信息">
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <label class="block text-gray-400 text-sm mb-1">网站描述</label>
                                        <input type="text" id="configSiteDesc" class="ui-input w-full px-3 py-2" value="${config.site?.description || ''}" placeholder="网站描述">
                                    </div>
                                    <div class="mt-4">
                                        <label class="block text-gray-400 text-sm mb-1">网站关键词</label>
                                        <input type="text" id="configSiteKeywords" class="ui-input w-full px-3 py-2" value="${config.site?.keywords || ''}" placeholder="网站关键词">
                                    </div>
                                </div>
                                
                                <div class="border-b border-orange-500/30 pb-4">
                                    <h3 class="text-orange-400 text-sm mb-3">支付接口</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">支付API</label>
                                            <input type="text" id="configPayApiUrl" class="ui-input w-full px-3 py-2" value="${config.pay?.apiUrl || ''}" placeholder="v免签支付API">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">通讯密钥</label>
                                            <input type="text" id="configPaySecretKey" class="ui-input w-full px-3 py-2" value="${config.pay?.secretKey || ''}" placeholder="通讯密钥（32位）">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="border-b border-orange-500/30 pb-4">
                                    <h3 class="text-orange-400 text-sm mb-3">邮件配置</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">邮件API密钥</label>
                                            <input type="text" id="configMailApiSecret" class="ui-input w-full px-3 py-2" value="${config.mail?.apiSecret || ''}" placeholder="API密钥（32位）">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">SMTP服务器</label>
                                            <input type="text" id="configMailSmtpHost" class="ui-input w-full px-3 py-2" value="${config.mail?.smtpHost || 'smtp.qq.com'}" placeholder="如: smtp.qq.com">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">SMTP端口</label>
                                            <input type="text" id="configMailSmtpPort" class="ui-input w-full px-3 py-2" value="${config.mail?.smtpPort || '465'}" placeholder="如: 465">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">邮箱账号</label>
                                            <input type="text" id="configMailSmtpUser" class="ui-input w-full px-3 py-2" value="${config.mail?.smtpUser || ''}" placeholder="SMTP账号/发件人邮箱">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">邮箱密码</label>
                                            <input type="text" id="configMailSmtpPass" class="ui-input w-full px-3 py-2" value="${config.mail?.smtpPass || ''}" placeholder="SMTP授权码">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">发件人名称</label>
                                            <input type="text" id="configMailFromName" class="ui-input w-full px-3 py-2" value="${config.mail?.fromName || 'vPay Faka'}" placeholder="显示名称">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="border-b border-orange-500/30 pb-4">
                                    <h3 class="text-orange-400 text-sm mb-3">登录设置</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4"><div class="md:col-span-2"><label class="custom-checkbox"><input type="checkbox" id="configShowAdminLogin" value="1" ${config.show_admin_login == 1 ? "checked" : ""}><span class="checkmark"></span><span class="text-gray-400 text-sm ml-2">前台显示管理后台入口</span></label><p class="text-gray-500 text-xs mt-1 ml-7">仅隐藏按钮，后台路径不变，建议开启下方安全码</p><div class="mt-3"><label class="custom-checkbox"><input type="checkbox" id="configSslVerify" value="1" ${config.ssl_verify == 1 ? "checked" : ""}><span class="checkmark"></span><span class="text-gray-400 text-sm ml-2">SSL证书验证</span></label><p class="text-gray-500 text-xs mt-1 ml-7">关闭后可解决"SSL certificate problem"错误，但会降低安全性</p></div></div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">管理员用户名</label>
                                            <input type="text" id="configAdminUsername" class="ui-input w-full px-3 py-2" value="${config.admin?.username || ''}" placeholder="管理员用户名">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">管理员密码</label>
                                            <input type="text" id="configAdminPassword" class="ui-input w-full px-3 py-2" value="${config.admin?.password || ''}" placeholder="管理员密码">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-gray-400 text-sm mb-1">后台安全码</label>
                                            <input type="text" id="configAdminAccessKey" class="ui-input w-full px-3 py-2" value="${escapeHtml(config.admin?.access_key || '')}" placeholder="留空不启用，设置后需带 ?key=xxx 访问后台">
                                            <p class="text-gray-500 text-xs mt-1">设置后只有通过正确链接才能访问后台，不知道安全码无法打开登录页</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="border-b border-orange-500/30 pb-4">
                                    <h3 class="text-orange-400 text-sm mb-3">客服设置</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">客服名称</label>
                                            <input type="text" id="configKfName" class="ui-input w-full px-3 py-2" value="${config.kf?.name || '在线客服'}" placeholder="客服名称">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">客服QQ</label>
                                            <input type="text" id="configKfQq" class="ui-input w-full px-3 py-2" value="${config.kf?.qq || ''}" placeholder="客服QQ号码">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">客服微信</label>
                                            <input type="text" id="configKfWechat" class="ui-input w-full px-3 py-2" value="${config.kf?.wechat || ''}" placeholder="客服微信号">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">客服电话</label>
                                            <input type="text" id="configKfPhone" class="ui-input w-full px-3 py-2" value="${config.kf?.phone || ''}" placeholder="客服电话号码">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-gray-400 text-sm mb-1">客服邮箱</label>
                                            <input type="text" id="configKfEmail" class="ui-input w-full px-3 py-2" value="${config.kf?.email || ''}" placeholder="客服邮箱地址">
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <h3 class="text-orange-400 text-sm mb-3">首页设置</h3>
                                    <div>
                                        <label class="block text-gray-400 text-sm mb-2">公告</label>
                                        <textarea id="configAnnouncement" class="ui-input w-full px-3 py-2 h-20 resize-none" placeholder="请输入公告，每行一条">${config.announcement || ''}</textarea>
                                        <p class="text-gray-500 text-xs mt-1">公告内容会显示在首页，换行用回车键</p>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">视频/封面标题</label>
                                            <input type="text" id="configVideoTitle" class="ui-input w-full px-3 py-2" value="${config.video_title || '服务介绍'}" placeholder="视频标题">
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-sm mb-1">视频链接</label>
                                            <input type="text" id="configVideoUrl" class="ui-input w-full px-3 py-2" value="${config.video_url || ''}" placeholder="在线视频链接">
                                        </div>

                                    </div>
                                </div>
                                
                                <div class="flex justify-end gap-3 pt-4">
                                    <button type="button" onclick="resetSiteConfig()" class="btn-outline px-6 py-2">重置</button>
                                    <button type="submit" class="btn-buy px-6 py-2">保存设置</button>
                                </div>
                            </form>
                        </div>
                    `;
                        setContent(html, function() {
                            document.getElementById('siteConfigForm').onsubmit = function(e) {
                                e.preventDefault();
                                saveSiteConfig();
                            };
                        });
                    }
                });
        }

        function saveSiteConfig() {
            const params = new URLSearchParams();
            params.append('csrf_token', '<?php echo $csrfToken; ?>');
            params.append('announcement', document.getElementById('configAnnouncement').value);
            params.append('video_title', document.getElementById('configVideoTitle').value);
            params.append('video_url', document.getElementById('configVideoUrl').value);
            params.append('site_name', document.getElementById('configSiteName').value);
            params.append('site_title', document.getElementById('configSiteTitle').value);
            params.append('site_description', document.getElementById('configSiteDesc').value);
            params.append('site_keywords', document.getElementById('configSiteKeywords').value);
            params.append('site_logo', document.getElementById('configSiteLogo').value);
            params.append('site_footer', document.getElementById('configSiteFooter').value);
            params.append('pay_apiUrl', document.getElementById('configPayApiUrl').value);
            params.append('pay_secretKey', document.getElementById('configPaySecretKey').value);
            params.append('mail_apiSecret', document.getElementById('configMailApiSecret').value);
            params.append('mail_smtpHost', document.getElementById('configMailSmtpHost').value);
            params.append('mail_smtpPort', document.getElementById('configMailSmtpPort').value);
            params.append('mail_smtpUser', document.getElementById('configMailSmtpUser').value);
            params.append('mail_smtpPass', document.getElementById('configMailSmtpPass').value);
            params.append('mail_fromName', document.getElementById('configMailFromName').value);
            params.append('admin_username', document.getElementById('configAdminUsername').value);
            params.append('admin_password', document.getElementById('configAdminPassword').value);
            params.append('admin_access_key', document.getElementById('configAdminAccessKey').value);
            var sslChk = document.getElementById('configSslVerify');
            params.append('ssl_verify', sslChk && sslChk.checked ? '1' : '0');
            params.append('kf_name', document.getElementById('configKfName').value);
            params.append('kf_qq', document.getElementById('configKfQq').value);
            params.append('kf_wechat', document.getElementById('configKfWechat').value);
            params.append('kf_phone', document.getElementById('configKfPhone').value);
            params.append('kf_email', document.getElementById('configKfEmail').value);
            var chk = document.getElementById('configShowAdminLogin');
            params.append('show_admin_login', chk && chk.checked ? '1' : '0');

            fetch('../api.php?action=updateSiteConfig', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params.toString()
                })
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1) {
                        showToast('设置已保存', 'success');
                        refreshLogsIfNeeded();
                    } else {
                        showToast('保存失败: ' + data.msg, 'error');
                    }
                });
        }

        function resetSiteConfig() {
            showConfirm('确定要重置所有配置到默认值吗？', function() {
                const params = new URLSearchParams();
                params.append('csrf_token', '<?php echo $csrfToken; ?>');

                fetch('../api.php?action=resetConfig', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: params.toString()
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.code === 1) {
                            showToast('配置已重置', 'success');
                            loadSiteConfig();
                            refreshLogsIfNeeded();
                        } else {
                            showToast('重置失败: ' + data.msg, 'error');
                        }
                    });
            });
        }

        function loadLogs(page) {
            if (!page) page = 1;
            fetch('../api.php?action=getAdminLogs&page=' + page)
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1) {
                        var total = data.total || 0;
                        var currentPage = data.page || 1;
                        var totalPages = data.pages || 1;

                        var html = '';
                        html += '<div class="ui-card p-4">';
                        html += '<div class="flex justify-between items-center mb-4">';
                        html += '<h3 class="text-orange-400 font-bold">操作日志</h3>';
                        html += '<span class="text-gray-400 text-sm">共 ' + total + ' 条记录</span>';
                        html += '</div>';
                        html += '<div class="overflow-x-auto">';
                        html += '<table class="w-full">';
                        html += '<thead>';
                        html += '<tr class="text-gray-400 text-sm border-b border-orange-500/20">';
                        html += '<th class="text-left py-3 px-2">操作类型</th>';
                        html += '<th class="text-left py-3 px-2">操作详情</th>';
                        html += '<th class="text-left py-3 px-2">操作者ID</th>';
                        html += '<th class="text-left py-3 px-2">IP地址</th>';
                        html += '<th class="text-left py-3 px-2">操作时间</th>';
                        html += '</tr>';
                        html += '</thead>';
                        html += '<tbody>';

                        for (var i = 0; i < data.data.length; i++) {
                            var log = data.data[i];
                            var actionClass = log.action === 'login' ? 'text-green-400' :
                                log.action === 'login_failed' ? 'text-red-400' : 'text-orange-400';
                            var action = escapeHtml(log.action || '');
                            var details = escapeHtml(log.details || '');
                            var ip = escapeHtml(log.ip || '');
                            html += '<tr class="border-b border-orange-500/10 hover:bg-white/5 transition-colors">';
                            html += '<td class="py-3 px-2"><span class="' + actionClass + ' text-sm font-medium">' + action + '</span></td>';
                            html += '<td class="py-3 px-2 text-gray-300 text-sm">' + details + '</td>';
                            html += '<td class="py-3 px-2 text-gray-400 text-sm">' + log.admin_id + '</td>';
                            html += '<td class="py-3 px-2 text-gray-400 text-sm">' + ip + '</td>';
                            html += '<td class="py-3 px-2 text-gray-400 text-sm">' + log.created_at + '</td>';
                            html += '</tr>';
                        }

                        html += '</tbody>';
                        html += '</table>';
                        html += '</div>';

                        if (totalPages > 1) {
                            html += '<div class="flex justify-center items-center mt-4 gap-2">';

                            if (currentPage > 1) {
                                html += '<button class="px-3 py-1 text-sm border border-orange-500/30 rounded hover:bg-orange-500/10 transition-colors" onclick="loadLogs(' + (currentPage - 1) + ')">上一页</button>';
                            } else {
                                html += '<button disabled class="px-3 py-1 text-sm border border-orange-500/30 rounded opacity-50 cursor-not-allowed">上一页</button>';
                            }

                            html += '<span class="text-gray-400 text-sm">第 ' + currentPage + ' / ' + totalPages + ' 页</span>';

                            if (currentPage < totalPages) {
                                html += '<button class="px-3 py-1 text-sm border border-orange-500/30 rounded hover:bg-orange-500/10 transition-colors" onclick="loadLogs(' + (currentPage + 1) + ')">下一页</button>';
                            } else {
                                html += '<button disabled class="px-3 py-1 text-sm border border-orange-500/30 rounded opacity-50 cursor-not-allowed">下一页</button>';
                            }

                            html += '</div>';
                        }

                        html += '</div>';
                        setContent(html);
                    }
                });
        }

        function getChartColor(index) {
            const colors = ['#f97316', '#fb923c', '#fbbf24', '#34d399', '#60a5fa'];
            return colors[index % colors.length];
        }

        function renderAreaChart(data) {
            if (!data || data.length === 0) return '';
            const maxValue = Math.max(...data.map(item => item.count));
            const padding = {
                top: 20,
                right: 25,
                bottom: 40,
                left: 40
            };
            const width = 500 - padding.left - padding.right;
            const height = 120 - padding.top - padding.bottom;
            const stepX = width / (data.length - 1);

            let points = [];
            let linePath = 'M ';
            data.forEach((item, index) => {
                const x = padding.left + index * stepX;
                const y = padding.top + height - (item.count / maxValue) * height;
                points.push({
                    x,
                    y,
                    count: item.count
                });
                linePath += `${x},${y} `;
            });

            let areaPath = `M ${padding.left},${padding.top + height + 8} `;
            data.forEach((item, index) => {
                const x = padding.left + index * stepX;
                const y = padding.top + height - (item.count / maxValue) * height;
                if (index === 0) {
                    areaPath += `L ${x},${y} `;
                } else {
                    const prevX = padding.left + (index - 1) * stepX;
                    const prevY = padding.top + height - (data[index - 1].count / maxValue) * height;
                    const cpX = (prevX + x) / 2;
                    areaPath += `C ${cpX},${prevY} ${cpX},${y} ${x},${y} `;
                }
            });
            areaPath += `L ${padding.left + width},${padding.top + height + 8} Z`;

            let dotElements = '';
            points.forEach((point, index) => {
                dotElements += `
                    <circle cx="${point.x}" cy="${point.y}" r="5" fill="#f97316" />
                    <circle cx="${point.x}" cy="${point.y}" r="2.5" fill="#ffffff" />
                    <text x="${point.x}" y="${point.y - 10}" text-anchor="middle" fill="#f97316" font-size="10" font-weight="500">${point.count}</text>
                `;
            });

            let gridLines = '';
            for (let i = 0; i <= 4; i++) {
                const y = padding.top + (height / 4) * i;
                gridLines += `<line x1="${padding.left}" y1="${y}" x2="${padding.left + width}" y2="${y}" stroke="#374151" stroke-width="1" stroke-dasharray="3,3" />`;
            }

            return `
                ${gridLines}
                <path d="${areaPath}" fill="url(#areaGradient)" />
                <path d="${linePath}" fill="none" stroke="#f97316" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                ${dotElements}
                ${data.map((item, index) => {
                    const x = padding.left + index * stepX;
                    const y = padding.top + height + 25;
                    return `<text x="${x}" y="${y}" text-anchor="middle" fill="#9ca3af" font-size="11">${item.date}</text>`;
                }).join('')}
            `;
        }

        function renderDonutChart(data) {
            if (!data || data.length === 0) return '';
            const centerX = 50;
            const centerY = 50;
            const radius = 40;
            const innerRadius = 28;
            let currentAngle = -Math.PI / 2;

            let paths = '';
            data.forEach((item, index) => {
                const angle = (item.percentage / 100) * 2 * Math.PI;
                const startAngle = currentAngle;
                const endAngle = currentAngle + angle;
                currentAngle = endAngle;

                const x1 = centerX + radius * Math.cos(startAngle);
                const y1 = centerY + radius * Math.sin(startAngle);
                const x2 = centerX + radius * Math.cos(endAngle);
                const y2 = centerY + radius * Math.sin(endAngle);
                const largeArcFlag = angle > Math.PI ? 1 : 0;

                const x1Inner = centerX + innerRadius * Math.cos(endAngle);
                const y1Inner = centerY + innerRadius * Math.sin(endAngle);
                const x2Inner = centerX + innerRadius * Math.cos(startAngle);
                const y2Inner = centerY + innerRadius * Math.sin(startAngle);

                const color = getChartColor(index);

                paths += `<path d="M ${x1},${y1} A ${radius},${radius} 0 ${largeArcFlag},1 ${x2},${y2} L ${x1Inner},${y1Inner} A ${innerRadius},${innerRadius} 0 ${largeArcFlag},0 ${x2Inner},${y2Inner} Z" fill="${color}" />`;
            });

            return paths;
        }

        function loadDashboard(page = 1) {
            fetch(`../api.php?action=getDashboard&page=${page}`)
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1) {
                        const d = data.data;
                        let html = `
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-orange-400">管理后台</h2>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="ui-card p-4 text-center">
                                <div class="text-2xl font-bold text-blue-400 mb-1">${d.productCount}</div>
                                <div class="text-gray-400 text-sm">商品总数</div>
                            </div>
                            <div class="ui-card p-4 text-center">
                                <div class="text-2xl font-bold text-green-400 mb-1">${d.completedCount}</div>
                                <div class="text-gray-400 text-sm">已完成订单</div>
                            </div>
                            <div class="ui-card p-4 text-center">
                                <div class="text-2xl font-bold text-yellow-400 mb-1">${d.pendingCount}</div>
                                <div class="text-gray-400 text-sm">待支付订单</div>
                            </div>
                            <div class="ui-card p-4 text-center">
                                <div class="text-2xl font-bold text-orange-400 mb-1">${d.totalSales}</div>
                                <div class="text-gray-400 text-sm">总销量</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="ui-card p-5 lg:col-span-2">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-gray-300 font-medium flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                        销售趋势
                                    </h4>
                                    <span class="text-xs text-gray-500">近7天</span>
                                </div>
                                <div class="h-48">
                                    <svg viewBox="0 0 500 140" class="w-full h-full">
                                        <defs>
                                            <linearGradient id="areaGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" style="stop-color:#f97316;stop-opacity:0.5" />
                                                <stop offset="100%" style="stop-color:#f97316;stop-opacity:0.08" />
                                            </linearGradient>
                                        </defs>
                                            ${renderAreaChart(d.salesTrend)}
                                    </svg>
                                </div>
                            </div>
                            <div class="ui-card p-5">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-gray-300 font-medium flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                                        </svg>
                                        商品占比
                                    </h4>
                                </div>
                                <div class="flex flex-col items-center">
                                    <div class="w-36 h-36 mb-3">
                                        <svg viewBox="0 0 100 100" class="w-full h-full">
                                            ${renderDonutChart(d.productSales)}
                                            <circle cx="50" cy="50" r="25" fill="#1f2937" />
                                            <text x="50" y="47" text-anchor="middle" fill="#9ca3af" font-size="10">总销量</text>
                                            <text x="50" y="62" text-anchor="middle" fill="#f97316" font-size="14" font-weight="bold">${d.totalSales}</text>
                                        </svg>
                                    </div>
                                    <div class="w-full space-y-2">
                                        ${d.productSales.slice(0, 4).map((item, index) => `
                                            <div class="flex items-center justify-between text-xs">
                                                <div class="flex items-center">
                                                    <div class="w-2 h-2 rounded-full mr-2" style="background-color: ${getChartColor(index)}"></div>
                                                    <span class="text-gray-400 truncate">${escapeHtml(item.name)}</span>
                                                </div>
                                                <span class="text-gray-300">${item.percentage}%</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            </div>
                            <div class="ui-card p-5 lg:col-span-2">
                                <h4 class="text-gray-300 font-medium mb-4 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                    最近订单
                                </h4>
                                <div class="space-y-3">
                        `;
                        d.recentOrders.forEach(order => {
                            html += `
                            <div class="flex flex-wrap justify-between items-center py-2 border-b border-orange-500/20">
                                <div class="flex-1 min-w-0">
                                    <div class="text-gray-300 text-sm truncate">${order.payId}</div>
                                    <div class="text-gray-500 text-xs">${order.product_name}</div>
                                </div>
                                <div class="flex gap-3 ml-4">
                                    <span class="text-orange-400 text-sm">¥${order.price}</span>
                                    <span class="text-xs px-2 py-1 rounded ${order.status == 'completed' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400'}">
                                        ${order.status == 'completed' ? '已完成' : '待支付'}
                                    </span>
                                </div>
                            </div>
                        `;
                        });
                        html += `
                                </div>
                                ${d.pagination.totalPages > 1 ? `
                                <div class="flex justify-center items-center mt-4 gap-2">
                                    <button onclick="loadDashboard(${d.pagination.page - 1})" ${d.pagination.page <= 1 ? 'disabled' : ''} class="px-3 py-1 text-sm border border-orange-500/30 rounded ${d.pagination.page <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-orange-500/10 transition-colors'}">上一页</button>
                                    <span class="text-gray-400 text-sm">第 ${d.pagination.page} / ${d.pagination.totalPages} 页</span>
                                    <button onclick="loadDashboard(${d.pagination.page + 1})" ${d.pagination.page >= d.pagination.totalPages ? 'disabled' : ''} class="px-3 py-1 text-sm border border-orange-500/30 rounded ${d.pagination.page >= d.pagination.totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-orange-500/10 transition-colors'}">下一页</button>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                        setContent(html);
                    }
                });
        }

        function showImagePreview(src) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/90 flex items-center justify-center z-50 cursor-pointer';
            modal.onclick = function() {
                modal.remove();
            };

            let imgSrc = src;
            if (src && !src.startsWith('http://') && !src.startsWith('https://') && !src.startsWith('/')) {
                imgSrc = '../' + src;
            }

            const img = document.createElement('img');
            img.src = imgSrc;
            img.className = 'max-w-[90vw] max-h-[90vh] object-contain rounded-lg';
            img.onclick = function(e) {
                e.stopPropagation();
            };

            modal.appendChild(img);
            document.body.appendChild(modal);
        }

        function loadProducts(page) {
            if (!page) page = 1;
            fetch('../api.php?action=getProducts&page=' + page)
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1) {
                        productsData = data.data;
                        let html = `
                        <div class="ui-card p-4">
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex items-center gap-4">
                                    <h3 class="text-orange-400 font-bold">商品管理</h3>
                                    <label class="custom-checkbox">
                                        <input type="checkbox" id="selectAllProducts" onclick="toggleSelectAllProducts()">
                                        <span class="checkmark"></span>
                                        <span class="text-gray-400 text-sm ml-2">全选</span>
                                    </label>
                                </div>
                                <div class="flex gap-2">
                                    <button class="px-4 py-2 text-sm border border-red-500/30 text-red-400 rounded hover:bg-red-500/10 transition-colors" onclick="batchDeleteProducts()" id="batchDeleteBtn" disabled>批量删除</button>
                                    <button class="btn-buy px-4 py-2 text-sm" onclick="openAddProductModal()">添加商品</button>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-gray-400 text-sm border-b border-orange-500/20">
                                            <th class="text-left py-3 px-2 w-14"></th>
                                            <th class="text-left py-3 px-2">封面</th>
                                            <th class="text-left py-3 px-2">商品名称</th>
                                            <th class="text-left py-3 px-2">价格</th>
                                            <th class="text-left py-3 px-2">库存</th>
                                            <th class="text-left py-3 px-2">销量</th>
                                            <th class="text-left py-3 px-2">分类</th>
                                            <th class="text-left py-3 px-2">操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                    `;
                        data.data.forEach(product => {
                            const icon = escapeHtml(product.icon || '');
                            const name = escapeHtml(product.name || '');
                            const category = escapeHtml(product.category || '');
                            html += `
                            <tr class="border-b border-orange-500/10 hover:bg-white/5 transition-colors">
                                <td class="py-3 px-2">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" class="product-checkbox" value="${product.id}" onchange="updateBatchDeleteBtn()">
                                        <span class="checkmark"></span>
                                    </label>
                                </td>
                                <td class="py-3 px-2">
                                <img src="${icon ? (icon.startsWith('http') ? icon : '../' + icon) : ''}" alt="封面" class="w-12 h-12 object-cover rounded cursor-pointer hover:ring-2 hover:ring-orange-500 transition-all" onclick="showImagePreview('${icon}')" onerror="this.style.display='none'">
                            </td>
                                <td class="py-3 px-2 text-gray-300">${name}</td>
                                <td class="py-3 px-2 text-orange-400">¥${product.price}</td>
                                <td class="py-3 px-2 text-gray-400">${product.stock}</td>
                                <td class="py-3 px-2 text-green-400">${product.sales}</td>
                                <td class="py-3 px-2 text-gray-400">${category}</td>
                                <td class="py-3 px-2">
                                    <button class="text-orange-400 text-sm hover:text-orange-300 mr-3" onclick="openEditProductModal(${product.id})">编辑</button>
                                    <button class="text-red-400 text-sm hover:text-red-300" onclick="deleteProduct(${product.id})">删除</button>
                                </td>
                            </tr>
                        `;
                        });
                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;

                        var total = data.total || 0;
                        var currentPage = data.page || 1;
                        var totalPages = data.pages || 1;

                        if (totalPages > 1) {
                            html += '<div class="flex justify-center items-center mt-4 gap-2">';
                            if (currentPage > 1) {
                                html += '<button class="px-3 py-1 text-sm border border-orange-500/30 rounded hover:bg-orange-500/10 transition-colors" onclick="loadProducts(' + (currentPage - 1) + ')">上一页</button>';
                            } else {
                                html += '<button disabled class="px-3 py-1 text-sm border border-orange-500/30 rounded opacity-50 cursor-not-allowed">上一页</button>';
                            }
                            html += '<span class="text-gray-400 text-sm">第 ' + currentPage + ' / ' + totalPages + ' 页</span>';
                            if (currentPage < totalPages) {
                                html += '<button class="px-3 py-1 text-sm border border-orange-500/30 rounded hover:bg-orange-500/10 transition-colors" onclick="loadProducts(' + (currentPage + 1) + ')">下一页</button>';
                            } else {
                                html += '<button disabled class="px-3 py-1 text-sm border border-orange-500/30 rounded opacity-50 cursor-not-allowed">下一页</button>';
                            }
                            html += '</div>';
                        }

                        html += '</div>';
                        setContent(html);
                    }
                });
        }

        function loadCards() {
            fetch('../api.php?action=getProducts')
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1) {
                        productsData = data.data;
                        let html = `
                        <div class="ui-card p-4">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-orange-400 font-bold">卡密管理</h3>
                                    <button class="btn-buy px-4 py-2 text-sm" onclick="openAddCardsModal()">添加卡密</button>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="text-gray-400 text-sm border-b border-orange-500/20">
                                                <th class="text-left py-3 px-2">商品名称</th>
                                                <th class="text-left py-3 px-2">SKU名称</th>
                                                <th class="text-left py-3 px-2">卡密数量</th>
                                                <th class="text-left py-3 px-2">操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                        `;
                        const promises = data.data.map(product => {
                            return fetch(`../api.php?action=getCardsGroupBySku&productId=${product.id}`)
                                .then(r => r.json())
                                .then(d => {
                                    return {
                                        product,
                                        skuData: d.code === 1 ? d.data : []
                                    };
                                });
                        });

                        Promise.all(promises).then(results => {
                            let html = `
                            <div class="ui-card p-4">
                                    <div class="flex justify-between items-center mb-4">
                                        <div class="flex items-center">
                                            <h3 class="text-orange-400 font-bold">卡密管理</h3>
                                            <label class="custom-checkbox ml-4">
                                                <input type="checkbox" id="selectAllCards" onclick="toggleSelectAllCards()">
                                                <span class="checkmark"></span>
                                                <span class="text-gray-400 text-sm ml-2">全选</span>
                                            </label>
                                        </div>
                                        <div class="flex gap-2">
                                            <button class="px-4 py-2 text-sm border border-red-500/30 text-red-400 rounded hover:bg-red-500/10 transition-colors" onclick="batchDeleteCards()" id="batchDeleteCardsBtn" disabled>批量删除</button>
                                            <button class="btn-buy px-4 py-2 text-sm" onclick="openAddCardsModal()">添加卡密</button>
                                        </div>
                                    </div>
                                    <div class="overflow-x-auto">
                                        <table class="w-full">
                                            <thead>
                                                <tr class="text-gray-400 text-sm border-b border-orange-500/20">
                                                    <th class="text-left py-3 px-2 w-14"></th>
                                                    <th class="text-left py-3 px-2">商品名称</th>
                                                    <th class="text-left py-3 px-2">SKU名称</th>
                                                    <th class="text-left py-3 px-2">卡密数量</th>
                                                    <th class="text-left py-3 px-2">操作</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                            `;

                            results.forEach(({
                                product,
                                skuData
                            }) => {
                                const name = escapeHtml(product.name || '');

                                if (skuData && skuData.length > 0) {
                                    skuData.forEach((skuItem) => {
                                        const sku = skuItem.sku || '无SKU';
                                        const count = skuItem.count || 0;
                                        const skuEnc = encodeURIComponent(skuItem.sku || '');
                                        html += `
                                        <tr class="border-b border-orange-500/10 hover:bg-white/5 transition-colors">
                                            <td class="py-3 px-2">
                                                <label class="custom-checkbox">
                                                    <input type="checkbox" class="card-checkbox" value="${product.id}_${skuEnc}" onchange="updateCardsSelectAll()">
                                                    <span class="checkmark"></span>
                                                </label>
                                            </td>
                                            <td class="py-3 px-2 text-gray-300">${name}</td>
                                            <td class="py-3 px-2 text-gray-400">${sku}</td>
                                            <td class="py-3 px-2 text-orange-400">${count}</td>
                                            <td class="py-3 px-2">
                                                <button onclick="viewCards(${product.id}, 1, '${skuItem.sku}')" class="text-orange-400 text-sm">查看卡密</button>
                                            </td>
                                        </tr>
                                        `;
                                    });
                                } else {
                                    html += `
                                    <tr class="border-b border-orange-500/10 hover:bg-white/5 transition-colors">
                                        <td class="py-3 px-2"></td>
                                        <td class="py-3 px-2 text-gray-300">${name}</td>
                                        <td class="py-3 px-2 text-gray-400">-</td>
                                        <td class="py-3 px-2 text-orange-400">-</td>
                                        <td class="py-3 px-2 text-gray-500 text-sm">-</td>
                                    </tr>
                                    `;
                                }
                            });

                            html += `
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            `;

                            setContent(html);
                        });
                    }
                });
        }

        function loadOrders(status, keyword = '', page = 1) {
            const url = `../api.php?action=getOrders&status=${status}&page=${page}${keyword ? '&keyword=' + encodeURIComponent(keyword) : ''}`;
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1) {
                        const currentPage = data.page || 1;
                        const totalPages = data.pages || 1;
                        let html = `
                        <div class="ui-card p-4">
                            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
                                <h3 class="text-orange-400 font-bold">订单管理</h3>
                                <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
                                    <div class="flex gap-2">
                                        <input type="text" id="orderSearchKeyword" class="ui-input px-3 py-1 text-sm w-48" placeholder="搜索订单号/商品/邮箱" value="${escapeHtml(keyword)}" onkeypress="if(event.key==='Enter')searchOrders()">
                                        <button class="btn-buy px-3 py-1 text-sm" onclick="searchOrders()">搜索</button>
                                        ${keyword ? '<button class="btn-outline px-3 py-1 text-sm" onclick="clearOrderSearch()">清除</button>' : ''}
                                    </div>
                                    <div class="flex gap-2">
                                        <button class="px-3 py-1 text-sm rounded transition-colors ${status == 'all' ? 'bg-orange-500/20 text-orange-400' : 'text-gray-400 hover:bg-white/5'}" onclick="loadOrders('all', '${keyword || ''}', 1)">全部</button>
                                        <button class="px-3 py-1 text-sm rounded transition-colors ${status == 'pending' ? 'bg-orange-500/20 text-orange-400' : 'text-gray-400 hover:bg-white/5'}" onclick="loadOrders('pending', '${keyword || ''}', 1)">待支付</button>
                                        <button class="px-3 py-1 text-sm rounded transition-colors ${status == 'completed' ? 'bg-orange-500/20 text-orange-400' : 'text-gray-400 hover:bg-white/5'}" onclick="loadOrders('completed', '${keyword || ''}', 1)">已完成</button>
                                    </div>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-gray-400 text-sm border-b border-orange-500/20">
                                            <th class="text-left py-3 px-2">订单号</th>
                                            <th class="text-left py-3 px-2">云端订单号</th>
                                            <th class="text-left py-3 px-2">商品</th>
                                            <th class="text-left py-3 px-2">金额</th>
                                            <th class="text-left py-3 px-2">状态</th>
                                            <th class="text-left py-3 px-2">卡密</th>
                                            <th class="text-left py-3 px-2">创建时间</th>
                                            <th class="text-left py-3 px-2">操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                    `;
                        if (data.data.length === 0) {
                            html += `
                                <tr>
                                    <td colspan="8" class="py-10 text-center text-gray-400">
                                        ${keyword ? '未找到匹配的订单' : '暂无订单'}
                                    </td>
                                </tr>
                            `;
                        } else {
                            data.data.forEach(order => {
                                const payId = escapeHtml(order.payId || '');
                                const orderId = escapeHtml(order.orderId || '');
                                const productName = escapeHtml(order.product_name || '');
                                const cards = order.cards ? order.cards.map(c => escapeHtml(c)).join('<br>') : '-';
                                const orderData = encodeURIComponent(JSON.stringify(order));

                                const truncate = (str, len) => str.length > len ? str.substring(0, len) + '...' : str;
                                html += `
                                <tr class="border-b border-orange-500/10 hover:bg-white/5 transition-colors">
                                    <td class="py-3 px-2 text-gray-300 text-sm truncate max-w-32" title="${payId}">${truncate(payId, 20)}</td>
                                    <td class="py-3 px-2 text-gray-400 text-sm truncate max-w-32" title="${orderId}">${truncate(orderId, 20)}</td>
                                    <td class="py-3 px-2 text-gray-300 text-sm">${productName}</td>
                                    <td class="py-3 px-2 text-orange-400">¥${order.price}</td>
                                    <td class="py-3 px-2">
                                        <span class="text-xs px-2 py-1 rounded ${order.status == 'completed' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400'}">
                                            ${order.status == 'completed' ? '已完成' : '待支付'}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2">
                                        <div class="font-mono text-xs text-gray-400 max-w-32 truncate">${cards}</div>
                                    </td>
                                    <td class="py-3 px-2 text-gray-400 text-sm">${order.created_at}</td>
                                    <td class="py-3 px-2">
                                        ${order.status != 'completed' ? `
                                        <button class="text-sm px-3 py-1 rounded bg-orange-500/20 text-orange-400 hover:bg-orange-500/30 transition-colors mr-2" onclick="completeOrder('${orderId}')">
                                            手动完成
                                        </button>
                                        ` : ''}
                                        <button class="text-sm px-3 py-1 rounded bg-blue-500/20 text-blue-400 hover:bg-blue-500/30 transition-colors" onclick="showOrderDetail('${orderData}')">
                                            查看详情
                                        </button>
                                    </td>
                                </tr>
                            `;
                            });
                        }
                        html += `
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex justify-center items-center mt-4 gap-2">
                                <button onclick="loadOrders('${status}', '${keyword || ''}', ${currentPage - 1})" ${currentPage <= 1 ? 'disabled' : ''} class="px-3 py-1 text-sm border border-orange-500/30 rounded ${currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-orange-500/10 transition-colors'}">上一页</button>
                                <span class="text-gray-400 text-sm">第 ${currentPage} / ${totalPages} 页</span>
                                <button onclick="loadOrders('${status}', '${keyword || ''}', ${currentPage + 1})" ${currentPage >= totalPages ? 'disabled' : ''} class="px-3 py-1 text-sm border border-orange-500/30 rounded ${currentPage >= totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-orange-500/10 transition-colors'}">下一页</button>
                            </div>
                        </div>
                    `;
                        setContent(html);
                    }
                });
        }

        function searchOrders() {
            const keyword = document.getElementById('orderSearchKeyword').value.trim();
            loadOrders('all', keyword);
        }

        function clearOrderSearch() {
            loadOrders('all', '');
        }

        function showOrderDetail(orderDataStr) {
            const order = JSON.parse(decodeURIComponent(orderDataStr));
            const content = document.getElementById('orderDetailContent');

            let cardsHtml = '';
            if (order.cards && order.cards.length > 0) {
                cardsHtml = `
                    <div class="mb-4">
                        <h4 class="text-gray-400 text-sm mb-2">卡密信息</h4>
                        <div class="bg-black/30 border border-orange-500/20 rounded p-3">
                            ${order.cards.map((card, idx) => `
                                <div class="flex items-center py-1 border-b border-orange-500/10 last:border-b-0">
                                    <span class="text-gray-400 text-sm w-16">卡密${idx + 1}:</span>
                                    <code class="flex-1 text-orange-400 font-mono text-sm break-all">${escapeHtml(card)}</code>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }

            content.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <h4 class="text-gray-400 text-sm mb-2">基本信息</h4>
                        <div class="bg-black/30 border border-orange-500/20 rounded p-3 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-400 text-sm">订单号:</span>
                                <span class="text-white text-sm">${escapeHtml(order.payId || '')}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400 text-sm">云端订单号:</span>
                                <span class="text-white text-sm">${escapeHtml(order.orderId || '')}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400 text-sm">商品名称:</span>
                                <span class="text-white text-sm">${escapeHtml(order.product_name || '')}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400 text-sm">金额:</span>
                                <span class="text-orange-400 text-sm font-medium">¥${order.price}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400 text-sm">状态:</span>
                                <span class="text-xs px-2 py-0.5 rounded ${order.status == 'completed' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400'}">
                                    ${order.status == 'completed' ? '已完成' : '待支付'}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400 text-sm">数量:</span>
                                <span class="text-white text-sm">${escapeHtml(order.quantity || '1')}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400 text-sm">支付时间:</span>
                                <span class="text-white text-sm">${escapeHtml(order.paid_at || '-')}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400 text-sm">创建时间:</span>
                                <span class="text-white text-sm">${escapeHtml(order.created_at || '')}</span>
                            </div>
                        </div>
                    </div>
                    ${cardsHtml}
                </div>
            `;

            document.getElementById('orderDetailModal').style.display = 'flex';
        }

        function clearIconInput() {
            document.getElementById('addIcon').value = '';
            document.getElementById('addIconImg').src = '';
            document.getElementById('addIconImg').style.display = 'none';
            document.getElementById('addIconPlaceholder').style.display = 'block';
        }

        function addSkuRow(name = '', price = '', stock = '') {
            const skuList = document.getElementById('skuList');
            const maxSkus = 4;
            const currentCount = skuList.children.length;

            if (currentCount >= maxSkus) {
                showToast(`最多只能添加 ${maxSkus} 个SKU规格`, 'error');
                return;
            }

            const rowId = 'skuRow_' + Date.now();
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.id = rowId;
            row.innerHTML = `
                <input type="text" class="flex-1 px-3 py-2 bg-gray-800 border border-gray-700 rounded text-white text-sm outline-none focus:border-orange-500 transition-colors" placeholder="规格名称" value="${name}">
                <input type="number" class="w-24 px-3 py-2 bg-gray-800 border border-gray-700 rounded text-white text-sm outline-none focus:border-orange-500 transition-colors" step="0.01" min="0" placeholder="价格" value="${price}">
                <button type="button" class="px-2 py-2 bg-red-600 hover:bg-red-500 text-white text-sm rounded" onclick="removeSkuRow('${rowId}')">×</button>
            `;
            skuList.appendChild(row);
        }

        function removeSkuRow(rowId) {
            const row = document.getElementById(rowId);
            if (row) {
                row.remove();
            }
        }

        function clearSkuList() {
            document.getElementById('skuList').innerHTML = '';
        }

        function getSkusData() {
            const rows = document.querySelectorAll('#skuList > div');
            const skus = [];
            rows.forEach(row => {
                const inputs = row.querySelectorAll('input');
                const name = inputs[0].value.trim();
                const price = inputs[1].value.trim();
                if (name) {
                    skus.push({
                        name: name,
                        price: parseFloat(price) || 0
                    });
                }
            });
            return skus;
        }

        function openAddProductModal() {
            document.getElementById('addProductId').value = '';
            document.getElementById('addIcon').value = '';
            document.getElementById('addIconImg').src = '';
            document.getElementById('addIconImg').style.display = 'none';
            document.getElementById('addIconPlaceholder').style.display = 'block';
            document.getElementById('addName').value = '';
            document.getElementById('addDescription').value = '';
            document.getElementById('addPrice').value = '';
            document.getElementById('addOriginalPrice').value = '';
            document.getElementById('addCategory').value = '默认分类';
            document.getElementById('addProductModal').style.display = 'flex';
        }

        function openEditProductModal(productId) {
            const product = productsData.find(p => p.id == productId);
            if (!product) {
                showToast('商品不存在', 'error');
                return;
            }
            document.getElementById('addProductId').value = product.id;
            document.getElementById('addIcon').value = product.icon;

            const iconImg = document.getElementById('addIconImg');
            const iconPlaceholder = document.getElementById('addIconPlaceholder');
            if (product.icon) {
                iconImg.src = product.icon.startsWith('http') ? product.icon : '../' + product.icon;
                iconImg.style.display = 'block';
                iconPlaceholder.style.display = 'none';
            } else {
                iconImg.src = '';
                iconImg.style.display = 'none';
                iconPlaceholder.style.display = 'block';
            }

            document.getElementById('addName').value = product.name;
            document.getElementById('addDescription').value = product.description;
            document.getElementById('addPrice').value = product.price;
            document.getElementById('addOriginalPrice').value = product.original_price;
            document.getElementById('addCategory').value = product.category;

            clearSkuList();
            let skus = [];
            try {
                skus = typeof product.skus === 'string' ? JSON.parse(product.skus) : (product.skus || []);
            } catch (e) {
                skus = [];
            }
            if (skus && skus.length > 0) {
                skus.forEach(sku => {
                    addSkuRow(sku.name, sku.price);
                });
            }

            document.getElementById('addProductModal').style.display = 'flex';
        }

        document.getElementById('addProductForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('addProductId').value;
            const skus = getSkusData();
            const data = {
                id: id,
                name: document.getElementById('addName').value,
                description: document.getElementById('addDescription').value,
                price: document.getElementById('addPrice').value,
                original_price: document.getElementById('addOriginalPrice').value || 0,
                category: document.getElementById('addCategory').value,
                icon: document.getElementById('addIcon').value,
                skus: JSON.stringify(skus),
                csrf_token: '<?php echo $csrfToken; ?>'
            };
            const action = id ? 'editProduct' : 'addProduct';
            fetch(`../api.php?action=${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams(data)
                })
                .then(r => r.json())
                .then(d => {
                    if (d.code === 1) {
                        showToast(d.msg, 'success');
                        closeModal('addProductModal');
                        showProducts();
                        refreshLogsIfNeeded();
                    } else {
                        showToast(d.msg, 'error');
                    }
                });
        });

        function toggleSelectAllProducts() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            const selectAll = document.getElementById('selectAllProducts');

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateBatchDeleteBtn();
        }

        function updateBatchDeleteBtn() {
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');
            const btn = document.getElementById('batchDeleteBtn');
            btn.disabled = checkboxes.length === 0;
        }

        function batchDeleteProducts() {
            const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
            const ids = Array.from(checkedBoxes).map(cb => cb.value);

            if (ids.length === 0) {
                showToast('请选择要删除的商品', 'error');
                return;
            }

            showConfirm(`确定删除选中的 ${ids.length} 个商品？`, function() {
                fetch('../api.php?action=batchDeleteProducts', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `ids=${ids.join(',')}&csrf_token=<?php echo $csrfToken; ?>`
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.code === 1) {
                            showToast(d.msg, 'success');
                            showProducts();
                            refreshLogsIfNeeded();
                        } else {
                            showToast(d.msg, 'error');
                        }
                    });
            });
        }

        function toggleSelectAllCards() {
            const selectAll = document.getElementById('selectAllCards');
            const checkboxes = document.querySelectorAll('.card-checkbox');

            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            updateCardsSelectAll();
        }

        function updateCardsSelectAll() {
            const btn = document.getElementById('batchDeleteCardsBtn');
            const checkboxes = document.querySelectorAll('.card-checkbox:checked');
            btn.disabled = checkboxes.length === 0;


            const selectAll = document.getElementById('selectAllCards');
            const allCheckboxes = document.querySelectorAll('.card-checkbox');
            if (allCheckboxes.length > 0) {
                selectAll.checked = checkboxes.length === allCheckboxes.length;
            }
        }

        function batchDeleteCards() {
            const checkedBoxes = document.querySelectorAll('.card-checkbox:checked');
            const items = Array.from(checkedBoxes).map(cb => cb.value);

            if (items.length === 0) {
                showToast('请选择要删除的卡密', 'error');
                return;
            }

            showConfirm(`确定删除选中的 ${items.length} 个SKU的卡密？`, function() {
                fetch('../api.php?action=batchDeleteCards', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `items=${items.join(',')}&csrf_token=<?php echo $csrfToken; ?>`
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.code === 1) {
                            showToast(d.msg, 'success');
                            showCards();
                            refreshLogsIfNeeded();
                        } else {
                            showToast(d.msg, 'error');
                        }
                    });
            });
        }

        function deleteProduct(id) {
            showConfirm('确定删除？', function() {
                fetch('../api.php?action=deleteProduct', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `id=${id}&csrf_token=<?php echo $csrfToken; ?>`
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.code === 1) {
                            showToast(d.msg, 'success');
                            showProducts();
                            refreshLogsIfNeeded();
                        } else {
                            showToast(d.msg, 'error');
                        }
                    });
            });
        }

        function refreshLogsIfNeeded() {
            if (currentPage === 'logs') {
                loadLogs(1);
            }
        }

        function openAddCardsModal() {
            const select = document.getElementById('addCardsProductId');
            select.innerHTML = '';

            const productsWithCount = [];
            let loadedCount = 0;

            if (productsData.length === 0) {
                select.innerHTML = '<option value="">暂无商品</option>';
                select.disabled = true;
                document.getElementById('addCardsText').value = '';
                document.getElementById('addCardsModal').style.display = 'flex';
                return;
            }

            productsData.forEach(product => {
                fetch(`../api.php?action=getCards&productId=${product.id}`)
                    .then(r => r.json())
                    .then(d => {
                        loadedCount++;
                        const count = (d.code === 1 && d.data) ? d.data.length : 0;
                        productsWithCount.push({
                            id: product.id,
                            name: product.name,
                            count: count
                        });

                        if (loadedCount === productsData.length) {
                            productsWithCount.sort((a, b) => b.count - a.count);

                            productsWithCount.forEach(p => {
                                const countText = ` 卡密：${escapeHtml(String(p.count))}`;
                                select.innerHTML += `<option value="${p.id}">${escapeHtml(p.name)}${countText}</option>`;
                            });

                            select.disabled = false;
                            document.getElementById('addCardsText').value = '';
                            document.getElementById('addCardsModal').style.display = 'flex';
                            if (select.value) {
                                loadProductCards(select.value);
                                loadProductSkus(select.value);
                            } else {
                                document.getElementById('addCardsSkuSection').style.display = 'none';
                            }
                        }
                    });
            });
        }

        document.getElementById('addCardsProductId').addEventListener('change', function() {
            loadProductCards(this.value, document.getElementById('addCardsSku').value);
            loadProductSkus(this.value);
        });

        let skuCardsCache = {};
        let currentEditingSku = '';
        let loadCardsRequestId = 0;

        document.getElementById('addCardsSku').addEventListener('change', function() {
            const newSku = this.value;
            const cardsText = document.getElementById('addCardsText');
            const skuSelect = document.getElementById('addCardsSku');
            const options = skuSelect.options;
            let hasSkus = false;

            for (let i = 0; i < options.length; i++) {
                if (options[i].value !== '') {
                    hasSkus = true;
                    break;
                }
            }

            if (currentEditingSku !== undefined && currentEditingSku !== null) {
                skuCardsCache[currentEditingSku] = cardsText.value;
            }

            if (!newSku) {

                cardsText.disabled = false;
                if (hasSkus) {

                    cardsText.placeholder = '卡密1\n卡密2\n卡密3\n\n';
                } else {

                    cardsText.placeholder = '卡密1\n卡密2\n卡密3';
                }

                if (skuCardsCache[''] !== undefined) {
                    cardsText.value = skuCardsCache[''];
                } else {
                    loadProductCards(document.getElementById('addCardsProductId').value, '');
                }
            } else {

                cardsText.disabled = false;
                cardsText.placeholder = '卡密1\n卡密2\n卡密3';

                if (skuCardsCache[newSku] !== undefined) {

                    cardsText.value = skuCardsCache[newSku];
                } else {

                    loadProductCards(document.getElementById('addCardsProductId').value, newSku);
                }
            }

            currentEditingSku = newSku;
        });

        function loadProductCards(productId, sku = '') {
            if (!productId) {
                document.getElementById('addCardsText').value = '';
                return;
            }

            const requestId = ++loadCardsRequestId;

            let url = `../api.php?action=getCards&productId=${productId}`;
            if (sku) {
                url += `&sku=${encodeURIComponent(sku)}`;
            }

            fetch(url)
                .then(r => r.json())
                .then(d => {
                    if (requestId !== loadCardsRequestId) return;
                    if (currentEditingSku !== sku) return;

                    if (d.code === 1 && d.data && d.data.length > 0) {
                        const cards = d.data.map(card => card.card_code).join('\n');
                        document.getElementById('addCardsText').value = cards;

                        if (sku && skuCardsCache[sku] === undefined) {
                            skuCardsCache[sku] = cards;
                        }
                    } else {
                        document.getElementById('addCardsText').value = '';
                    }
                })
                .catch(() => {
                    if (requestId !== loadCardsRequestId) return;
                    if (currentEditingSku !== sku) return;
                    document.getElementById('addCardsText').value = '';
                });
        }

        function loadProductSkus(productId) {
            const skuSection = document.getElementById('addCardsSkuSection');
            const skuSelect = document.getElementById('addCardsSku');
            const cardsText = document.getElementById('addCardsText');

            skuSelect.innerHTML = '<option value="">无SKU</option>';

            if (!productId) {
                skuSection.style.display = 'none';
                cardsText.disabled = true;
                cardsText.placeholder = '请先选择商品';
                return;
            }


            cardsText.disabled = false;
            cardsText.placeholder = '卡密1\n卡密2\n卡密3';
            currentEditingSku = '';

            fetch(`../api.php?action=getProductById&id=${productId}`)
                .then(r => r.json())
                .then(d => {
                    if (d.code === 1 && d.data && d.data.skus && d.data.skus.length > 0) {
                        d.data.skus.forEach(sku => {
                            const option = document.createElement('option');
                            option.value = sku.name;
                            option.textContent = sku.name;
                            skuSelect.appendChild(option);
                        });
                        skuSection.style.display = 'block';
                    } else {
                        skuSection.style.display = 'none';
                    }
                })
                .catch(() => {
                    skuSection.style.display = 'none';
                });
        }

        document.getElementById('addCardsForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (currentEditingSku !== undefined && currentEditingSku !== null) {
                skuCardsCache[currentEditingSku] = document.getElementById('addCardsText').value;
            }

            const productId = document.getElementById('addCardsProductId').value;
            const skuSelect = document.getElementById('addCardsSku');
            const options = skuSelect.options;

            const actualSkus = [];
            for (let i = 0; i < options.length; i++) {
                if (options[i].value !== '') {
                    actualSkus.push(options[i].value);
                }
            }

            let skusToSave = Object.entries(skuCardsCache).filter(([sku, cards]) => cards.trim());

            if (skusToSave.length === 0) {
                showToast('请先输入卡密', 'error');
                return;
            }

            let savedCount = 0;
            let errorOccurred = false;

            const saveNextSku = (index) => {
                if (index >= skusToSave.length || errorOccurred) {
                    if (errorOccurred) {
                        showToast('保存失败', 'error');
                    } else {
                        showToast(`成功保存 ${savedCount} 个SKU的卡密`, 'success');
                        closeModal('addCardsModal');
                        showCards();
                        refreshLogsIfNeeded();
                    }
                    return;
                }

                const [sku, cards] = skusToSave[index];
                const data = {
                    product_id: productId,
                    cards: cards,
                    sku: sku,
                    csrf_token: '<?php echo $csrfToken; ?>'
                };

                fetch('../api.php?action=addCards', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams(data)
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.code === 1) {
                            savedCount++;
                            saveNextSku(index + 1);
                        } else {
                            errorOccurred = true;
                            saveNextSku(index + 1);
                        }
                    })
                    .catch(() => {
                        errorOccurred = true;
                        saveNextSku(index + 1);
                    });
            };

            saveNextSku(0);
        });

        function viewCards(productId, page = 1, sku = '') {
            const cardsList = document.getElementById('cardsList');
            cardsList.innerHTML = '<div class="text-center py-5 text-gray-400">加载中...</div>';
            document.getElementById('viewCardsModal').style.display = 'flex';
            const actualSku = sku === '无SKU' ? '' : sku;
            let url = `../api.php?action=getCards&productId=${productId}&page=${page}`;

            url += `&sku=${encodeURIComponent(actualSku)}`;
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1 && data.data && data.data.length > 0) {
                        const currentPage = data.page || 1;
                        const totalPages = data.pages || 1;
                        const total = data.total || 0;

                        let html = '<div class="space-y-2">';
                        const startIndex = (currentPage - 1) * 15;
                        data.data.forEach((card, index) => {
                            
                            const sku = (card.sku !== undefined && card.sku !== null && card.sku !== '') ? card.sku : '无SKU';
                            const cardCode = card.card_code || '';
                            const isUsed = parseInt(card.used) === 1;
                            html += `<div class="flex items-start py-3 border-b border-orange-500/10">
                                <span class="text-gray-500 text-sm w-10 text-right pr-3 mt-1">${startIndex + index + 1}.</span>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-gray-400 text-xs bg-gray-800 px-2 py-0.5 rounded">${sku}</span>
                                        ${isUsed ? '<span class="text-red-400 text-xs bg-red-900/30 px-2 py-0.5 rounded">已使用</span>' : ''}
                                    </div>
                                    <span class="text-gray-300 font-mono text-sm break-all">${cardCode}</span>
                                </div>
                            </div>`;
                        });
                        html += '</div>';

                        if (totalPages > 1) {
                            const skuParam = sku ? `, '${sku}'` : '';
                            html += `<div class="flex justify-center items-center mt-4 gap-2">
                                <button onclick="viewCards(${productId}, ${currentPage - 1}${skuParam})" ${currentPage <= 1 ? 'disabled' : ''} class="px-3 py-1 text-sm border border-orange-500/30 rounded ${currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-orange-500/10 transition-colors'}">上一页</button>
                                <span class="text-gray-400 text-sm">第 ${currentPage} / ${totalPages} 页</span>
                                <button onclick="viewCards(${productId}, ${currentPage + 1}${skuParam})" ${currentPage >= totalPages ? 'disabled' : ''} class="px-3 py-1 text-sm border border-orange-500/30 rounded ${currentPage >= totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-orange-500/10 transition-colors'}">下一页</button>
                            </div>`;
                        }

                        cardsList.innerHTML = html;
                    } else {
                        cardsList.innerHTML = '<div class="text-center py-5 text-gray-500">暂无卡密</div>';
                    }
                });
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                info: 'fa-info-circle'
            };
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fa ${icons[type]}"></i>
                <span class="toast-message">${escapeHtml(message)}</span>
                <span class="toast-close" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></span>
            `;
            container.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.3s ease-out forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            const modalContent = modal.querySelector('.modal');

            if (modalContent) {
                modalContent.classList.add('closing');
            }
            modal.classList.add('closing');

            setTimeout(() => {
                modal.style.display = 'none';
                if (modalContent) {
                    modalContent.classList.remove('closing');
                }
                modal.classList.remove('closing');
            }, 200);
        }

        function completeOrder(orderId) {
            showConfirm('确定要手动完成该订单吗？', function() {
                const formData = new URLSearchParams();
                formData.append('orderId', orderId);

                fetch('../api.php?action=completeOrder', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.code === 1) {
                            showToast('订单已完成', 'success');
                            loadOrders('all');
                        } else {
                            showToast(data.msg || '操作失败', 'error');
                        }
                    })
                    .catch(error => {
                        showToast('网络错误', 'error');
                    });
            });
        }

        function showConfirm(message, callback) {
            const confirmModal = document.createElement('div');
            confirmModal.className = 'fixed inset-0 bg-black/70 flex items-center justify-center z-50';

            const content = document.createElement('div');
            content.className = 'bg-gray-800 border border-gray-700 rounded-lg p-6 w-full max-w-sm mx-4 animate-modal-in';

            const title = document.createElement('p');
            title.className = 'text-gray-300 text-center text-base mb-6';
            title.textContent = message;

            const buttonContainer = document.createElement('div');
            buttonContainer.className = 'flex justify-center gap-4';

            const cancelBtn = document.createElement('button');
            cancelBtn.className = 'px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg text-sm';
            cancelBtn.textContent = '取消';
            cancelBtn.onclick = () => {
                closeConfirmModal();
            };

            const confirmBtn = document.createElement('button');
            confirmBtn.className = 'px-4 py-2 bg-orange-500 hover:bg-orange-400 text-white rounded-lg text-sm';
            confirmBtn.textContent = '确定';
            confirmBtn.onclick = () => {
                closeConfirmModal(() => {
                    if (callback) callback();
                });
            };

            function closeConfirmModal(onComplete) {
                content.classList.add('animate-modal-out');
                confirmModal.classList.add('animate-modal-out');

                setTimeout(() => {
                    confirmModal.remove();
                    if (onComplete) onComplete();
                }, 200);
            }

            buttonContainer.appendChild(cancelBtn);
            buttonContainer.appendChild(confirmBtn);
            content.appendChild(title);
            content.appendChild(buttonContainer);
            confirmModal.appendChild(content);
            document.body.appendChild(confirmModal);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const pageMap = {
                'dashboard': showDashboard,
                'products': showProducts,
                'cards': showCards,
                'orders': showOrders,
                'logs': showLogs,
                'siteconfig': showSiteConfig
            };
            const savedPage = localStorage.getItem('adminCurrentPage') || 'dashboard';
            const pageFunc = pageMap[savedPage] || showDashboard;
            pageFunc();

            document.getElementById('addIcon').addEventListener('input', function() {
                const iconUrl = this.value.trim();
                const iconImg = document.getElementById('addIconImg');
                const iconPlaceholder = document.getElementById('addIconPlaceholder');

                if (iconUrl) {
                    let previewUrl = iconUrl;
                    if (!iconUrl.startsWith('http://') && !iconUrl.startsWith('https://') && !iconUrl.startsWith('/')) {
                        previewUrl = '../' + iconUrl;
                    }
                    iconImg.src = previewUrl;
                    iconImg.onload = function() {
                        iconImg.style.display = 'block';
                        iconPlaceholder.style.display = 'none';
                    };
                    iconImg.onerror = function() {
                        iconImg.style.display = 'none';
                        iconPlaceholder.style.display = 'block';
                    };
                } else {
                    iconImg.src = '';
                    iconImg.style.display = 'none';
                    iconPlaceholder.style.display = 'block';
                }
            });
        });
    </script>
</body>

</html>
