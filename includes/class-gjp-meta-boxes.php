<?php
/**
 * Meta Boxes for Job Postings
 */

if (!defined('ABSPATH')) {
    exit;
}

class GJP_Meta_Boxes {

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
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        global $post;
        if (!$post || GJP_Post_Type::POST_TYPE !== $post->post_type) {
            return;
        }

        wp_enqueue_style('gjp-admin', GJP_PLUGIN_URL . 'assets/css/admin.css', array(), GJP_VERSION);
        wp_enqueue_script('gjp-admin', GJP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), GJP_VERSION, true);
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'gjp_job_details',
            __('求人詳細情報', 'google-job-posting'),
            array($this, 'render_job_details_meta_box'),
            GJP_Post_Type::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'gjp_salary_info',
            __('給与情報', 'google-job-posting'),
            array($this, 'render_salary_meta_box'),
            GJP_Post_Type::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'gjp_location_info',
            __('勤務地情報', 'google-job-posting'),
            array($this, 'render_location_meta_box'),
            GJP_Post_Type::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'gjp_work_schedule',
            __('勤務スケジュール', 'google-job-posting'),
            array($this, 'render_schedule_meta_box'),
            GJP_Post_Type::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'gjp_additional_info',
            __('追加情報', 'google-job-posting'),
            array($this, 'render_additional_info_meta_box'),
            GJP_Post_Type::POST_TYPE,
            'normal',
            'default'
        );
    }

    /**
     * Render job details meta box
     */
    public function render_job_details_meta_box($post) {
        wp_nonce_field('gjp_save_meta_boxes', 'gjp_meta_nonce');

        $employment_type = get_post_meta($post->ID, '_gjp_employment_type', true);
        $job_title = get_post_meta($post->ID, '_gjp_job_title', true);
        $valid_through = get_post_meta($post->ID, '_gjp_valid_through', true);
        ?>
        <div class="gjp-meta-field">
            <label for="gjp_job_title"><?php _e('職種', 'google-job-posting'); ?></label>
            <input type="text" id="gjp_job_title" name="gjp_job_title" value="<?php echo esc_attr($job_title); ?>" class="widefat" />
            <p class="description"><?php _e('求人のタイトル（職種名）を入力してください。', 'google-job-posting'); ?></p>
        </div>

        <div class="gjp-meta-field">
            <label for="gjp_employment_type"><?php _e('雇用形態', 'google-job-posting'); ?></label>
            <select id="gjp_employment_type" name="gjp_employment_type" class="widefat">
                <option value=""><?php _e('選択してください', 'google-job-posting'); ?></option>
                <option value="FULL_TIME" <?php selected($employment_type, 'FULL_TIME'); ?>><?php _e('正社員', 'google-job-posting'); ?></option>
                <option value="PART_TIME" <?php selected($employment_type, 'PART_TIME'); ?>><?php _e('アルバイト・パート', 'google-job-posting'); ?></option>
                <option value="CONTRACTOR" <?php selected($employment_type, 'CONTRACTOR'); ?>><?php _e('契約社員', 'google-job-posting'); ?></option>
                <option value="TEMPORARY" <?php selected($employment_type, 'TEMPORARY'); ?>><?php _e('派遣社員', 'google-job-posting'); ?></option>
                <option value="INTERN" <?php selected($employment_type, 'INTERN'); ?>><?php _e('インターン', 'google-job-posting'); ?></option>
                <option value="VOLUNTEER" <?php selected($employment_type, 'VOLUNTEER'); ?>><?php _e('ボランティア', 'google-job-posting'); ?></option>
                <option value="PER_DIEM" <?php selected($employment_type, 'PER_DIEM'); ?>><?php _e('日雇い', 'google-job-posting'); ?></option>
                <option value="OTHER" <?php selected($employment_type, 'OTHER'); ?>><?php _e('その他', 'google-job-posting'); ?></option>
            </select>
        </div>

        <div class="gjp-meta-field">
            <label for="gjp_valid_through"><?php _e('掲載終了日', 'google-job-posting'); ?></label>
            <input type="date" id="gjp_valid_through" name="gjp_valid_through" value="<?php echo esc_attr($valid_through); ?>" class="widefat" />
            <p class="description"><?php _e('求人の掲載終了日を設定してください（オプション）。', 'google-job-posting'); ?></p>
        </div>
        <?php
    }

    /**
     * Render salary meta box
     */
    public function render_salary_meta_box($post) {
        $salary_currency = get_post_meta($post->ID, '_gjp_salary_currency', true) ?: 'JPY';
        $salary_value = get_post_meta($post->ID, '_gjp_salary_value', true);
        $salary_min = get_post_meta($post->ID, '_gjp_salary_min', true);
        $salary_max = get_post_meta($post->ID, '_gjp_salary_max', true);
        $salary_unit = get_post_meta($post->ID, '_gjp_salary_unit', true) ?: 'MONTH';
        $salary_text = get_post_meta($post->ID, '_gjp_salary_text', true);
        ?>
        <div class="gjp-meta-field">
            <label for="gjp_salary_text"><?php _e('給与（テキスト表示）', 'google-job-posting'); ?></label>
            <input type="text" id="gjp_salary_text" name="gjp_salary_text" value="<?php echo esc_attr($salary_text); ?>" class="widefat" />
            <p class="description"><?php _e('例：月給25万円〜35万円、時給1,200円〜', 'google-job-posting'); ?></p>
        </div>

        <div class="gjp-meta-field">
            <label for="gjp_salary_currency"><?php _e('通貨', 'google-job-posting'); ?></label>
            <select id="gjp_salary_currency" name="gjp_salary_currency" class="widefat">
                <option value="JPY" <?php selected($salary_currency, 'JPY'); ?>><?php _e('日本円（JPY）', 'google-job-posting'); ?></option>
                <option value="USD" <?php selected($salary_currency, 'USD'); ?>><?php _e('米ドル（USD）', 'google-job-posting'); ?></option>
            </select>
        </div>

        <div class="gjp-meta-field">
            <label for="gjp_salary_unit"><?php _e('給与単位', 'google-job-posting'); ?></label>
            <select id="gjp_salary_unit" name="gjp_salary_unit" class="widefat">
                <option value="HOUR" <?php selected($salary_unit, 'HOUR'); ?>><?php _e('時給', 'google-job-posting'); ?></option>
                <option value="DAY" <?php selected($salary_unit, 'DAY'); ?>><?php _e('日給', 'google-job-posting'); ?></option>
                <option value="WEEK" <?php selected($salary_unit, 'WEEK'); ?>><?php _e('週給', 'google-job-posting'); ?></option>
                <option value="MONTH" <?php selected($salary_unit, 'MONTH'); ?>><?php _e('月給', 'google-job-posting'); ?></option>
                <option value="YEAR" <?php selected($salary_unit, 'YEAR'); ?>><?php _e('年俸', 'google-job-posting'); ?></option>
            </select>
        </div>

        <div class="gjp-meta-field-group">
            <div class="gjp-meta-field">
                <label for="gjp_salary_min"><?php _e('最低給与', 'google-job-posting'); ?></label>
                <input type="number" id="gjp_salary_min" name="gjp_salary_min" value="<?php echo esc_attr($salary_min); ?>" class="widefat" step="1000" />
            </div>

            <div class="gjp-meta-field">
                <label for="gjp_salary_max"><?php _e('最高給与', 'google-job-posting'); ?></label>
                <input type="number" id="gjp_salary_max" name="gjp_salary_max" value="<?php echo esc_attr($salary_max); ?>" class="widefat" step="1000" />
            </div>
        </div>
        <?php
    }

    /**
     * Render location meta box
     */
    public function render_location_meta_box($post) {
        $street_address = get_post_meta($post->ID, '_gjp_street_address', true);
        $address_locality = get_post_meta($post->ID, '_gjp_address_locality', true);
        $address_region = get_post_meta($post->ID, '_gjp_address_region', true);
        $postal_code = get_post_meta($post->ID, '_gjp_postal_code', true);
        $address_country = get_post_meta($post->ID, '_gjp_address_country', true) ?: 'JP';
        $access_info = get_post_meta($post->ID, '_gjp_access_info', true);
        ?>
        <div class="gjp-meta-field">
            <label for="gjp_postal_code"><?php _e('郵便番号', 'google-job-posting'); ?></label>
            <input type="text" id="gjp_postal_code" name="gjp_postal_code" value="<?php echo esc_attr($postal_code); ?>" class="widefat" placeholder="100-0001" />
        </div>

        <div class="gjp-meta-field">
            <label for="gjp_address_region"><?php _e('都道府県', 'google-job-posting'); ?></label>
            <input type="text" id="gjp_address_region" name="gjp_address_region" value="<?php echo esc_attr($address_region); ?>" class="widefat" placeholder="東京都" />
        </div>

        <div class="gjp-meta-field">
            <label for="gjp_address_locality"><?php _e('市区町村', 'google-job-posting'); ?></label>
            <input type="text" id="gjp_address_locality" name="gjp_address_locality" value="<?php echo esc_attr($address_locality); ?>" class="widefat" placeholder="千代田区" />
        </div>

        <div class="gjp-meta-field">
            <label for="gjp_street_address"><?php _e('番地・建物名', 'google-job-posting'); ?></label>
            <input type="text" id="gjp_street_address" name="gjp_street_address" value="<?php echo esc_attr($street_address); ?>" class="widefat" placeholder="千代田1-1 ○○ビル5F" />
        </div>

        <div class="gjp-meta-field">
            <label for="gjp_address_country"><?php _e('国', 'google-job-posting'); ?></label>
            <input type="text" id="gjp_address_country" name="gjp_address_country" value="<?php echo esc_attr($address_country); ?>" class="widefat" placeholder="JP" />
            <p class="description"><?php _e('ISO 3166-1 alpha-2形式（例：JP）', 'google-job-posting'); ?></p>
        </div>

        <div class="gjp-meta-field">
            <label for="gjp_access_info"><?php _e('交通アクセス', 'google-job-posting'); ?></label>
            <textarea id="gjp_access_info" name="gjp_access_info" rows="3" class="widefat"><?php echo esc_textarea($access_info); ?></textarea>
            <p class="description"><?php _e('最寄り駅や交通アクセス情報を入力してください。', 'google-job-posting'); ?></p>
        </div>
        <?php
    }

    /**
     * Render schedule meta box
     */
    public function render_schedule_meta_box($post) {
        $work_days = get_post_meta($post->ID, '_gjp_work_days', true);
        $work_hours = get_post_meta($post->ID, '_gjp_work_hours', true);
        ?>
        <div class="gjp-meta-field">
            <label for="gjp_work_days"><?php _e('就業日', 'google-job-posting'); ?></label>
            <input type="text" id="gjp_work_days" name="gjp_work_days" value="<?php echo esc_attr($work_days); ?>" class="widefat" placeholder="月〜金" />
            <p class="description"><?php _e('例：月〜金、週3日〜、シフト制', 'google-job-posting'); ?></p>
        </div>

        <div class="gjp-meta-field">
            <label for="gjp_work_hours"><?php _e('就業時間', 'google-job-posting'); ?></label>
            <input type="text" id="gjp_work_hours" name="gjp_work_hours" value="<?php echo esc_attr($work_hours); ?>" class="widefat" placeholder="9:00〜18:00" />
            <p class="description"><?php _e('例：9:00〜18:00、10:00〜19:00（休憩1時間）', 'google-job-posting'); ?></p>
        </div>
        <?php
    }

    /**
     * Render additional info meta box
     */
    public function render_additional_info_meta_box($post) {
        $comment = get_post_meta($post->ID, '_gjp_comment', true);
        $remote_allowed = get_post_meta($post->ID, '_gjp_remote_allowed', true);
        ?>
        <div class="gjp-meta-field">
            <label for="gjp_comment"><?php _e('コメント', 'google-job-posting'); ?></label>
            <textarea id="gjp_comment" name="gjp_comment" rows="5" class="widefat"><?php echo esc_textarea($comment); ?></textarea>
            <p class="description"><?php _e('求人に関する追加情報やコメントを入力してください。', 'google-job-posting'); ?></p>
        </div>

        <div class="gjp-meta-field">
            <label>
                <input type="checkbox" id="gjp_remote_allowed" name="gjp_remote_allowed" value="1" <?php checked($remote_allowed, '1'); ?> />
                <?php _e('リモートワーク可能', 'google-job-posting'); ?>
            </label>
        </div>
        <?php
    }

    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        // Check nonce
        if (!isset($_POST['gjp_meta_nonce']) || !wp_verify_nonce($_POST['gjp_meta_nonce'], 'gjp_save_meta_boxes')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check post type
        if (GJP_Post_Type::POST_TYPE !== get_post_type($post_id)) {
            return;
        }

        // Save meta fields
        $meta_fields = array(
            'gjp_job_title',
            'gjp_employment_type',
            'gjp_valid_through',
            'gjp_salary_currency',
            'gjp_salary_value',
            'gjp_salary_min',
            'gjp_salary_max',
            'gjp_salary_unit',
            'gjp_salary_text',
            'gjp_street_address',
            'gjp_address_locality',
            'gjp_address_region',
            'gjp_postal_code',
            'gjp_address_country',
            'gjp_access_info',
            'gjp_work_days',
            'gjp_work_hours',
            'gjp_comment',
        );

        foreach ($meta_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Save checkbox
        $remote_allowed = isset($_POST['gjp_remote_allowed']) ? '1' : '0';
        update_post_meta($post_id, '_gjp_remote_allowed', $remote_allowed);
    }
}
