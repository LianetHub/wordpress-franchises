<?php

defined('ABSPATH') || exit;

if (! function_exists('franchises_home_collections')) {
    /**
     * Подборки на главной (редактируются в PHP, не в JS).
     *
     * @return list<array{key: string, label: string, type: string, tag?: string}>
     */
    function franchises_home_collections(): array
    {
        $items = [
            ['key' => 'new', 'label' => 'Новые франшизы', 'type' => 'new'],
            ['key' => 'beginners', 'label' => 'Для начинающих', 'type' => 'tag', 'tag' => 'Для начинающих'],
            ['key' => 'fast-payback', 'label' => 'Быстрая окупаемость', 'type' => 'tag', 'tag' => 'Быстрая окупаемость'],
            ['key' => 'no-royalty', 'label' => 'Без роялти', 'type' => 'tag', 'tag' => 'Без роялти'],
            ['key' => 'no-pausal', 'label' => 'Без паушального взноса', 'type' => 'tag', 'tag' => 'Без паушального взноса'],
            ['key' => 'premium', 'label' => 'Премиум', 'type' => 'tag', 'tag' => 'Премиум'],
        ];

        return apply_filters('franchises_home_collections', $items);
    }
}

if (! function_exists('franchises_home_collection_query')) {
    function franchises_home_collection_query(array $collection, int $limit = 50): WP_Query
    {
        $args = [
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => max(1, $limit),
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        ];

        $type = (string) ($collection['type'] ?? '');

        if ($type === 'new') {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } elseif ($type === 'popular') {
            $args['orderby'] = 'menu_order';
            $args['order'] = 'ASC';
        } else {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }

        return new WP_Query($args);
    }
}

if (! function_exists('franchises_home_collection_matches')) {
    function franchises_home_collection_matches(array $collection, array $card): bool
    {
        $type = (string) ($collection['type'] ?? '');

        if ($type === 'verified') {
            return ! empty($card['verified']);
        }

        if ($type === 'new' || $type === 'popular') {
            return true;
        }

        if ($type === 'tag') {
            $needle = (string) ($collection['tag'] ?? '');
            if ($needle === '') {
                return true;
            }
            $tags = array_filter(array_map('trim', explode('|', (string) ($card['tags'] ?? ''))));

            return in_array($needle, $tags, true);
        }

        return true;
    }
}

if (! function_exists('franchises_render_home_collections_section')) {
    function franchises_render_home_collections_section(): void
    {
        if (! class_exists('WooCommerce')) {
            return;
        }

        $collections = franchises_home_collections();
        if ($collections === []) {
            return;
        }

        $shop_url = home_url('/');
        if (function_exists('wc_get_page_id')) {
            $shop_page_id = wc_get_page_id('shop');
            if ($shop_page_id > 0) {
                $shop_url = (string) get_permalink($shop_page_id);
            }
        }

        $first_key = (string) ($collections[0]['key'] ?? '');
?>
        <div class="segment-tabs segment-tags" aria-label="<?php esc_attr_e('Подборки', 'franchises'); ?>" data-collections-chips>
            <?php foreach ($collections as $collection) :
                $key = (string) ($collection['key'] ?? '');
                if ($key === '') {
                    continue;
                }
                $active = $key === $first_key;
            ?>
                <button
                    type="button"
                    class="collection-tile<?php echo $active ? ' active' : ''; ?>"
                    data-collection="<?php echo esc_attr($key); ?>"
                    aria-pressed="<?php echo $active ? 'true' : 'false'; ?>">
                    <?php echo esc_html((string) ($collection['label'] ?? '')); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($collections as $collection) :
            $key = (string) ($collection['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $is_active = $key === $first_key;
            $query = franchises_home_collection_query($collection, 50);
            $shown = 0;
        ?>
            <div
                class="popular-grid"
                data-collection-panel="<?php echo esc_attr($key); ?>"
                <?php echo $is_active ? '' : ' hidden'; ?>>
                <?php
                if ($query->have_posts()) {
                    $i = 0;
                    while ($query->have_posts() && $shown < 10) {
                        $query->the_post();
                        $card = franchises_franchise_card_from_post(get_the_ID());
                        if (! franchises_home_collection_matches($collection, $card)) {
                            continue;
                        }
                        $coll_type = (string) ($collection['type'] ?? '');
                        if ($coll_type === 'popular') {
                            $card['popularity'] = max(1, 100 - $shown);
                        }
                        if ($coll_type === 'new') {
                            $card['date'] = (int) get_post_time('U', true);
                        }
                        $card['order'] = $i++;
                        get_template_part('templates/components/franchise-card', null, ['franchise_card' => $card]);
                        $shown++;
                    }
                    wp_reset_postdata();
                }
                if ($shown === 0) {
                    echo '<p class="popular-sub" style="grid-column:1/-1;">' . esc_html__('В этой подборке пока нет франшиз.', 'franchises') . '</p>';
                }
                ?>
            </div>
        <?php endforeach; ?>

        <div class="segment-actions">
            <a class="btn btn-primary" href="<?php echo esc_url($shop_url); ?>" data-collections-open>
                <?php esc_html_e('Смотреть подборку полностью', 'franchises'); ?>
            </a>
        </div>
<?php
    }
}
