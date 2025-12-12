<?php
/**
 * Structured Data Generator for Google for Jobs
 */

if (!defined('ABSPATH')) {
    exit;
}

class GJP_Structured_Data {

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
        add_action('wp_head', array($this, 'output_structured_data'), 1);
    }

    /**
     * Output structured data in the head
     */
    public function output_structured_data() {
        // Get configured post type from field mapper
        $field_mapper = GJP_Field_Mapper::get_instance();
        $mapping = $field_mapper->get_field_mapping();
        $post_type = isset($mapping['post_type']) ? $mapping['post_type'] : 'jobs';

        // Check if current page is a single job post
        if (!is_singular($post_type)) {
            return;
        }

        global $post;
        $structured_data = $this->generate_structured_data($post->ID);

        if (!empty($structured_data)) {
            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode($structured_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            echo "\n" . '</script>' . "\n";
        }
    }

    /**
     * Generate structured data for a job posting
     */
    public function generate_structured_data($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return array();
        }

        // Get field mapper
        $field_mapper = GJP_Field_Mapper::get_instance();

        // Get settings
        $settings = get_option('gjp_settings', array());
        $company_name = isset($settings['company_name']) ? $settings['company_name'] : get_bloginfo('name');
        $company_url = isset($settings['company_url']) ? $settings['company_url'] : home_url();
        $company_logo = isset($settings['company_logo']) ? $settings['company_logo'] : '';

        // Get mapped values from existing fields
        $job_title = $field_mapper->get_mapped_value($post_id, 'job_title');
        if (empty($job_title)) {
            $job_title = $post->post_title;
        }

        $employment_type_raw = $field_mapper->get_mapped_value($post_id, 'employment_type');
        $employment_type = $this->parse_employment_type($employment_type_raw);
        $valid_through = get_post_meta($post_id, '_gjp_valid_through', true);

        // Salary info - from mapped field
        $salary_text = $field_mapper->get_mapped_value($post_id, 'salary');
        $salary_data = $this->parse_salary($salary_text);
        $salary_currency = $salary_data['currency'];
        $salary_min = $salary_data['min'];
        $salary_max = $salary_data['max'];
        $salary_unit = $salary_data['unit'];

        // Location info - from mapped field
        $location_text = $field_mapper->get_mapped_value($post_id, 'location');
        $location_data = $this->parse_location($location_text);
        $street_address = $location_data['street'];
        $address_locality = $location_data['locality'];
        $address_region = $location_data['region'];
        $postal_code = $location_data['postal_code'];
        $address_country = 'JP';
        $remote_allowed = false;

        // Build structured data
        $structured_data = array(
            '@context' => 'https://schema.org/',
            '@type' => 'JobPosting',
            'title' => $job_title,
            'description' => $this->get_description($post),
            'datePosted' => get_the_date('c', $post_id),
            'hiringOrganization' => array(
                '@type' => 'Organization',
                'name' => $company_name,
                'sameAs' => $company_url,
            ),
            'jobLocation' => array(
                '@type' => 'Place',
                'address' => array(
                    '@type' => 'PostalAddress',
                ),
            ),
        );

        // Add company logo if available
        if (!empty($company_logo)) {
            $structured_data['hiringOrganization']['logo'] = $company_logo;
        }

        // Add employment type
        if (!empty($employment_type)) {
            $structured_data['employmentType'] = $employment_type;
        }

        // Add valid through date
        if (!empty($valid_through)) {
            $structured_data['validThrough'] = date('c', strtotime($valid_through));
        }

        // Add salary information
        if (!empty($salary_min) || !empty($salary_max)) {
            $base_salary = array(
                '@type' => 'MonetaryAmount',
                'currency' => $salary_currency,
                'value' => array(
                    '@type' => 'QuantitativeValue',
                    'unitText' => $salary_unit,
                ),
            );

            if (!empty($salary_min) && !empty($salary_max)) {
                $base_salary['value']['minValue'] = floatval($salary_min);
                $base_salary['value']['maxValue'] = floatval($salary_max);
            } elseif (!empty($salary_min)) {
                $base_salary['value']['value'] = floatval($salary_min);
            } elseif (!empty($salary_max)) {
                $base_salary['value']['value'] = floatval($salary_max);
            }

            $structured_data['baseSalary'] = $base_salary;
        }

        // Add location address
        $address_parts = array();
        if (!empty($street_address)) {
            $structured_data['jobLocation']['address']['streetAddress'] = $street_address;
            $address_parts[] = $street_address;
        }
        if (!empty($address_locality)) {
            $structured_data['jobLocation']['address']['addressLocality'] = $address_locality;
            $address_parts[] = $address_locality;
        }
        if (!empty($address_region)) {
            $structured_data['jobLocation']['address']['addressRegion'] = $address_region;
            $address_parts[] = $address_region;
        }
        if (!empty($postal_code)) {
            $structured_data['jobLocation']['address']['postalCode'] = $postal_code;
        }
        if (!empty($address_country)) {
            $structured_data['jobLocation']['address']['addressCountry'] = $address_country;
        }

        // Add remote work information
        if ($remote_allowed === '1') {
            $structured_data['jobLocationType'] = 'TELECOMMUTE';
        }

        // Add identifier
        $structured_data['identifier'] = array(
            '@type' => 'PropertyValue',
            'name' => $company_name,
            'value' => $post_id,
        );

        return apply_filters('gjp_structured_data', $structured_data, $post_id);
    }

    /**
     * Get job description
     * Optimized for Google for Jobs search ranking
     */
    private function get_description($post) {
        $sections = array();
        $field_mapper = GJP_Field_Mapper::get_instance();

        // 1. アピールポイント・特徴（最初に配置して目立たせる）
        $appeal_points = $field_mapper->get_mapped_value($post->ID, 'appeal_points');
        if (!empty($appeal_points)) {
            $sections[] = '【アピールポイント】' . "\n" . strip_tags($appeal_points);
        }

        // 2. お仕事内容（詳細）
        $job_content = $field_mapper->get_mapped_value($post->ID, 'job_content');
        if (!empty($job_content)) {
            $sections[] = '【お仕事内容】' . "\n" . strip_tags($job_content);
        } elseif (!empty($post->post_content)) {
            // フォールバック：投稿本文を使用
            $content = wp_strip_all_tags($post->post_content);
            $sections[] = '【お仕事内容】' . "\n" . $content;
        }

        // 3. 給与情報
        $salary_text = $field_mapper->get_mapped_value($post->ID, 'salary');
        if (!empty($salary_text)) {
            $sections[] = '【給与】' . strip_tags($salary_text);
        }

        // 4. 勤務地情報
        $location_text = $field_mapper->get_mapped_value($post->ID, 'location');
        if (!empty($location_text)) {
            $sections[] = '【勤務地】' . strip_tags($location_text);
        }

        // 5. 交通アクセス
        $access_info = $field_mapper->get_mapped_value($post->ID, 'access');
        if (!empty($access_info)) {
            $sections[] = '【交通アクセス】' . strip_tags($access_info);
        }

        // 6. 勤務時間・日数
        $work_hours = $field_mapper->get_mapped_value($post->ID, 'work_hours');
        $work_days = $field_mapper->get_mapped_value($post->ID, 'work_days');

        $work_info_parts = array();
        if (!empty($work_hours)) {
            $work_info_parts[] = '勤務時間: ' . strip_tags($work_hours);
        }
        if (!empty($work_days)) {
            $work_info_parts[] = '勤務日: ' . strip_tags($work_days);
        }
        if (!empty($work_info_parts)) {
            $sections[] = '【勤務時間】' . implode(' / ', $work_info_parts);
        }

        // 7. 雇用形態
        $employment_type_raw = $field_mapper->get_mapped_value($post->ID, 'employment_type');
        if (!empty($employment_type_raw)) {
            $sections[] = '【雇用形態】' . strip_tags($employment_type_raw);
        }

        // 8. 期間
        $period = $field_mapper->get_mapped_value($post->ID, 'period');
        if (!empty($period)) {
            $sections[] = '【期間】' . strip_tags($period);
        }

        // 全セクションを結合
        $description = implode("\n\n", array_filter($sections));

        // 最低限の情報を確保
        if (strlen($description) < 50) {
            $job_title = $field_mapper->get_mapped_value($post->ID, 'job_title');
            if (empty($job_title)) {
                $job_title = $post->post_title;
            }
            $description = $job_title . 'の求人情報です。' . "\n" . $description;
        }

        return trim($description);
    }

    /**
     * Get structured data for a specific post (for debugging/preview)
     */
    public function get_structured_data_preview($post_id) {
        return $this->generate_structured_data($post_id);
    }

    /**
     * Parse employment type from Japanese text to Google for Jobs format
     */
    private function parse_employment_type($text) {
        if (empty($text)) {
            return '';
        }

        $text = strip_tags($text);

        // マッピング：日本語 → Google for Jobs形式
        $mappings = array(
            '正社員' => 'FULL_TIME',
            'アルバイト' => 'PART_TIME',
            'パート' => 'PART_TIME',
            '契約社員' => 'CONTRACTOR',
            '派遣' => 'TEMPORARY',
            'インターン' => 'INTERN',
            'ボランティア' => 'VOLUNTEER',
            '日雇い' => 'PER_DIEM',
        );

        foreach ($mappings as $jp => $en) {
            if (stripos($text, $jp) !== false) {
                return $en;
            }
        }

        return 'OTHER';
    }

    /**
     * Parse salary from Japanese text
     */
    private function parse_salary($text) {
        $result = array(
            'currency' => 'JPY',
            'min' => null,
            'max' => null,
            'unit' => 'MONTH',
        );

        if (empty($text)) {
            return $result;
        }

        $text = strip_tags($text);

        // 給与単位を判定
        if (preg_match('/(時給|時間給)/u', $text)) {
            $result['unit'] = 'HOUR';
        } elseif (preg_match('/(日給)/u', $text)) {
            $result['unit'] = 'DAY';
        } elseif (preg_match('/(月給|月額)/u', $text)) {
            $result['unit'] = 'MONTH';
        } elseif (preg_match('/(年俸|年収)/u', $text)) {
            $result['unit'] = 'YEAR';
        }

        // 金額を抽出（カンマを除去）
        $text_numbers = preg_replace('/,/', '', $text);

        // パターン1: 「XXX円〜YYY円」形式
        if (preg_match('/([0-9]+)\s*円?\s*[〜～~-]\s*([0-9]+)\s*円?/u', $text_numbers, $matches)) {
            $result['min'] = intval($matches[1]);
            $result['max'] = intval($matches[2]);
        }
        // パターン2: 「XXX円〜」形式
        elseif (preg_match('/([0-9]+)\s*円?\s*[〜～~-]/u', $text_numbers, $matches)) {
            $result['min'] = intval($matches[1]);
        }
        // パターン3: 「XXX円」形式
        elseif (preg_match('/([0-9]+)\s*円?/u', $text_numbers, $matches)) {
            $result['min'] = intval($matches[1]);
        }

        return $result;
    }

    /**
     * Parse location from Japanese address text
     */
    private function parse_location($text) {
        $result = array(
            'region' => '',
            'locality' => '',
            'street' => '',
            'postal_code' => '',
        );

        if (empty($text)) {
            return $result;
        }

        $text = strip_tags($text);

        // 郵便番号を抽出
        if (preg_match('/([0-9]{3}-[0-9]{4})/u', $text, $matches)) {
            $result['postal_code'] = $matches[1];
        }

        // 都道府県を抽出
        $prefectures = array(
            '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
            '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
            '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県',
        );

        foreach ($prefectures as $pref) {
            if (stripos($text, $pref) !== false) {
                $result['region'] = $pref;
                // 都道府県以降の部分を取得
                $parts = explode($pref, $text, 2);
                if (isset($parts[1])) {
                    $remaining = trim($parts[1]);

                    // 市区町村を抽出（最初の市・区・町・村まで）
                    if (preg_match('/^([^0-9]+?[市区町村])/u', $remaining, $matches)) {
                        $result['locality'] = $matches[1];
                        $result['street'] = trim(str_replace($result['locality'], '', $remaining));
                    } else {
                        $result['street'] = $remaining;
                    }
                }
                break;
            }
        }

        return $result;
    }
}
