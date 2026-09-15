<?php

declare(strict_types=1);

namespace StageArtPlugIn;

use StageArtPlugIn\Infrastructure\Schema\Schema;
use StageArtPlugIn\Presentation\Admin\AdminMenu;
use StageArtPlugIn\Presentation\Admin\MemberAdmin;
use StageArtPlugIn\Presentation\Admin\SiteSettingsAdmin;
use StageArtPlugIn\Presentation\PublicSite\MemberShortcodes;
use StageArtPlugIn\Presentation\Rest\HealthController;
use StageArtPlugIn\Presentation\Rest\MemberController;

final class Plugin
{
    public function boot():void
    {
        if(get_option('stageart_plugin_db_version')!==Schema::DB_VERSION)Schema::activate();
        add_action('admin_menu',[new AdminMenu(),'register']);
        add_action('admin_menu',[new SiteSettingsAdmin(),'register'],20);
        add_action('admin_menu',[new MemberAdmin(),'register'],20);
        add_action('rest_api_init',static function():void{(new HealthController())->register_routes();(new MemberController())->register_routes();});
        (new MemberShortcodes())->register();
    }
}
