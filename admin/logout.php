<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 管理员登出
 * @License     MIT
 */
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <script>
        localStorage.removeItem('adminCurrentPage');
        window.location.href = 'login.php';
    </script>
</head>

<body></body>

</html>