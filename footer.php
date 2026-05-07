</main>

<footer class="footer">
    <div class="footer-shell">
        <div class="footer-top">
            <a href="<?php echo home_url(); ?>" class="footer-logo">
                <span class="footer-logo-mark">
                    <?php
                    $logo_icon = get_field('logo_icon', 'option');
                    if ($logo_icon):
                    ?>
                        <img src="<?php echo esc_url($logo_icon['url']); ?>" alt="logo icon">
                    <?php endif; ?>
                </span>
                <?php echo esc_html(get_field('logo_text', 'option')); ?>
            </a>
            <div class="footer-columns">
                <div>
                    <div class="footer-title">Контакты</div>
                    <ul class="footer-list">
                        <?php
                        $email = get_field('email', 'option');
                        $phone = get_field('phone', 'option');
                        $address = get_field('address', 'option');
                        ?>

                        <?php if ($email): ?>
                            <li class="footer-item">
                                <a href="mailto:<?php echo antispambot($email); ?>"><?php echo esc_html($email); ?></a>
                            </li>
                        <?php endif; ?>

                        <?php if ($phone): ?>
                            <li class="footer-item">
                                <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $phone); ?>"><?php echo esc_html($phone); ?></a>
                            </li>
                        <?php endif; ?>

                        <?php if ($address): ?>
                            <li class="footer-item"><?php echo esc_html($address); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div>
                    <div class="footer-title">Разделы</div>
                    <ul class="footer-list">
                        <li class="footer-item"><a href="catalog.html">Каталог</a></li>
                        <li class="footer-item"><a href="#contacts">Контакты</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-note">
                Портал использует данные браузера (cookies и местоположение) для корректной работы разделов и сбора статистики.
                Оставаясь на сайте, вы соглашаетесь с <a href="privacy-policy.html">политикой конфиденциальности</a>.
            </div>
            <div class="footer-legal">© <?php echo date('Y'); ?> <?php echo esc_html(get_field('company_name', 'option')); ?></div>
        </div>
    </div>
</footer>
</div>
<?php require_once(TEMPLATE_PATH . '_modals.php'); ?>
<?php wp_footer(); ?>
</body>

</html>