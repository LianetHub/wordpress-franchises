</main>

<footer class="footer">
    <div class="footer-shell">
        <div class="footer-top">
            <a href="<?php echo home_url(); ?>" class="logo">
                <span class="logo__mark">
                    <?php
                    $logo_icon_footer = get_field('logo_icon_footer', 'option');
                    if ($logo_icon_footer):
                    ?>
                        <img src="<?php echo esc_url($logo_icon_footer['url']); ?>" alt="logo icon footer">
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
                        <li class="footer-item"><a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">Каталог</a></li>
                        <li class="footer-item"><a href="<?php echo esc_url(function_exists('franchises_contacts_page_url') ? franchises_contacts_page_url() : home_url('/#contacts')); ?>">Контакты</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-note">
                Портал использует данные браузера (cookies и местоположение) для корректной работы разделов и сбора статистики.
                Оставаясь на сайте, вы соглашаетесь с <a href="<?php echo esc_url(franchises_privacy_policy_url()); ?>">политикой конфиденциальности</a>.
            </div>
            <div class="footer-legal">© <?php echo date('Y'); ?> <?php echo esc_html(get_field('company_name', 'option')); ?></div>
        </div>
    </div>
</footer>
<?php require_once(TEMPLATE_PATH . '_modals.php'); ?>
<?php wp_footer(); ?>

<script>
document.addEventListener('wpcf7mailsent', function(event) {
    if (typeof window.ym !== 'function') return;

    var counterId = 109308668;

    // Общая цель для всех форм
    window.ym(counterId, 'reachGoal', 'subm');

    // Проверяем текущую страницу и скрытое поле franchise-url
    var pageUrl = window.location.href;
    var franchiseUrl = '';

    if (event.detail && event.detail.inputs) {
        event.detail.inputs.forEach(function(input) {
            if (input.name === 'franchise-url') {
                franchiseUrl = String(input.value || '');
            }
        });
    }

    var checkUrl = pageUrl + ' ' + franchiseUrl;

    // Дополнительная цель для двух прачечных франшиз
    if (
        checkUrl.indexOf('/franshizy/stirka-com/') !== -1 ||
        checkUrl.indexOf('/franshizy/prachechnaya-rf/') !== -1
    ) {
        window.ym(counterId, 'reachGoal', 'prachechnye');
    }
}, false);
</script>

</body>
</html>