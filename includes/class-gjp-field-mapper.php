<?php
/**
 * Field Mapper - Map existing custom fields to Google for Jobs structure
 */

if (!defined('ABSPATH')) {
    exit;
}

class GJP_Field_Mapper {

    /**
     * Single instance
     */
    private static $instance = null;

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
        // 初期化処理
    }

    /**
     * Get field mapping settings
     */
    public function get_field_mapping() {
        $default_mapping = array(
            'post_type' => 'jobs', // 既存のカスタム投稿タイプ
            'fields' => array(
                'job_title' => '', // 職種（投稿タイトルを使用する場合は空）
                'employment_type' => '', // 雇用形態
                'salary' => '', // 給与
                'location' => '', // 勤務地
                'access' => '', // 交通アクセス
                'work_days' => '', // 就業日
                'work_hours' => '', // 就業時間
                'job_content' => '', // お仕事内容
                'appeal_points' => '', // コメント（アピールポイント）
                'period' => '', // 期間
            ),
        );

        $saved_mapping = get_option('gjp_field_mapping', $default_mapping);

        return wp_parse_args($saved_mapping, $default_mapping);
    }

    /**
     * Save field mapping
     */
    public function save_field_mapping($mapping) {
        return update_option('gjp_field_mapping', $mapping);
    }

    /**
     * Detect custom fields from a sample job post
     */
    public function detect_fields_from_post($post_id) {
        $custom_fields = get_post_custom($post_id);
        $detected_fields = array();

        // カスタムフィールドをすべて取得（_で始まるものも含む）
        foreach ($custom_fields as $key => $values) {
            $detected_fields[$key] = is_array($values) ? $values[0] : $values;
        }

        return $detected_fields;
    }

    /**
     * Get sample job posts
     */
    public function get_sample_job_posts($limit = 5) {
        $mapping = $this->get_field_mapping();
        $post_type = $mapping['post_type'];

        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        );

        return get_posts($args);
    }

    /**
     * Get mapped value from post
     */
    public function get_mapped_value($post_id, $field_key) {
        $mapping = $this->get_field_mapping();

        // フィールドキーが設定されていない場合
        if (empty($mapping['fields'][$field_key])) {
            // 職種の場合は投稿タイトルを返す
            if ($field_key === 'job_title') {
                return get_the_title($post_id);
            }
            return '';
        }

        $custom_field_name = $mapping['fields'][$field_key];

        // カスタムフィールドから値を取得
        $value = get_post_meta($post_id, $custom_field_name, true);

        return $value;
    }

    /**
     * Suggest field mappings based on field names
     */
    public function suggest_field_mappings($custom_fields) {
        $suggestions = array();

        // 職種
        $job_title_patterns = array('job_title', 'shokushu', '職種', 'category', 'job_category');
        $suggestions['job_title'] = $this->find_matching_field($custom_fields, $job_title_patterns);

        // 雇用形態
        $employment_patterns = array('employment_type', 'koyou_keitai', '雇用形態', 'employment', 'type');
        $suggestions['employment_type'] = $this->find_matching_field($custom_fields, $employment_patterns);

        // 給与
        $salary_patterns = array('salary', 'kyuyo', '給与', 'pay', 'wage');
        $suggestions['salary'] = $this->find_matching_field($custom_fields, $salary_patterns);

        // 勤務地
        $location_patterns = array('location', 'kinmuchi', '勤務地', 'address', 'place');
        $suggestions['location'] = $this->find_matching_field($custom_fields, $location_patterns);

        // 交通アクセス
        $access_patterns = array('access', 'kotsu', '交通', 'アクセス', 'transportation');
        $suggestions['access'] = $this->find_matching_field($custom_fields, $access_patterns);

        // 就業日
        $work_days_patterns = array('work_days', 'shugyoubi', '就業日', 'working_days');
        $suggestions['work_days'] = $this->find_matching_field($custom_fields, $work_days_patterns);

        // 就業時間
        $work_hours_patterns = array('work_hours', 'shugyou_jikan', '就業時間', 'working_hours', 'time');
        $suggestions['work_hours'] = $this->find_matching_field($custom_fields, $work_hours_patterns);

        // お仕事内容
        $job_content_patterns = array('job_content', 'oshigoto_naiyou', 'お仕事内容', 'description', 'content', 'naiyou');
        $suggestions['job_content'] = $this->find_matching_field($custom_fields, $job_content_patterns);

        // アピールポイント・コメント
        $appeal_patterns = array('comment', 'appeal', 'コメント', 'アピール', 'point', 'remarks');
        $suggestions['appeal_points'] = $this->find_matching_field($custom_fields, $appeal_patterns);

        // 期間
        $period_patterns = array('period', 'kikan', '期間', 'duration', 'term');
        $suggestions['period'] = $this->find_matching_field($custom_fields, $period_patterns);

        return $suggestions;
    }

    /**
     * Find matching field name from patterns
     */
    private function find_matching_field($custom_fields, $patterns) {
        foreach ($patterns as $pattern) {
            foreach (array_keys($custom_fields) as $field_name) {
                // 部分一致で検索（大文字小文字を区別しない）
                if (stripos($field_name, $pattern) !== false) {
                    return $field_name;
                }
            }
        }
        return '';
    }
}
