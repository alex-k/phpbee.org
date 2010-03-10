<?php

function smarty_function_assign_htmloptions($params, &$smarty) {
	$obj = new $params['_class'];
	$vv=array();

	if(method_exists($obj,'get_htmloptions')) {
		$vv=$obj->get_htmloptions($params);
	}
	if($params['_type']=='label') $vv=array_combine(array_values($vv),array_values($vv));
	if(isset($params['_empty'])) $vv=array(''=>$params['_empty'])+$vv;
	$smarty->assign($params['name'],$vv);
}

?>
