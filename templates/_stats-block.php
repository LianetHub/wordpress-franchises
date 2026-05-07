<?php
$stats = get_field('stats_list');
?>

<?php if ($stats): ?>
    <section class="about-stats" aria-label="Цифры платформы">
        <div class="stats-grid">
            <?php foreach ($stats as $item):
                $value = $item['stat_value'];
                $label = $item['stat_label'];
            ?>
                <?php if ($value || $label): ?>
                    <div class="stat">
                        <?php if ($value): ?>
                            <div class="stat-value"><?php echo esc_html($value); ?></div>
                        <?php endif; ?>

                        <?php if ($label): ?>
                            <div class="stat-label"><?php echo esc_html($label); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>