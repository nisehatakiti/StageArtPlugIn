<?php

declare(strict_types=1);
namespace StageArtPlugIn;
use StageArtPlugIn\Infrastructure\Schema\Schema;
use StageArtPlugIn\Presentation\Admin\AdminMenu;
use StageArtPlugIn\Presentation\Admin\MemberAdmin;
use StageArtPlugIn\Presentation\Admin\ProductionAdmin;
use StageArtPlugIn\Presentation\Admin\SiteSettingsAdmin;
use StageArtPlugIn\Presentation\PublicSite\MemberRouter;
use StageArtPlugIn\Presentation\PublicSite\MemberShortcodes;
use StageArtPlugIn\Presentation\PublicSite\ProductionRouter;
use StageArtPlugIn\Presentation\Rest\HealthController;
use StageArtPlugIn\Presentation\Rest\MemberController;
final class Plugin{public function boot():void{if(get_option('stageart_plugin_db_version')!==Schema::DB_VERSION)Schema::activate();add_action('init',static function():void{register_post_type('stageart_production',['labels'=>['name'=>'公演','singular_name'=>'公演'],'public'=>false,'show_ui'=>false,'supports'=>['title'],'rewrite'=>false]);},5);add_action('admin_menu',[new AdminMenu(),'register']);add_action('admin_menu',[new SiteSettingsAdmin(),'register'],20);add_action('admin_menu',[new MemberAdmin(),'register'],20);add_action('admin_menu',[new ProductionAdmin(),'register'],20);add_action('rest_api_init',static function():void{(new HealthController())->register_routes();(new MemberController())->register_routes();});(new MemberShortcodes())->register();(new MemberRouter())->register();(new ProductionRouter())->register();}}
