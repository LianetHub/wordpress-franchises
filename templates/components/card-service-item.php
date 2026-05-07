<?php
$service_post = $args['post'] ?? null;
if (!$service_post) return;

$sid = $service_post->ID;
$phone = get_field('phone', 'option');
$phone_clean = $phone ? preg_replace('/[^\d+]/', '', $phone) : '';

$icon = get_field('category_icon', $service_post);
$image = get_field('service_main_image') ?: [
    'url' => get_the_post_thumbnail_url($service_post, 'full'),
    'alt' => get_the_title()
];
$link = get_permalink($service_post);

$min_price = 0;
$prices_group = get_field('all_services_prices_list', 'option');
$service_data = $prices_group['service_data_' . $sid] ?? null;
$min_price = !empty($service_data['service_price']) ? (int)preg_replace('/[^\d]/', '', $service_data['service_price']) : 0;
?>

<li class="services__item">
    <?php if ($image): ?>
        <a href="<?php echo esc_url($link); ?>" class="services__item-image">
            <img src="<?php echo esc_url($image['url']); ?>"
                alt="<?php echo esc_attr($image['alt']); ?>"
                width="482"
                height="686"
                loading="lazy"
                decoding="async">
        </a>
    <?php endif; ?>
    <div class="services__item-body">
        <div class="services__item-main">
            <div class="services__item-header">
                <div class="services__item-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="27" height="27" viewBox="0 0 48 48">
                        <g fill="none">
                            <path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M14 26c-1.04-1.793-2.15-5.551 2.008-10.244c1.213-1.141 2.806-2.64 5.716-3.423C24.842 10.867 26.401 8.52 24.323 5C25.882 5.978 29 9.693 29 16.733" />
                            <path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M16.253 27.93C8 23.57 4.51 30.195 4 33.755c4 0 8.679 2.911 10.721 5.823c3.676 4.66 9.36 3.56 11.742 2.427c7.352-3.883 9.87-3.56 10.21-2.912c.41 3.106 1.532 3.883 2.043 3.883c3.676.388 4.935-4.045 5.105-6.31c.817-9.319-1.361-9.707-2.552-8.736c-4.902 5.824-7.829 6.957-8.68 6.795c-3.675-.777-3.233-2.265-2.552-2.913C36.572 26.377 36.504 14.34 35.653 9c-2.45 5.825-6.467 8.251-8.169 8.737c-10.21 2.718-11.742 7.928-11.231 10.193" />
                            <circle cx="12" cy="31.412" r="2" fill="#fff" />
                        </g>
                    </svg>
                </div>

                <a href="<?php echo esc_url($link); ?>" class="services__item-info">
                    <div class="services__item-title">
                        <?php echo fix_widows_after_prepositions($service_post->post_title); ?>
                    </div>
                    <?php if ($min_price > 0): ?>
                        <div class="services__item-price">
                            от&nbsp;<?php echo format_service_price($min_price); ?>&nbsp;₽
                        </div>
                    <?php endif; ?>
                </a>
            </div>
            <a class="services__item-btn btn btn-secondary-outline icon-phone"
                href="tel:<?php echo $phone_clean; ?>">Вызвать бригаду</a>
        </div>

    </div>
</li>