<?php
function smarty_function_gl($params, $template) {
	$p=$template->getTemplateVars('_gsparams');
	$d=$template->getTemplateVars('_gsdata');
	if (isset($params['module'])) $p['module_name']='module_'.$params['module'];
	if (isset($params['module_name'])) $p['module_name']=$params['module_name'];

		$up= gs_var_storage::load('urlprefix');
	    return cfg('www_root').'/'.$up.ltrim(call_user_func($p['module_name'].'::gl',key($params),current($params),$d['gspgid']),'/');
}

?>
