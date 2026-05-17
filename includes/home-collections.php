<?php

defined('ABSPATH') || exit;

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
    function franchises_home_collection_product_ids(int $selection_id, int $limit = 10): array
    {
        if ($selection_id <= 0 || ! function_exists('franchises_selection_product_ids')) {
            return [];
        }

        $ids = franchises_selection_product_ids($selection_id, max(50, $limit));

        return array_slice($ids, 0, max(1, $limit));
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

        $first_key = (string) ($collections[0]['key'] ?? '');
        $first_url = (string) ($collections[0]['url'] ?? '');
        if ($first_url === '' && function_exists('wc_get_page_id')) {
            $shop_page_id = wc_get_page_id('shop');
            if ($shop_page_id > 0) {
                $first_url = (string) get_permalink($shop_page_id);
            }
        }
        if ($first_url === '') {
            $first_url = home_url('/');
        }
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
        </div>

        <?php foreach ($collections as $collection) :
            $key = (string) ($collection['key'] ?? '');
            $selection_id = (int) ($collection['id'] ?? 0);
            if ($key === '' || $selection_id <= 0) {
                continue;
            }
            $is_active = $key === $first_key;
            $product_ids = franchises_home_collection_product_ids($selection_id, 10);
            $shown = 0;
        ?>
            <div
                class="popular-grid"
                data-collections-grid
                data-collection-panel="<?php echo esc_attr($key); ?>"
                <?php echo $is_active ? '' : ' hidden'; ?>>
                <?php
                if (function_exists('franchises_render_franchise_card_for_product')) {
                    foreach ($product_ids as $product_id) {
                        if (franchises_render_franchise_card_for_product((int) $product_id, ['order' => $shown])) {
                            $shown++;
                        }
                    }
                }

                if ($shown === 0) {
                    echo '<p class="popular-sub" style="grid-column:1/-1;">' . esc_html__('В этой подборке пока нет франшиз.', 'franchises') . '</p>';
                }
                ?>
            </div>
        <?php endforeach; ?>

        <div class="segment-actions">
            <a class="btn btn-primary" href="<?php echo esc_url($first_url); ?>" data-collections-open>
                <?php esc_html_e('Смотреть подборку полностью', 'franchises'); ?>
            </a>
        </div>
<?php
    }
}
