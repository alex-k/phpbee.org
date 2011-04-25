<?php
function smarty_function_htmlview($params, &$smarty)
{
		    $n=$params['item'];
		    $tpl=gs_tpl::get_instance();
		    $field=$params['field'];
		    
		    $field_name=isset($params['name_format']) ? sprintf($params['name_format'],$field) : $field;
		    
		    $structure=$n->get_recordset()->structure['htmlforms'][$field];
		    $options=$n->get_recordset()->structure['fields'][$field];

			
		    switch ($structure['type']) {
			    case 'image':
			    	$ret=sprintf("<img src='/img/%d/small.jpg'>",$n->$field_name->first()->get_id());
				break;
			    case 'checkbox':
			    	$ret=sprintf("<input type='checkbox' readonly='readonly' disabled='disabled' %s>",$n->$field_name?'checked':'');
				break;
			     default:
			     	$ret=$n->$field_name;
		    }
		    return $ret;
}
?>
