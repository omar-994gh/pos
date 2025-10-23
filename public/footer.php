<?php
// footer.php - Footer للنظام
// لا نعرض الـ footer في صفحة الإعدادات
$currentPage = basename($_SERVER['PHP_SELF']);
$hideFooterPages = ['settings.php', 'settings_handler.php'];

if (!in_array($currentPage, $hideFooterPages)):
?>
<footer class="app-footer mt-5">
    <div class="footer-content">
        <div class="footer-top">
            <span class="footer-item">
                <i class="footer-icon">📱</i>
                نسخة POSX v4.0 Beta
            </span>
            <span class="footer-divider">|</span>
            <span class="footer-item">
                <i class="footer-icon">🏢</i>
                شركة صادق حسن لتجهيزات نقاط البيع
            </span>
            <span class="footer-divider">|</span>
            <span class="footer-item">
                <i class="footer-icon">👨‍💻</i>
                تمت البرمجة بواسطة: عمر العثمان
            </span>
            <span class="footer-divider">|</span>
            <span class="footer-item">
                <i class="footer-icon">✨</i>
                نسخة مدفوعة بكامل الميزات
            </span>
        </div>
        <div class="footer-bottom">
            <span class="footer-copyright">
                © <?= date('Y') ?> جميع الحقوق محفوظة
            </span>
        </div>
    </div>
</footer>
<?php endif; ?>
