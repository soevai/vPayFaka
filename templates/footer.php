<?php
/**
 * @Author      发光的神 (VoxShadow)
 * @Version     1.0.0
 * @Since       2026-05-01
 * @LastUpdated 2026-05-10
 * @Description vPay 页脚模板
 * @License     MIT
 */
if (isset($config['site']['footer']) && !empty(trim($config['site']['footer']))) { ?>
    <footer class="footer text-center py-6">
        <div class="container mx-auto">
            <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($config['site']['footer']); ?></p>
        </div>
    </footer>
<?php } ?>
</body>

</html>