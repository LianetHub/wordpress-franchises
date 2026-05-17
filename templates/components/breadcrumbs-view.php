<?php

defined('ABSPATH') || exit;

if (empty($breadcrumb_items) || ! is_array($breadcrumb_items)) {
    return;
}

$items_count = count($breadcrumb_items);
?>

<nav aria-label="<?php echo esc_attr($breadcrumb_aria_label); ?>" class="<?php echo esc_attr($breadcrumb_nav_class); ?>">
    <?php if ($breadcrumb_with_container) : ?>
        <div class="container">
        <?php endif; ?>
        <ul class="breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">
            <?php foreach ($breadcrumb_items as $index => $item) : ?>
                <?php
                $href = (string) ($item['href'] ?? '');
                $is_last = $index === $items_count - 1;
                $is_link = $href !== '' && ! $is_last;
                ?>
                <li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <?php if ($is_link) : ?>
                        <a href="<?php echo esc_url($href); ?>" itemprop="item" class="breadcrumbs__link">
                            <span itemprop="name"><?php echo esc_html($item['label']); ?></span>
                        </a>
                    <?php else : ?>
                        <span itemprop="name" class="breadcrumbs__current"><?php echo esc_html($item['label']); ?></span>
                        <link itemprop="item" href="<?php echo esc_url($breadcrumb_current_url); ?>">
                    <?php endif; ?>
                    <meta itemprop="position" content="<?php echo (int) ($index + 1); ?>">
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($breadcrumb_with_container) : ?>
        </div>
    <?php endif; ?>
</nav>