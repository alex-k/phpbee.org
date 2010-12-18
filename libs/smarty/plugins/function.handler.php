<?php
function smarty_function_handler($params, &$smarty) {
	/*
	var_dump($smarty->get_template_vars('_gsdata'));
	var_dump($smarty->get_template_vars('_gsparams'));
	var_dump($params);
	*/

	$data=$smarty->get_template_vars('_gsdata');
	/*
	$subdir=$smarty->get_template_vars('_module_subdir');
	if($subdir) $params['gspgid']=$subdir.'/'.$params['gspgid'];
	*/
	$params['gspgid']=trim($params['gspgid'],'/');
	$data['gspgid_handler']=$data['gspgid'];
	$data['gspgid']=$params['gspgid'];
	$data=array_merge($data,$params);
	$smarty->assign('gspgid_form',$data['gspgid']);
	$smarty->assign('gspgdata_form',$data);
	$o_p=new gs_parser($data);
	return $o_p->process();
}
?>
