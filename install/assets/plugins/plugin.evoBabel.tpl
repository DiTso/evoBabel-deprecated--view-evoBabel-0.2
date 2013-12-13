/**
 * evoBabel
 *
 * plugin for work evoBabel
 *
 * @author	    webber (web-ber12@yandex.ru)
 * @category	plugin
 * @version	    0.1
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal	@guid 223453636a8c613426979b9dea1ff0415abf
 * @internal    @events OnPageNotFound,OnDocFormSave,OnBeforeEmptyTrash,OnEmptyTrash
 * @internal    @properties &synch_TV=ids TV для синхронизации;text;13,14 &synch_template=ids шаблонов для синхронизации;text;7
 * @internal    @installset MultiLang
 * @internal	@modx_category Manager and Admin
 */


if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

$content_table=$modx->getFullTableName('site_content');
$tvs_table=$modx->getFullTableName('site_tmplvar_contentvalues');

$e =& $modx->event;
switch ($e->name ) {
    case 'OnPageNotFound'://переадресация на нужную страницу 404, указать ее в языковом чанке
		$docid=0;
		$id=$modx->runSnippet("lang",array('a'=>'Страница не найдена'));
		$docid=(int)$id;
		if($docid==0){
			$id=$modx->runSnippet("lang",array('a'=>'Главная страница'));
			$docid=(int)$id;
			if($docid==0){
				$id=$modx->runSnippet("lang",array('a'=>'Корневая папка'));
				$docid=(int)$id;
			}
		}
		if($docid!=0){
			$modx->sendRedirect($modx->makeUrl($docid));
		}
		break ;
		
	case 'OnDocFormSave'://синхронизация выбранных TV на выбранном шаблоне
		if($e->params['mode']=='upd'&&(isset($synch_template)&&$synch_template!='')&&(isset($synch_TV)&&$synch_TV!='')){
			$docid=$e->params['id'];
			include_once(MODX_BASE_PATH."assets/snippets/evoBabel/functions.evoBabel.php");
			$q=$modx->db->query("SELECT description FROM {$content_table} WHERE id={$docid} AND template IN ({$synch_template}) LIMIT 0,1");
			if($modx->db->getRecordCount($q)==1){
				$res=$modx->db->getRow($q);
				$rels=$res['description'];
				$relations=getRelationsArray($rels);
				$q=$modx->db->query("SELECT tmplvarid,value FROM {$tvs_table} WHERE contentid={$docid} AND tmplvarid IN ({$synch_TV})");
				while($tvs=$modx->db->getRow($q)){
					foreach($relations as $k=>$v){
						if($v!=$docid){
							$q2=$modx->db->query("SELECT tmplvarid,value FROM {$tvs_table} WHERE contentid={$v} AND tmplvarid IN ({$tvs['tmplvarid']}) LIMIT 0,1");
							if($modx->db->getRecordCount($q2)==0){
								$modx->db->insert(array('tmplvarid'=>$tvs['tmplvarid'],'contentid'=>$v,'value'=>$tvs['value']),$tvs_table);
							}
							else{
								$modx->db->update(array('value'=>$tvs['value']),$tvs_table,"tmplvarid={$tvs['tmplvarid']} AND contentid={$v}");
							}
						}
					}
				}
			}
		}
		break;
		
	case 'OnBeforeEmptyTrash': //собираем связи окончательно удаляемых ресурсов, чтобы потом скорректировать их связанные версии
		if(isset($ids)&&is_array($ids)){
			$del_ids=implode(',',$ids);
			$del_array=array();
			include_once(MODX_BASE_PATH."assets/snippets/evoBabel/functions.evoBabel.php");
			$q=$modx->db->query("SELECT id,description FROM ".$modx->getFullTableName('site_content')." WHERE id IN ({$del_ids})");
			while($row=$modx->db->getRow($q)){
				if($row['description']!=''){
					$rel_array=getRelationsArray($row['description']);
					$del_array[$row['id']]=$rel_array;
				}
			}
			$_SESSION['del_array']=$del_array;
		}
		break;
		
	case 'OnEmptyTrash': //корректируем связи языковых версий с учетом окончательного удаления ресурсов
		$del_array=$_SESSION['del_array'];
		if(!empty($del_array)){
			foreach($del_array as $del_id=>$del_rels){
				if(is_array($del_rels)){
					$newrel='';
					$oldrel='';
					foreach($del_rels as $k=>$v){
						$oldrel.=$k.':'.$v.'||';
						if($v!=$del_id){
							$newrel.=$k.':'.$v.'||';
						}
					}
					$oldrel=substr($oldrel,0,-2);
					$newrel=substr($newrel,0,-2);
					if($oldrel!=''){
						$modx->db->update(array('description'=>$newrel),$modx->getFullTableName('site_content'),"`description`='".$oldrel."'");
					}
				}
			}
		}
		break;
	
    default:
        return ;
}
?>