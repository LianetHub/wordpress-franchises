<?php

/**
 * Форма фильтров каталога франшиз.
 *
 * @var string              $shop_url
 * @var string              $form_action
 * @var array<int,WP_Term>  $parents
 * @var string              $cur_sphere
 * @var string              $cur_category
 * @var int                 $cur_payback_max
 * @var bool                $cur_verified
 * @var int                 $cur_invest_max
 * @var int                 $cur_profit_min
 * @var string              $cur_orderby
 * @var array<string,string> $orderby_options
 * @var string              $count_line
 * @var string              $reset_url
 * @var bool                $show_reset
 * @var bool                $advanced_open
 * @var string              $selection_url
 */

defined('ABSPATH') || exit;

$shop_url = isset($shop_url) ? (string) $shop_url : '';
$form_action = isset($form_action) ? (string) $form_action : $shop_url;
$selection_url = isset($selection_url) ? (string) $selection_url : '';
$parents = isset($parents) && is_array($parents) ? $parents : [];
$cur_sphere = isset($cur_sphere) ? (string) $cur_sphere : '';
$cur_category = isset($cur_category) ? (string) $cur_category : '';
$cur_payback_max = isset($cur_payback_max) ? (int) $cur_payback_max : 0;
$cur_verified = ! empty($cur_verified);
$cur_invest_max = isset($cur_invest_max) ? (int) $cur_invest_max : 0;
$cur_profit_min = isset($cur_profit_min) ? (int) $cur_profit_min : 0;
$cur_orderby = isset($cur_orderby) ? (string) $cur_orderby : 'menu_order';
$orderby_options = isset($orderby_options) && is_array($orderby_options) ? $orderby_options : [];
$count_line = isset($count_line) ? (string) $count_line : '';
$reset_url = isset($reset_url) ? (string) $reset_url : $shop_url;
$show_reset = ! empty($show_reset);
$advanced_open = ! empty($advanced_open);

$filter_card_classes = 'filter-card';
if (! $advanced_open) {
    $filter_card_classes .= ' advanced-collapsed';
}

?>
<div class="catalog-filters">
    <div class="catalog-filter-popup" id="catalog-filter-popup" hidden>
        <h2 class="catalog-filter-popup__title title-md">Фильтры</h2>
        <form method="get" action="<?php echo esc_url($form_action); ?>" aria-label="Фильтр каталога" id="franchises-catalog-filters" data-shop-url="<?php echo esc_url($shop_url); ?>"<?php echo $selection_url !== '' ? ' data-selection-url="' . esc_url($selection_url) . '"' : ''; ?>>
            <div class="<?php echo esc_attr($filter_card_classes); ?>">
                <div class="filter-grid">
                    <select class="filter-select filter-select-native" name="sphere" data-filter="sphere" aria-label="Сфера бизнеса">
                        <option value=""><?php esc_html_e('Все сферы', 'woocommerce'); ?></option>
                        <?php foreach ($parents as $p) :
                            if (! $p instanceof WP_Term) {
                                continue;
                            }
                            $sphere_url = function_exists('franchises_product_cat_flat_url')
                                ? franchises_product_cat_flat_url($p)
                                : (is_wp_error(get_term_link($p)) ? $shop_url : (string) get_term_link($p));
                        ?>
                            <option value="<?php echo esc_attr($p->name); ?>" data-url="<?php echo esc_url($sphere_url); ?>" <?php selected($cur_sphere, $p->name); ?>><?php echo esc_html($p->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select filter-select-native" name="category" data-filter="category" aria-label="Категория франшизы">
                        <option value=""><?php esc_html_e('Все категории', 'woocommerce'); ?></option>
                        <?php foreach ($parents as $p) :
                            if (! $p instanceof WP_Term) {
                                continue;
                            }
                            $children = get_terms([
                                'taxonomy'   => 'product_cat',
                                'hide_empty' => true,
                                'parent'     => (int) $p->term_id,
                                'orderby'    => 'name',
                                'order'      => 'ASC',
                            ]);
                            if (is_wp_error($children) || ! $children) {
                                continue;
                            }
                            foreach ($children as $ch) :
                                if (! $ch instanceof WP_Term) {
                                    continue;
                                }
                                $child_url = function_exists('franchises_product_cat_flat_url')
                                    ? franchises_product_cat_flat_url($ch)
                                    : (is_wp_error(get_term_link($ch)) ? $shop_url : (string) get_term_link($ch));
                        ?>
                                <option value="<?php echo esc_attr($ch->name); ?>" data-sphere="<?php echo esc_attr($p->name); ?>" data-url="<?php echo esc_url($child_url); ?>" <?php selected($cur_category, $ch->name); ?>><?php echo esc_html($ch->name); ?></option>
                        <?php
                            endforeach;
                        endforeach; ?>
                    </select>
                    <select class="filter-select filter-select-native" name="payback_max" data-filter="payback" aria-label="Окупаемость">
                        <option value="0">Любая окупаемость</option>
                        <option value="12" <?php selected($cur_payback_max, 12); ?>>до 12 мес.</option>
                        <option value="18" <?php selected($cur_payback_max, 18); ?>>до 18 мес.</option>
                        <option value="24" <?php selected($cur_payback_max, 24); ?>>до 24 мес.</option>
                        <option value="36" <?php selected($cur_payback_max, 36); ?>>до 36 мес.</option>
                    </select>
                    <button class="btn btn-primary filter-btn" type="submit">Подобрать</button>
                </div>
                <label class="filter-check filter-check-inline">
                    <input type="checkbox" name="verified" value="1" <?php checked($cur_verified); ?>>
                    <span>Только проверенные франшизы</span>
                </label>

                <div class="filter-advanced" <?php echo $advanced_open ? '' : ' style="display:none"'; ?>>
                    <div class="range-filters" aria-label="Фильтры по вложениям и прибыли">
                        <div class="range-card">
                            <div class="range-head">
                                <div class="range-title">Вложения</div>
                                <div class="range-value" id="invest-value"><?php echo $cur_invest_max > 0 ? 'до ' . esc_html(franchises_format_money_ru($cur_invest_max)) : 'Любые вложения'; ?></div>
                            </div>
                            <input type="hidden" name="invest_max" id="invest_max_input" value="<?php echo esc_attr((string) $cur_invest_max); ?>">
                            <input
                                class="range-input"
                                id="invest-range"
                                type="range"
                                min="50000"
                                max="3000000"
                                step="50000"
                                value="<?php echo esc_attr((string) ($cur_invest_max > 0 ? $cur_invest_max : 3000000)); ?>"
                                data-range-hidden="#invest_max_input"
                                data-range-label="#invest-value"
                                data-range-empty-label="<?php echo esc_attr__('Любые вложения', 'franchises'); ?>"
                                data-range-prefix="<?php echo esc_attr__('до ', 'franchises'); ?>"
                                data-range-empty-value="0"
                                data-range-empty-at="max">
                            <div class="preset-row">
                                <?php
                                $invest_presets = [50000, 100000, 300000, 500000, 1000000, 3000000];
                                foreach ($invest_presets as $invest_preset) :
                                ?>
                                    <button class="preset-btn" type="button" data-invest="<?php echo esc_attr((string) $invest_preset); ?>"><?php echo esc_html__('до ', 'franchises') . esc_html(franchises_format_money_ru($invest_preset)); ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="range-card">
                            <div class="range-head">
                                <div class="range-title">Прибыль в месяц</div>
                                <div class="range-value" id="profit-value"><?php echo $cur_profit_min > 0 ? 'от ' . esc_html(franchises_format_money_ru($cur_profit_min)) : 'Любая прибыль'; ?></div>
                            </div>
                            <input type="hidden" name="profit_min" id="profit_min_input" value="<?php echo esc_attr((string) $cur_profit_min); ?>">
                            <input
                                class="range-input"
                                id="profit-range"
                                type="range"
                                min="0"
                                max="1000000"
                                step="50000"
                                value="<?php echo esc_attr((string) ($cur_profit_min > 0 ? $cur_profit_min : 0)); ?>"
                                data-range-hidden="#profit_min_input"
                                data-range-label="#profit-value"
                                data-range-empty-label="<?php echo esc_attr__('Любая прибыль', 'franchises'); ?>"
                                data-range-prefix="<?php echo esc_attr__('от ', 'franchises'); ?>"
                                data-range-empty-value="0"
                                data-range-empty-at="min">
                            <div class="preset-row">
                                <?php
                                $profit_presets = [100000, 200000, 300000, 500000, 1000000];
                                foreach ($profit_presets as $profit_preset) :
                                ?>
                                    <button class="preset-btn" type="button" data-profit="<?php echo esc_attr((string) $profit_preset); ?>"><?php echo esc_html__('от ', 'franchises') . esc_html(franchises_format_money_ru($profit_preset)); ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="filter-card__footer">
                    <button
                        class="filter-toggle"
                        type="button"
                        aria-expanded="<?php echo $advanced_open ? 'true' : 'false'; ?>"
                        data-label-show="Показать дополнительные фильтры"
                        data-label-hide="Скрыть дополнительные фильтры">
                        <span class="filter-toggle__text"><?php echo $advanced_open ? 'Скрыть дополнительные фильтры' : 'Показать дополнительные фильтры'; ?></span>
                    </button>
                    <?php if ($show_reset) : ?>
                        <a class="filter-reset" href="<?php echo esc_url($reset_url); ?>">Сбросить фильтры</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div class="catalog-count mobile-count" id="catalog-count-mobile"><?php echo esc_html($count_line); ?></div>
    <div class="catalog-toolbar">
        <div class="catalog-count" id="catalog-count"><?php echo esc_html($count_line); ?></div>
        <select class="filter-select sort-select filter-select-native dropdown--fit" name="orderby" form="franchises-catalog-filters" data-sort aria-label="Сортировка" onchange="document.getElementById('franchises-catalog-filters').requestSubmit();">
            <?php foreach ($orderby_options as $val => $label) : ?>
                <option value="<?php echo esc_attr($val); ?>" <?php selected($cur_orderby, $val); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>