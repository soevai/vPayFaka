<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 支付页面
 * @License     MIT
 */
require_once 'functions.php';
include 'config.php';
?>
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Content-Language" content="zh-cn">
    <meta name="apple-mobile-web-app-capable" content="no" />
    <meta name="apple-touch-fullscreen" content="yes" />
    <meta name="format-detection" content="telephone=no,email=no" />
    <meta name="apple-mobile-web-app-status-bar-style" content="white">
    <meta name="renderer" content="webkit" />
    <meta name="force-rendering" content="webkit" />
    <meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1" />
    <meta http-equiv="Expires" content="0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Cache-control" content="no-cache">
    <meta http-equiv="Cache" content="no-cache">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>扫码支付 - <?php echo htmlspecialchars($config['site']['name'] ?? ''); ?></title>
    <link rel="icon" href="<?php echo !empty($config['site']['logo']) ? htmlspecialchars($config['site']['logo']) : 'templates/image/favicon.ico'; ?>" type="image/x-icon">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            background-color: #f0f0f0;
            text-align: center;
            padding: 40px 20px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .header svg {
            width: 36px;
            height: 36px;
        }

        .header h1 {
            font-size: 24px;
            color: #333;
            font-weight: 400;
        }

        .wx-color {
            color: #07C160;
        }

        .zfb-color {
            color: #1677FF;
        }

        .lang-switch {
            font-size: 14px;
            color: #666;
            text-align: right;
            margin-bottom: 15px;
        }

        .lang-switch a {
            color: #07c160;
            text-decoration: none;
        }

        .lang-switch.zfb a {
            color: #1677FF;
        }

        .back-merchant-btn {
            display: block;
            width: 100%;
            height: 44px;
            line-height: 44px;
            margin-top: 20px;
            padding: 0 20px;
            border: 1px solid #07C160;
            border-radius: 6px;
            color: #07C160;
            font-size: 15px;
            text-align: center;
            cursor: pointer;
            background-color: transparent;
            transition: background-color 0.2s;
        }

        .back-merchant-btn:hover {
            background-color: #f0f9f0;
        }

        .back-merchant-btn.zfb {
            border-color: #1677FF;
            color: #1677FF;
        }

        .back-merchant-btn.zfb:hover {
            background-color: #e8f0fe;
        }

        .pay-card {
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            max-width: 450px;
            margin: 0 auto;
            padding: 30px 25px;
        }

        .order-info {
            text-align: left;
            margin-bottom: 25px;
            font-size: 15px;
            color: #555;
        }

        .order-info p {
            margin: 8px 0;
        }

        #money {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .order-info .label {
            display: inline-block;
            min-width: 50px;
            color: #666;
        }

        .qr-code-area {
            margin: 20px auto;
            text-align: center;
        }

        .qr-code-area img {
            width: 210px;
            height: 210px;
            display: inline-block;
            border: none;
        }

        .expire-tip {
            display: none;
            color: red;
            font-size: 16px;
            margin: 20px 0;
        }

        .time-item {
            margin: 15px 0;
            font-size: 14px;
            color: #666;
        }

        .time-item strong {
            background: #3ec742;
            color: #fff;
            line-height: 25px;
            font-size: 15px;
            font-family: Arial;
            padding: 0 10px;
            margin-right: 10px;
            border-radius: 5px;
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        .time-item.zfb strong {
            background: #1677FF;
        }

        .tip-text {
            margin-top: 20px;
            font-size: 14px;
            color: #07c160;
        }

        .tip-text.zfb {
            color: #1677FF;
        }

        .detail {
            margin-top: 20px;
            text-align: left;
            font-size: 14px;
            color: #666;
        }

        .detail dl {
            display: none;
            line-height: 28px;
        }

        .detail-open dl {
            display: block;
        }

        .detail .arrow {
            text-align: center;
            color: #07c160;
            cursor: pointer;
            margin-top: 10px;
        }

        .foot {
            text-align: center;
            margin: 30px auto;
            color: #888888;
            font-size: 12px;
            line-height: 20px;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #07C160;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .loading-spinner.zfb {
            border-top-color: #1677FF;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-text {
            margin-top: 15px;
            color: #666;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div id="loading-overlay" class="loading-overlay">
        <div>
            <div class="loading-spinner"></div>
            <div class="loading-text">加载中...</div>
        </div>
    </div>

    <div id="body" style="display: none;">
        <div class="header">
            <svg id="payIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024">
            </svg>
            <h1 id="payTitle"></h1>
        </div>

        <div class="pay-card">
            <div class="lang-switch">
                <a href="javascript:void(0)" onclick="switchLang('zh')">中文</a> | <a href="javascript:void(0)"
                    onclick="switchLang('en')">EN</a>
            </div>
            <div class="order-info">
                <p><span class="label">金额：</span><span id="money">￥0.00</span></p>
                <p><span class="label">订单：</span><span id="payId"></span></p>
                <p><span class="label">描述：</span><span id="productName"></span></p>
            </div>

            <div class="expire-tip" id="timeOut">
                <p>订单已过期，请重新发起支付！</p><br>
            </div>

            <div id="orderbody">
                <div class="qr-code-area">
                    <div style="position: relative;display: inline-block;">
                        <img id='show_qrcode' alt="支付二维码" width="210" height="210">
                    </div>
                </div>

                <div class="time-item">
                    <strong id="hour_show">0时</strong>
                    <strong id="minute_show">0分</strong>
                    <strong id="second_show">0秒</strong>
                </div>

                <button class="back-merchant-btn" onclick="goBack()">← 返回首页</button>

                <div class="detail" id="orderDetail">
                    <dl class="detail-ct" id="desc" style="display: none;">
                        <dt>金额</dt>
                        <dd id="detailPrice"></dd>
                        <dt>商户订单：</dt>
                        <dd id="detailPayId"></dd>
                        <dt>创建时间：</dt>
                        <dd id="detailDate"></dd>
                        <dt>状态</dt>
                        <dd>等待支付</dd>
                    </dl>
                    <a href="javascript:void(0)" class="arrow" onclick="toggleDetail()"><i class="ico-arrow"></i></a>
                </div>
            </div>

            <div class="tip-text" id="tipText">
                如何付款？
            </div>
        </div>

        <div class="foot">
            <div class="inner" id="footText">
                <p>手机用户可保存上方二维码到手机中</p>
                <p>在微信扫一扫中选择“相册”即可</p>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script>
        var orderData = {};
        var payType = 1;

        function toggleDetail() {
            if ($('#orderDetail').hasClass('detail-open')) {
                $('#orderDetail .detail-ct').slideUp(500, function() {
                    $('#orderDetail').removeClass('detail-open');
                });
            } else {
                $('#orderDetail .detail-ct').slideDown(500, function() {
                    $('#orderDetail').addClass('detail-open');
                });
            }
        }

        function formatDate(now) {
            now = new Date(now * 1000)
            return now.getFullYear() +
                "-" + (now.getMonth() > 8 ? (now.getMonth() + 1) : "0" + (now.getMonth() + 1)) +
                "-" + (now.getDate() > 9 ? now.getDate() : "0" + now.getDate()) +
                " " + (now.getHours() > 9 ? now.getHours() : "0" + now.getHours()) +
                ":" + (now.getMinutes() > 9 ? now.getMinutes() : "0" + now.getMinutes()) +
                ":" + (now.getSeconds() > 9 ? now.getSeconds() : "0" + now.getSeconds());
        }

        var myTimer;

        function timer(intDiff) {
            updateTime(intDiff);
            myTimer = window.setInterval(function() {
                intDiff--;
                updateTime(intDiff);
                if (intDiff <= 0) {
                    qrcode_timeout();
                    clearInterval(myTimer);
                }
            }, 1000);
        }

        function updateTime(intDiff) {
            var day = 0,
                hour = 0,
                minute = 0,
                second = 0;
            if (intDiff > 0) {
                day = Math.floor(intDiff / (60 * 60 * 24));
                hour = Math.floor(intDiff / (60 * 60)) - (day * 24);
                minute = Math.floor(intDiff / 60) - (day * 24 * 60) - (hour * 60);
                second = Math.floor(intDiff) - (day * 24 * 60 * 60) - (hour * 60 * 60) - (minute * 60);
            }
            if (minute <= 9) minute = '0' + minute;
            if (second <= 9) second = '0' + second;
            $('#hour_show').html('<s id="h"></s>' + hour + '时');
            $('#minute_show').html('<s></s>' + minute + '分');
            $('#second_show').html('<s></s>' + second + '秒');
        }

        function qrcode_timeout() {
            document.getElementById("orderbody").style.display = "none";
            document.getElementById("timeOut").style.display = "block";
            setTimeout(function() {
                window.location.href = 'index.php';
            }, 3000);
        }

        function goBack() {
            window.location.href = 'index.php';
        }

        function switchLang(lang) {
            var labelElements = document.getElementsByClassName('label');
            var expireTip = document.getElementById('timeOut');
            var backBtn = document.querySelector('.back-merchant-btn');
            var tipText = document.getElementById('tipText');
            var foot = document.getElementById('footText');
            var header = document.getElementById('payTitle');

            if (lang === 'en') {
                labelElements[0].textContent = 'Amount: ';
                labelElements[1].textContent = 'Order: ';
                labelElements[2].textContent = 'Desc: ';
                expireTip.querySelector('p').textContent = 'Order expired, please re-initiate payment!';
                backBtn.textContent = '← Back to Home';
                tipText.textContent = 'How to pay?';
                foot.innerHTML = '<p>Mobile users can save the QR code to their phone</p><p>Select "Album" in ' + (payType === 2 ? 'Alipay' : 'WeChat') + ' scan</p>';
                header.textContent = payType === 2 ? 'Alipay' : 'WeChat Pay';
            } else {
                labelElements[0].textContent = '金额：';
                labelElements[1].textContent = '订单：';
                labelElements[2].textContent = '描述：';
                expireTip.querySelector('p').textContent = '订单已过期，请重新发起支付！';
                backBtn.textContent = '← 返回首页';
                tipText.textContent = '如何付款？';
                foot.innerHTML = '<p>手机用户可保存上方二维码到手机中</p><p>在' + (payType === 2 ? '支付宝' : '微信') + '扫一扫中选择"相册"即可</p>';
                header.textContent = payType === 2 ? '支付宝支付' : '微信支付';
            }
        }

        function getQueryString(name) {
            var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i");
            var r = window.location.search.substr(1).match(reg);
            if (r != null)
                return decodeURI(r[2]);
            return null;
        }

        function loadOrderData(data) {
            orderData = data;

            payType = parseInt(data.payType) || 1;

            document.getElementById('payTitle').textContent = payType === 2 ? '支付宝支付' : '微信支付';
            document.getElementById('payTitle').className = payType === 2 ? 'zfb-color' : 'wx-color';

            var loadingSpinner = document.querySelector('.loading-spinner');
            var langSwitch = document.querySelector('.lang-switch');
            var backBtn = document.querySelector('.back-merchant-btn');
            var timeItem = document.querySelector('.time-item');
            var tipText = document.querySelector('.tip-text');

            if (payType === 2) {
                loadingSpinner.classList.add('zfb');
                langSwitch.classList.add('zfb');
                backBtn.classList.add('zfb');
                timeItem.classList.add('zfb');
                tipText.classList.add('zfb');
            } else {
                loadingSpinner.classList.remove('zfb');
                langSwitch.classList.remove('zfb');
                backBtn.classList.remove('zfb');
                timeItem.classList.remove('zfb');
                tipText.classList.remove('zfb');
            }

            var iconSvg = payType === 2 ?
                '<path d="M588.8 672s-76.8 64-102.4 76.8c-25.6 12.8-44.8 25.6-70.4 32-19.2 6.4-38.4 12.8-51.2 12.8s-25.6 0-38.4 6.4H307.2c-25.6 0-51.2 0-76.8-6.4l-57.6-38.4c-19.2-12.8-32-25.6-38.4-44.8-12.8-19.2-12.8-38.4-12.8-64 0-19.2 6.4-38.4 19.2-57.6 12.8-19.2 25.6-32 44.8-44.8s38.4-19.2 57.6-25.6c12.8-6.4 38.4-6.4 57.6-6.4 19.2 0 44.8 0 64 6.4 19.2 6.4 38.4 6.4 51.2 12.8 19.2 6.4 32 12.8 51.2 19.2 19.2 6.4 32 12.8 44.8 19.2 6.4 6.4 12.8 6.4 19.2 6.4 6.4 0 12.8 6.4 19.2 6.4 19.2-12.8 25.6-32 38.4-51.2 6.4-19.2 19.2-32 25.6-44.8 6.4-12.8 6.4-25.6 12.8-38.4 6.4-6.4 6.4-19.2 6.4-19.2H320v-32h147.2V313.6H262.4v-32h204.8v-64c0-6.4 0-6.4 6.4-12.8 6.4 0 12.8-6.4 19.2-6.4H576v83.2h211.2v32H569.6v76.8h166.4c-6.4 19.2-6.4 44.8-19.2 70.4-6.4 19.2-19.2 44.8-32 70.4-12.8 25.6-32 51.2-51.2 83.2 0 0 166.4 76.8 339.2 108.8 32-64 44.8-134.4 44.8-211.2 0-281.6-230.4-512-512-512C230.4 0 0 230.4 0 512s230.4 512 512 512c172.8 0 332.8-89.6 422.4-224-96-19.2-192-57.6-345.6-128z m-403.2-25.6c0 89.6 96 96 115.2 96 51.2 0 89.6-19.2 121.6-38.4 32-12.8 83.2-64 83.2-64l6.4-6.4c-12.8-6.4-25.6-19.2-38.4-25.6-12.8-6.4-83.2-38.4-140.8-44.8-115.2-12.8-147.2 57.6-147.2 83.2z" fill="#00AAEE"/>' :
                '<path d="M964.16 294.4c-3.328-5.568-5.888-5.76-11.168-2.528-17.44 10.56-35.168 20.64-52.864 30.752-45.024 25.76-90.144 51.424-135.2 77.184-53.344 30.464-106.688 60.896-159.936 91.488-75.328 43.264-150.56 86.656-225.856 129.984-21.056 12.096-41.184 5.6-51.232-16.48-8.448-18.432-16.992-36.832-25.44-55.264-21.92-48.032-43.84-96.064-65.696-144.128-4.032-8.864-2.528-15.264 4.16-20.8 6.304-5.28 13.824-5.248 21.568 0.256 35.136 24.864 70.08 49.92 105.376 74.56 15.84 11.072 33.12 12.64 51.008 4.8 45.824-20.16 91.648-40.224 137.376-60.48 64.928-28.8 129.728-57.76 194.624-86.528a30516.544 30516.544 0 0 1 165.056-72.8c7.136-3.104 7.616-5.44 2.56-11.104-40.96-45.664-89.376-81.248-144.32-108.864a568.64 568.64 0 0 0-159.488-52.064c-33.728-5.856-67.84-9.536-99.712-7.68-45.92-1.28-88.576 3.968-130.976 13.088a560.096 560.096 0 0 0-98.976 30.784c-85.76 35.904-157.696 89.216-211.008 165.76C30.816 336.32 8.352 405.12 7.776 480.48a340.96 340.96 0 0 0 15.264 102.656c20.16 66.24 56.832 122.016 106.176 170.24 16.832 16.416 35.136 31.072 53.792 45.28 11.584 8.8 15.552 20.704 12.224 34.048-6.752 27.136-14.72 54.016-22.144 80.992l-3.648 13.696a15.776 15.776 0 0 0 6.304 17.792c6.496 4.544 13.76 3.424 20.576-0.48 36.352-20.8 72.704-41.6 109.12-62.272 10.624-6.048 21.888-10.528 34.432-8.032 9.312 1.824 18.496 4.416 27.68 6.912 39.36 10.656 79.616 15.808 120.288 17.216 41.248 1.408 82.304-0.64 123.2-7.424 34.112-5.632 67.584-13.504 99.872-25.216 58.976-21.344 113.568-50.72 161.6-91.424 66.944-56.64 113.856-125.6 134.88-211.104a349.12 349.12 0 0 0 5.184-139.2c-7.36-46.304-24.224-89.44-48.416-129.792" fill="#07C160"/>';

            document.getElementById('payIcon').innerHTML = iconSvg;

            document.getElementById('money').textContent = '￥' + (data.reallyPrice || data.price || 0);
            document.getElementById('payId').textContent = data.payId || '';
            document.getElementById('productName').textContent = data.product_name || '支付测试';

            document.getElementById('detailPrice').textContent = data.price || 0;
            document.getElementById('detailPayId').textContent = data.payId || '';
            document.getElementById('detailDate').textContent = formatDate(data.date || Math.floor(Date.now() / 1000));

            var payUrl = data.payUrl;
            var qrCodeUrl = '';

            if (payUrl && payUrl.trim()) {
                payUrl = payUrl.trim();

                if ((payUrl.startsWith('http://') || payUrl.startsWith('https://')) ||
                    payUrl.startsWith('QR.ALIPAY.COM') ||
                    payUrl.startsWith('weixin://') ||
                    payUrl.startsWith('wxpay://') ||
                    payUrl.startsWith('wxp://')) {

                    if (!payUrl.startsWith('http')) {
                        if (payUrl.startsWith('QR.ALIPAY.COM')) {
                            payUrl = 'https://' + payUrl;
                        }
                    }

                    if (payUrl.match(/\.(png|jpg|jpeg|gif|svg)$/i)) {
                        qrCodeUrl = payUrl;
                    } else {
                        qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' + encodeURIComponent(payUrl);
                    }
                } else {
                    var fallbackData = data.orderId || data.payId || 'no_payment_url';
                    qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' + encodeURIComponent(fallbackData);
                }
            } else {
                var fallbackData = data.orderId || data.payId || 'no_payment_url';
                qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=' + encodeURIComponent(fallbackData);
            }

            document.getElementById('show_qrcode').src = qrCodeUrl;
            document.getElementById('show_qrcode').onload = function() {
                document.getElementById('loading-overlay').style.display = 'none';
                document.getElementById('body').style.display = 'block';
            };
            document.getElementById('show_qrcode').onerror = function() {
                document.getElementById('loading-overlay').style.display = 'none';
                document.getElementById('body').style.display = 'block';
            };

            var timeOut = data.timeOut || 5;
            var date = data.date || Math.floor(Date.now() / 1000);
            var time = Math.floor(Date.now() / 1000) - date;
            time = timeOut * 60 - time;

            if (time < 0) time = 0;

            timer(time);

            checkPayment();
        }

        function getOrderData() {
            var orderId = getQueryString("orderId");


            if (!orderId) {
                showToast('订单号为空', 'error');
                timer(0);
                return;
            }

            fetch('api.php?action=getOrder&orderId=' + orderId)
                .then(response => response.json())
                .then(data => {

                    if (data.code == 1 && data.data) {
                        loadOrderData(data.data);
                    } else {
                        showToast(data.msg || '获取订单失败', 'error');
                        timer(0);
                        checkPayment();
                    }
                })
                .catch(error => {
                    showToast('网络请求失败', 'error');
                    timer(0);
                    checkPayment();
                });
        }

        function checkPayment() {
            var orderId = getQueryString("orderId");
            fetch('api.php?action=checkOrder&orderId=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.code == 1 && data.data) {
                        var isCompleted = false;

                        if (data.data.state !== undefined) {
                            isCompleted = parseInt(data.data.state) === 1;
                        }

                        if (data.data.status !== undefined) {
                            isCompleted = isCompleted || data.data.status === 'completed';
                        }

                        if (data.data.order && data.data.order.status !== undefined) {
                            isCompleted = isCompleted || data.data.order.status === 'completed';
                        }

                        if (isCompleted) {
                            window.location.href = 'index.php?orderId=' + orderId;
                            return;
                        }
                    }
                    setTimeout(checkPayment, 3000);
                })
                .catch(error => {
                    setTimeout(checkPayment, 3000);
                });
        }

        getOrderData();
    </script>
</body>

</html>