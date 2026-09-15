<?php

declare(strict_types=1);

namespace StageArtPlugIn;

use StageArtPlugIn\Presentation\Admin\AdminMenu;
use StageArtPlugIn\Presentation\Rest\HealthController;

final class Plugin
{
    public function boot(): void
    {
        add_action('admin_menu', [new AdminMenu(), 'register']);
        add_action('rest_api_init', static function (): void {
            (new HealthController())->register_routes();
        });
    }
}
