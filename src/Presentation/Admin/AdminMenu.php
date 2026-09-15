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
        echo '<p>StageArt機能をWordPressサイトへ提供するプラグインです。</p>';
        echo '<p>今後、Organization / Production / Performanceを中心に、公開情報と運営業務を段階的に実装します。</p>';
        echo '</div>';
    }
}
