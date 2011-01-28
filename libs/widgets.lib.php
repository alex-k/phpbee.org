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
		$this->validate_errors=NULL;
		$this->fieldname=$fieldname;
		$this->value=is_string($fieldname) && isset($data[$fieldname]) ? $data[$fieldname] : NULL;
		$this->params=$params;
		$this->record=$record;
		$this->data=$data;
		$this->tpl=gs_tpl::get_instance();
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
class gs_widget_label extends gs_widget{
	function html() {
		return $this->value;
	}
	function clean() {
		return null;
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

class gs_widget_file extends gs_widget{
	function html() {
		return sprintf('<input class="fFile" type="file" name="%s" >', $this->fieldname);
	}
	function clean() {
		if (!$this->value) return $this->value;
		return array(
				$this->fieldname.'_data'=>file_get_contents($this->value['tmp_name']),
				$this->fieldname.'_filename'=>$this->value['name'],
				$this->fieldname.'_mimetype'=>$this->value['type'],
				$this->fieldname.'_size'=>$this->value['size'],
				);
				
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
		$ret="<select class=\"fSelect\" name=\"".$this->fieldname."\">\n";
		foreach ($this->params['options'] as $v) {
			$ret.="<option value=\"$v\" <% if (t.values.".$this->fieldname."==\"$v\") { %> selected=\"selected\" <% } %> >$v</option>\n";
		}

		$ret.="</select>\n";
		return $ret;
	}
	function html() {
		$ret="<select class=\"fSelect\"  name=\"".$this->fieldname."\">\n";
		if (!is_array($this->params['options'])) $this->params['options']=array_combine(explode(',',$this->params['options']),explode(',',$this->params['options']));
		foreach ($this->params['options'] as $v=>$l) {
			$ret.=sprintf("<option value=\"%s\" %s>%s</option>\n", $v, (trim($this->value)==$v) ? 'selected="selected"' : '', $l);
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
class gs_widget_radio extends gs_widget{
	function html() {
		if (!is_array($this->params['options'])) $this->params['options']=array_combine(explode(',',$this->params['options']),explode(',',$this->params['options']));
		foreach ($this->params['options'] as $v=>$l) {
		$s.=sprintf('<label><input class="fRadio" type="radio" name="%s" value="%s" %s> %s </label>', $this->fieldname,$v, trim($this->value)==$v || (isset($this->params['default']) && $v==$this->params['default']) ? 'checked="checked"' : '', $l);
		}
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
			$ret.=sprintf("<option value=\"%d\" %s>%s</option>\n",$v->get_id(), (is_array($this->value) && (in_array($v->get_id(),$this->value) || array_key_exists($v->get_id(),$this->value))) ? 'selected="selected"' : '',trim($v));
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
class gs_widget_form_add extends gs_widget{
	function html() {
		if ($this->value) return $this->form_add_ok($this->value);
		$idname=$this->fieldname.'_'.md5(rand());
		$s=sprintf('<input type="hidden" name="%s" id="%s" value="%s">', $this->fieldname,$idname,$this->value);
		$s.=sprintf('<iframe src="%sform_add/%s/%s" style="width:100%%; border: 0px;"></iframe>', $this->tpl->get_template_vars('www_subdir'), $this->params['options']['recordset'],$idname);
		return $s;
	}
	function clean() {
		return $this->value;
	}
	function form_add_ok($value=false) {
		if ($value) {
			$s=$this->params['options']['recordset'];
			$rec=new $s;
			$rec=$rec->get_by_id($value);
			return trim($rec);
		}
		$data=$this->fieldname;
		$rec=new $data['gspgid_va'][1];
		$rec=$rec->get_by_id($data['gspgid_va'][0]);
		printf("%s<script>window.top.document.getElementById('%s').value=%d;</script>",$rec,$data['gspgid_va'][2],$data['gspgid_va'][0]);
	}
}

class gs_widget_lMany2One extends gs_widget {
	function clean() {
		md('==============');
		$ret=array();
		$rs=new $this->params['options']['recordset'];
		$obj=$rs->new_record();
		$f=gs_base_handler::get_form_for_record($obj,$this->params['gs_form_params'],$this->data,$this->fieldname.":");
		$f_val=$f->validate();
		if (!$f_val['STATUS']) {
			$this->validate_errors=$f_val;
			throw new gs_widget_validate_exception($this->fieldname);
		}
		$ret=$f->clean();
		return $ret;
	}
	function html() {
		$f_arr=array();
		$f_arr[]=array('label'=>'',
				'input'=>$this->record->{$this->fieldname}->html_list(),
				);
		$rs=new $this->params['options']['recordset'];
		$obj=$rs->new_record();
		$f=gs_base_handler::get_form_for_record($obj,$this->params['gs_form_params'],$this->data,$this->fieldname.":");
		$f_arr=array_merge($f_arr,$f->_prepare_inputs());
		return $f_arr;
	}
}



?>
