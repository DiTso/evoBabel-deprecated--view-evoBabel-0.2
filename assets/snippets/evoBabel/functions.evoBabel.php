<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

function checkPage($id){//проверка существования страницы
	global $modx;
	$result=$modx->db->getValue($modx->db->query("SELECT id FROM ".$modx->getFullTableName('site_content')." WHERE id={$id} LIMIT 0,1"));
	return $result;
	
}

function checkActivePage($id){//проверка существования страницы и активности страницы
	global $modx;
	$result=$modx->db->getValue($modx->db->query("SELECT id FROM ".$modx->getFullTableName('site_content')." WHERE id={$id} AND deleted=0 AND published=1 LIMIT 0,1"));
	return $result;
	
}

function getSiteLangs($lang_template_id){
	global $modx;
	$q=$modx->db->query("SELECT * FROM ".$modx->getFullTableName('site_content')." WHERE parent=0 AND template=".$lang_template_id." AND published=1 AND deleted=0 ORDER BY menuindex ASC");
	while($row=$modx->db->getRow($q)){
		$langs[$row['id']]['name']=$row['longtitle'];
		$langs[$row['id']]['home']=$row['description'];
		$langs[$row['id']]['alias']=$row['alias'];
	}
	return $langs;
}

function _loadParent($id, $height){
	global $modx;
    $parents = array();
    $q=$modx->db->query("SELECT parent FROM ".$modx->getFullTableName("site_content")." WHERE id=".(int)$id);
    if($modx->db->getRecordCount($q)==1){
        $q = $modx->db->getRow($q);
        $parents[$q['parent']] = $id;
        if($height>0 && $q['parent']>0){
            $data=_loadParent($q['parent'],$height--);
            foreach($data as $key=>$val){
                $parents[$key] = $val;
            }
        }
    }
    return $parents;
}

function getParentIds($id, $height= 10) {
    $parents = _loadParent($id,$height);
    reset($parents);
    unset($parents[key($parents)]);
    return $parents;
}

function getCurLangId($id){
	$res=getParentIds($id);
	return $res[0];
}



function getRelations($id){//получаем строку отношений для ресурса
	global $modx;
	$res=$modx->db->getValue($modx->db->query("SELECT description FROM ".$modx->getFullTableName('site_content')." WHERE id={$id} LIMIT 0,1"));
	return $res;
}	

function getRelationsArray($relations){ //array ['lang_alias']=>['lang_page_id']
	global $modx;
	$arr=array();
	if($relations!=''){
		$arr1=explode("||",$relations);
		foreach($arr1 as $k=>$v){
			if(isset($v)&&$v!=''){
				$arr2=explode(":",$v);
				$arr[$arr2[0]]=$arr2[1];
			}
		}
	}
	return $arr;
}

function getFullRelationsArray($id,$langsArray){//полные отношения - недостающие заменяем на корневые языки
	global $modx;
	if(!isset($langsArray[$id])){
		$relations=getRelations($id);
		$relationsArray=getRelationsArray($relations);
		foreach ($langsArray as $k=>$v){
		if(!isset($relationsArray[$v['alias']])){
			$relationsArray[$v['alias']]=$k;
			}
		}
	}
	else{
		foreach ($langsArray as $k=>$v){
			$relationsArray[$v['alias']]=$k;
		}
	}
	return $relationsArray;
}





?>