<?php
function smarty_function_htmlforms($params, &$smarty)
{
		    $n=$params['item'];
		    $tpl=gs_tpl::get_instance();
		    $field=$params['field'];
		    
		    $field_name=isset($params['name_format']) ? sprintf($params['name_format'],$field) : $field;
		    
		    $data=$tpl->get_template_vars('_gsdata');
		    $data=gs_cacher::load($data['_gscacheid'],'sendback_html_form'); $data=is_array($data) && isset($data['data']) ? $data['data']: new gs_null(GS_NULL_XML);
		    $value=isset($data[$field]) ? $data[$field] : (isset($params['value']) ? $params['value'] : $n[$field] );
		    
		    $structure=$n->get_recordset()->structure['htmlforms'][$field];
		    $options=$n->get_recordset()->structure['fields'][$field];

			load_file(cfg('tpl_plugins_dir').'/smarty_function_htmlforms_input.php');
			$fname='smarty_function_htmlforms_'.$structure['type'];
			return $fname($field_name,$value,$params,$options);
}
?>
