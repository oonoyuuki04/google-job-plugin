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
        if (!is_singular(GJP_Post_Type::POST_TYPE)) {
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
        if (!$post || GJP_Post_Type::POST_TYPE !== $post->post_type) {
            return array();
        }

        // Get settings
        $settings = get_option('gjp_settings', array());
        $company_name = isset($settings['company_name']) ? $settings['company_name'] : get_bloginfo('name');
        $company_url = isset($settings['company_url']) ? $settings['company_url'] : home_url();
        $company_logo = isset($settings['company_logo']) ? $settings['company_logo'] : '';

        // Get meta data
        $job_title = get_post_meta($post_id, '_gjp_job_title', true) ?: $post->post_title;
        $employment_type = get_post_meta($post_id, '_gjp_employment_type', true);
        $valid_through = get_post_meta($post_id, '_gjp_valid_through', true);

        // Salary info
        $salary_currency = get_post_meta($post_id, '_gjp_salary_currency', true) ?: 'JPY';
        $salary_min = get_post_meta($post_id, '_gjp_salary_min', true);
        $salary_max = get_post_meta($post_id, '_gjp_salary_max', true);
        $salary_unit = get_post_meta($post_id, '_gjp_salary_unit', true) ?: 'MONTH';

        // Location info
        $street_address = get_post_meta($post_id, '_gjp_street_address', true);
        $address_locality = get_post_meta($post_id, '_gjp_address_locality', true);
        $address_region = get_post_meta($post_id, '_gjp_address_region', true);
        $postal_code = get_post_meta($post_id, '_gjp_postal_code', true);
        $address_country = get_post_meta($post_id, '_gjp_address_country', true) ?: 'JP';
        $remote_allowed = get_post_meta($post_id, '_gjp_remote_allowed', true);

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
     */
    private function get_description($post) {
        $description = '';

        // Use post content as base description
        if (!empty($post->post_content)) {
            $description = wp_strip_all_tags($post->post_content);
            $description = str_replace(array("\r\n", "\r", "\n"), ' ', $description);
        }

        // Use excerpt if content is empty
        if (empty($description) && !empty($post->post_excerpt)) {
            $description = wp_strip_all_tags($post->post_excerpt);
        }

        // Add additional information
        $work_days = get_post_meta($post->ID, '_gjp_work_days', true);
        $work_hours = get_post_meta($post->ID, '_gjp_work_hours', true);
        $access_info = get_post_meta($post->ID, '_gjp_access_info', true);
        $comment = get_post_meta($post->ID, '_gjp_comment', true);

        $additional_info = array();

        if (!empty($work_days)) {
            $additional_info[] = '就業日: ' . $work_days;
        }

        if (!empty($work_hours)) {
            $additional_info[] = '就業時間: ' . $work_hours;
        }

        if (!empty($access_info)) {
            $additional_info[] = '交通アクセス: ' . $access_info;
        }

        if (!empty($comment)) {
            $additional_info[] = $comment;
        }

        if (!empty($additional_info)) {
            $description .= "\n\n" . implode("\n", $additional_info);
        }

        // Ensure minimum length
        if (strlen($description) < 10) {
            $description = $post->post_title . 'の求人情報です。';
        }

        return trim($description);
    }

    /**
     * Get structured data for a specific post (for debugging/preview)
     */
    public function get_structured_data_preview($post_id) {
        return $this->generate_structured_data($post_id);
    }
}
