<?php

namespace WPChill\KB;

class Taxonomy
{
    public function register()
    {
        $labels = array(
            'name' => _x('KB Categories', 'taxonomy general name', 'wpchill-kb'),
            'singular_name' => _x('KB Category', 'taxonomy singular name', 'wpchill-kb'),
            'search_items' => __('Search KB Categories', 'wpchill-kb'),
            'all_items' => __('All KB Categories', 'wpchill-kb'),
            'parent_item' => __('Parent KB Category', 'wpchill-kb'),
            'parent_item_colon' => __('Parent KB Category:', 'wpchill-kb'),
            'edit_item' => __('Edit KB Category', 'wpchill-kb'),
            'update_item' => __('Update KB Category', 'wpchill-kb'),
            'add_new_item' => __('Add New KB Category', 'wpchill-kb'),
            'new_item_name' => __('New KB Category Name', 'wpchill-kb'),
            'menu_name' => __('KB Categories', 'wpchill-kb'),
        );

        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'kb-cat'),
        );

        $args = apply_filters('wpchill_kb_taxonomy_args', $args);

        register_taxonomy('kb_category', array('kb'), $args);
    }
}