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
	function __construct($fieldname,$data,$params=array()) {
		$this->validate_errors=NULL;
		$this->fieldname=$fieldname;
		$this->value=is_string($fieldname) && isset($data[$fieldname]) ? $data[$fieldname] : NULL;
		$this->params=$params;
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
/*class gs_widget_image extends gs_widget{
	function html() {
		return trim($this->value) ? sprintf('<img src=/img/%s>',trim($this->value)) : '';
	}
}*/

class gs_widget_wysiwyg extends gs_widget{
	function html() {
		return sprintf('<textarea class="fWysiwyg" name="%s" _images="lMany2One_%s">%s</textarea>', $this->fieldname,$this->params['images_key'],trim($this->value));
	}
}

class gs_widget_file extends gs_widget{
	function html() {
		return sprintf('<input class="fFile" type="file" name="%s" >', $this->fieldname);
	}
	function clean() {
		if (!isset($this->value['tmp_name'])) return array();
		return array(
				$this->fieldname.'_data'=>file_get_contents($this->value['tmp_name']),
				$this->fieldname.'_filename'=>$this->value['name'],
				$this->fieldname.'_mimetype'=>$this->value['type'],
				$this->fieldname.'_size'=>$this->value['size'],
				//$this->fieldname=>$this->get_id(),
				);
				
	}
}

class gs_widget_image extends gs_widget_file{
	function html() {
		md($this->fieldname,1);
		md($this->data,1);
		//if ($this->value) return sprintf('img src="%s">', $this->value);
			return parent::html();
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
		$ret="<input type=\"hidden\" name=\"".$this->fieldname."\" value=\"0\">";
		$ret.=sprintf("<select class=\"lMany2Many\" multiple=\"on\" name=\"%s[]\">\n", $this->fieldname);
		foreach ($this->params['variants'] as $k=>$v) {
			$ret.=sprintf("<option value=\"%d\" %s>%s</option>\n",$k, (is_array($this->value) && (in_array($k,$this->value) || array_key_exists($k,$this->value))) ? 'selected="selected"' : '',$v);
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
		$ret=sprintf("<select  class=\"lOne2One\" name=\"%s\">\n", $this->fieldname);
		if ($this->params['nulloption']) $ret.='<option value=""></option>';
		foreach ($this->params['variants'] as $k=>$v) {
			$ret.=sprintf("<option value=\"%d\" %s>%s</option>\n",$k, ($this->value==$k) ? 'selected="selected"' : '',$v);
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

/*
* {controller _class=$_gsdata.gspgid_va.0 _params=$params _assign="list"}
* output form: {handler gspgid="$subdir/form/`$_gsdata.gspgid_va.0`" _params=$params}
*/
class gs_widget_lMany2One extends gs_widget {
	function clean() {
		//md($this->data,1);
		return array('fake'=>true);
	}
	function html() {
		$rid_name=$this->params['options']['local_field_name'];
		$rid=isset ($this->data[$rid_name]) ? $this->data[$rid_name] : 0;
		$hash=isset($this->data[$this->params['linkname'].'_hash']) ? $this->data[$this->params['linkname'].'_hash'] : time().rand(10,99);
		$s=sprintf('<a href="/admin/many2one/%s/%s/%d/%s" target="_blank" onclick="window.open(this.href,\'_blank\',\'width=800,height=400,scrollbars=yes, resizable=yes\'); return false;" id="lMany2One_%s">%s</a>',$this->params['options']['recordset'],$this->params['options']['foreign_field_name'],$rid,$hash,$this->params['linkname'],gs_dict::get('LOAD_IMAGES'));
		$s.=sprintf('<input type="hidden" name="%s" value="%s">', $this->params['linkname'].'_hash',$hash);
		return $s;
	}
}
class gs_widget_private extends gs_widget {
	function html() {
		return '';
	}
}



?>
