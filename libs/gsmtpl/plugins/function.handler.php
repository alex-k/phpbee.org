<?php
function smarty_function_handler($params, &$smarty) {
	$data=$smarty->getTemplateVars('_gsdata');
	//$gsparams=$smarty->getTemplateVars('_gsparams');
	if (isset($params['_params']) && is_array($params['_params'])) $params=array_merge($params,$params['_params']);
	$params['gspgid']=trim($params['gspgid'],'/');
	mlog($params['gspgid']);
	//mlog($params);
	if (!isset($data['gspgid_root'])) {
		$data['gspgid_root']=$data['gspgid'];
	}
	$data['gspgid_handler']=$data['gspgid'];
	$data['gspgid']=$params['gspgid'];
	$data['handler_params']=$params;
	$data['foo']='bar';

	//var_dump($data);

	$tpl=gs_tpl::get_instance();

	if (isset($params['_record'])) {
		$tpl->assign('_record',$params['_record']);
	}
	$assign=array();
	$assign['gspgdata_form']=$data;
	$assign['gspgid_form']=$data['gspgid'];
	$assign['gspgid_handler']=$data['gspgid_handler'];
	$assign['gspgid_root']=$data['gspgid_root'];
	$assign['handler_params']=$params;

	$tpl->assign($assign);

	$o_p=gs_parser::get_instance($data);
	if (isset($params['scope'])) {
		$hndl=$o_p->get_current_handler();
		if ($hndl[0]['params']['module_name']!=$params['scope']) return '';
	}
	return $o_p->process();
}
?>
