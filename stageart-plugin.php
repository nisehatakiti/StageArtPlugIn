<?php
/**
 * Plugin Name: StageArt PlugIn
 * Description: StageArt features for standalone WordPress sites.
 * Version: 0.3.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: nisehatakiti
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: stageart-plugin
 * Domain Path: /languages
 * AuthCore: application
 * AuthCore Application Key: stageart
 * AuthCore Application Name: StageArt
 * AuthCore Application Version: 0.3.0
 * AuthCore Application URI: https://github.com/nisehatakiti/StageArtPlugIn
 * AuthCore Vendor: nisehatakiti
 * AuthCore Vendor URI: https://github.com/nisehatakiti
 * AuthCore Description: 舞台芸術団体向け公演・団体・チケット管理
 * AuthCore Icon: dashicons-theater
 */

declare(strict_types=1);

if (!defined('ABSPATH')) exit;

define('STAGEART_PLUGIN_VERSION','0.3.0');
define('STAGEART_PLUGIN_FILE',__FILE__);
define('STAGEART_PLUGIN_DIR',plugin_dir_path(__FILE__));
define('STAGEART_PLUGIN_URL',plugin_dir_url(__FILE__));

spl_autoload_register(static function(string $class):void{ $prefix='StageArtPlugIn\\'; if(!str_starts_with($class,$prefix))return; $relative=substr($class,strlen($prefix)); $path=STAGEART_PLUGIN_DIR.'src/'.str_replace('\\','/',$relative).'.php'; if(is_file($path))require_once $path; });

register_activation_hook(STAGEART_PLUGIN_FILE,static function():void{
    StageArtPlugIn\Infrastructure\Schema\Schema::activate();
    (new StageArtPlugIn\Presentation\PublicSite\MemberRouter())->add_rewrite_rules();
    flush_rewrite_rules();
});

add_action('plugins_loaded',static function():void{(new StageArtPlugIn\Plugin())->boot();});
