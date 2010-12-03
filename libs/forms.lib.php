<?php
interface g_forms_interface {
	function __construct($rs);
	function as_table();
	function as_list();
	function as_labels();
	function clean();
}

abstract class g_forms implements g_forms_interface{
	function checkbox($field,$value,$params=array(),$datatype=array()) {
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
	function show($field,$value,$params=array(),$datatype=array()) {
		$ret=sprintf('%s', $value);
		return $ret;
	}
	function hidden($field,$value,$params=array(),$datatype=array()) {
		$ret=sprintf('<input type="hidden" name="%s" value="%s">', $field,$value);
		return $ret;
	}
	function input($field,$value,$params=array(),$datatype=array()) {
		$ret=sprintf('<input type="%s" name="%s" value="%s" %s class="%s" %s>', 
							isset($datatype['input_type']) ? $datatype['input_type'] : 'text', 
							$field,$value,
							isset($datatype['options']) && !($datatype['options']>15)   ? 'size="'.$datatype['options'].'" maxlength="'.$datatype['options'].'"':'',
							isset($params['class'])?$params['class']:'edit',
							isset($params['style'])?'style="'.$params['style'].'"':''
							);
		return $ret;
	}
	function textarea($field,$value,$params=array(),$datatype=array()) {
		$ret=sprintf('<textarea name="%s" %s class="%s" %s>%s</textarea>', $field,
							isset($datatype['options']) && !($datatype['options']>15)   ? 'size="'.$datatype['options'].'" maxlength="'.$datatype['options'].'"':'',
							isset($params['class'])?$params['class']:'edit',
							isset($params['style'])?'style="'.$params['style'].'"':''
							,$value);
		return $ret;
	}
	function select($field,$value,$params=array(),$structure=array()) {
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
	function radio($field,$value,$params=array(),$structure=array()) {
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
	function datetime($field,$value,$params=array()) {
		$ret=sprintf('<input type="text" onfocus="setCal(this.id);" id="%s"name="%s" value="%s" class="%s" %s>',$field,$field,$value,
					isset($params['class'])?$params['class']:'edit',
					isset($params['style'])?'style="'.$params['style'].'"':''
					);
		return $ret;
	}
	function image($field,$value,$params=array(),$datatype=array()) {
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
	function __construct($h,$data=array()) {
		$this->record=NULL;
		$this->params=NULL;
		$this->clean_data=array();
		if (is_object($h) && get_class($h)=='gs_record') {
			$this->record=$h;
			$rs=$this->record->get_recordset();
			$h=$rs->structure['htmlforms'];
		}
		foreach ($h as $k=>$ih) {
			if(isset($ih['hidden']) && $ih['hidden']) unset($h[$k]);
		}
		$this->data=$data;
		$this->htmlforms=$h;
	}
	function clean($name=null) {
		return $name ? $this->clean_data[$name] : $this->clean_data;
	}
	protected function error(&$ret, $k,$m) {
		$ret['STATUS']=false;
		$ret['ERRORS'][]=array('FIELD'=>$k,'ERROR'=>$m);
		$ret['FIELDS'][$k][]=$m;
	}

	function validate() {
		$this->clean_data=array();
		$ret=array(
			'STATUS'=>true,
			'ERRORS'=>array(),
			'FIELDS'=>array(),
			);
		foreach ($this->htmlforms as $k=>$h) {
			$wclass="gs_widget_".$h['type'];
			$w =new $wclass($k,$this->data,$this->params,$this->record);
			try {
				$value=$w->clean();
				$this->clean_data[$k]=$value;
			} catch (gs_widget_validate_exception $e) {
				$this->error($ret, $k,$e->getMessage());
			}
			if (!isset($h['validate'])) $h['validate']='notEmpty';
			$validate=is_array($h['validate']) ? $h['validate'] : array($h['validate']);
			foreach ($validate as $v) {
				$vname='gs_validate_'.$v;
				$val=new $vname();
				if (!$val->validate($k,$value,$this->data,isset($h['validate_params'])?$h['validate_params'] : array() ,$this->record)) {
					$this->error($ret, $k,$vname);
				}

			}
		}
		return $ret;

	}
}

class g_forms_html extends g_forms {
	function _prepare_inputs(){
		$arr=array();
		foreach($this->htmlforms as $field => $v) {
			$wclass="gs_widget_".$v['type'];
			$w =new $wclass($field,$this->data,$v,$this->record);
			$arr[$field]=array('label'=>isset($v['verbose_name']) ? $v['verbose_name']:$field,
						'input'=>$w->html()
						);
		}
		return $arr;
	}
	function as_table($delimiter="\n"){
		$arr=array();
		$inputs=$this->_prepare_inputs();
		foreach($inputs as $field=>$v) 
			$arr[]=sprintf('<tr><td><label for="%s">%s</label></td><td>%s</td></tr>',$field, $v['label'],$v['input']);

		return implode($delimiter,$arr);
	}
	function as_list(){}
	function as_labels($delimiter="<br/>\n"){
		$arr=array();
		$inputs=$this->_prepare_inputs();
		foreach($inputs as $field=>$v) 
			$arr[]=sprintf('<label>%s: %s</label>', $v['label'],$v['input']);

		return implode($delimiter,$arr);
	}

}
class g_forms_jstpl extends g_forms_html {
	function _prepare_inputs(){
		$arr=array();
		foreach($this->htmlforms as $field => $v) {
			$wclass="gs_widget_".$v['type'];
			$w =new $wclass($field,array($field=>"<%=t.values.$field%>"),$v,$this->record);
			$arr[$field]=array('label'=>isset($v['verbose_name']) ? $v['verbose_name']:$field,
						//'input'=>$w->html($field,"<%=t.values.$field%>",$v)
						'input'=>$w->js()
						);
		}
		return $arr;
	}
}

?>
