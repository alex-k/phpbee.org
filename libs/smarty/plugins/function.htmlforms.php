<?php
function smarty_function_htmlforms($params, &$smarty)
{
		    $n=$params['item'];
		    $tpl=gs_tpl::get_instance();
		    $field=$params['field'];
		    
		    $field_name=isset($params['name_format']) ? sprintf($params['name_format'],$field) : $field;
		    
		    $data=$tpl->get_template_vars('_gsdata');
		    $data=gs_cacher::load($data['_gscacheid'],'sendback_html_form') ? gs_cacher::load($data['_gscacheid'],'sendback_html_form')  : $data; 
		    $data=is_array($data) && isset($data['data']) ? $data['data']: new gs_null(GS_NULL_XML);
		    $value=isset($data[$field]) ? $data[$field] : (isset($params['value']) ? $params['value'] : $n[$field] );
		    $structure=$n->get_recordset()->structure['htmlforms'][$field];
		    $options=is_array($n->get_recordset()->structure['fields'][$field]) ? $n->get_recordset()->structure['fields'][$field] : array();
		    if (is_array($n->get_recordset()->structure['htmlforms'][$field])) $options=array_merge($options, $n->get_recordset()->structure['htmlforms'][$field]);


			load_file(cfg('tpl_plugins_dir').'/smarty_function_htmlforms_input.php');
			$fname='smarty_function_htmlforms_'.$structure['type'];
			$ret=function_exists($fname) ? $fname($field_name,$value,$params,$options) : '';
			if (isset($structure['validate']) && !is_array($structure['validate'])) $structure['validate']=array($structure['validate']);

			if(isset($structure['validate'])) {
				foreach($structure['validate'] as $criteria) {
				//$ret.=sprintf("{validate criteria='%s' id='%s' message='*'}",$structure['validate'],$field_name);
				$validate_params=array('criteria'=>$criteria,'id'=>$field_name,'message'=>isset($structure['validate_message'])? $structure['validate_message'] : '*');
				if (isset($structure['validate_params']) && is_array($structure['validate_params'])) $validate_params=array_merge($validate_params,$structure['validate_params']);

				$ret.=smarty_function_validate($validate_params,$smarty);
				}
			}
			return $ret;
}
?>
