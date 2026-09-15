<?php

declare(strict_types=1);

namespace StageArtPlugIn\Domain\Production;

final class ProductionRepository
{
    private \wpdb $db;
    private string $participants;
    private string $performances;
    private string $labels;
    private string $tickets;

    public function __construct(?\wpdb $db = null){global $wpdb;$this->db=$db??$wpdb;$p=$this->db->prefix;$this->participants=$p.'stageart_plugin_production_participants';$this->performances=$p.'stageart_plugin_production_performances';$this->labels=$p.'stageart_plugin_performance_labels';$this->tickets=$p.'stageart_plugin_production_ticket_prices';}
    public function participants(int $productionId, string $kind=''):array{$sql="SELECT * FROM {$this->participants} WHERE production_id=%d";$args=[$productionId];if($kind!==''){$sql.=' AND kind=%s';$args[]=$kind;}$sql.=' ORDER BY display_order ASC,id ASC';$r=$this->db->get_results($this->db->prepare($sql,...$args),ARRAY_A)?:[];foreach($r as &$x){$x['id']=(int)$x['id'];$x['member_id']=(int)$x['member_id'];$x['auth_user_id']=$x['auth_user_id']!==null?(int)$x['auth_user_id']:null;}return $r;}
    public function performances(int $productionId):array{$r=$this->db->get_results($this->db->prepare("SELECT p.*,l.symbol,l.name AS label_name FROM {$this->performances} p LEFT JOIN {$this->labels} l ON l.id=p.label_id WHERE p.production_id=%d ORDER BY p.performance_date,p.start_time,p.id",$productionId),ARRAY_A)?:[];foreach($r as &$x){$x['id']=(int)$x['id'];$x['label_id']=$x['label_id']!==null?(int)$x['label_id']:null;}return $r;}
    public function labels(int $productionId):array{$r=$this->db->get_results($this->db->prepare("SELECT * FROM {$this->labels} WHERE production_id=%d ORDER BY display_order,id",$productionId),ARRAY_A)?:[];foreach($r as &$x)$x['id']=(int)$x['id'];return $r;}
    public function tickets(int $productionId):array{$r=$this->db->get_results($this->db->prepare("SELECT * FROM {$this->tickets} WHERE production_id=%d ORDER BY display_order,id",$productionId),ARRAY_A)?:[];foreach($r as &$x){$x['id']=(int)$x['id'];$x['amount']=(int)$x['amount'];$x['show_on_reservation']=(bool)$x['show_on_reservation'];}return $r;}
    public function saveParticipants(int $productionId,string $kind,array $rows):void{global $wpdb;$wpdb->delete($this->participants,['production_id'=>$productionId,'kind'=>$kind],['%d','%s']);$now=gmdate('Y-m-d H:i:s');foreach(array_values($rows) as $i=>$r){$name=sanitize_text_field((string)($r['name']??''));if($name==='')continue;$wpdb->insert($this->participants,['production_id'=>$productionId,'kind'=>$kind,'name'=>$name,'role'=>sanitize_text_field((string)($r['role']??'')),'member_id'=>!empty($r['member_id'])?(int)$r['member_id']:null,'auth_user_id'=>!empty($r['auth_user_id'])?(int)$r['auth_user_id']:null,'display_order'=>$i,'created_at'=>$now,'updated_at'=>$now],['%d','%s','%s','%s','%d','%d','%d','%s','%s']);}}
    public function saveLabels(int $productionId,array $rows):void{global $wpdb;$wpdb->delete($this->labels,['production_id'=>$productionId],['%d']);$now=gmdate('Y-m-d H:i:s');foreach(array_values($rows) as $i=>$r){$symbol=sanitize_text_field((string)($r['symbol']??''));$name=sanitize_text_field((string)($r['name']??''));if($symbol==='')continue;$wpdb->insert($this->labels,['production_id'=>$productionId,'symbol'=>$symbol,'name'=>$name,'display_order'=>$i,'created_at'=>$now,'updated_at'=>$now],['%d','%s','%s','%d','%s','%s']);}}
    public function savePerformances(int $productionId,array $rows):void{global $wpdb;$wpdb->delete($this->performances,['production_id'=>$productionId],['%d']);$now=gmdate('Y-m-d H:i:s');foreach($rows as $r){$date=preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($r['date']??''))?$r['date']:'';$start=preg_match('/^\d{2}:\d{2}$/',(string)($r['start']??''))?$r['start']:'';if(!$date||!$start)continue;$end=preg_match('/^\d{2}:\d{2}$/',(string)($r['end']??''))?$r['end']:null;$label=!empty($r['label_id'])?(int)$r['label_id']:null;$wpdb->insert($this->performances,['production_id'=>$productionId,'performance_date'=>$date,'start_time'=>$start,'end_time'=>$end,'label_id'=>$label,'created_at'=>$now,'updated_at'=>$now],['%d','%s','%s','%s','%d','%s','%s']);}}
    public function saveTickets(int $productionId,array $rows):void{global $wpdb;$wpdb->delete($this->tickets,['production_id'=>$productionId],['%d']);$now=gmdate('Y-m-d H:i:s');foreach(array_values($rows) as $i=>$r){$name=sanitize_text_field((string)($r['description']??''));if($name==='')continue;$amount=max(0,(int)($r['amount']??0));$wpdb->insert($this->tickets,['production_id'=>$productionId,'description'=>$name,'amount'=>$amount,'show_on_reservation'=>!empty($r['show_on_reservation'])?1:0,'display_order'=>$i,'created_at'=>$now,'updated_at'=>$now],['%d','%s','%d','%d','%d','%s','%s']);}}
}
