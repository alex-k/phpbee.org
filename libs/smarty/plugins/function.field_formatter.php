<?php
function smarty_function_field_formatter($params, $template) {
	switch ($params['type']) {
		case 'fCheckbox':
			$str=$params['value'] ? '/i/admin/ico_on.gif' : '/i/admin/ico_off.gif';
			echo '<img src="'.$str.'">';
		break;
		default:
			echo $params['value'];
		break;
	}
	
	return '';
}

?>
