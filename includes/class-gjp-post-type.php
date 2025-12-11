<?php
/**
 * Custom Post Type for Job Postings
 */

if (!defined('ABSPATH')) {
    exit;
}

class GJP_Post_Type {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Post type slug
     */
    const POST_TYPE = 'job_posting';

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
    }

    /**
     * Register custom post type
     */
    public static function register_post_type() {
        $labels = array(
            'name'                  => __('求人情報', 'google-job-posting'),
            'singular_name'         => __('求人', 'google-job-posting'),
            'menu_name'             => __('求人情報', 'google-job-posting'),
            'name_admin_bar'        => __('求人', 'google-job-posting'),
            'add_new'               => __('新規追加', 'google-job-posting'),
            'add_new_item'          => __('新規求人を追加', 'google-job-posting'),
            'new_item'              => __('新規求人', 'google-job-posting'),
            'edit_item'             => __('求人を編集', 'google-job-posting'),
            'view_item'             => __('求人を表示', 'google-job-posting'),
            'all_items'             => __('すべての求人', 'google-job-posting'),
            'search_items'          => __('求人を検索', 'google-job-posting'),
            'parent_item_colon'     => __('親求人:', 'google-job-posting'),
            'not_found'             => __('求人が見つかりませんでした。', 'google-job-posting'),
            'not_found_in_trash'    => __('ゴミ箱に求人はありません。', 'google-job-posting'),
        );

        $args = array(
            'labels'                => $labels,
            'description'           => __('求人情報の管理', 'google-job-posting'),
            'public'                => true,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'query_var'             => true,
            'rewrite'               => array('slug' => 'jobs'),
            'capability_type'       => 'post',
            'has_archive'           => true,
            'hierarchical'          => false,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-businessman',
            'supports'              => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest'          => true,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // 雇用形態タクソノミー
        $employment_labels = array(
            'name'              => __('雇用形態', 'google-job-posting'),
            'singular_name'     => __('雇用形態', 'google-job-posting'),
            'search_items'      => __('雇用形態を検索', 'google-job-posting'),
            'all_items'         => __('すべての雇用形態', 'google-job-posting'),
            'parent_item'       => __('親雇用形態', 'google-job-posting'),
            'parent_item_colon' => __('親雇用形態:', 'google-job-posting'),
            'edit_item'         => __('雇用形態を編集', 'google-job-posting'),
            'update_item'       => __('雇用形態を更新', 'google-job-posting'),
            'add_new_item'      => __('新規雇用形態を追加', 'google-job-posting'),
            'new_item_name'     => __('新規雇用形態名', 'google-job-posting'),
            'menu_name'         => __('雇用形態', 'google-job-posting'),
        );

        register_taxonomy(
            'employment_type',
            self::POST_TYPE,
            array(
                'hierarchical'      => true,
                'labels'            => $employment_labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => array('slug' => 'employment-type'),
                'show_in_rest'      => true,
            )
        );

        // 職種タクソノミー
        $job_category_labels = array(
            'name'              => __('職種', 'google-job-posting'),
            'singular_name'     => __('職種', 'google-job-posting'),
            'search_items'      => __('職種を検索', 'google-job-posting'),
            'all_items'         => __('すべての職種', 'google-job-posting'),
            'parent_item'       => __('親職種', 'google-job-posting'),
            'parent_item_colon' => __('親職種:', 'google-job-posting'),
            'edit_item'         => __('職種を編集', 'google-job-posting'),
            'update_item'       => __('職種を更新', 'google-job-posting'),
            'add_new_item'      => __('新規職種を追加', 'google-job-posting'),
            'new_item_name'     => __('新規職種名', 'google-job-posting'),
            'menu_name'         => __('職種', 'google-job-posting'),
        );

        register_taxonomy(
            'job_category',
            self::POST_TYPE,
            array(
                'hierarchical'      => true,
                'labels'            => $job_category_labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => array('slug' => 'job-category'),
                'show_in_rest'      => true,
            )
        );

        // エリアタクソノミー
        $area_labels = array(
            'name'              => __('エリア', 'google-job-posting'),
            'singular_name'     => __('エリア', 'google-job-posting'),
            'search_items'      => __('エリアを検索', 'google-job-posting'),
            'all_items'         => __('すべてのエリア', 'google-job-posting'),
            'parent_item'       => __('親エリア', 'google-job-posting'),
            'parent_item_colon' => __('親エリア:', 'google-job-posting'),
            'edit_item'         => __('エリアを編集', 'google-job-posting'),
            'update_item'       => __('エリアを更新', 'google-job-posting'),
            'add_new_item'      => __('新規エリアを追加', 'google-job-posting'),
            'new_item_name'     => __('新規エリア名', 'google-job-posting'),
            'menu_name'         => __('エリア', 'google-job-posting'),
        );

        register_taxonomy(
            'job_area',
            self::POST_TYPE,
            array(
                'hierarchical'      => true,
                'labels'            => $area_labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => array('slug' => 'area'),
                'show_in_rest'      => true,
            )
        );
    }
}
