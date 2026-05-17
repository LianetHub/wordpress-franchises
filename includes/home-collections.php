<?php

defined('ABSPATH') || exit;

if (! function_exists('franchises_home_collections_preview_limit')) {
    function franchises_home_collections_preview_limit(): int
    {
        return max(1, (int) apply_filters('franchises_home_collections_preview_limit', 20));
    }
}

if (! function_exists('franchises_home_collections_shop_key')) {
    function franchises_home_collections_shop_key(): string
    {
        return 'all-franchises';
    }
}

if (! function_exists('franchises_home_collections_shop_url')) {
    function franchises_home_collections_shop_url(): string
    {
        if (function_exists('wc_get_page_id')) {
            $shop_page_id = wc_get_page_id('shop');
            if ($shop_page_id > 0) {
                $permalink = get_permalink($shop_page_id);
                if (is_string($permalink) && $permalink !== '') {
                    return $permalink;
                }
            }
        }

        return home_url('/');
    }
}

if (! function_exists('franchises_home_collections')) {
    /**
     * Подборки на главной из CPT selection (порядок — menu_order в админке).
     *
     * @return list<array{key: string, label: string, id: int, url: string}>
     */
    function franchises_home_collections(): array
    {
        if (! function_exists('franchises_get_selection_posts')) {
            return apply_filters('franchises_home_collections', []);
        }

        $items = [];
        foreach (franchises_get_selection_posts(40) as $post) {
            if (! $post instanceof WP_Post) {
                continue;
            }
            $id = (int) $post->ID;
            $url = get_permalink($post);
            if (! is_string($url) || $url === '') {
                continue;
            }
            $slug = (string) $post->post_name;
            $items[] = [
                'key'   => $slug !== '' ? $slug : 'selection-' . $id,
                'label' => get_the_title($post),
                'id'    => $id,
                'url'   => $url,
            ];
        }

        return apply_filters('franchises_home_collections', $items);
    }
}

if (! function_exists('franchises_home_collection_product_ids')) {
    /**
     * ID франшиз для превью на главной (та же логика, что на странице подборки).
     *
     * @return list<int>
     */
    function franchises_home_collection_product_ids(int $selection_id, ?int $limit = null): array
    {
        if ($selection_id <= 0 || ! function_exists('franchises_selection_product_ids')) {
            return [];
        }

        $limit = $limit ?? franchises_home_collections_preview_limit();
        $ids = franchises_selection_product_ids($selection_id, max(50, $limit));

        return array_slice($ids, 0, max(1, $limit));
    }
}

if (! function_exists('franchises_home_shop_product_ids')) {
    /**
     * Превью для вкладки «Все франшизы» на главной.
     *
     * @return list<int>
     */
    function franchises_home_shop_product_ids(?int $limit = null): array
    {
        $limit = $limit ?? franchises_home_collections_preview_limit();

        $query = new WP_Query([
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => max(1, $limit),
            'orderby'             => 'menu_order',
            'order'               => 'ASC',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        ]);

        $ids = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = (int) get_the_ID();
                if ($post_id > 0) {
                    $ids[] = $post_id;
                }
            }
        }
        wp_reset_postdata();

        return $ids;
    }
}

if (! function_exists('franchises_render_home_collection_cards')) {
    /**
     * @param list<int> $product_ids
     */
    function franchises_render_home_collection_cards(array $product_ids): int
    {
        if (! function_exists('franchises_render_franchise_card_for_product')) {
            return 0;
        }

        $shown = 0;
        foreach ($product_ids as $product_id) {
            if (franchises_render_franchise_card_for_product((int) $product_id, ['order' => $shown])) {
                $shown++;
            }
        }

        return $shown;
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

        $preview_limit = franchises_home_collections_preview_limit();
        $shop_key = franchises_home_collections_shop_key();
        $shop_url = franchises_home_collections_shop_url();

        $first_key = (string) ($collections[0]['key'] ?? '');
        $first_url = (string) ($collections[0]['url'] ?? $shop_url);
?>
        <div class="segment-tabs segment-tags" aria-label="<?php esc_attr_e('Подборки', 'franchises'); ?>" data-collections-chips>
            <?php foreach ($collections as $collection) :
                $key = (string) ($collection['key'] ?? '');
                if ($key === '') {
                    continue;
                }
                $active = $key === $first_key;
                $collection_url = (string) ($collection['url'] ?? '');
            ?>
                <button
                    type="button"
                    class="collection-tile<?php echo $active ? ' active' : ''; ?>"
                    data-collection="<?php echo esc_attr($key); ?>"
                    data-collection-url="<?php echo esc_url($collection_url); ?>"
                    aria-pressed="<?php echo $active ? 'true' : 'false'; ?>">
                    <?php echo esc_html((string) ($collection['label'] ?? '')); ?>
                </button>
            <?php endforeach; ?>
            <button
                type="button"
                class="collection-tile"
                data-collection="<?php echo esc_attr($shop_key); ?>"
                data-collection-url="<?php echo esc_url($shop_url); ?>"
                aria-pressed="false">
                <?php esc_html_e('Все франшизы', 'franchises'); ?>
            </button>
        </div>

        <?php foreach ($collections as $collection) :
            $key = (string) ($collection['key'] ?? '');
            $selection_id = (int) ($collection['id'] ?? 0);
            if ($key === '' || $selection_id <= 0) {
                continue;
            }
            $is_active = $key === $first_key;
            $product_ids = franchises_home_collection_product_ids($selection_id, $preview_limit);
            $panel_url = (string) ($collection['url'] ?? $shop_url);
        ?>
            <div
                class="popular-grid"
                data-collections-grid
                data-collection-panel="<?php echo esc_attr($key); ?>"
                data-collection-more-url="<?php echo esc_url($panel_url); ?>"
                <?php echo $is_active ? '' : ' hidden'; ?>>
                <?php
                $shown = franchises_render_home_collection_cards($product_ids);
                if ($shown === 0) {
                    echo '<p class="popular-sub" style="grid-column:1/-1;">' . esc_html__('В этой подборке пока нет франшиз.', 'franchises') . '</p>';
                }
                ?>
            </div>
        <?php endforeach; ?>

        <?php
        $shop_product_ids = franchises_home_shop_product_ids($preview_limit);
        ?>
        <div
            class="popular-grid"
            data-collections-grid
            data-collection-panel="<?php echo esc_attr($shop_key); ?>"
            data-collection-more-url="<?php echo esc_url($shop_url); ?>"
            hidden>
            <?php
            $shop_shown = franchises_render_home_collection_cards($shop_product_ids);
            if ($shop_shown === 0) {
                echo '<p class="popular-sub" style="grid-column:1/-1;">' . esc_html__('В каталоге пока нет опубликованных франшиз.', 'franchises') . '</p>';
            }
            ?>
        </div>

        <div class="segment-actions">
            <a class="btn btn-primary" href="<?php echo esc_url($first_url); ?>" data-collections-open>
                <?php esc_html_e('Смотреть подборку полностью', 'franchises'); ?>
            </a>
        </div>
<?php
    }
}
