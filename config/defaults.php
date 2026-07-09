<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 默认配置数据
 * @License     MIT
 */
return [
    ['key' => 'site_name', 'value' => 'vPay Faka', 'description' => '网站名称'],
    ['key' => 'site_title', 'value' => 'vPay Faka - 自动发卡平台', 'description' => '网站标题'],
    ['key' => 'site_description', 'value' => '专业的虚拟商品自动发卡平台', 'description' => '网站描述'],
    ['key' => 'site_keywords', 'value' => '发卡网,自动发卡,虚拟商品,卡密销售', 'description' => '网站关键词'],
    ['key' => 'site_logo', 'value' => '', 'description' => '网站Logo'],
    ['key' => 'site_footer', 'value' => '', 'description' => '网站底部'],
    ['key' => 'video_url', 'value' => '', 'description' => '视频地址'],
    ['key' => 'video_title', 'value' => '', 'description' => '视频标题'],
    ['key' => 'notice_content', 'value' => "vPay Faka 开源自动发卡系统\n简洁 | 轻量 | 安全 | 高效\n作者：@发光的神（VoxShadow）\n仅供学习交流，严禁违法违规与商业用途\n使用责任自负，与作者无关\nGithub：https://github.com/soevai/vPayFaka", 'description' => '公告内容'],
    ['key' => 'pay_apiUrl', 'value' => '', 'description' => 'v免签地址'],
    ['key' => 'pay_secretKey', 'value' => '', 'description' => '支付密钥'],
    ['key' => 'mail_apiUrl', 'value' => '', 'description' => '邮件API地址'],
    ['key' => 'mail_apiSecret', 'value' => '', 'description' => '邮件API密钥(请在后台配置)'],
    ['key' => 'mail_smtpHost', 'value' => 'smtp.qq.com', 'description' => 'SMTP服务器'],
    ['key' => 'mail_smtpPort', 'value' => '465', 'description' => 'SMTP端口'],
    ['key' => 'mail_smtpUser', 'value' => '', 'description' => 'SMTP账号(请在后台配置)'],
    ['key' => 'mail_smtpPass', 'value' => '', 'description' => 'SMTP密码(请在后台配置)'],
    ['key' => 'mail_fromName', 'value' => 'vPay Faka', 'description' => '发件人名称'],
    ['key' => 'admin_username', 'value' => 'admin', 'description' => '管理员用户名'],
    ['key' => 'admin_password', 'value' => password_hash('123456', PASSWORD_DEFAULT), 'description' => '管理员密码'],
    ['key' => 'kf_name', 'value' => '在线客服', 'description' => '客服名称'],
    ['key' => 'kf_qq', 'value' => '', 'description' => '客服QQ'],
    ['key' => 'kf_wechat', 'value' => '', 'description' => '客服微信'],
    ['key' => 'kf_phone', 'value' => '', 'description' => '客服电话'],
    ['key' => 'kf_email', 'value' => '', 'description' => '客服邮箱'],
    ['key' => 'ssl_verify', 'value' => '0', 'description' => 'SSL证书验证(1开启/0关闭)'],
    ['key' => 'show_admin_login', 'value' => '1', 'description' => '前台显示后台登录按钮(1显示/0隐藏)'],
    ['key' => 'admin_access_key', 'value' => '', 'description' => '后台安全码（留空不启用，设置后需带 ?key=xxx 访问后台）'],
];
