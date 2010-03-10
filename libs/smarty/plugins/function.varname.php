<?php

function smarty_function_varname($params, &$smarty) {
	$r=$smarty->get_template_vars($params['name']);
	return !empty($r) ? $r : $params['value'];
}

?>
