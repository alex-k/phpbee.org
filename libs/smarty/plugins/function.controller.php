<?php
function smarty_function_controller($params, &$smarty)
{
		    //var_dump($params);
		    //die();
		    if (isset($params['_params'])) {
				$params=array_merge($params,$params['_params']);
		    }
		    $obj=new $params['_class'];
		    if (isset($params['_id'])) $params[$obj->id_field_name]=$params['_id'];
		    if ($params['_assign_type']=='class') {
			    $obj->new_record(array());
			    $smarty->assign($params['_assign'],$obj->current());
			    return;
		    }

		    $vars=array();
		    foreach ($params as $k=>$v) {
				if ($v!==FALSE && strpos($k,'_')!==0) {
					$vv=explode(',',$v);
					foreach ($vv as $val) {
						$vars[]=array($k,$val); 					
					}
				}
		    }

		    $_search_options=unserialize(base64_decode($params['_search_options']));
		    if (is_array($_search_options)) {
					$fields=$obj->structure['fields'];
					foreach ($_search_options as $k=>$v) {
						if($v!==FALSE && isset($obj->structure['fields'][$k])) $vars[]=array($k,$v);
					}
		    }
		    foreach ($vars as $val) {
			    list($k,$v)=$val;
			    if (preg_match('/^(LIKE|!=|<=|>=|<|>)(.*)/',$v,$matches)) {
				    $options[]=array('type'=>'value', 'field'=>$k,'case'=>$matches[1],'value'=>$matches[2]);
			    } else {
				    $options[]=array('type'=>'value','field'=>$k,'value'=>$v);
			    }
		    }

		    if (isset($params['_offset'])) $_offset=(int)$params['_offset'];

		    if (!empty($params['_paging'])) {
			    	if (!isset($_offset)) {
					$get=$smarty->get_template_vars('_gsdata');
					$_offset=(int)$get[$params['_assign'].'_paging'];
				}

				$count=$obj->count_records($options);
				list($_paging_type,$_paging_itemsperpage)=sscanf($params['_paging'],"%[A-Za-z]:%d");
				require_once('function.controller.paging.php');
				$pages=gs_controller_paging($params['_assign']."_paging", $_paging_type,$count,$_paging_itemsperpage,$_offset);
				$smarty->assign($params['_assign']."_paging",$pages);
		    }
		    
		    
		    if (isset($params['_limit'])) $options[]=array('type'=>'limit','value'=>$params['_limit']);
		    	else if (isset($params['_paging'])) sscanf($params['_paging'],'pagenums:%d',$limit) && $options[]=array('type'=>'limit','value'=>$limit); 
		    if (isset($_offset)) $options[]=array('type'=>'offset','value'=>$_offset);
		    if (isset($params['_orderby'])) $options[]=array('type'=>'orderby','value'=>$params['_orderby']);
		    
		    $fields=(isset($params['_fields'])) ? $params['_fields'] : NULL;
		    
		    
		    $ret=$obj->find_records($options,$fields);
		    //$vars=$ret->get_values();
		    if ($params['_assign_type']=='plain') {
			$ret=$ret->current();
					
			if (!$ret) return;

			$vars=$ret->get_values();
		        if ($params['_skip_filled'] && is_array($vars)) {
					$tpl_vars=$smarty->get_template_vars();
					foreach ($vars as $k=>$v) {
							if(isset($tpl_vars[$k])) unset($vars[$k]);
					}
			}
		        $smarty->assign($vars);
			if(isset($params['_assign'])) {
					//$smarty->assign($params['_assign'],$vars);
					$smarty->assign($params['_assign'],$ret);
			}
			return;
					
		    } 
		    //$smarty->assign($params['_assign'],$vars);
		    $smarty->assign($params['_assign'],$ret);
		    
}
?>
