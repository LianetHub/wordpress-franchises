<?php
$title = get_field('hero_title');
$subtitle = get_field('hero_subtitle');
$slides = function_exists('franchises_hero_slides_from_selections')
    ? franchises_hero_slides_from_selections()
    : [];
?>

<section class="hero">
    <?php if ($title): ?>
        <h1 class="hero__title title"><?php echo esc_html($title); ?></h1>
    <?php endif; ?>

    <?php if ($subtitle): ?>
        <p class="hero__subtitle"><?php echo esc_html($subtitle); ?></p>
    <?php endif; ?>

    <?php if ($slides !== []): ?>
        <div class="hero__slider swiper">
            <div class="swiper-wrapper">
                <?php foreach ($slides as $slide) :
                    $text = (string) ($slide['text'] ?? '');
                    $link = (string) ($slide['link'] ?? '#');
                    $image_url = (string) ($slide['image_url'] ?? '');
                    if ($image_url === '') {
                        continue;
                    }
                ?>
                    <a href="<?php echo esc_url($link); ?>"
                        class="hero__slide swiper-slide"
                        style="background-image: url('<?php echo esc_url($image_url); ?>')">
                        <span class="hero__slide-label"><?php echo esc_html($text); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="hero__slider-pagination swiper-pagination"></div>
        </div>
    <?php endif; ?>
</section>