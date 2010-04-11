<?
function smarty_function_htmlforms_input($field,$value,$params=array(),$datatype=array()) {
	$ret=sprintf('<input type="text" name="%s" value="%s" %s class="%s" %s>', $field,$value,
						isset($datatype['options']) && !($datatype['options']>15)   ? 'size="'.$datatype['options'].'" maxlength="'.$datatype['options'].'"':'',
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
