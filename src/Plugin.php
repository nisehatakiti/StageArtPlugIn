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
final class Plugin{
 public function boot():void{
  if(get_option('stageart_plugin_db_version')!==Schema::DB_VERSION)Schema::activate();
  add_action('init',static function():void{register_post_type('stageart_production',['labels'=>['name'=>'公演','singular_name'=>'公演'],'public'=>false,'show_ui'=>false,'supports'=>['title'],'rewrite'=>false]);},5);
  add_action('admin_menu',[new AdminMenu(),'register']);add_action('admin_menu',[new SiteSettingsAdmin(),'register'],20);add_action('admin_menu',[new MemberAdmin(),'register'],20);add_action('admin_menu',[new ProductionAdmin(),'register'],20);
  add_action('save_post_stageart_production',static function(int $postId):void{if(defined('DOING_AUTOSAVE')&&DOING_AUTOSAVE)return;if(!current_user_can('manage_options'))return;if(!isset($_POST['stageart_production_action']))return;if(!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stageart_production_action'])),'stageart_production_action'))return;foreach(['cast_release','staff_release'] as $k){$v=wp_unslash($_POST[$k]??'');if($v){$d=\DateTimeImmutable::createFromFormat('Y-m-d H:i',str_replace('T',' ',trim((string)$v)),new \DateTimeZone('Asia/Tokyo'));if($d)update_post_meta($postId,$k,$d->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'));}else delete_post_meta($postId,$k);}},20);
  add_action('rest_api_init',static function():void{(new HealthController())->register_routes();(new MemberController())->register_routes();});(new MemberShortcodes())->register();(new MemberRouter())->register();(new ProductionRouter())->register();
 }
}
