<?php
function smarty_function_handler($params, &$smarty) {
	/*
	var_dump($smarty->get_template_vars('_gsdata'));
	var_dump($smarty->get_template_vars('_gsparams'));
	var_dump($params);
	*/

	$data=$smarty->get_template_vars('_gsdata');
	//$data['gspgid']=$params['url'];
	$data['gspgid_handler']=$data['gspgid'];
	$data=array_merge($data,$params);
	$smarty->assign('gspgid_form',$data['gspgid']);
	$smarty->assign('gspgdata_form',$data);
	$o_p=new gs_parser($data);
	return $o_p->process();
}
?>
