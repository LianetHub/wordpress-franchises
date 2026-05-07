<?php
$title = get_field('hero_title');
$subtitle = get_field('hero_subtitle');
$slides = get_field('hero_slides');
?>

<section class="hero">
    <?php if ($title): ?>
        <h1 class="hero__title title"><?php echo esc_html($title); ?></h1>
    <?php endif; ?>

    <?php if ($subtitle): ?>
        <p class="hero__subtitle"><?php echo esc_html($subtitle); ?></p>
    <?php endif; ?>

    <?php if ($slides && is_array($slides)): ?>
        <div class="hero__slider swiper">
            <div class="swiper-wrapper">
                <?php foreach ($slides as $slide):
                    $text = isset($slide['slide_text']) ? $slide['slide_text'] : '';
                    $link = isset($slide['slide_link']) ? $slide['slide_link'] : '#';
                    $image_data = isset($slide['slide_image']) ? $slide['slide_image'] : '';

                    $image_url = '';
                    if (is_array($image_data)) {
                        $image_url = $image_data['url'];
                    } elseif (is_numeric($image_data)) {
                        $image_url = wp_get_attachment_url($image_data);
                    } else {
                        $image_url = $image_data;
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