<?php

function smarty_function_varname($params, &$smarty) {
	$r=$smarty->getTemplateVars($params['name']);
	return !empty($r) ? $r : $params['value'];
}

?>
