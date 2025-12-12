# セットアップ手順（既存サイト向け）

このプラグインは既存のWordPressサイトの求人投稿（カスタム投稿タイプ `jobs`）から、Googleしごと検索用の構造化データを自動生成します。

## 前提条件

既存のWordPressサイトで以下が既に設定されていることを前提としています：
- カスタム投稿タイプ `jobs` が存在する
- 求人情報がカスタムフィールドで管理されている

## セットアップ手順

### 1. プラグインのインストール

1. このフォルダ全体を `/wp-content/plugins/google-job-posting/` にアップロード
2. WordPress管理画面で「プラグイン」→「インストール済みプラグイン」
3. 「Google Job Posting」を有効化

### 2. 既存フィールド名の調査

既存のカスタムフィールド名を調べるために、以下の方法を使用します：

#### 方法A: Field Inspector ツールを使用

1. `tools/field-inspector.php` の内容をテーマの `functions.php` に一時的に追加
2. WordPress管理画面で既存の求人投稿を編集
3. ページ上部に表示されるすべてのカスタムフィールド名を記録
4. 記録後、追加したコードを削除

#### 方法B: データベースから確認

```sql
SELECT meta_key, meta_value
FROM wp_postmeta
WHERE post_id = [既存の求人投稿ID]
ORDER BY meta_key;
```

### 3. フィールドマッピングの設定

調査したフィールド名を基に、`includes/class-gjp-field-mapper.php` の `get_field_mapping()` 関数内のデフォルト値を更新します：

```php
$default_mapping = array(
    'post_type' => 'jobs', // カスタム投稿タイプ名
    'fields' => array(
        'job_title' => '',           // 職種フィールド名（空の場合は投稿タイトルを使用）
        'employment_type' => 'wpcf-koyou-keitai', // 雇用形態フィールド名（例）
        'salary' => 'wpcf-kyuyo',                 // 給与フィールド名（例）
        'location' => 'wpcf-kinmuchi',           // 勤務地フィールド名（例）
        'access' => 'wpcf-kotsu-access',         // 交通アクセスフィールド名（例）
        'work_days' => 'wpcf-shugyoubi',         // 就業日フィールド名（例）
        'work_hours' => 'wpcf-shugyou-jikan',    // 就業時間フィールド名（例）
        'job_content' => 'wpcf-oshigoto-naiyou', // お仕事内容フィールド名（例）
        'appeal_points' => 'wpcf-comment',       // コメント（アピールポイント）フィールド名（例）
        'period' => 'wpcf-kikan',                // 期間フィールド名（例）
    ),
);
```

**注意**: `wpcf-` プレフィックスは Types プラグイン使用時の例です。実際のフィールド名は使用しているプラグインやテーマによって異なります。

### 4. 会社情報の設定

1. WordPress管理画面で「求人設定」→「設定」にアクセス
2. 以下の情報を入力：
   - 会社名
   - 会社URL
   - 会社ロゴ（推奨サイズ: 112x112px以上）
3. 設定を保存

### 5. 動作確認

1. 既存の求人投稿ページを表示
2. ページのソースコードを表示（Ctrl+U または Cmd+U）
3. `<script type="application/ld+json">` タグが出力されているか確認
4. [Google リッチリザルト テスト](https://search.google.com/test/rich-results)でURLをテスト

## フィールド名の例

### Types プラグイン使用時
- プレフィックス: `wpcf-`
- 例: `wpcf-kyuyo`、`wpcf-kinmuchi`

### ACF（Advanced Custom Fields）使用時
- プレフィックスなし、または設定したフィールド名
- 例: `salary`、`location`

### カスタム開発の場合
- 開発時に設定したフィールド名
- 例: `job_salary`、`job_location`

## トラブルシューティング

### 構造化データが出力されない

1. プラグインが有効化されているか確認
2. 表示しているページが `jobs` カスタム投稿タイプか確認
3. フィールドマッピングが正しく設定されているか確認

### フィールドの値が取得できない

1. フィールド名が正しいか確認（アンダースコア `_` で始まる場合と始まらない場合がある）
2. Field Inspector ツールで正確なフィールド名を確認
3. デバッグ：一時的に `var_dump()` で値を確認

```php
// 一時的なデバッグコード（使用後は削除）
add_action('wp_head', function() {
    if (is_singular('jobs')) {
        global $post;
        $field_mapper = GJP_Field_Mapper::get_instance();
        $salary = $field_mapper->get_mapped_value($post->ID, 'salary');
        echo '<!-- Debug: salary = ' . esc_html($salary) . ' -->';
    }
});
```

## サポート

問題が発生した場合は、GitHubのIssuesで報告してください。
