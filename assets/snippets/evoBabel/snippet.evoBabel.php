<?php
//@author webber (web-ber12@yandex.ru)

//значения по умолчанию на вкладке Свойства - &lang_template_id=id шаблона языка;text;11
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

$out='';

if(isset($_GET['id'])&&(int)$_GET['id']!=0){
	include_once('functions.evoBabel.php');

	//получаем массив языков сайта
	$langs=getAllSiteLangs($lang_template_id);

	//id текущего ресурса
	$id=(int)$_GET['id'];
	//id языка текущего ресурса
	$topid=getCurLangId($id);
	
	/*****************создаем версии********************/
	if(isset($_GET['ebabel'])&&(int)$_GET['ebabel']!=0&&isset($_GET['parent'])&&(int)$_GET['parent']!=0){
		$version_lang_id=(int)$_GET['ebabel'];
		$version_parent_id=(int)$_GET['parent'];
		include_once(MODX_BASE_PATH.'assets/libs/MODxAPI/modResource.php');
		$DOC = new modResource($modx);
		//копируем ресурс в нового родителя
		$sample=$DOC->copy($id);
		$new_pagetitle=$sample->get('pagetitle').' ('.$langs[$version_lang_id]['name'].')';
		$sample->set('parent',$version_parent_id);
		$sample->set('pagetitle',$new_pagetitle);
		$sample->set('published','0');
		$new_id=$sample->save(true,true);
		if($new_id){//если ресурс скопирован, создаем новые связи
		//проверяем старые связи
			$curr_rel=getRelations($id);
			if($curr_rel==''){//если связи не было, то просто создаем новую
				$new_rel=$langs[$topid]['alias'].':'.$id.'||'.$langs[$version_lang_id]['alias'].':'.$new_id;
				$modx->db->update(array('description'=>$new_rel),$modx->getFullTableName('site_content'),'id='.$id);
				$modx->db->update(array('description'=>$new_rel),$modx->getFullTableName('site_content'),'id='.$new_id);
			}
			else{//если связь есть, то обновляем ее везде
				$rel_arr=getRelationsArray($curr_rel);
				$new_rel='';
				foreach($langs as $k=>$v){
					if(isset($rel_arr[$v['alias']])&&checkPage($rel_arr[$v['alias']])){//если страница старая
						$new_rel.=$v['alias'].':'.$rel_arr[$v['alias']].'||';
					}
					else if($k==$version_lang_id){
						$new_rel.=$v['alias'].':'.$new_id.'||';
					}
					else{}
				}
				$new_rel=substr($new_rel,0,-2);
				$rel_arr2=getRelationsArray($new_rel);
				foreach($rel_arr2 as $k=>$v){
					$modx->db->update(array('description'=>$new_rel),$modx->getFullTableName('site_content'),'id='.$v);
				}
			}
			echo '<script type="text/javascript">location.href="index.php?a=27&id='.$id.'"</script>';
	
		}

	}
	/*********************** конец создания версий ****************/
	
	
	//id родительского ресурса и его полные связи
	$parent_id=$modx->db->getValue($modx->db->query("SELECT parent FROM ".$modx->getFullTableName('site_content')." WHERE id={$id} LIMIT 0,1"));
	$parent_rels=getFullRelationsArray($parent_id,$langs);

	
	$out.='<b>Текущая версия:</b> '.$langs[$topid]['name'].'<br><br>';
	$out.='<b>Другие языки:</b><br><br><div class="actionButtons">';

	//получаем связь текущей страницы
	$relation=getRelations($id);
	if($relation){
		$rels=getRelationsArray($relation);
		foreach ($langs as $k=>$v){
			if($k!=$topid){
				if(isset($rels[$v['alias']])&&checkPage($rels[$v['alias']])){	
					$out.='<div style="height:38px;">'.$v['name'].' - <a href="index.php?a=27&id='.$rels[$v['alias']].'" class="primary"><img alt="icons_save" src="media/style/MODxRE/images/icons/save.png"/> перейти</a></div>';	
				}
				else{
				$out.='<div style="height:38px;">'.$v['name'].' - <a href="index.php?a=27&id='.$id.'&ebabel='.$k.'&parent='.$parent_rels[$v['alias']].'"><img src="media/style/MODxRE/images/icons/page_white_copy.png" alt="icons_resource_duplicate"/> создать</a></div>';
				}
			}
		}
	}
	else{//если связей нет, то выводим ссылки на создание без проверок
		foreach ($langs as $k=>$v){
			if($k!=$topid){
				$out.='<div style="height:35px;">'.$v['name'].' - <a href="index.php?a=27&id='.$id.'&ebabel='.$k.'&parent='.$parent_rels[$v['alias']].'"><img src="media/style/MODxRE/images/icons/page_white_copy.png" alt="icons_resource_duplicate"/> создать</a>';
				if($parent_rels[$v['alias']]==$k&&$k!=$parent_id&&!isset($langs[$parent_id])){$out.=' &nbsp; <b><font color=red>Внимание!</font></b> Рекомендуется создать сначала языковую версию <a href="index.php?a=27&id='.$parent_id.'"><img src="media/style/MODxRE/images/icons/delete.png" alt="icons_delete_document"/> родителя</a>';}
				$out.='</div>';
			}
		}
	}
	
	$out.='</div><br>';
	echo $out;
}
?>