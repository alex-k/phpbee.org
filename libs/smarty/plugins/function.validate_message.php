<?php
function smarty_function_validate_message($params, &$smarty) {
	$validate=$smarty->getTemplateVars('validate');
	return $validate['MESSAGES'][$params['id']] ? '<span class="error_message">'.$validate['MESSAGES'][$params['id']].'</span>' : '' ;

}

?>
