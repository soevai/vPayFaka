<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 主逻辑
 * @License     MIT
 */
require_once 'functions.php';

if (!checkInstallation()) {
    header('Location: install.php');
    exit;
}

include 'config.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($config['site']['title'] ?? ''); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($config['site']['description'] ?? ''); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($config['site']['keywords'] ?? ''); ?>">
    <link rel="icon" href="<?php echo !empty($config['site']['logo']) ? htmlspecialchars($config['site']['logo']) : 'templates/image/favicon.ico'; ?>" type="image/x-icon">
    <link rel="stylesheet" href="assets/css/tailwind.min.css">
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0f0f14;
            --bg-secondary: #1a1a24;
            --bg-card: rgba(20, 20, 30, 0.8);
            --text-primary: #ffffff;
            --text-secondary: #aaaaaa;
            --border-color: #ff660040;
            --accent-color: #ff6600;
        }

        body {
            background: var(--bg-primary);
            font-family: "Microsoft YaHei", sans-serif;
            color: var(--text-primary);
            background-image:
                linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                url("templates/image/bg.jpg");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .navbar {
            background: rgba(15, 15, 20, 0.9);
            border-bottom: 1px solid var(--border-color);
        }

        .ui-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            box-shadow: 0 0 15px rgba(255, 100, 0, 0.15);
        }

        .product-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .product-card:hover {
            border-color: #ff6600;
            box-shadow: 0 0 20px rgba(255, 100, 0, 0.3);
            transform: translateY(-5px);
        }

        .cate-btn {
            background: linear-gradient(180deg, #1a2f1a, #0a150a);
            border: 1px solid #33aa33;
            color: #fff;
            padding: 8px 24px;
            font-weight: bold;
            transition: 0.3s;
            border-radius: 4px;
        }

        .cate-btn:hover,
        .cate-btn.active {
            background: linear-gradient(180deg, #2a4f2a, #1a3a1a);
            border-color: #44dd44;
            box-shadow: 0 0 10px rgba(68, 221, 68, 0.3);
        }

        .btn-buy {
            background: linear-gradient(180deg, #ff4500, #cc3700);
            border: 1px solid #ff6600;
            color: #fff;
            font-weight: bold;
            transition: 0.3s;
            border-radius: 4px;
        }

        .btn-buy:hover {
            background: linear-gradient(180deg, #ff6622, #dd4811);
            box-shadow: 0 0 12px rgba(255, 100, 0, 0.4);
            transform: translateY(-2px);
        }

        .btn-buy:disabled {
            background: #333;
            border-color: #555;
            color: #888;
            cursor: not-allowed;
        }

        .btn-login {
            background: transparent;
            border: 1px solid #ff6600;
            color: #ff6600;
            transition: 0.3s;
            border-radius: 4px;
        }

        .btn-login:hover {
            background: rgba(255, 100, 0, 0.1);
        }

        .tag-auto {
            background: #22aa22;
            color: #fff;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 2px;
        }

        .tag-recommend {
            background: #ff4500;
            color: #fff;
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 2px;
        }

        .modal {
            background: rgba(20, 20, 30, 0.95);
            border: 1px solid #ff6600;
            border-radius: 4px;
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

        .ui-input {
            background: #1a1a24;
            border: 1px solid #ff660040;
            color: #fff;
            transition: 0.3s;
            border-radius: 4px;
        }

        .ui-input:focus {
            border-color: #ff6600;
            outline: none;
            box-shadow: 0 0 8px rgba(255, 100, 0, 0.3);
        }

        .price-text {
            color: #ff4500;
        }

        .video-container {
            position: relative;
            width: 100%;
            height: 320px;
            background: #000;
            border: 1px solid #ff660040;
            border-radius: 4px;
            overflow: hidden;
        }

        .video-container iframe,
        .video-container video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .video-container video::-webkit-media-controls {
            display: none !important;
        }

        .video-container video::-webkit-media-controls-enclosure {
            display: none !important;
        }

        .video-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
            cursor: pointer;
            z-index: 10;
        }

        .video-placeholder.hidden {
            display: none;
        }

        .play-icon {
            color: #ff6600;
            font-size: 48px;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .play-icon:hover {
            transform: scale(1.1);
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }

        .qty-btn {
            background: linear-gradient(180deg, #2a2a3a, #1a1a2a);
            border: 1px solid #ff6600;
            color: #ff6600;
            font-weight: bold;
            transition: all 0.2s;
        }

        .qty-btn:hover {
            background: linear-gradient(180deg, #3a3a4a, #2a2a3a);
            box-shadow: 0 0 8px rgba(255, 100, 0, 0.3);
        }

        .qty-input {
            background: #1a1a24;
            border: 1px solid #ff6600;
            color: #fff;
            font-weight: bold;
            text-align: center;
        }

        .qty-input:focus {
            outline: none;
            border-color: #ff8800;
            box-shadow: 0 0 8px rgba(255, 136, 0, 0.3);
        }

        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }

        ::-webkit-scrollbar-track {
            background: #1a1a24;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #ff6600, #cc4400);
            border-radius: 4px;
            border: 1px solid #ff8800;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #ff8800, #dd5500);
            box-shadow: 0 0 8px rgba(255, 102, 0, 0.5);
        }

        body {
            scrollbar-width: thin;
            scrollbar-color: #ff6600 #1a1a24;
        }

        .skeleton {
            background: linear-gradient(90deg, #1a1a24 25%, #2a2a3a 50%, #1a1a24 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s ease-in-out infinite;
            border-radius: 4px;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .skeleton-card {
            background: rgba(20, 20, 30, 0.9);
            border: 1px solid #ff660040;
            border-radius: 4px;
            padding: 16px;
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            background: rgba(30, 30, 40, 0.95);
            border: 1px solid #ff6600;
            border-radius: 8px;
            padding: 14px 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5), 0 0 20px rgba(255, 102, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 280px;
            max-width: 400px;
            animation: toastSlideIn 0.3s ease-out forwards;
        }

        .toast-success {
            border-color: #00cc00;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5), 0 0 20px rgba(0, 204, 0, 0.2);
        }

        .toast-error {
            border-color: #ff4444;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5), 0 0 20px rgba(255, 68, 68, 0.2);
        }

        .toast-warning {
            border-color: #ffaa00;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5), 0 0 20px rgba(255, 170, 0, 0.2);
        }

        .toast-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        .toast-message {
            color: #fff;
            font-size: 14px;
            line-height: 1.5;
        }

        @keyframes toastSlideIn {
            from {
                opacity: 0;
                transform: translateX(100px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes toastSlideOut {
            from {
                opacity: 1;
                transform: translateX(0);
            }

            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }
    </style>
</head>

<body>

    <div class="toast-container" id="toastContainer"></div>

    <div class="navbar w-full fixed top-0 z-50">
        <div class="container mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="text-xl font-bold text-orange-500 flex items-center gap-2 cursor-pointer" onclick="showHome()">
                    <i class="fa fa-store"></i>
                    <?php echo htmlspecialchars($config['site']['name'] ?? ''); ?>
                </div>
                <div class="hidden md:flex gap-6 text-gray-300 text-sm">
                    <a href="#" onclick="showHome()" class="text-orange-400 font-medium">首页</a>
                    <a href="#" onclick="showOrderModal()" class="hover:text-orange-400 transition-colors">订单查询</a>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="hidden md:flex items-center bg-black/30 border border-orange-500/30 rounded px-3 py-1.5">
                    <i class="fa fa-search text-orange-400 mr-2"></i>
                    <input type="text" placeholder="搜索商品关键词" class="bg-transparent outline-none text-sm w-40 text-white" id="searchInp">
                </div>
                <button class="btn-login px-3 py-1.5 text-sm font-medium flex items-center gap-1" onclick="showKfModal()">
                    <i class="fa fa-headphones"></i>
                    在线客服
                </button>
                <?php $adminLink = 'admin/index.php'; if (!empty($config['security']['admin_access_key'])) { $adminLink .= '?key=' . urlencode($config['security']['admin_access_key']); } ?>
                <?php if ($config["security"]["show_admin_login"]): ?><a href="<?php echo $adminLink; ?>" class="btn-login px-3 py-1.5 text-sm font-medium">管理后台</a><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="pt-20">
        <div id="mainContent">
            <div class="container mx-auto px-4 mb-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <div class="ui-card p-4">
                        <div class="text-yellow-400 font-bold mb-2">公告</div>
                        <div class="border-b border-orange-500/30 mb-3"></div>
                        <div class="text-yellow-400 text-sm leading-relaxed" id="announcementContent">
                            <div class="skeleton h-4 mb-2 w-3/4"></div>
                            <div class="skeleton h-4 mb-2 w-1/2"></div>
                            <div class="skeleton h-4 w-2/3"></div>
                        </div>
                    </div>
                    <div class="ui-card p-4 lg:col-span-2">
                        <div class="text-gray-300 text-sm mb-2 text-center" id="videoTitle">
                            <span class="skeleton h-4 w-1/3 inline-block"></span>
                        </div>
                        <div class="video-container" id="videoContainer">
                            <div class="skeleton w-full h-32"></div>
                            <div class="video-placeholder">
                                <i class="fa fa-play-circle play-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container mx-auto px-4 mb-6">
                <div class="flex justify-center gap-4" id="cateBox"></div>
            </div>

            <div class="container mx-auto px-4 pb-20">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" id="goodsBox"></div>
            </div>
        </div>
    </div>

    <div id="buyModal" class="fixed inset-0 bg-black/70 modal-backdrop hidden items-center justify-center z-50">
        <div class="modal w-full max-w-md p-6 mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-orange-400" id="mTitle"></h3>
                <span onclick="closeBuyModal()" class="text-gray-400 text-xl cursor-pointer hover:text-orange-400 transition-colors">×</span>
            </div>
            <p class="text-gray-400 text-sm mb-4" id="mDesc"></p>
            <div class="mb-4">
                <span class="text-2xl font-bold price-text" id="mPrice"></span>
                <span class="text-gray-500 line-through ml-2 text-sm" id="mOldPrice"></span>
            </div>
            <input type="email" placeholder="请输入接收邮箱" id="buyAccount"
                class="ui-input w-full px-4 py-3 mb-4" required>

            <div class="mb-4" id="skuSection" style="display: none;">
                <p class="text-gray-400 text-sm mb-3">选择规格</p>
                <div class="flex flex-wrap gap-2" id="skuOptions"></div>
            </div>

            <div class="mb-4">
                <p class="text-gray-400 text-sm mb-3">购买数量</p>
                <div class="flex items-center justify-start gap-3">
                    <button onclick="changeQuantity(-1)" class="qty-btn w-10 h-10 rounded-lg text-xl font-bold">−</button>
                    <input type="number" id="buyQuantity" value="1" min="1" class="qty-input w-20 h-10 rounded-lg text-lg">
                    <button onclick="changeQuantity(1)" class="qty-btn w-10 h-10 rounded-lg text-xl font-bold">+</button>
                    <span class="text-gray-400 text-sm ml-2" id="stockInfo">库存: --</span>
                </div>
            </div>

            <div class="mb-4">
                <p class="text-gray-400 text-sm mb-3">选择支付方式</p>
                <div style="display: flex; gap: 10px;">
                    <button type="button" id="wxBtn" onclick="selectPayType(1)"
                        style="flex: 1; padding: 10px; border: 2px solid #ff6600; background: #1a1a24; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" style="width: 24px; height: 24px; flex-shrink: 0;">
                            <path d="M964.16 294.4c-3.328-5.568-5.888-5.76-11.168-2.528-17.44 10.56-35.168 20.64-52.864 30.752-45.024 25.76-90.144 51.424-135.2 77.184-53.344 30.464-106.688 60.896-159.936 91.488-75.328 43.264-150.56 86.656-225.856 129.984-21.056 12.096-41.184 5.6-51.232-16.48-8.448-18.432-16.992-36.832-25.44-55.264-21.92-48.032-43.84-96.064-65.696-144.128-4.032-8.864-2.528-15.264 4.16-20.8 6.304-5.28 13.824-5.248 21.568 0.256 35.136 24.864 70.08 49.92 105.376 74.56 15.84 11.072 33.12 12.64 51.008 4.8 45.824-20.16 91.648-40.224 137.376-60.48 64.928-28.8 129.728-57.76 194.624-86.528a30516.544 30516.544 0 0 1 165.056-72.8c7.136-3.104 7.616-5.44 2.56-11.104-40.96-45.664-89.376-81.248-144.32-108.864a568.64 568.64 0 0 0-159.488-52.064c-33.728-5.856-67.84-9.536-99.712-7.68-45.92-1.28-88.576 3.968-130.976 13.088a560.096 560.096 0 0 0-98.976 30.784c-85.76 35.904-157.696 89.216-211.008 165.76C30.816 336.32 8.352 405.12 7.776 480.48a340.96 340.96 0 0 0 15.264 102.656c20.16 66.24 56.832 122.016 106.176 170.24 16.832 16.416 35.136 31.072 53.792 45.28 11.584 8.8 15.552 20.704 12.224 34.048-6.752 27.136-14.72 54.016-22.144 80.992l-3.648 13.696a15.776 15.776 0 0 0 6.304 17.792c6.496 4.544 13.76 3.424 20.576-0.48 36.352-20.8 72.704-41.6 109.12-62.272 10.624-6.048 21.888-10.528 34.432-8.032 9.312 1.824 18.496 4.416 27.68 6.912 39.36 10.656 79.616 15.808 120.288 17.216 41.248 1.408 82.304-0.64 123.2-7.424 34.112-5.632 67.584-13.504 99.872-25.216 58.976-21.344 113.568-50.72 161.6-91.424 66.944-56.64 113.856-125.6 134.88-211.104a349.12 349.12 0 0 0 5.184-139.2c-7.36-46.304-24.224-89.44-48.416-129.792" fill="#0DC803"></path>
                        </svg>
                        <span style="color: #fff;">微信支付</span>
                    </button>
                    <button type="button" id="zfbBtn" onclick="selectPayType(2)"
                        style="flex: 1; padding: 10px; border: 2px solid #444; background: #1a1a24; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" style="width: 24px; height: 24px; flex-shrink: 0;">
                            <path d="M588.8 672s-76.8 64-102.4 76.8c-25.6 12.8-44.8 25.6-70.4 32-19.2 6.4-38.4 12.8-51.2 12.8s-25.6 0-38.4 6.4H307.2c-25.6 0-51.2 0-76.8-6.4l-57.6-38.4c-19.2-12.8-32-25.6-38.4-44.8-12.8-19.2-12.8-38.4-12.8-64 0-19.2 6.4-38.4 19.2-57.6 12.8-19.2 25.6-32 44.8-44.8s38.4-19.2 57.6-25.6c12.8-6.4 38.4-6.4 57.6-6.4 19.2 0 44.8 0 64 6.4 19.2 6.4 38.4 6.4 51.2 12.8 19.2 6.4 32 12.8 51.2 19.2 19.2 6.4 32 12.8 44.8 19.2 6.4 6.4 12.8 6.4 19.2 6.4 6.4 0 12.8 6.4 19.2 6.4 19.2-12.8 25.6-32 38.4-51.2 6.4-19.2 19.2-32 25.6-44.8 6.4-12.8 6.4-25.6 12.8-38.4 6.4-6.4 6.4-19.2 6.4-19.2H320v-32h147.2V313.6H262.4v-32h204.8v-64c0-6.4 0-6.4 6.4-12.8 6.4 0 12.8-6.4 19.2-6.4H576v83.2h211.2v32H569.6v76.8h166.4c-6.4 19.2-6.4 44.8-19.2 70.4-6.4 19.2-19.2 44.8-32 70.4-12.8 25.6-32 51.2-51.2 83.2 0 0 166.4 76.8 339.2 108.8 32-64 44.8-134.4 44.8-211.2 0-281.6-230.4-512-512-512C230.4 0 0 230.4 0 512s230.4 512 512 512c172.8 0 332.8-89.6 422.4-224-96-19.2-192-57.6-345.6-128z m-403.2-25.6c0 89.6 96 96 115.2 96 51.2 0 89.6-19.2 121.6-38.4 32-12.8 83.2-64 83.2-64l6.4-6.4c-12.8-6.4-25.6-19.2-38.4-25.6-12.8-6.4-83.2-38.4-140.8-44.8-115.2-12.8-147.2 57.6-147.2 83.2z" fill="#00AAEE"></path>
                        </svg>
                        <span style="color: #fff;">支付宝支付</span>
                    </button>
                </div>
            </div>

            <button onclick="confirmBuy()" class="btn-buy w-full py-3 font-medium">立即购买</button>
        </div>
    </div>

    <div id="payModal" class="fixed inset-0 bg-black/70 modal-backdrop hidden items-center justify-center z-50">
        <div class="modal w-full max-w-md p-6 mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-orange-400" id="payModalTitle">扫码支付</h3>
                <span onclick="closePayModal()" class="text-gray-400 text-xl cursor-pointer hover:text-orange-400 transition-colors">×</span>
            </div>
            <div class="text-center">
                <div class="text-yellow-400 text-2xl font-bold mb-2" id="payAmount">¥0.00</div>
                <div class="text-gray-400 text-sm mb-4" id="payOrderId">订单号：</div>
                <div class="bg-white p-4 rounded-lg inline-block mb-4">
                    <img id="payQrCode" src="" alt="收款码" class="w-64 h-64 object-contain">
                </div>
                <div class="text-gray-400 text-sm mb-4" id="payTip">请使用微信/支付宝扫码支付</div>
                <div class="w-full bg-gray-700 rounded-full h-2 mb-4">
                    <div class="bg-orange-500 h-2 rounded-full transition-all duration-500" id="payProgress"></div>
                </div>
                <div class="text-gray-500 text-xs">支付完成后将自动跳转到订单详情</div>
            </div>
        </div>
    </div>

    <div id="orderModal" class="fixed inset-0 bg-black/70 modal-backdrop hidden items-center justify-center z-50">
        <div class="modal w-full max-w-md p-6 mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-orange-400">订单查询</h3>
                <span onclick="closeOrderModal()" class="text-gray-400 text-xl cursor-pointer hover:text-orange-400 transition-colors">×</span>
            </div>
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-orange-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa fa-search text-orange-500 text-3xl"></i>
                </div>
                <p class="text-gray-400 text-sm">输入订单号查询详情</p>
            </div>
            <form id="orderForm">
                <div class="mb-4">
                    <label class="block text-gray-400 text-sm mb-2">商户订单号</label>
                    <input type="text" name="payId" id="orderPayId" class="ui-input w-full px-4 py-3 text-sm text-center" placeholder="请输入商户订单号">
                </div>
                <button type="submit" class="btn-buy w-full py-3 font-medium">查询订单</button>
            </form>
            <div id="orderResult" class="mt-4 hidden"></div>
        </div>
    </div>

    <div id="kfModal" class="fixed inset-0 bg-black/70 modal-backdrop hidden items-center justify-center z-50">
        <div class="modal w-full max-w-md p-6 mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-orange-400">
                    <i class="fa fa-headphones mr-2"></i>在线客服
                </h3>
                <span onclick="closeKfModal()" class="text-gray-400 text-xl cursor-pointer hover:text-orange-400 transition-colors">×</span>
            </div>
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa fa-user-circle text-green-500 text-4xl"></i>
                </div>
                <h4 class="text-lg font-medium text-white mb-1"><?php echo htmlspecialchars($config['kf']['name'] ?? '在线客服'); ?></h4>
                <p class="text-gray-400 text-sm">欢迎咨询，我将竭诚为您服务</p>
            </div>
            <div class="space-y-4">
                <?php if (!empty($config['kf']['qq'])): ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-800/50 rounded-lg">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6" viewBox="0 0 1024 1024">
                                <path d="M511.09761 957.257c-80.159 0-153.737-25.019-201.11-62.386-24.057 6.702-54.831 17.489-74.252 30.864-16.617 11.439-14.546 23.106-11.55 27.816 13.15 20.689 225.583 13.211 286.912 6.767v-3.061z" fill="#FAAD08"></path>
                                <path d="M496.65061 957.257c80.157 0 153.737-25.019 201.11-62.386 24.057 6.702 54.83 17.489 74.253 30.864 16.616 11.439 14.543 23.106 11.55 27.816-13.15 20.689-225.584 13.211-286.914 6.767v-3.061z" fill="#FAAD08"></path>
                                <path d="M497.12861 474.524c131.934-0.876 237.669-25.783 273.497-35.34 8.541-2.28 13.11-6.364 13.11-6.364 0.03-1.172 0.542-20.952 0.542-31.155C784.27761 229.833 701.12561 57.173 496.64061 57.162 292.15661 57.173 209.00061 229.832 209.00061 401.665c0 10.203 0.516 29.983 0.547 31.155 0 0 3.717 3.821 10.529 5.67 33.078 8.98 140.803 35.139 276.08 36.034h0.972z" fill="#000000"></path>
                                <path d="M860.28261 619.782c-8.12-26.086-19.204-56.506-30.427-85.72 0 0-6.456-0.795-9.718 0.148-100.71 29.205-222.773 47.818-315.792 46.695h-0.962C410.88561 582.017 289.65061 563.617 189.27961 534.698 185.44461 533.595 177.87261 534.063 177.87261 534.063 166.64961 563.276 155.56661 593.696 147.44761 619.782 108.72961 744.168 121.27261 795.644 130.82461 796.798c20.496 2.474 79.78-93.637 79.78-93.637 0 97.66 88.324 247.617 290.576 248.996a718.01 718.01 0 0 1 5.367 0C708.80161 950.778 797.12261 800.822 797.12261 703.162c0 0 59.284 96.111 79.783 93.637 9.55-1.154 22.093-52.63-16.623-177.017" fill="#000000"></path>
                                <path d="M434.38261 316.917c-27.9 1.24-51.745-30.106-53.24-69.956-1.518-39.877 19.858-73.207 47.764-74.454 27.875-1.224 51.703 30.109 53.218 69.974 1.527 39.877-19.853 73.2-47.742 74.436m206.67-69.956c-1.494 39.85-25.34 71.194-53.24 69.956-27.888-1.238-49.269-34.559-47.742-74.435 1.513-39.868 25.341-71.201 53.216-69.974 27.909 1.247 49.285 34.576 47.767 74.453" fill="#FFFFFF"></path>
                                <path d="M683.94261 368.627c-7.323-17.609-81.062-37.227-172.353-37.227h-0.98c-91.29 0-165.031 19.618-172.352 37.227a6.244 6.244 0 0 0-0.535 2.505c0 1.269 0.393 2.414 1.006 3.386 6.168 9.765 88.054 58.018 171.882 58.018h0.98c83.827 0 165.71-48.25 171.881-58.016a6.352 6.352 0 0 0 1.002-3.395c0-0.897-0.2-1.736-0.531-2.498" fill="#FAAD08"></path>
                                <path d="M467.63161 256.377c1.26 15.886-7.377 30-19.266 31.542-11.907 1.544-22.569-10.083-23.836-25.978-1.243-15.895 7.381-30.008 19.25-31.538 11.927-1.549 22.607 10.088 23.852 25.974m73.097 7.935c2.533-4.118 19.827-25.77 55.62-17.886 9.401 2.07 13.75 5.116 14.668 6.316 1.355 1.77 1.726 4.29 0.352 7.684-2.722 6.725-8.338 6.542-11.454 5.226-2.01-0.85-26.94-15.889-49.905 6.553-1.579 1.545-4.405 2.074-7.085 0.242-2.678-1.834-3.786-5.553-2.196-8.135" fill="#000000"></path>
                                <path d="M504.33261 584.495h-0.967c-63.568 0.752-140.646-7.504-215.286-21.92-6.391 36.262-10.25 81.838-6.936 136.196 8.37 137.384 91.62 223.736 220.118 224.996H506.48461c128.498-1.26 211.748-87.612 220.12-224.996 3.314-54.362-0.547-99.938-6.94-136.203-74.654 14.423-151.745 22.684-215.332 21.927" fill="#FFFFFF"></path>
                                <path d="M323.27461 577.016v137.468s64.957 12.705 130.031 3.91V591.59c-41.225-2.262-85.688-7.304-130.031-14.574" fill="#EB1C26"></path>
                                <path d="M788.09761 432.536s-121.98 40.387-283.743 41.539h-0.962c-161.497-1.147-283.328-41.401-283.744-41.539l-40.854 106.952c102.186 32.31 228.837 53.135 324.598 51.926l0.96-0.002c95.768 1.216 222.4-19.61 324.6-51.924l-40.855-106.952z" fill="#EB1C26"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-gray-400 text-xs">QQ</div>
                            <div class="text-white font-medium"><?php echo htmlspecialchars($config['kf']['qq']); ?></div>
                        </div>
                        <button class="copy-btn px-3 py-1.5 bg-orange-500/20 hover:bg-orange-500/30 text-orange-400 text-sm rounded-lg transition-colors flex items-center gap-1" data-text="<?php echo htmlspecialchars($config['kf']['qq']); ?>">
                            <i class="fa fa-copy"></i>
                            复制
                        </button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($config['kf']['wechat'])): ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-800/50 rounded-lg">
                        <div class="w-10 h-10 bg-green-500/20 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6" viewBox="0 0 1024 1024">
                                <path d="M512 85.333333c235.648 0 426.666667 191.018667 426.666667 426.666667s-191.018667 426.666667-426.666667 426.666667S85.333333 747.648 85.333333 512 276.352 85.333333 512 85.333333z m119.04 366.293334c-100.608 0-179.797333 66.474667-179.797333 147.925333 0 81.749333 79.232 147.968 179.797333 147.968 21.077333 0 42.325333-5.12 63.445333-10.197333l57.984 30.677333-15.914666-50.986667c42.453333-30.72 74.112-71.466667 74.112-117.461333 0-81.493333-84.608-147.925333-179.626667-147.925333zM424.746667 298.666667C308.48 298.666667 213.333333 375.04 213.333333 472.064c0 56.021333 31.658667 101.973333 84.565334 137.685333l-21.077334 61.312 73.898667-35.712c26.453333 4.992 47.616 10.24 74.026667 10.24 6.656 0 13.226667-0.341333 19.712-0.810666a148.224 148.224 0 0 1-6.528-42.752c0-89.088 79.317333-161.365333 179.712-161.365334 6.869333 0 13.653333 0.469333 20.437333 1.194667C619.776 359.68 528.725333 298.666667 424.746667 298.666667z m148.010666 234.624c16.042667 0 26.453333 10.24 26.453334 20.352 0 10.325333-10.410667 20.394667-26.453334 20.394666-10.538667 0-21.162667-10.069333-21.162666-20.394666 0-10.154667 10.666667-20.352 21.162666-20.352z m116.266667 0c15.914667 0 26.453333 10.24 26.453333 20.352 0 10.325333-10.538667 20.394667-26.453333 20.394666-10.410667 0-21.034667-10.069333-21.034667-20.394666 0-10.154667 10.581333-20.352 21.034667-20.352z m-185.002667-147.925334c15.957333 0 26.453333 10.154667 26.453334 25.472 0 15.274667-10.496 25.472-26.453334 25.472-15.786667 0-31.701333-10.24-31.701333-25.472 0-15.36 15.872-25.472 31.701333-25.472z m-147.968 0c15.872 0 26.410667 10.069333 26.410667 25.472 0 15.274667-10.538667 25.472-26.410667 25.472s-31.829333-10.24-31.829333-25.472c0-15.36 15.957333-25.472 31.829333-25.472z" fill="#22C787"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-gray-400 text-xs">微信</div>
                            <div class="text-white font-medium"><?php echo htmlspecialchars($config['kf']['wechat']); ?></div>
                        </div>
                        <button class="copy-btn px-3 py-1.5 bg-orange-500/20 hover:bg-orange-500/30 text-orange-400 text-sm rounded-lg transition-colors flex items-center gap-1" data-text="<?php echo htmlspecialchars($config['kf']['wechat']); ?>">
                            <i class="fa fa-copy"></i>
                            复制
                        </button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($config['kf']['phone'])): ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-800/50 rounded-lg">
                        <div class="w-10 h-10 bg-orange-500/20 rounded-full flex items-center justify-center">
                            <i class="fa fa-phone text-orange-500"></i>
                        </div>
                        <div class="flex-1">
                            <div class="text-gray-400 text-xs">电话</div>
                            <div class="text-white font-medium"><?php echo htmlspecialchars($config['kf']['phone']); ?></div>
                        </div>
                        <button class="copy-btn px-3 py-1.5 bg-orange-500/20 hover:bg-orange-500/30 text-orange-400 text-sm rounded-lg transition-colors flex items-center gap-1" data-text="<?php echo htmlspecialchars($config['kf']['phone']); ?>">
                            <i class="fa fa-copy"></i>
                            复制
                        </button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($config['kf']['email'])): ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-800/50 rounded-lg">
                        <div class="w-10 h-10 bg-red-500/20 rounded-full flex items-center justify-center">
                            <i class="fa fa-envelope text-red-500"></i>
                        </div>
                        <div class="flex-1">
                            <div class="text-gray-400 text-xs">邮箱</div>
                            <div class="text-white font-medium"><?php echo htmlspecialchars($config['kf']['email']); ?></div>
                        </div>
                        <button class="copy-btn px-3 py-1.5 bg-orange-500/20 hover:bg-orange-500/30 text-orange-400 text-sm rounded-lg transition-colors flex items-center gap-1" data-text="<?php echo htmlspecialchars($config['kf']['email']); ?>">
                            <i class="fa fa-copy"></i>
                            复制
                        </button>
                    </div>
                <?php endif; ?>
                <?php if (empty($config['kf']['qq']) && empty($config['kf']['wechat']) && empty($config['kf']['phone']) && empty($config['kf']['email'])): ?>
                    <div class="text-center text-gray-500 py-4">
                        <p>暂无客服联系方式</p>
                        <p class="text-xs mt-1">请联系管理员配置客服信息</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        let selectedProduct = null;
        let selectedPayType = 1;
        let skuStockMap = {};

        function selectPayType(type) {
            selectedPayType = type;
            if (type === 1) {
                document.getElementById('wxBtn').style.borderColor = '#ff6600';
                document.getElementById('wxBtn').style.backgroundColor = '#1a1a24';
                document.getElementById('zfbBtn').style.borderColor = '#444';
                document.getElementById('zfbBtn').style.backgroundColor = '#1a1a24';
                document.getElementById('zfbBtn').style.boxShadow = 'none';
            } else {
                document.getElementById('zfbBtn').style.borderColor = '#ff6600';
                document.getElementById('zfbBtn').style.backgroundColor = '#1a1a24';
                document.getElementById('wxBtn').style.borderColor = '#444';
                document.getElementById('wxBtn').style.backgroundColor = '#1a1a24';
                document.getElementById('wxBtn').style.boxShadow = 'none';
            }
        }

        function getCurrentStock() {
            if (!selectedProduct) return 999;
            const selectedSku = document.getElementById('selectedSku');
            if (selectedSku && selectedSku.value) {
                const parts = selectedSku.value.split('_');
                const skuName = parts.length > 1 ? parts.slice(1).join('_') : selectedSku.value;
                return skuStockMap[skuName] !== undefined ? skuStockMap[skuName] : 0;
            }

            return skuStockMap['无SKU'] !== undefined ? skuStockMap['无SKU'] : (selectedProduct.stock || 0);
        }

        function changeQuantity(delta) {
            const input = document.getElementById('buyQuantity');
            const stock = getCurrentStock();
            let value = parseInt(input.value) || 1;
            value = Math.max(1, Math.min(stock, value + delta));
            input.value = value;
            updateTotalPrice();
        }

        function updateTotalPrice() {
            if (selectedProduct) {
                const quantity = parseInt(document.getElementById('buyQuantity').value) || 1;
                let currentPrice = selectedProduct.price;
                const selectedSku = document.getElementById('selectedSku');
                let skus = [];
                try {
                    skus = typeof selectedProduct.skus === 'string' ? JSON.parse(selectedProduct.skus) : (selectedProduct.skus || []);
                } catch (e) {
                    skus = [];
                }
                if (selectedSku && skus && skus.length > 0) {

                    const skuValue = selectedSku.value;
                    const skuName = skuValue.includes('_') ? skuValue.split('_')[1] : skuValue;
                    const sku = skus.find(s => s.name === skuName);
                    if (sku) {
                        currentPrice = sku.price;
                    }
                }
                const totalPrice = (currentPrice * quantity).toFixed(2);
                document.getElementById('mPrice').innerText = `¥${totalPrice}`;
            }
        }

        function closeModal(modalId) {
            const backdrop = document.getElementById(modalId);
            const modal = backdrop.querySelector('.modal');
            backdrop.classList.add('closing');
            modal.classList.add('closing');
            setTimeout(() => {
                backdrop.style.display = 'none';
                backdrop.classList.remove('closing');
                modal.classList.remove('closing');
            }, 200);
        }

        function fetchCategories() {
            fetch('api.php?action=getCategories')
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1 && data.data) {
                        renderCate(data.data);
                    }
                });
        }

        function fetchProducts(cate = '全部') {
            showLoadingSkeleton();

            fetch('api.php?action=getProducts')
                .then(response => {
                    return response.json();
                })
                .then(data => {

                    if (data.code === 1 && data.data) {
                        const filtered = cate === '全部' ? data.data : data.data.filter(x => x.category === cate);

                        renderGoods(filtered);
                    } else {
                        showToast(data.msg || '获取商品列表失败', 'error');
                    }
                })
                .catch(error => {
                    showToast('网络请求失败，请稍后重试', 'error');
                });
        }

        function showLoadingSkeleton() {
            const goodsBox = document.getElementById('goodsBox');

            fetch('api.php?action=getProducts')
                .then(response => response.json())
                .then(data => {
                    const count = data.code === 1 && data.data ? data.data.length : 4;

                    let html = '';
                    for (let i = 0; i < count; i++) {
                        html += `
                        <div class="skeleton-card">
                            <div class="skeleton w-full h-36 mb-4"></div>
                            <div class="skeleton w-3/4 h-5 mb-2"></div>
                            <div class="skeleton w-1/2 h-4 mb-3"></div>
                            <div class="skeleton w-1/3 h-4 mb-3"></div>
                            <div class="flex justify-between items-center">
                                <div class="skeleton w-16 h-4"></div>
                                <div class="skeleton w-16 h-8 rounded"></div>
                            </div>
                        </div>`;
                    }

                    goodsBox.innerHTML = html;
                })
                .catch(() => {

                    let html = '';
                    for (let i = 0; i < 4; i++) {
                        html += `
                        <div class="skeleton-card">
                            <div class="skeleton w-full h-36 mb-4"></div>
                            <div class="skeleton w-3/4 h-5 mb-2"></div>
                            <div class="skeleton w-1/2 h-4 mb-3"></div>
                            <div class="skeleton w-1/3 h-4 mb-3"></div>
                            <div class="flex justify-between items-center">
                                <div class="skeleton w-16 h-4"></div>
                                <div class="skeleton w-16 h-8 rounded"></div>
                            </div>
                        </div>`;
                    }
                    goodsBox.innerHTML = html;
                });
        }

        function renderCate(categories) {
            const cateArr = ['全部', ...categories];
            let html = '';
            cateArr.forEach((item, i) => {
                const cls = i === 0 ? 'cate-btn active' : 'cate-btn';
                html += `<button class="${cls}" data-cate="${escapeHtml(item)}">${escapeHtml(item)}</button>`;
            });
            document.getElementById('cateBox').innerHTML = html;

            document.querySelectorAll('.cate-btn').forEach(btn => {
                btn.onclick = function() {
                    document.querySelectorAll('.cate-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    const c = this.dataset.cate;
                    fetchProducts(c);
                };
            });
        }

        function showToast(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const icons = {
                info: '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
                success: '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
                error: '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
                warning: '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>'
            };

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <span class="toast-icon">${icons[type] || icons.info}</span>
                <span class="toast-message"></span>
            `;
            toast.querySelector('.toast-message').textContent = message;

            container.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'toastSlideOut 0.3s ease-out forwards';
                setTimeout(() => {
                    container.removeChild(toast);
                }, 300);
            }, 3000);
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function renderGoods(list) {
            const goodsBox = document.getElementById('goodsBox');

            let html = '';
            list.forEach(item => {
                const stock = parseInt(item.stock) || 0;
                const disabled = stock <= 0 ? 'disabled' : '';
                const tagHtml = stock <= 0 ?
                    '<span class="bg-gray-600 text-gray-300 text-xs px-2 py-1 rounded">售罄</span>' :
                    '<span class="tag-auto">自动发货</span>';

                const name = escapeHtml(item.name);
                const description = escapeHtml(item.description || '暂无描述');
                let image = escapeHtml(item.icon || 'templates/image/bg.jpg');

                let displayPrice = item.price;
                try {
                    const skus = typeof item.skus === 'string' ? JSON.parse(item.skus) : item.skus;
                    if (skus && skus.length > 0 && skus[0].price) {
                        displayPrice = skus[0].price;
                    }
                } catch (e) {}

                html += `
        <div class="product-card p-4 animate-fadeIn">
            <div class="relative mb-3">
                <img src="${image}" alt="${name}" class="w-full h-36 object-cover rounded" onerror="this.src='templates/image/bg.jpg'">
                <div class="absolute top-2 left-2 flex gap-1">${tagHtml}</div>
            </div>
            <h3 class="text-orange-400 font-bold mb-2 text-sm">${name}</h3>
            <p class="text-gray-400 text-xs mb-3">${description}</p>
            <div class="text-yellow-500 text-sm font-bold mb-3">售价：<span class="text-orange-400 text-base">¥${escapeHtml(String(displayPrice))}</span></div>
            <div class="flex justify-between items-center">
                <div class="text-xs text-gray-500">库存：${escapeHtml(String(stock))} | 销量：${escapeHtml(String(item.sales))}</div>
                <button onclick="openBuyModal(${Number(item.id)})" class="btn-buy px-4 py-1.5 text-xs rounded" ${disabled}>
                    ${stock <= 0 ? '已售罄' : '立即购买'}
                </button>
            </div>
        </div>`;
            });

            setTimeout(() => {
                goodsBox.innerHTML = html;
            }, 300);
        }

        function openBuyModal(id) {
            document.getElementById('orderModal').style.display = 'none';

            Promise.all([
                fetch('api.php?action=getProducts').then(r => r.json()),
                fetch(`api.php?action=getCardsGroupBySku&productId=${id}`).then(r => r.json())
            ]).then(([productsData, skuStockData]) => {
                if (productsData.code === 1 && productsData.data) {
                    const g = productsData.data.find(x => String(x.id) === String(id));
                    if (g) {
                        selectedProduct = g;
                        document.getElementById('mTitle').innerText = escapeHtml(g.name);
                        document.getElementById('mDesc').innerText = escapeHtml(g.description || '暂无描述');
                        document.getElementById('buyQuantity').value = 1;
                        document.getElementById('buyAccount').value = '';

                        const accountInput = document.getElementById('buyAccount');
                        if (showEmailInput) {
                            accountInput.style.display = 'block';
                        } else {
                            accountInput.style.display = 'none';
                        }

                        const skuSection = document.getElementById('skuSection');
                        const skuOptions = document.getElementById('skuOptions');

                        let skus = [];
                        try {
                            skus = typeof g.skus === 'string' ? JSON.parse(g.skus) : (g.skus || []);
                        } catch (e) {
                            skus = [];
                        }

                        skuStockMap = {};
                        if (skuStockData.code === 1 && skuStockData.data) {
                            skuStockData.data.forEach(item => {
                                skuStockMap[item.sku] = item.count;
                            });
                        }

                        if (skus && skus.length > 0) {
                            document.getElementById('mPrice').innerText = `¥${parseFloat(g.price).toFixed(2)}`;
                            document.getElementById('mOldPrice').innerText = `原价 ¥${parseFloat(g.original_price).toFixed(2)}`;
                            const noSkuStock = skuStockMap['无SKU'] !== undefined ? skuStockMap['无SKU'] : 0;
                            document.getElementById('stockInfo').innerText = `库存: ${noSkuStock}`;

                            skuSection.style.display = 'block';
                            skuOptions.innerHTML = '';
                            skus.forEach((sku, index) => {
                                const button = document.createElement('button');

                                button.className = 'px-3 py-1.5 rounded-lg bg-gray-700 text-gray-300 text-sm hover:bg-gray-600';
                                button.innerHTML = `${escapeHtml(sku.name)} ¥${escapeHtml(String(sku.price))}`;
                                button.onclick = function(event) {
                                    selectSku(sku, g, skus, index, skuStockMap);
                                };
                                skuOptions.appendChild(button);
                            });

                            document.getElementById('selectedSku')?.remove();
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.id = 'selectedSku';
                            hiddenInput.value = '';
                            document.getElementById('buyModal').querySelector('.modal').appendChild(hiddenInput);
                        } else {
                            document.getElementById('mPrice').innerText = `¥${parseFloat(g.price).toFixed(2)}`;
                            document.getElementById('mOldPrice').innerText = `原价 ¥${parseFloat(g.original_price).toFixed(2)}`;
                            document.getElementById('stockInfo').innerText = `库存: ${g.stock}`;
                            skuSection.style.display = 'none';
                            document.getElementById('selectedSku')?.remove();
                        }

                        document.getElementById('buyModal').style.display = 'flex';
                        selectPayType(1);
                    }
                }
            }).catch(error => {
                showToast('获取商品信息失败', 'error');
            });
        }

        function closeBuyModal() {
            closeModal('buyModal');
        }

        function selectSku(sku, product, skus, index, skuStockMap = {}) {

            document.getElementById('mPrice').innerText = `¥${parseFloat(sku.price).toFixed(2)}`;
            document.getElementById('mOldPrice').innerText = `原价 ¥${parseFloat(product.original_price).toFixed(2)}`;
            const displayStock = skuStockMap[sku.name] !== undefined ? skuStockMap[sku.name] : 0;
            document.getElementById('stockInfo').innerText = `库存: ${displayStock}`;

            document.querySelectorAll('#skuOptions button').forEach(btn => {
                btn.className = 'px-3 py-1.5 rounded-lg bg-gray-700 text-gray-300 text-sm hover:bg-gray-600';
            });
            const buttons = document.querySelectorAll('#skuOptions button');
            if (index !== undefined && index >= 0 && buttons[index]) {
                buttons[index].className = 'px-3 py-1.5 rounded-lg bg-orange-500 text-white text-sm';
            }

            document.getElementById('selectedSku').value = `${index}_${sku.name}`;
        }

        function confirmBuy() {

            const accountInput = document.getElementById('buyAccount');
            const account = accountInput.value;
            const quantity = parseInt(document.getElementById('buyQuantity').value) || 1;
            const sku = document.getElementById('selectedSku')?.value || '';

            if (showEmailInput) {
                if (!account) {
                    showToast('请输入接收邮箱', 'warning');
                    return;
                }

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(account)) {
                    showToast('请输入正确的邮箱格式', 'warning');
                    return;
                }
            }

            if (!selectedProduct) {
                showToast('请选择商品', 'warning');
                return;
            }

            closeModal('buyModal');

            let params = 'productId=' + selectedProduct.id + '&payType=' + selectedPayType + '&quantity=' + quantity + '&sku=' + encodeURIComponent(sku) + '&csrf_token=' + encodeURIComponent('<?php echo generateCSRFToken(); ?>');
            if (showEmailInput) {
                params += '&account=' + encodeURIComponent(account);
            }

            fetch('api.php?action=createOrder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params
                })
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1 && data.data && data.data.orderId) {
                        window.location.href = 'pay.php?orderId=' + encodeURIComponent(data.data.orderId);
                    } else {
                        showToast(data.msg || '创建订单失败', 'error');
                    }
                })
                .catch(error => {
                    showToast('请求失败: ' + error.message, 'error');
                });
        }

        function closePayModal() {
            document.getElementById('payModal').style.display = 'none';
            document.getElementById('payProgress').style.width = '0%';
        }

        function checkPayStatus(orderId) {
            fetch('api.php?action=checkOrder&orderId=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1 && data.data && data.data.state === 1) {
                        closePayModal();
                        showOrderDetail(orderId);
                    } else {
                        setTimeout(() => checkPayStatus(orderId), 3000);
                    }
                })
                .catch(error => {
                    setTimeout(() => checkPayStatus(orderId), 3000);
                });
        }

        function showOrderModal() {
            document.getElementById('orderResult').style.display = 'none';
            document.getElementById('orderPayId').value = '';
            document.getElementById('orderModal').style.display = 'flex';
        }

        function closeOrderModal() {
            closeModal('orderModal');
        }

        function initCopyButtons() {
            document.querySelectorAll('.copy-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const text = this.getAttribute('data-text');
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(text).then(() => {
                            showToast('已复制到剪贴板', 'success');
                        }).catch(err => {
                            fallbackCopy(text);
                        });
                    } else {
                        fallbackCopy(text);
                    }
                });
            });
        }

        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-9999px';
            textArea.style.top = '-9999px';
            document.body.appendChild(textArea);
            textArea.select();
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showToast('已复制到剪贴板', 'success');
                } else {
                    showToast('复制失败', 'error');
                }
            } catch (err) {
                showToast('复制失败', 'error');
            }
            document.body.removeChild(textArea);
        }

        function showKfModal() {
            document.getElementById('kfModal').style.display = 'flex';
        }

        function closeKfModal() {
            closeModal('kfModal');
        }

        function showHome() {
            window.location.reload();
        }

        function showOrderDetail(orderId) {
            document.getElementById('orderPayId').value = orderId;
            document.getElementById('orderResult').style.display = 'none';
            document.getElementById('orderModal').style.display = 'flex';

            checkOrderStatus(orderId);
        }

        function searchOrdersByAccount(account) {
            if (!account) {
                showToast('请输入接收邮箱', 'warning');
                return;
            }

            fetch('api.php?action=searchOrdersByAccount&account=' + encodeURIComponent(account))
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1 && data.data) {
                        renderOrderList(data.data);
                    } else {
                        showToast(data.msg || '查询失败', 'error');
                    }
                });
        }

        function renderOrderList(orders) {
            let html = '<div class="space-y-4">';
            orders.forEach(order => {
                let cardsHtml = '';
                if (order.cards && order.cards.length > 0) {
                    cardsHtml = `
                        <div class="mt-3 pt-3 border-t border-gray-700">
                            <div class="text-sm text-gray-400 mb-1">卡密信息:</div>
                            <div class="font-mono text-green-400 text-sm">
                                ${order.cards.join('<br>')}
                            </div>
                        </div>
                    `;
                }
                html += `
                    <div class="p-4 bg-gray-800/50 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-orange-400 font-mono">${escapeHtml(order.payId || '')}</span>
                            <span class="text-sm ${order.status === 'completed' ? 'text-green-400' : 'text-yellow-400'}">
                                ${order.status === 'completed' ? '已完成' : '待支付'}
                            </span>
                        </div>
                        <div class="text-sm">
                            <div>商品: ${escapeHtml(order.product_name)}</div>
                            <div>金额: ¥${escapeHtml(String(order.price))}</div>
                            <div>时间: ${escapeHtml(order.created_at || '')}</div>
                        </div>
                        ${cardsHtml}
                    </div>
                `;
            });
            html += '</div>';
            document.getElementById('orderResult').innerHTML = html;
            document.getElementById('orderResult').style.display = 'block';
        }

        function checkOrderStatus(orderId) {
            const url = orderId.startsWith('FAKA') ?
                'api.php?action=getOrder&payId=' + orderId :
                'api.php?action=getOrder&orderId=' + orderId;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1 && data.data) {
                        renderOrderDetail(data.data);
                        const status = data.data.status;
                        const state = data.data.state;
                        if ((status === 0 || status === 'pending' || status === 'processing') &&
                            (state === 0 || state === 'pending' || state === 'processing')) {
                            setTimeout(() => checkOrderStatus(orderId), 3000);
                        }
                    } else {
                        document.getElementById('orderResult').innerHTML = '<p class="text-red-400 text-center text-lg">订单不存在</p>';
                        document.getElementById('orderResult').style.display = 'block';
                    }
                })
                .catch(error => {
                    showToast('查询失败: ' + error.message);
                });
        }

        function renderOrderDetail(order) {
            const statusNum = parseInt(order.status) || parseInt(order.state) || 0;
            const statusText = {
                0: '待支付',
                1: '已支付',
                2: '已完成',
                3: '已取消',
                'pending': '待支付',
                'completed': '已完成',
                'cancelled': '已取消'
            };
            const statusColor = {
                0: 'text-yellow-400',
                1: 'text-blue-400',
                2: 'text-green-400',
                3: 'text-red-400',
                'pending': 'text-yellow-400',
                'completed': 'text-green-400',
                'cancelled': 'text-red-400'
            };

            const payTypeText = {
                1: '微信支付',
                2: '支付宝',
                'wechat': '微信支付',
                'alipay': '支付宝'
            };

            const orderId = order.order_id || order.payId || order.orderId || '未知';
            const statusKey = typeof order.status === 'string' ? order.status : statusNum;
            const payType = payTypeText[order.payType] || '未知支付方式';
            const reallyPrice = order.reallyPrice || order.price || 0;
            const paidAt = order.paid_at || order.paidAt || order.created_at || '未知';

            let cardsHtml = '';
            const cards = order.cards || (order.data && order.data.cards) || [];
            if (cards && cards.length > 0) {
                cardsHtml = `
        <div class="mt-4 p-4 bg-orange-500/10 rounded-lg border border-orange-500/30">
            <div class="text-orange-400 font-bold text-lg mb-3">卡密信息</div>
            <div class="space-y-2">
                ${cards.map((card, i) => `
                <div class="flex items-center gap-2 p-3 bg-black/30 rounded-lg">
                    <span class="text-gray-400 text-sm">卡密${i + 1}:</span>
                    <span class="text-green-400 font-mono text-sm font-bold">${escapeHtml(card)}</span>
                </div>
                `).join('')}
            </div>
        </div>
        `;
            }

            const html = `
    <div class="p-4 bg-gray-800/50 rounded-lg">
        <div class="flex justify-between items-center mb-3">
            <span class="text-gray-400 text-sm">订单号</span>
            <span class="text-orange-400 font-mono text-sm font-bold">${escapeHtml(orderId)}</span>
        </div>
        <div class="flex justify-between items-center mb-3">
            <span class="text-gray-400 text-sm">商品名称</span>
            <span class="text-white text-sm">${escapeHtml(order.product_name) || '未知商品'}</span>
        </div>
        <div class="flex justify-between items-center mb-3">
            <span class="text-gray-400 text-sm">购买数量</span>
            <span class="text-white text-sm">${escapeHtml(String(order.quantity || 1))}</span>
        </div>
        <div class="flex justify-between items-center mb-3">
            <span class="text-gray-400 text-sm">支付方式</span>
            <span class="text-white text-sm">${escapeHtml(payType)}</span>
        </div>
        <div class="flex justify-between items-center mb-3">
            <span class="text-gray-400 text-sm">订单金额</span>
            <span class="text-yellow-400 text-xl font-bold">¥${escapeHtml(String(order.price || 0))}</span>
        </div>
        <div class="flex justify-between items-center mb-3">
            <span class="text-gray-400 text-sm">实付金额</span>
            <span class="text-green-400 text-lg font-bold">¥${escapeHtml(String(reallyPrice))}</span>
        </div>
        <div class="flex justify-between items-center mb-3">
            <span class="text-gray-400 text-sm">订单状态</span>
            <span class="${statusColor[statusKey] || 'text-gray-400'} text-lg font-bold">${statusText[statusKey] || '未知'}</span>
        </div>
        <div class="flex justify-between items-center mb-3">
            <span class="text-gray-400 text-sm">支付时间</span>
            <span class="text-gray-300 text-sm">${escapeHtml(paidAt)}</span>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-gray-400 text-sm">创建时间</span>
            <span class="text-gray-300 text-sm">${escapeHtml(order.created_at || order.date || '未知')}</span>
        </div>
        ${cardsHtml}
    </div>
    `;

            document.getElementById('orderResult').innerHTML = html;
            document.getElementById('orderResult').style.display = 'block';
        }

        document.getElementById('buyQuantity').addEventListener('input', function() {
            const stock = getCurrentStock();
            let value = parseInt(this.value) || 1;
            value = Math.max(1, Math.min(stock, value));
            this.value = value;
            updateTotalPrice();
        });

        document.getElementById('orderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const payId = document.getElementById('orderPayId').value;
            if (payId) {
                checkOrderStatus(payId);
            }
        });

        document.getElementById('searchInp').addEventListener('input', function() {
            const key = this.value.toLowerCase();
            fetch('api.php?action=getProducts')
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1 && data.data) {
                        const filtered = data.data.filter(x =>
                            x.name.toLowerCase().includes(key) ||
                            (x.description && x.description.toLowerCase().includes(key))
                        );
                        renderGoods(filtered);
                    }
                });
        });

        fetchCategories();
        fetchProducts();
        fetchSiteConfig();

        let siteConfig = {};
        let showEmailInput = false;

        function fetchSiteConfig() {
            fetch('api.php?action=getSiteConfig')
                .then(response => response.json())
                .then(data => {
                    if (data.code === 1 && data.data) {
                        siteConfig = data.data;
                        showEmailInput = data.data.mail_enabled === true;
                        const config = data.data;

                        document.getElementById('announcementContent').textContent = config.announcement;
                        document.getElementById('announcementContent').innerHTML = document.getElementById('announcementContent').textContent.replace(/\n/g, '<br>');
                        document.getElementById('videoTitle').textContent = config.video_title || '服务介绍';

                        const videoUrl = config.video_url || '';
                        let videoHtml = '';
                        let displayUrl = videoUrl;

                        if (videoUrl && videoUrl.indexOf('://') === -1) {
                            if (!videoUrl.startsWith('/')) {
                                displayUrl = '/' + videoUrl;
                            }
                        }

                        if (videoUrl.endsWith('.mp4') || videoUrl.endsWith('.webm') || videoUrl.endsWith('.ogg') || videoUrl.endsWith('.mov')) {
                            videoHtml = `
                    <video id="mainVideo" width="100%" height="100%" poster="templates/image/bg.jpg">
                        <source src="${displayUrl}" type="video/mp4">
                    </video>
                    <div class="video-placeholder" id="videoPlaceholder">
                        <i class="fa fa-play-circle play-icon"></i>
                    </div>
                `;
                        } else if (videoUrl) {
                            videoHtml = `
                    <img src="${displayUrl}" class="w-full h-full object-cover" alt="视频封面">
                    <div class="video-placeholder">
                        <i class="fa fa-play-circle play-icon"></i>
                    </div>
                `;
                        } else {
                            videoHtml = `
                    <img src="templates/image/bg.jpg" class="w-full h-full object-cover" alt="视频封面">
                    <div class="video-placeholder">
                        <i class="fa fa-play-circle play-icon"></i>
                    </div>
                `;
                        }

                        document.getElementById('videoContainer').innerHTML = videoHtml;

                        setupVideoPlayer();
                    }
                });
        }

        function setupVideoPlayer() {
            const video = document.getElementById('mainVideo');
            const placeholder = document.getElementById('videoPlaceholder');

            if (video && placeholder) {
                placeholder.addEventListener('click', function() {
                    if (video.paused) {
                        placeholder.classList.add('hidden');
                        video.play();
                    }
                });

                video.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (!video.paused) {
                        video.pause();
                        placeholder.classList.remove('hidden');
                    }
                });

                video.addEventListener('ended', function() {
                    placeholder.classList.remove('hidden');
                    video.currentTime = 0;
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            initCopyButtons();
            const urlParams = new URLSearchParams(window.location.search);
            const orderId = urlParams.get('orderId');
            if (orderId) {
                setTimeout(() => {
                    showOrderDetail(orderId);
                }, 500);
            }
        });


        window.openBuyModal = openBuyModal;
        window.selectPayType = selectPayType;
        window.confirmBuy = confirmBuy;
        window.showOrderModal = showOrderModal;
        window.checkOrderStatus = checkOrderStatus;
    </script>

    <?php include __DIR__ . '/templates/footer.php'; ?>