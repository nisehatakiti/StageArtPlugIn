<?php

declare(strict_types=1);
namespace StageArtPlugIn\Presentation\Admin;
final class SurveyAdminLink{
 public function register():void{add_action('admin_footer',[$this,'footer']);}
 public function footer():void{if(($_GET['page']??'')!=='stageart-productions'||empty($_GET['id']))return;$id=(int)$_GET['id'];echo '<p class="description" style="margin-left:20px">この公演のアンケートは、公演アンケート管理から設定できます。</p>';}
}
