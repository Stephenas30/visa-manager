<?php

add_filter('manage_edit-{post_type}_columns', 'add_custom_column');
function add_custom_column($columns)
{
    $columns['custom_column'] = __('Custom Column', 'textdomain');
    return $columns;
}


add_action('manage_{post_type}_posts_custom_column', 'custom_column_content', 10, 2);
function custom_column_content($column, $post_id)
{
    if ($column === 'custom_column') {
        echo get_post_meta($post_id, 'custom_meta_key', true);
    }
}
