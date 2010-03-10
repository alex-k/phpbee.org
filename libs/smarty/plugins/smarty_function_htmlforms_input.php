<?
function smarty_function_htmlforms_input($field,$value,$params=array()) {
	$ret=sprintf('<input type="text" name="%s" value="%s" class="%s" %s>', $field,$value,
						isset($params['class'])?$params['class']:'edit',
						isset($params['style'])?'style="'.$params['style'].'"':''
						);
	return $ret;
}
function smarty_function_htmlforms_datetime($field,$value,$params=array()) {
	$ret=sprintf('<input type="text" onfocus="setCal(this.id);" id="%s"name="%s" value="%s" class="%s" %s>',$field,$field,$value,
				isset($params['class'])?$params['class']:'edit',
				isset($params['style'])?'style="'.$params['style'].'"':''
				);
	return $ret;
}
?>
