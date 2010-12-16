<?
class gs_widget_validate_exception extends gs_exception {}
interface gs_widget_interface {
	function __construct($fieldname,$data);
	function html();
	function js();
	function clean();
	function validate();
}
abstract class gs_widget implements gs_widget_interface {
	function __construct($fieldname,$data,$params=array(),$record=NULL) {
		$this->fieldname=$fieldname;
		$this->value=isset($data[$fieldname]) ? $data[$fieldname] : NULL;
		$this->params=$params;
		$this->record=$record;
	}
	function clean() {
		if (!$this->validate()) throw new gs_widget_validate_exception($this->fieldname);
		return $this->value;
	}
	function validate() {
		return true;
	}
	function js() {
		return $this->html();
	}
	function html() {
		return sprintf('<input class="gs_widget" type="text" name="%s" value="%s">', $this->fieldname,trim($this->value));
	}
}
class gs_widget_input extends gs_widget{
	function html() {
		return sprintf('<input class="fString" type="text" name="%s" value="%s">', $this->fieldname,trim($this->value));
	}
}
class gs_widget_password extends gs_widget{
	function html() {
		return sprintf('<input class="fPassword" type="password" name="%s" value="%s">', $this->fieldname,trim($this->value));
	}
}
class gs_widget_hidden extends gs_widget{
	function html() {
		return sprintf('<input class="fHidden" type="hidden" name="%s" value="%s">', $this->fieldname,trim($this->value));
	}
}
class gs_widget_text extends gs_widget{
	function html() {
		return sprintf('<textarea class="fText" name="%s">%s</textarea>', $this->fieldname,trim($this->value));
	}
}
class gs_widget_datetime extends gs_widget{
	function html() {
		return sprintf('<input class="fDateTime" type="text" name="%s" value="%s">', $this->fieldname,trim($this->value));
	}
	function clean() {
		return date('Y-m-d H:i:s',strtotime($this->value));
	}
}

class gs_widget_email extends gs_widget{
	function validate() {
		$v=new gs_validate_isEmail();
		return $v->validate($this->fieldname,$this->value);
	}
}

class gs_widget_select extends gs_widget{
	function js() {
		$ret="<select name=\"".$this->fieldname."\">\n";
		foreach ($this->params['options'] as $v) {
			$ret.="<option value=\"$v\" <% if (t.values.".$this->fieldname."==\"$v\") { %> selected=\"selected\" <% } %> >$v</option>\n";
		}

		$ret.="</select>\n";
		return $ret;
	}
	function html() {
		$ret="<select name=\"".$this->fieldname."\">\n";
		foreach ($this->params['options'] as $v) {
			$ret.=sprintf("<option value=\"%s\" %s>%s</option>\n", $v, (trim($this->value)==$v) ? 'selected="selected"' : '', $v);
		}

		$ret.="</select>\n";
		return $ret;
	}
}
class gs_widget_checkbox extends gs_widget{
	function html() {
		$s=sprintf('<input type="hidden" name="%s" value="0">', $this->fieldname);
		$s.=sprintf('<input class="fCheckbox" type="checkbox" name="%s" value="1" %s>', $this->fieldname,trim($this->value) ? 'checked="checked"' : '');
		return $s;
	}
	function js() {
		$s=sprintf('<input type="hidden" class="fCheckbox" name="%s" value="0">', $this->fieldname);
		$s.="<input type=\"checkbox\" name=\"$this->fieldname\" value=\"1\" <%if(t.values.$this->fieldname == 1) { %> checked=\"checked\"i<% } %> >";
		return $s;
	}
}

class gs_widget_lMany2Many extends gs_widget{
	function js() {
		$ret="<select class=\"lMany2Many\" multiple=\"on\" name=\"".$this->fieldname."[]\">\n";
		$ret.="<% for (vid in t.values.".$this->fieldname.".variants) { %>
			<option value=\"<%=vid%>\" <% if (t.values.".$this->fieldname.".selected[vid]) { %> selected=\"selected\" <% } %>  ><%=t.values.".$this->fieldname.".variants[vid]%></option>
			<% } %>
			";
		$ret.="</select>\n";
		return $ret;
	}
	function html() {
		$rsl=$this->record->init_linked_recordset($this->fieldname);
		$rsname=$rsl->structure['recordsets']['childs']['recordset'];
		$rs=new $rsname();
		$variants=$rs->find_records();


		$ret=sprintf("<select class=\"lMany2Many\" multiple=\"on\" name=\"%s[]\">\n", $this->fieldname);
		foreach ($variants as $v) {
			$ret.=sprintf("<option value=\"%d\" %s>%s</option>\n",$v->get_id(), (is_array($this->value) && in_array($v->get_id(),$this->value)) ? 'selected="selected"' : '',trim($v));
		}
		$ret.="</select>\n";

		return $ret;
	}
	function clean() {
		if (!$this->validate()) throw new gs_widget_validate_exception($this->fieldname);
		$ret=is_array($this->value) && count($this->value)>0 ? array_combine(array_values($this->value),array_values($this->value)) : array();
		if($this->record) {
			$this->record->{$this->fieldname}->flush($ret);
		}
		return $ret;
	}
}
class gs_widget_lOne2One extends gs_widget{
	function js() {
		$ret="<select class=\"lOne2One\" name=\"".$this->fieldname."\">\n";
		$ret.="<% for (vid in t.values.".$this->fieldname.".variants) { %>
			<option value=\"<%=vid%>\" <% if (t.values.".$this->fieldname.".selected == vid) { %> selected=\"selected\" <% } %>  ><%=t.values.".$this->fieldname.".variants[vid]%></option>
			<% } %>
			";
		$ret.="</select>\n";
		return $ret;
	}
	function html() {
		$rsl=$this->record->init_linked_recordset($this->params['linkname']);
		$variants=$rsl->find_records();
		$ret=sprintf("<select  class=\"lOne2One\" name=\"%s\">\n", $this->fieldname);
		if ($this->params['nulloption']) $ret.='<option value=""></option>';
		foreach ($variants as $v) {
			$ret.=sprintf("<option value=\"%d\" %s>%s</option>\n",$v->get_id(), ($this->value==$v->get_id()) ? 'selected="selected"' : '',trim($v));
		}
		$ret.="</select>\n";

		return $ret;
	}
}


/*
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
*/


?>
