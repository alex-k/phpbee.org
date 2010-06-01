<?php
function smarty_function_htmlview($params, &$smarty)
{
	$ret='';
	$n=$params['item'];
	$tpl=gs_tpl::get_instance();
	$field=$params['field'];

	$field_name=isset($params['name_format']) ? sprintf($params['name_format'],$field) : $field;

	$value=isset($data[$field]) ? $data[$field] : (isset($params['value']) ? $params['value'] : $n[$field] );

	$structure=$n->get_recordset()->structure['htmlforms'][$field];
	$options=$n->get_recordset()->structure['fields'][$field];
	switch ($structure['type']) {
		case 'image':
			$ret=$n->$field->first() ? sprintf("<img src='/img/%s/small.jpg'>",$n->$field->first()->get_id()) : '';
			break;
		default:
			$ret=$n->$field;
			break;
	}
	return $ret;
}
?>
