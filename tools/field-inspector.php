<?php
/**
 * Field Name Inspector
 *
 * このファイルを一時的に有効化して、既存のjobs投稿のカスタムフィールド名を確認します
 * 使い方：このファイルの内容をWordPressの管理画面やPHPファイルで実行
 */

// 既存のjobs投稿のカスタムフィールドを確認
add_action('admin_notices', 'gjp_display_jobs_custom_fields');

function gjp_display_jobs_custom_fields() {
    $screen = get_current_screen();

    // jobsの編集画面のみ表示
    if ($screen && $screen->post_type === 'jobs' && $screen->base === 'post') {
        global $post;

        if ($post) {
            // すべてのカスタムフィールドを取得
            $custom_fields = get_post_custom($post->ID);

            echo '<div class="notice notice-info" style="padding: 15px; max-height: 400px; overflow-y: auto;">';
            echo '<h3>既存のカスタムフィールド一覧</h3>';
            echo '<p>このプラグインで使用するフィールド名を確認してください。</p>';
            echo '<table class="widefat" style="margin-top: 10px;">';
            echo '<thead><tr><th>フィールド名</th><th>値</th></tr></thead>';
            echo '<tbody>';

            foreach ($custom_fields as $key => $values) {
                // WordPress内部フィールド（_で始まるもの）も表示
                $display_key = $key;
                $display_value = is_array($values) ? implode(', ', $values) : $values;

                // 長い値は省略
                if (strlen($display_value) > 100) {
                    $display_value = substr($display_value, 0, 100) . '...';
                }

                echo '<tr>';
                echo '<td><strong>' . esc_html($display_key) . '</strong></td>';
                echo '<td>' . esc_html($display_value) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '</div>';
        }
    }
}

// 既存のjobs投稿タイプを確認
add_action('admin_notices', 'gjp_check_jobs_post_type');

function gjp_check_jobs_post_type() {
    if (isset($_GET['gjp_check_post_types'])) {
        $post_types = get_post_types(['public' => true], 'objects');

        echo '<div class="notice notice-info" style="padding: 15px;">';
        echo '<h3>登録されているカスタム投稿タイプ</h3>';
        echo '<ul>';

        foreach ($post_types as $post_type) {
            if ($post_type->name === 'jobs' || strpos($post_type->name, 'job') !== false) {
                echo '<li><strong>' . esc_html($post_type->name) . '</strong> - ' . esc_html($post_type->label) . '</li>';
            }
        }

        echo '</ul>';
        echo '</div>';
    }
}
?>

<!--
使用方法：

1. このコードをテーマのfunctions.phpに一時的に追加、または
2. WordPressの管理画面 > ツール > テーマファイルエディタで追加

3. jobs投稿の編集画面を開くと、すべてのカスタムフィールド名が表示されます

4. 確認後、このコードを削除してください
-->
