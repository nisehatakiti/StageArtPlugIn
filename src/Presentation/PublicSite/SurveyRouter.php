<?php

declare(strict_types=1);
namespace StageArtPlugIn\Presentation\PublicSite;
use StageArtPlugIn\Domain\Survey\SurveyRepository;
final class SurveyRouter{
 private SurveyRepository $repo;
 public function __construct(){ $this->repo=new SurveyRepository(); }
 public function register():void{add_action('init',[$this,'rewrite']);add_filter('query_vars',[$this,'vars']);add_action('template_redirect',[$this,'render']);}
 public function rewrite():void{add_rewrite_rule('^production/([^/]+)/([^/]+)/?$','index.php?stageart_production_slug=$matches[1]&stageart_survey_slug=$matches[2]','top');}
 public function vars(array $vars):array{$vars[]='stageart_production_slug';$vars[]='stageart_survey_slug';return $vars;}
 public function render():void{
  $productionSlug=(string)get_query_var('stageart_production_slug');$surveySlug=(string)get_query_var('stageart_survey_slug');if($productionSlug===''||$surveySlug==='')return;
  $production=get_page_by_path($productionSlug,OBJECT,'stageart_production');if(!$production||$production->post_status!=='publish')return;
  $survey=$this->repo->findByProduction((int)$production->ID);if(!$survey||(string)$survey['slug']!==$surveySlug||(string)$survey['status']!=='publish')return;
  if(!$this->released($survey['release_at']??null)){status_header(404);nocache_headers();$this->page($production,'近日公開','このアンケートは現在公開されていません。');exit;}
  $questions=$this->repo->questions((int)$survey['id']);get_header();echo '<main class="stageart-survey"><div class="stageart-survey-inner"><p><a href="'.esc_url(home_url('/production/'.$production->post_name.'/')).'">'.esc_html($production->post_title).'へ戻る</a></p><h1>'.esc_html($survey['title']).'</h1>';if(!empty($survey['description']))echo '<div class="stageart-survey-description">'.wp_kses_post($survey['description']).'</div>';
  if(!$questions)echo '<p>現在、アンケートの設問はありません。</p>';else{echo '<form method="post"><input type="hidden" name="stageart_survey_id" value="'.(int)$survey['id'].'">';wp_nonce_field('stageart_submit_survey','stageart_survey_nonce');foreach($questions as $q)$this->question($q);echo '<p><button type="submit" name="stageart_submit_survey" value="1">回答を送信</button></p></form>';}echo '</div></main>';get_footer();exit;
 }
 private function question(array $q):void{$name='q_'.(int)$q['id'];$req=!empty($q['is_required']);echo '<fieldset class="stageart-survey-question"><legend>'.esc_html($q['label']).($req?' *':'').'</legend>';if(!empty($q['description']))echo '<p>'.esc_html($q['description']).'</p>';switch($q['type']){case'radio':foreach((array)$q['options'] as $o)echo '<label><input type="radio" name="'.esc_attr($name).'" value="'.esc_attr($o).'" '.($req?'required':'').'> '.esc_html($o).'</label><br>';break;case'checkbox':foreach((array)$q['options'] as $o)echo '<label><input type="checkbox" name="'.esc_attr($name).'[]" value="'.esc_attr($o).'"> '.esc_html($o).'</label><br>';break;case'select':echo '<select name="'.esc_attr($name).'" '.($req?'required':'').'><option value="">選択してください</option>';foreach((array)$q['options'] as $o)echo '<option value="'.esc_attr($o).'">'.esc_html($o).'</option>';echo '</select>';break;case'rating':echo '<select name="'.esc_attr($name).'" '.($req?'required':'').'><option value="">評価を選択</option>';for($n=1;$n<=5;$n++)echo '<option value="'.$n.'">'.$n.'</option>';echo '</select>';break;case'textarea':echo '<textarea name="'.esc_attr($name).'" rows="5" '.($req?'required':'').'></textarea>';break;default:echo '<input type="text" name="'.esc_attr($name).'" value="" '.($req?'required':'').'>'; }echo '</fieldset>';}
 private function released(?string $utc):bool{if(!$utc)return true;try{$d=new \DateTimeImmutable($utc,new \DateTimeZone('UTC'));return $d->getTimestamp()<=time();}catch(\Throwable){return false;}}
 private function page(\WP_Post $production,string $title,string $message):void{get_header();echo '<main class="stageart-survey"><div class="stageart-survey-inner"><p><a href="'.esc_url(home_url('/production/'.$production->post_name.'/')).'">'.esc_html($production->post_title).'へ戻る</a></p><h1>'.esc_html($title).'</h1><p>'.esc_html($message).'</p></div></main>';get_footer();}
}
