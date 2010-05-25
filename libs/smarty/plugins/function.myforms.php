<?php

function smarty_function_myforms($params, &$smarty) {
	if (!is_string($params['item']) && ( !is_object($params['item']) || get_class($params['item'])!='gs_record' ) ) return;
	$rs=is_string($params['item']) ? new $params['item'] : $params['item']->get_recordset();

	$obj=is_object($params['item']) && get_class($params['item'])=='gs_record' ? $params['item'] : $rs->new_record();

	$forms=explode(',',$params['forms']);
	$formname=array_shift($forms);


	$smarty->assign('_formname',$formname);
	$smarty->assign('_classname',get_class($rs));
	if (count($forms)) $smarty->assign('_add_forms',count($forms) ? $forms : false);


	$type=isset($params['type']) ? $params['type']: 'all';
	$smarty->assign('_formtype',$type);
	$type.='.';
	$fields=array();
	foreach($rs->structure['myforms'] as $k=>$v) if (strpos($k,$type)===0) $fields[str_replace($type,'',$k)]=$v['fields'];
	$smarty->assign('_fields',$fields);

	$smarty->assign('_item',$obj);

	switch ($params['template']) {
		default:
		case 'table':
			return $smarty->fetch('myforms/table.html');
		break;
	}
}

?>
