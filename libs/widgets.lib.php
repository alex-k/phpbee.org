<?php

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
				return sprintf('<input class="gs_widget" type="text" name="%s" value="%s"%s>', $this->fieldname,trim($this->value),(isset($this->params['readonly']) && $this->params['readonly']) ? 'disabled="disabled"' : '');
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
				return sprintf('<input class="fString" type="text" name="%s" value="%s">', $this->fieldname,htmlspecialchars(trim($this->value)));
		}
}

class gs_widget_int extends gs_widget{
		function html() {
				return sprintf('<input class="fInt" type="text" name="%s" value="%d">', $this->fieldname,trim($this->value));
		}
}

class gs_widget_password2 extends gs_widget{
	    function html() {
		            $f2=$this->fieldname.'_repeat';
			            $v2=isset($this->data[$f2]) ? $this->data[$f2] : '';
				            return sprintf('<input class="fPassword" type="password" name="%s" value="%s"%s></td><td class="helper_tr_error"></td></tr><tr class="helper_tr"><td class="helper_tr_title">%s</td><td class="helper_tr_field"><input class="fPassword" type="password" name="%s_repeat" value="%s"%s>', $this->fieldname,trim($this->value),$this->params['readonly'] ? 'disabled="disabled"' : '',gs_dict::get('REPEAT_PASSWORD'),$this->fieldname,($this->value==$v2) ? $this->value : '',$this->params['readonly'] ? 'disabled="disabled"' : '');
					        }
						    
						        function validate() {
								        $f1=$this->fieldname;
									        $f2=$this->fieldname.'_repeat';
										        return (isset($this->data[$f2]) && $this->data[$f1]==$this->data[$f2]);
											    }
}


class gs_widget_password extends gs_widget{
		function html() {
				return sprintf('<input class="fPassword" type="password" name="%s" value="%s">', $this->fieldname,htmlspecialchars(trim($this->value)));
		}
}
class gs_widget_hidden extends gs_widget{
		function html() {
				return sprintf('<input class="fHidden" type="hidden" name="%s" value="%s">', $this->fieldname,htmlspecialchars(trim($this->value)));
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

		function clean() {
				parent::clean();
				//$result=iconv('CP1251','UTF-8',$result);
				if (function_exists ('tidy_parse_string')) {
						$config = array('indent' => TRUE,
								'show-body-only' => TRUE,
								'output-xhtml' => TRUE,
								);
						$tidy = tidy_parse_string($this->value, $config, 'UTF8');
						$tidy->cleanRepair();
						$this->value=trim($tidy);
				}
				return $this->value;
		}

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
				
				$ret=array(
					$this->fieldname.'_data'=>file_get_contents($this->value['tmp_name']),
					$this->fieldname.'_filename'=>$this->value['name'],
					$this->fieldname.'_mimetype'=>$this->value['type'],
					$this->fieldname.'_size'=>$this->value['size'],
					//$this->fieldname=>$this->get_id(),
				);
				if (stripos($this->value['type'],'image')===0) {
					list($ret[$this->fieldname.'_width'],$ret[$this->fieldname.'_height'])=getimagesize($this->value['tmp_name']);
				}
			return $ret;
		}
}

class gs_widget_coords extends gs_widget{
		function html() {
			return sprintf('<input type="text" class="coords" name="%s[X]" id="coord_x" value="%d">:<input type="text" class="coords" name="%s[Y]" id="coord_y" value="%d"><br><div id="coords_map" class="main29_map"></div>', $this->fieldname,$this->data[$this->fieldname.'_x'], $this->fieldname,$this->data[$this->fieldname.'_y']);
		}
		function clean() {
			parent::clean();
			return array(
				$this->fieldname.'_x'=>$this->value['X'],
				$this->fieldname.'_y'=>$this->value['Y'],
				);
		}
		
		function validate() {
			return (!empty($this->value['X']) && !empty($this->value['Y']));
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
				return sprintf('<input class="fDateTime" type="text" name="%s" value="%s">', $this->fieldname,htmlspecialchars(trim($this->value)));
		}
		function clean() {
				return date('Y-m-d H:i:s',strtotime(!empty($this->value) ? $this->value : 'now' ));
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
						$ret.=sprintf("<option value=\"%s\" %s>%s</option>\n", htmlspecialchars($v), (trim($this->value)==$v) ? 'selected="selected"' : '', $l);
				}

				$ret.="</select>\n";
				return $ret;
		}
}
class gs_widget_select_enter extends gs_widget{
		function html() {
				$ret="<select onChange=\"$('input[selname=".$this->fieldname."]').val('');\" class=\"fSelect\"  name=\"".$this->fieldname."[select]\"><option></option>\n";
				if (!is_array($this->params['options'])) $this->params['options']=array_combine(explode(',',$this->params['options']),explode(',',$this->params['options']));
				foreach ($this->params['options'] as $v=>$l) {
						$ret.=sprintf("<option value=\"%s\" %s>%s</option>\n",
									htmlspecialchars($v),
									(empty($this->value['enter']) && trim($this->value['select'])==$v) ? 'selected="selected"' : '',
									$l);
				}
				$ret.="</select>\n";
				$ret.=sprintf("</label><label><input class=\"fSelect\"  name=\"%s[enter]\" selname=\"%s\" value=\"%s\">",
								$this->fieldname,
								$this->fieldname,
								trim($this->value['enter'])
								);
				return $ret;
		}
		function clean() {
			$ret=isset($this->value['enter']) && !empty($this->value['enter']) ? $this->value['enter'] : $this->value['select'];
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
				$s.=sprintf('<label><input class="fRadio" type="radio" name="%s" value="%s" %s> %s </label>', $this->fieldname,htmlspecialchars($v), trim($this->value)==$v || (isset($this->params['default']) && $v==$this->params['default']) ? 'checked="checked"' : '', $l);
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
		$e_data=gs_base_handler::explode_data($this->data);
		if (isset($e_data[$this->fieldname]) && is_array($e_data[$this->fieldname]) && !empty($e_data[$this->fieldname])) {
			//$this->value=array_combine(array_values($e_data[$this->fieldname]),array_values($e_data[$this->fieldname]));
			$this->value=array_combine(array_keys($e_data[$this->fieldname]),array_keys($e_data[$this->fieldname]));
		}
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
		$s.=sprintf('<iframe src="%sform_add/%s/%s" style="width:100%%; border: 0px;"></iframe>', $this->tpl->getTemplateVars('www_subdir'), $this->params['options']['recordset'],$idname);
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
		$e_data=gs_base_handler::explode_data($this->data);
		$rs=new $this->params['options']['recordset'];
		$options=array($rs->id_field_name=>isset($e_data[$this->fieldname]) ? array_keys($e_data[$this->fieldname]) : array());
		$links=$rs->find_records($options);
		$rid_name=$this->params['options']['local_field_name'];
		$rid=isset ($this->data[$rid_name]) ? $this->data[$rid_name] : 0;
		$hash=isset($this->data[$this->params['linkname'].'_hash']) ? $this->data[$this->params['linkname'].'_hash'] : time().rand(10,99);
		$s=sprintf('<a href="/admin/many2one/%s/%s/%d/%s" target="_blank" onclick="window.open(this.href,\'_blank\',\'width=800,height=400,scrollbars=yes, resizable=yes\'); return false;" id="lMany2One_%s">%s</a>',
		$this->params['options']['recordset'],
		$this->params['options']['foreign_field_name'],
		$rid,
		$hash,
		$this->params['linkname'],
		gs_dict::get('LOAD_RECORDS'));
		$s.=sprintf('<input type="hidden" name="%s" value="%s">', $this->params['linkname'].'_hash',$hash);
		return $s;
	}
}

class gs_widget_gallery extends gs_widget {
		function clean() {
				//md($this->data,1);
				return array('fake'=>true);
		}
		function html() {
				$rid_name=$this->params['options']['local_field_name'];
				$rid=isset ($this->data[$rid_name]) ? $this->data[$rid_name] : 0;
				$r=new $this->params['options']['recordset'];
				$find=array();
				$find[$this->params['options']['foreign_field_name']]=$rid;
				$hash=isset($this->data[$this->params['linkname'].'_hash']) ? $this->data[$this->params['linkname'].'_hash'] : time().rand(10,99);
				$images=$r->find_records($find);
				$images=$images->get_values();

				$s='<div class="many2one_gallery" id="gallery_'.$hash.'">';
						if (count($images)) {foreach ($images as $im) {
								$s.=sprintf('<img src="/img/h/%s/100/%d.jpg" title="%s">',$this->params['options']['recordset'],$im['id'],$im['name']);
						}}
				$s.='<div class="clear"></div></div>';



				$s.=sprintf('<a href="/admin/many2one/%s/%s/%d/%s/as_gallery" target="_blank" onclick="window.open(this.href,\'_blank\',\'width=800,height=400,scrollbars=yes, resizable=yes\'); return false;" id="lMany2One_%s">%s</a>',$this->params['options']['recordset'],$this->params['options']['foreign_field_name'],$rid,$hash,$this->params['linkname'],gs_dict::get('LOAD_RECORDS'));
				$s.=sprintf('<input type="hidden" name="%s" value="%s">', $this->params['linkname'].'_hash',$hash);
				return $s;
		}
}

class gs_widget_parent_list extends gs_widget_lOne2One{}

class gs_data_widget_parent_list {
	function gd($rec,$k,$hh,$params,$data) {
		$v=$hh[$k];
		if (method_exists($rec->get_recordset(),'form_variants_'.$v['linkname'])) {
			$vrecs=call_user_func(array($rec->get_recordset(),'form_variants_'.$v['linkname']),$rec,$data);
		} else {
			$rname=get_class($rec->init_linked_recordset($v['linkname']));
			$vro=new $rname;
			$vrecs=$vro->find_records();
		}
		$variants=array();
		foreach ($vrecs as $vrec) $variants[$vrec->get_id()]=trim($vrec);
		$hh[$k]['variants']=$variants;
		return $hh;
	}
}
class gs_data_widget_include_form {
	function gd($rec,$k,$hh,$params,$data) {
		if ($hh[$k]['type']=='lOne2One') {
			$l_k=$hh[$k]['linkname'];
			$nrs=$rec->$l_k;
		} else {
			$l_k=$k;
			$nrs=$rec->$k;
		}

		$nrs->first(true);

		foreach($nrs as $nobj) {
			$f=gs_base_handler::get_form_for_record($nobj,$params,$data);
			$forms=$f->htmlforms;
			$i=intval($nobj->get_id());
			foreach($forms as $fk=>$fv) {
				$key="$l_k:$fk";
				$pfx_key=$hh[$k]['type']=='lOne2One' ? $key : "$l_k:$i:$fk";

				$hh[$pfx_key]=$fv;
				if(isset($data['handler_params'][$key])) {
					$data['handler_params'][$pfx_key]=$data['handler_params'][$key];
				}
				
			}
		}
		unset($hh[$k]);
		return $hh;
	}
}

class gs_widget_window_form extends gs_widget {
		function clean() {
				return array('fake'=>true);
		}
		function html() {
				$rid_name=$this->params['options']['local_field_name'];
				$rid=isset ($this->data[$rid_name]) ? $this->data[$rid_name] : 0;
				$r=new $this->params['options']['recordset'];
				$find=array();
				$find[$this->params['options']['foreign_field_name']]=$rid;
				$hash=isset($this->data[$this->params['linkname'].'_hash']) ? $this->data[$this->params['linkname'].'_hash'] : time().rand(10,99);
				$images=$r->find_records($find);
				$images=$images->get_values();


				$s='';
				$s.=sprintf('<a href="/admin/window_form/%s/%s/%d/%s" target="_blank" onclick="window.open(this.href,\'_blank\',\'width=800,height=400,scrollbars=yes, resizable=yes\'); return false;" id="lMany2One_%s">%s</a>',$this->params['options']['recordset'],$this->params['options']['foreign_field_name'],$rid,$hash,$this->params['linkname'],gs_dict::get('LOAD_RECORDS'));
				$s.=sprintf('<input type="hidden" name="%s" value="%s">', $this->params['linkname'].'_hash',$hash);
				return $s;
		}
}

class gs_widget_iframe_gallery extends gs_widget {
		function clean() {
				return array('fake'=>true);
		}
		function html() {
				$rid_name=$this->params['options']['local_field_name'];
				$rid=isset ($this->data[$rid_name]) ? $this->data[$rid_name] : 0;
				$hash=isset($this->data[$this->params['linkname'].'_hash']) ? $this->data[$this->params['linkname'].'_hash'] : time().rand(10,99);
				$s='<div class="many2one_gallery" id="gallery_'.$hash.'">';
				if ($rid>0) {
						$r=new $this->params['options']['recordset'];
						$find=array();
						$find[$this->params['options']['foreign_field_name']]=$rid;
						$images=$r->find_records($find);
						$s.=(string)$images;
					   /* 
				$images=$images->get_values();
								if (count($images)) {foreach ($images as $im) {
										$s.=sprintf('<img src="/img/h/%s/100/%d.jpg" title="%s">',$this->params['options']['recordset'],$im['id'],$im['name']);
								}}
			*/
				}
				$s.='<div class="clear"></div></div>';
				
				$s.=sprintf('<a href="/admin/news/iframe_gallery/%s/%s/%d/%s" target="gal_%s" id="lMany2One_%s">%s</a>',
						$this->params['options']['recordset'],
						$this->params['options']['foreign_field_name'],
						$rid,
						$hash,
						$hash,
						$this->params['linkname'],
						$this->params['linkname'],
						gs_dict::get('GALLERY_MANAGE_RECORDS'));
				$s.='<iframe name="gal_'.$hash.'" class="gallery_ifr" id="gal_'.$hash.'" frameBorder="0"></iframe>';
				$s.=sprintf('<input type="hidden" name="%s" value="%s">', $this->params['linkname'].'_hash',$hash);
				return $s;
		}
}
class gs_widget_alex_gal extends gs_widget_iframe_gallery {}


class gs_widget_private extends gs_widget {
		function html() {
				return '';
		}
}


class gs_widget_lMany2Many_checkboxes extends gs_widget {
	function html() {
		$e_data=gs_base_handler::explode_data($this->data);
		if (isset($e_data[$this->fieldname]) && is_array($e_data[$this->fieldname]) && !empty($e_data[$this->fieldname])) {
			$this->value=array_combine(array_keys($e_data[$this->fieldname]),array_keys($e_data[$this->fieldname]));
		}
		$ret="<input type=\"hidden\" name=\"".$this->fieldname."\" value=\"0\">";
		$ret.="<span>";
		foreach ($this->params['variants'] as $k=>$v) {
			$ret.=sprintf("<label class=\"lMany2Many_checkbox\"><input type=\"checkbox\" name=\"%s[]\" value=\"%d\" %s>%s</label>\n",$this->fieldname,$k, (is_array($this->value) && (in_array($k,$this->value) || array_key_exists($k,$this->value))) ? 'checked="checked"' : '',$v);
		}
		$ret.="</span>";
		return $ret;
	}
	function clean() {
		if (!$this->validate()) throw new gs_widget_validate_exception($this->fieldname);
		$ret=is_array($this->value) && count($this->value)>0 ? array_combine(array_values($this->value),array_values($this->value)) : array();
		return $ret;
	}
}
class gs_widget_lMany2Many_checkboxes_all extends gs_widget_lMany2Many_checkboxes {
	function html() { 
		$ret=parent::html();
		$ret.="<br><label><input type=\"checkbox\" onChange=\"$('.lMany2Many_checkbox :checkbox').attr('checked',this.checked);\">all</label>\n";
		return $ret;
	}

}


?>
