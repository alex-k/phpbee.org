<?
function smarty_function_htmlforms_input($params,$structure,$n) {
	$ret=sprintf('<input type="text" class="edit" name="%s" value="%s">',$params['field'],isset($params['value']) ? $params['value'] :$n[$field]);
	return $ret;
}
?>
