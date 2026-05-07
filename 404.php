<?php get_header(); ?>

<?php
$image = get_field('hero_image');
$tags = get_field('hero_tags');
$phone = get_field('phone', 'option');
$phone_clean = $phone ? preg_replace('/[^\d+]/', '', $phone) : '';
?>

<section class="hero">
    <div class="hero__container container">
        <div class="hero__image">
            <?php if ($image): ?>
                <img src="<?php echo esc_url($image['url']); ?>"
                    alt="<?php echo esc_attr($image['alt']); ?>"
                    class="hero__image-main"
                    fetchpriority="high"
                    loading="eager">
            <?php else: ?>
                <img src="<?php echo get_template_directory_uri(); ?>/assets/hero_cleaners.png"
                    alt="Спецуборка"
                    class="hero__image-main"
                    fetchpriority="high"
                    loading="eager">
            <?php endif; ?>

            <?php if ($tags): ?>
                <ul class="hero__tags">
                    <?php foreach ($tags as $tag): ?>
                        <li class="hero__tag">
                            <?php if ($tag['icon']): ?>
                                <div class="hero__tag-icon">
                                    <img src="<?php echo esc_url($tag['icon']['url']); ?>"
                                        alt="<?php echo esc_attr($tag['icon']['alt']); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="hero__tag-text"><?php echo esc_html($tag['text']); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="hero__offer">
            <h1 class="hero__title title-lg">404 <br> страница не найдена</h1>
            <div class="hero__btns">
                <?php if ($phone): ?>
                    <a href="tel:<?php echo $phone_clean; ?>" class="hero__btn btn btn-primary">Срочный вызов</a>
                <?php endif; ?>
                <a href="/" class="hero__btn btn btn-outline">На главную</a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>