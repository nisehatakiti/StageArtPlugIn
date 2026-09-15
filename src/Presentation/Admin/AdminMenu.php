<?php

declare(strict_types=1);

namespace StageArtPlugIn\Presentation\Admin;

final class AdminMenu
{
    public function register(): void
    {
        add_menu_page(
            'StageArt',
            'StageArt',
            'manage_options',
            'stageart-plugin',
            [$this, 'render'],
            'dashicons-tickets-alt',
            30
        );
    }

    public function render(): void
    {
        echo '<div class="wrap">';
        echo '<h1>StageArt</h1>';
        echo '<p>舞台芸術団体のWordPressサイトを管理するためのStageArt PlugInです。</p>';
        echo '<p>団体情報はサイト設定として管理し、公演・メンバーなどのコンテンツを独立して管理します。</p>';
        echo '<p>メンバーには、劇団共通の自由記述項目を追加・編集・並び替え・無効化・再利用できる設計を採用しています。無効化した項目の入力データは保持され、再利用時に復活します。</p>';
        echo '</div>';
    }
}
