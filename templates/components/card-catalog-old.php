<?php
$sid = get_the_ID();
$title = get_the_title();
$link = get_permalink();
$image = get_field('service_card_image') ?: ['url' => get_the_post_thumbnail_url($sid, 'full'), 'alt' => $title];
$description = get_the_excerpt();

// Получаем глобальный массив цен (лучше вынести получение переменной $prices_group выше цикла, если это возможно)
$prices_group = get_field('all_services_prices_list', 'option');
$service_data = $prices_group['service_data_' . $sid] ?? null;
$display_price = !empty($service_data['service_price']) ? $service_data['service_price'] : '';

$post_terms = get_the_terms($sid, 'service_cat');
$tag_names = [];
if ($post_terms && !is_wp_error($post_terms)) {
    foreach (array_slice($post_terms, 0, 2) as $t) {
        $tag_names[] = $t->name;
    }
}
?>

<div class="catalog__card">
    <a href="<?php echo esc_url($link); ?>" class="catalog__card-image">
        <?php if (!empty($image['url'])): ?>
            <img src="<?php echo esc_url($image['url']); ?>"
                alt="<?php echo esc_attr($image['alt'] ?: $title); ?>"
                class="cover-image"
                loading="lazy">
        <?php else: ?>
            <div class="catalog__card-placeholder">
                <img
                    src="<?php echo get_template_directory_uri(); ?>/assets/img/catalog-card-placeholder.svg"
                    alt="Нет фото"
                    loading="lazy"
                    class="cover-image">
            </div>
        <?php endif; ?>
    </a>

    <div class="catalog__card-content">
        <h3 class="catalog__card-title">
            <a href="<?php echo esc_url($link); ?>">
                <?php echo esc_html($title); ?>
            </a>
        </h3>

        <?php if ($description): ?>
            <div class="catalog__card-description">
                <?php echo wp_trim_words($description, 20, '...'); ?>
            </div>
        <?php endif; ?>

        <div class="catalog__card-footer">
            <?php if ($display_price): ?>
                <div class="catalog__card-price">
                    от <?php echo format_service_price($display_price); ?> ₽
                </div>
            <?php endif; ?>

            <a href="<?php echo esc_url($link); ?>" class="catalog__card-btn btn btn-primary">
                Подробнее
            </a>
        </div>
    </div>
</div>
<style lang="scss">
    .catalog {
        padding: rem(50) 0 rem(100);
        background: #fff;

        // .catalog__hint
        &__hint {}

        // .catalog__title
        &__title {}

        // .catalog__subtitle
        &__subtitle {}


        // .catalog__filters
        &__filters {
            margin-top: 1rem;
        }

        // .catalog__grid
        &__grid {
            display: grid;

            gap: rem(16);
            margin-top: 3rem;

            @media (min-width:$md4) {
                margin-top: 5rem;
                gap: rem(30);
                grid-template-columns: repeat(2, 1fr);

            }

            @media (min-width:$md3) {
                grid-template-columns: repeat(3, 1fr);

            }
        }


        // .catalog__support
        &__support {
            margin-top: rem(30);
            display: grid;

            @media (min-width:$md3) {
                grid-template-columns: repeat(2, 1fr);

            }
        }

        // .catalog__support-card
        &__support-card {
            display: flex;
            border-radius: 1.25rem;
            background: #FFF;
            padding: rem(30);
            box-shadow: 0 rem(6) rem(24) 0 rgba(0, 0, 0, 0.06);
            gap: 1rem;
            flex-direction: column;

            @media (min-width:$md4) {
                flex-direction: row;
            }

            &.catalog__support-card--blue {
                background: linear-gradient(150deg, #306E8C 3.77%, #1F4B62 153.22%);
                color: #fff;

                .catalog__support-desc {
                    color: #D7D7D7;
                }
            }
        }

        // .catalog__support-main
        &__support-main {
            flex: 1 1 auto;
        }

        // .catalog__support-caption
        &__support-caption {
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: 0.0375rem;
        }

        // .catalog__support-desc
        &__support-desc {
            margin-top: rem(10);
            color: #4B5D68;
            font-size: 1rem;
            font-weight: 300;
            letter-spacing: 0.03rem;
        }

        // .catalog__support-btns
        &__support-btns {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: rem(10);
        }

        // .catalog__support-btn
        &__support-btn {}


        // .catalog__card
        &__card {
            display: flex;
            flex-direction: column;
            border-radius: 1.25rem;
            background: #FFF;
            box-shadow: 0 rem(6) rem(24) 0 rgba(0, 0, 0, 0.06);
        }

        // .catalog__card-image
        &__card-image {
            border-radius: 1.25rem 1.25rem 0 0;
            overflow: hidden;
            height: 0;
            width: 100%;
            position: relative;
            padding-bottom: #{math.div(275, 447) * 100%};
            background: linear-gradient(90deg,
                    rgba(70, 126, 158, 0.1) 25%,
                    rgba(70, 126, 158, 0.2) 50%,
                    rgba(70, 126, 158, 0.1) 75%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite linear;

            &.is-ready {
                animation: none;
                background: #EAF3F8;
            }

            & img {
                position: absolute;
                top: 0;
                left: 0;
            }
        }

        // .catalog__card-content
        &__card-content {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            gap: rem(10);
            padding: rem(24) rem(16);

            @media (min-width:$md4) {
                padding: rem(30);

            }
        }

        // .catalog__card-title
        &__card-title {
            font-size: 1.25rem;
            font-weight: 600;
            letter-spacing: 0.0375rem;
            transition: color 0.3s ease;

            @media (any-hover: hover) {
                &:hover {
                    color: $accent;
                }
            }
        }

        // .catalog__card-description
        &__card-description {
            color: #4B5D68;
            font-size: 1rem;
            letter-spacing: 0.03rem;
            padding-bottom: 0.5rem;
        }




        // .catalog__empty
        &__empty {
            background: #f7f9fb;
            border-radius: 1.25rem;
            padding: 1.875rem;
            box-shadow: 0 .375rem 1.5rem 0 rgba(0, 0, 0, .06);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;

            @media (min-width:$md4) {
                grid-column: span 2;
            }

            @media (min-width:$md3) {
                grid-column: span 3;
            }
        }

        // .catalog__empty-title
        &__empty-title {}

        // .catalog__empty-text
        &__empty-text {}

        // .catalog__card-services
        &__card-services {
            display: flex;
            flex-wrap: wrap;
            gap: rem(10);

        }


        // .catalog__card-footer
        &__card-footer {
            border-top: rem(1) solid #DDE6F0;
            margin-top: auto;
            padding-top: 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;

            @media (min-width:$md5) {
                padding-top: 2.2rem;
                gap: 1rem;
                justify-content: space-between;
                align-items: flex-end;
                flex-direction: row;
                flex-wrap: wrap;
            }
        }

        // .catalog__card-service
        &__card-service {
            color: #467E9E;
            font-size: 0.875rem;
            // font-weight: 500;;
            letter-spacing: 0.0225rem;
            padding: 0.375rem 1.25rem;
            border-radius: 0.75rem;
            background: #EAF3F8;
            transition: background-color 0.3s ease;

            @media (any-hover: hover) {
                &:hover {
                    background-color: color.adjust($color: #EAF3F8, $lightness: -10%);
                }
            }
        }

        // .catalog__card-price
        &__card-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2da05a;
        }

        // .catalog__card-btn
        &__card-btn {
            flex-shrink: 0;
        }
    }
</style>