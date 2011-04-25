<?php
function smarty_function_handler($params, &$smarty) {
	$data=$smarty->getTemplateVars('_gsdata');
	$gsparams=$smarty->getTemplateVars('_gsparams');
	if (is_array($params['_params'])) $params=array_merge($params,$params['_params']);
	$params['gspgid']=trim($params['gspgid'],'/');
	if (!isset($data['gspgid_root'])) {
		$data['gspgid_root']=$data['gspgid'];
	}
	$data['gspgid_handler']=$data['gspgid'];
	$data['gspgid']=$params['gspgid'];
	$data['handler_params']=$params;
	//$data=array_merge($data,$params);

	$smarty->assign('gspgid_form',$data['gspgid']);
	$smarty->assign('gspgid_handler',$data['gspgid_handler']);
	$smarty->assign('gspgid_root',$data['gspgid_root']);
	$smarty->assign('gspgdata_form',$data);
	$smarty->assign('handler_params',$params);
	
	$o_p=new gs_parser($data);
	return $o_p->process();
}
?>
