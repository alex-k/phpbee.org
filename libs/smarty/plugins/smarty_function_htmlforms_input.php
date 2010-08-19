<?
function smarty_function_htmlforms_checkbox($field,$value,$params=array(),$datatype=array()) {
	$ret=sprintf('<input type="hidden" name="%s" id="%s" value="%s"><input onChange="this.previousSibling.value =this.checked ? 1 : 0" type="checkbox"  value="1" %s %s class="%s" %s>', 
						$field,$field,
						$value ? 1 : 0 ,
						$value ? 'CHECKED="CHECKED"' : '' ,
						isset($datatype['options']) && !($datatype['options']>15)   ? 'size="'.$datatype['options'].'" maxlength="'.$datatype['options'].'"':'',
						isset($params['class'])?$params['class']:'edit',
						isset($params['style'])?'style="'.$params['style'].'"':''
						);
	return $ret;
}
function smarty_function_htmlforms_show($field,$value,$params=array(),$datatype=array()) {
	$ret=sprintf('%s', $value);
	return $ret;
}
function smarty_function_htmlforms_hidden($field,$value,$params=array(),$datatype=array()) {
	$ret=sprintf('<input type="hidden" name="%s" value="%s">', $field,$value);
	return $ret;
}
function smarty_function_htmlforms_input($field,$value,$params=array(),$datatype=array()) {
	$ret=sprintf('<input type="text" name="%s" value="%s" %s class="%s" %s>', $field,$value,
						isset($datatype['options']) && !($datatype['options']>15)   ? 'size="'.$datatype['options'].'" maxlength="'.$datatype['options'].'"':'',
						isset($params['class'])?$params['class']:'edit',
						isset($params['style'])?'style="'.$params['style'].'"':''
						);
	return $ret;
}
function smarty_function_htmlforms_textarea($field,$value,$params=array(),$datatype=array()) {
	$ret=sprintf('<textarea name="%s" %s class="%s" %s>%s</textarea>', $field,
						isset($datatype['options']) && !($datatype['options']>15)   ? 'size="'.$datatype['options'].'" maxlength="'.$datatype['options'].'"':'',
						isset($params['class'])?$params['class']:'edit',
						isset($params['style'])?'style="'.$params['style'].'"':''
						,$value);
	return $ret;
}
function smarty_function_htmlforms_select($field,$value,$params=array(),$structure=array()) {
	$options=array();
	$options[]='<option value="0"></option>';
	if(is_array($structure['options'])) foreach ($structure['options'] as $k=>$v) {
		$options[]=sprintf('<option value="%s" %s>%s</option>',
					$k,
					$k==$value ? 'selected' : '',
					$v
				    );
	}
	//$ret=sprintf('<input type="hidden" name="%s" id="%s" value="%s"><input onChange="this.previousSibling.value =this.checked ? 1 : 0" type="checkbox"  value="1" %s %s class="%s" %s>', 
	$ret=sprintf('<input type="hidden" name="%s" id="%s" value="%s"><select id="%s" onChange="this.previousSibling.value =this.value ? this.value : \'\';" class="%s" %s>%s</select>',
				$field,$field,$value,$field,
				isset($params['class'])?$params['class']:'edit',
				isset($params['style'])?'style="'.$params['style'].'"':'',
				implode("\n",$options)
				);
	return $ret;
}
function smarty_function_htmlforms_radio($field,$value,$params=array(),$structure=array()) {
	$options=array();
	if(is_array($structure['options'])) foreach ($structure['options'] as $k=>$v) {
		$ret.=sprintf('<input type="radio" value="%s" %s name="%s" id="%s" %s %s>%s<br>',
					$k,
					$k==$value ? 'checked' : '',
					$field,$field,
					isset($params['class'])?$params['class']:'edit',
					isset($params['style'])?'style="'.$params['style'].'"':'',
					$v
				    );
	}
	return $ret;
}
function smarty_function_htmlforms_datetime($field,$value,$params=array()) {
	$ret=sprintf('<input type="text" onfocus="setCal(this.id);" id="%s"name="%s" value="%s" class="%s" %s>',$field,$field,$value,
				isset($params['class'])?$params['class']:'edit',
				isset($params['style'])?'style="'.$params['style'].'"':''
				);
	return $ret;
}
function smarty_function_htmlforms_image($field,$value,$params=array(),$datatype=array()) {
	$ret='';
	if(is_numeric($value)) $ret.=sprintf("<img src='/img/%d/110.jpg'><br>\n",$value);
	$ret.=sprintf('<input type="file" name="%s"  %s class="%s" %s>', $field,
						isset($datatype['options']) && !($datatype['options']>15)   ? 'size="'.$datatype['options'].'" maxlength="'.$datatype['options'].'"':'',
						isset($params['class'])?$params['class']:'edit',
						isset($params['style'])?'style="'.$params['style'].'"':'',
						$field
						);
	return $ret;
}
?>
