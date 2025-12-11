<?php
/**
 * Admin Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class GJP_Admin {

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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Add custom column to job listing
        add_filter('manage_' . GJP_Post_Type::POST_TYPE . '_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_' . GJP_Post_Type::POST_TYPE . '_posts_custom_column', array($this, 'render_custom_columns'), 10, 2);

        // Add structured data preview meta box
        add_action('add_meta_boxes', array($this, 'add_preview_meta_box'));
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Enqueue media uploader on settings page
        if ('toplevel_page_gjp-settings' === $hook) {
            wp_enqueue_media();
        }
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Google Job Posting 設定', 'google-job-posting'),
            __('求人設定', 'google-job-posting'),
            'manage_options',
            'gjp-settings',
            array($this, 'render_settings_page'),
            'dashicons-businessman',
            30
        );

        add_submenu_page(
            'gjp-settings',
            __('設定', 'google-job-posting'),
            __('設定', 'google-job-posting'),
            'manage_options',
            'gjp-settings'
        );

        add_submenu_page(
            'gjp-settings',
            __('すべての求人', 'google-job-posting'),
            __('すべての求人', 'google-job-posting'),
            'edit_posts',
            'edit.php?post_type=' . GJP_Post_Type::POST_TYPE
        );

        add_submenu_page(
            'gjp-settings',
            __('新規追加', 'google-job-posting'),
            __('新規追加', 'google-job-posting'),
            'edit_posts',
            'post-new.php?post_type=' . GJP_Post_Type::POST_TYPE
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('gjp_settings_group', 'gjp_settings', array($this, 'sanitize_settings'));

        add_settings_section(
            'gjp_company_section',
            __('会社情報', 'google-job-posting'),
            array($this, 'render_company_section'),
            'gjp-settings'
        );

        add_settings_field(
            'company_name',
            __('会社名', 'google-job-posting'),
            array($this, 'render_company_name_field'),
            'gjp-settings',
            'gjp_company_section'
        );

        add_settings_field(
            'company_url',
            __('会社URL', 'google-job-posting'),
            array($this, 'render_company_url_field'),
            'gjp-settings',
            'gjp_company_section'
        );

        add_settings_field(
            'company_logo',
            __('会社ロゴ', 'google-job-posting'),
            array($this, 'render_company_logo_field'),
            'gjp-settings',
            'gjp_company_section'
        );
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        if (isset($input['company_name'])) {
            $sanitized['company_name'] = sanitize_text_field($input['company_name']);
        }

        if (isset($input['company_url'])) {
            $sanitized['company_url'] = esc_url_raw($input['company_url']);
        }

        if (isset($input['company_logo'])) {
            $sanitized['company_logo'] = esc_url_raw($input['company_logo']);
        }

        return $sanitized;
    }

    /**
     * Render company section
     */
    public function render_company_section() {
        echo '<p>' . __('Googleしごと検索に表示される会社情報を設定してください。', 'google-job-posting') . '</p>';
    }

    /**
     * Render company name field
     */
    public function render_company_name_field() {
        $settings = get_option('gjp_settings', array());
        $value = isset($settings['company_name']) ? $settings['company_name'] : get_bloginfo('name');
        ?>
        <input type="text" name="gjp_settings[company_name]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php _e('求人を掲載する会社名を入力してください。', 'google-job-posting'); ?></p>
        <?php
    }

    /**
     * Render company URL field
     */
    public function render_company_url_field() {
        $settings = get_option('gjp_settings', array());
        $value = isset($settings['company_url']) ? $settings['company_url'] : home_url();
        ?>
        <input type="url" name="gjp_settings[company_url]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php _e('会社のウェブサイトURLを入力してください。', 'google-job-posting'); ?></p>
        <?php
    }

    /**
     * Render company logo field
     */
    public function render_company_logo_field() {
        $settings = get_option('gjp_settings', array());
        $value = isset($settings['company_logo']) ? $settings['company_logo'] : '';
        ?>
        <input type="text" id="gjp_company_logo" name="gjp_settings[company_logo]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <button type="button" class="button" id="gjp_upload_logo_button"><?php _e('画像を選択', 'google-job-posting'); ?></button>
        <p class="description"><?php _e('会社のロゴ画像をアップロードしてください（推奨サイズ: 112x112px以上）。', 'google-job-posting'); ?></p>
        <?php if (!empty($value)) : ?>
            <p><img src="<?php echo esc_url($value); ?>" style="max-width: 200px; height: auto; margin-top: 10px;" /></p>
        <?php endif; ?>

        <script>
        jQuery(document).ready(function($) {
            $('#gjp_upload_logo_button').click(function(e) {
                e.preventDefault();
                var image = wp.media({
                    title: '<?php _e('会社ロゴを選択', 'google-job-posting'); ?>',
                    multiple: false
                }).open().on('select', function() {
                    var uploaded_image = image.state().get('selection').first();
                    var image_url = uploaded_image.toJSON().url;
                    $('#gjp_company_logo').val(image_url);
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="gjp-settings-intro">
                <h2><?php _e('Google Job Posting プラグインについて', 'google-job-posting'); ?></h2>
                <p><?php _e('このプラグインは、WordPress上の求人情報をGoogleしごと検索（Google for Jobs）に自動的に連携させます。', 'google-job-posting'); ?></p>
                <p><?php _e('求人情報を投稿すると、自動的にJSON-LD形式の構造化データが生成され、Googleの検索結果に表示されやすくなります。', 'google-job-posting'); ?></p>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields('gjp_settings_group');
                do_settings_sections('gjp-settings');
                submit_button();
                ?>
            </form>

            <div class="gjp-help-section">
                <h2><?php _e('使い方', 'google-job-posting'); ?></h2>
                <ol>
                    <li><?php _e('上記の会社情報を設定してください。', 'google-job-posting'); ?></li>
                    <li><?php _e('「求人情報」から新しい求人を追加してください。', 'google-job-posting'); ?></li>
                    <li><?php _e('職種、雇用形態、給与、勤務地などの必要な情報を入力してください。', 'google-job-posting'); ?></li>
                    <li><?php _e('求人を公開すると、自動的にGoogleしごと検索用の構造化データが生成されます。', 'google-job-posting'); ?></li>
                    <li><?php _e('Google Search Consoleで構造化データをテストすることをおすすめします。', 'google-job-posting'); ?></li>
                </ol>

                <h3><?php _e('構造化データのテスト', 'google-job-posting'); ?></h3>
                <p>
                    <?php _e('公開した求人ページのURLを、Googleの', 'google-job-posting'); ?>
                    <a href="https://search.google.com/test/rich-results" target="_blank">
                        <?php _e('リッチリザルトテスト', 'google-job-posting'); ?>
                    </a>
                    <?php _e('でテストしてください。', 'google-job-posting'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Add custom columns to job listing
     */
    public function add_custom_columns($columns) {
        $new_columns = array();

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            if ($key === 'title') {
                $new_columns['employment_type'] = __('雇用形態', 'google-job-posting');
                $new_columns['location'] = __('勤務地', 'google-job-posting');
                $new_columns['salary'] = __('給与', 'google-job-posting');
            }
        }

        return $new_columns;
    }

    /**
     * Render custom columns
     */
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'employment_type':
                $employment_type = get_post_meta($post_id, '_gjp_employment_type', true);
                $types = array(
                    'FULL_TIME' => __('正社員', 'google-job-posting'),
                    'PART_TIME' => __('アルバイト・パート', 'google-job-posting'),
                    'CONTRACTOR' => __('契約社員', 'google-job-posting'),
                    'TEMPORARY' => __('派遣社員', 'google-job-posting'),
                    'INTERN' => __('インターン', 'google-job-posting'),
                    'VOLUNTEER' => __('ボランティア', 'google-job-posting'),
                    'PER_DIEM' => __('日雇い', 'google-job-posting'),
                    'OTHER' => __('その他', 'google-job-posting'),
                );
                echo isset($types[$employment_type]) ? esc_html($types[$employment_type]) : '—';
                break;

            case 'location':
                $locality = get_post_meta($post_id, '_gjp_address_locality', true);
                $region = get_post_meta($post_id, '_gjp_address_region', true);
                $location = trim($region . ' ' . $locality);
                echo !empty($location) ? esc_html($location) : '—';
                break;

            case 'salary':
                $salary_text = get_post_meta($post_id, '_gjp_salary_text', true);
                if (!empty($salary_text)) {
                    echo esc_html($salary_text);
                } else {
                    $min = get_post_meta($post_id, '_gjp_salary_min', true);
                    $max = get_post_meta($post_id, '_gjp_salary_max', true);
                    if (!empty($min) && !empty($max)) {
                        echo esc_html(number_format($min) . '円〜' . number_format($max) . '円');
                    } elseif (!empty($min)) {
                        echo esc_html(number_format($min) . '円〜');
                    } else {
                        echo '—';
                    }
                }
                break;
        }
    }

    /**
     * Add preview meta box
     */
    public function add_preview_meta_box() {
        add_meta_box(
            'gjp_structured_data_preview',
            __('構造化データプレビュー', 'google-job-posting'),
            array($this, 'render_preview_meta_box'),
            GJP_Post_Type::POST_TYPE,
            'side',
            'low'
        );
    }

    /**
     * Render preview meta box
     */
    public function render_preview_meta_box($post) {
        ?>
        <div class="gjp-preview">
            <p><?php _e('この求人のJSON-LD構造化データを確認できます。', 'google-job-posting'); ?></p>
            <p>
                <a href="https://search.google.com/test/rich-results?url=<?php echo urlencode(get_permalink($post->ID)); ?>"
                   target="_blank"
                   class="button button-secondary">
                    <?php _e('リッチリザルトテスト', 'google-job-posting'); ?>
                </a>
            </p>
            <?php if ($post->post_status === 'publish') : ?>
                <p>
                    <button type="button" class="button button-secondary gjp-show-json" data-post-id="<?php echo $post->ID; ?>">
                        <?php _e('JSON-LDを表示', 'google-job-posting'); ?>
                    </button>
                </p>
                <div class="gjp-json-preview" style="display:none; margin-top:10px;">
                    <textarea readonly style="width:100%;height:300px;font-family:monospace;font-size:11px;"></textarea>
                </div>
            <?php else : ?>
                <p class="description"><?php _e('公開後に構造化データが生成されます。', 'google-job-posting'); ?></p>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.gjp-show-json').click(function() {
                var button = $(this);
                var preview = button.closest('.gjp-preview').find('.gjp-json-preview');

                if (preview.is(':visible')) {
                    preview.hide();
                    button.text('<?php _e('JSON-LDを表示', 'google-job-posting'); ?>');
                } else {
                    var postId = button.data('post-id');
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'gjp_get_structured_data',
                            post_id: postId,
                            nonce: '<?php echo wp_create_nonce('gjp_preview_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                preview.find('textarea').val(JSON.stringify(response.data, null, 2));
                                preview.show();
                                button.text('<?php _e('JSON-LDを非表示', 'google-job-posting'); ?>');
                            }
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
}

// AJAX handler for structured data preview
add_action('wp_ajax_gjp_get_structured_data', function() {
    check_ajax_referer('gjp_preview_nonce', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error();
        return;
    }

    $structured_data = GJP_Structured_Data::get_instance()->get_structured_data_preview($post_id);

    wp_send_json_success($structured_data);
});
