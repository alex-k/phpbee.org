<?php

function smarty_function_assign_array($params, &$smarty) {
	$v=array_values(preg_split('/(, |,)/',$params['value']));
	if (is_numeric($params['max'])) foreach ($v as $k=>$val) {
		if (is_numeric($val) && $val>$params['max']) unset($v[$k]);
	}
	$vv=array_combine($v,$v);
	if (isset($vv['All'])) {
		unset($vv['All']);
		$vv=array(''=>'All')+$vv;
	}
	$smarty->assign($params['name'],$vv);
}

?>
