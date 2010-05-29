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
		    switch ($structure['type']) {
			    case 'input':
			    	load_file(cfg('tpl_plugins_dir').'/smarty_function_htmlforms_input.php');
				$ret=smarty_function_htmlforms_input($field_name,$value,$params,$options);
				break;
			    case 'datetime':
			    	load_file(cfg('tpl_plugins_dir').'/smarty_function_htmlforms_input.php');
				$ret=smarty_function_htmlforms_datetime($field_name,$value,$params);
				break;
			    case 'select':
			    	load_file(cfg('tpl_plugins_dir').'/smarty_function_htmlforms_input.php');
				$ret=smarty_function_htmlforms_select($field_name,$value,$params,$structure);
				break;
			    case 'textarea':
			    	load_file(cfg('tpl_plugins_dir').'/smarty_function_htmlforms_input.php');
				$ret=smarty_function_htmlforms_textarea($field_name,$value,$params,$structure);
				break;
			    case 'image':
			    	load_file(cfg('tpl_plugins_dir').'/smarty_function_htmlforms_input.php');
				$ret=smarty_function_htmlforms_image($field_name,$value,$params,$structure);
				break;
			    case 'show':
			    	load_file(cfg('tpl_plugins_dir').'/smarty_function_htmlforms_input.php');
				$ret=smarty_function_htmlforms_show($field_name,$value,$params,$structure);
				break;
			    default:
				return;
		    }
		    return($ret);
}
?>
