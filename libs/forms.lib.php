<?php
interface g_forms_interface {
	function __construct($rs);
	function as_table();
	function as_list();
	function as_labels();
	function clean();
}

abstract class g_forms implements g_forms_interface{
	function __construct($h,$params=array(),$data=array()) {
		if (!is_array($data)) $data=array();
		$this->params=$params;
		$this->clean_data=array();
		$form_default=array();
		foreach ($h as $k=>$ih) {
			if(isset($ih['hidden']) && $ih['hidden']) {
				unset($h[$k]);
			}
			if(isset($ih['default'])) $form_default[$k]=$ih['default'];
		}
		if (count($form_default)>0) $data=array_merge($form_default,$data);
		$this->data=$data;
		$this->htmlforms=$h;
		$this->view=array('type'=>'helper','class'=>'dl','elements'=>array(
							array('type'=>'helper','class'=>'dt','elements'=>$h),
					)
			);
	}
	function show($validate=array(),$view=NULL) {
		$delimiter="\n";
		$arr=array();
		$inputs=$this->_prepare_inputs();
		if($view===NULL) $view=$this->view;
		$hclass='helper_'.$view['class'];
		$helper=new $hclass();
		foreach($view['elements'] as $field=>$v)  {
			$value=$inputs[$field];
			if ($v['type']=='helper') $value['input']=$this->show($validate,$v);
			if ($this->htmlforms[$field]['type']=='private') {
			} else if ($this->htmlforms[$field]['type']=='hidden' || $this->htmlforms[$field]['widget']=='hidden') {
				$arr[]=$value['input'];
			} else {
				$arr[]=$helper->show($value['label'],$value['input'],$validate['FIELDS'][$field]);
			}
		}
		return implode($delimiter,$arr);
	}
	function clean($name=null) {
		return $name ? $this->clean_data[$name] : $this->clean_data;
	}
	protected function error(&$ret, $k,$m,$err_array=null) {
		if(is_array($err_array)) {
			$ret=array_merge_recursive($ret,$err_array);
			return;
		}
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
		foreach ($this->htmlforms as $field=>$h) {
			$k=$field;
			$wclass=(isset($h['widget']) ? $h['widget'] : $h['type']);
			if(empty($wclass)) continue;
			$wclass='gs_widget_'.$wclass;
			$h['gs_form_params']=$this->params;
			$w =new $wclass($k,$this->data,$h);
			try {
				$value=$w->clean();
				if (is_array($value) && !is_numeric(key($value))) {
					foreach ($value as $vk=>$vv) {
						$this->clean_data[$vk]=$vv;
					}
				} else {
					$this->clean_data[$k]=$value;
				}
			} catch (gs_widget_validate_exception $e) {
				$this->error($ret, $k,$e->getMessage(),$w->validate_errors);
			}
			if (!isset($h['validate'])) $h['validate']='notEmpty';
			$validate=is_array($h['validate']) ? $h['validate'] : array($h['validate']);
			foreach ($validate as $v) {
				$vname='gs_validate_'.$v;
				$val=new $vname();
				if (!$val->validate($k,$value,$this->data,isset($h['validate_params'])?$h['validate_params'] : array())) {
					$this->error($ret, $k,$vname);
				}

			}
		}
		if(is_array($ret['STATUS'])) $ret['STATUS']=!in_array(FALSE,$ret['STATUS']);
		return $ret;

	}
	function as_url() {
		$arr=array();
		foreach($this->htmlforms as $k=>$f) {
			$arr[$k]=$this->data[$k];
		}
		return http_build_query($arr);
	}
	function get_input($field,$suffix=null) {
		$inputs=$this->_prepare_inputs();
		$v=$inputs[$field];
		return sprintf('<label>%s%s %s</label>', $v['label'],trim($v['label']) ? $suffix : null ,$v['input']);
	}


}

class g_forms_html extends g_forms {
	function _prepare_inputs(){
		$arr=array();
		foreach($this->htmlforms as $field => $v) {
			$wclass=isset($v['widget']) ? $v['widget'] : $v['type'];
			if (!$wclass) continue;
			$wclass="gs_widget_$wclass";
			$v['gs_form_params']=$this->params;
			$w =new $wclass($field,$this->data,$v);
			if($v['type']=='label') {
				$arr[$field]=array('input'=>$v['verbose_name']);
				continue;
			}
			$arr[$field]=array('label'=>isset($v['verbose_name']) ? $v['verbose_name']:$field,
						'input'=>$w->html()
						);
		}
		return $arr;
	}
	function as_dl($delimiter="\n",$validate=array(),$inputs=null,$outstr='<dl class="row"><dt><label for="%s">%s%s</label></dt> <dd><div>%s</div>%s</dd> </dl>'){
		$arr=array();
		if($inputs===null) $inputs=$this->_prepare_inputs();
		foreach($inputs as $field=>$v)  {
			$e="";
			if (isset($validate['FIELDS'][$field])) {
				$e='<div class="error">Error: '.implode(',',$validate['FIELDS'][$field]).'</div>';
			}
			if ($this->htmlforms[$field]['type']=='private') {
			} else if ($this->htmlforms[$field]['type']=='hidden' || $this->htmlforms[$field]['widget']=='hidden') {
				$arr[]=$v['input'];
			} else {
				if(is_array($v['input'])) {
					if ($this->htmlforms[$field]['widget_params']=='inline') {
						$arr[]=$this->as_dl($delimiter,$validate,$v['input'],$outstr);
					} else {
						$v['input']=$this->as_dl($delimiter,$validate,$v['input'],$outstr);
						$arr[]=sprintf($outstr,$field,$v['label'],$v['label']?':':'',$v['input'],$e);
					}
				} else {
					$arr[]=sprintf($outstr,$field,$v['label'],$v['label']?':':'',$v['input'],$e);
				}
			}
		}
		return implode($delimiter,$arr);
	}
	function as_table($delimiter="\n"){
		$arr=array();
		$inputs=$this->_prepare_inputs();
		foreach($inputs as $field=>$v) 
			$arr[]=sprintf('<tr><td><label for="%s">%s</label></td><td>%s</td></tr>',$field, $v['label'],$v['input']);

		return implode($delimiter,$arr);
	}
	function as_list(){}
	function as_labels($delimiter="<br/>\n",$suffix=':'){
		$arr=array();
		$inputs=$this->_prepare_inputs();
		foreach($inputs as $field=>$v) 
			$arr[]=sprintf('<label>%s%s %s</label>', $v['label'],trim($v['label']) ? $suffix : null ,$v['input']);

		return implode($delimiter,$arr);
	}
	
	function as_inline($delimiter=" \n",$validate=array()){
		$arr=array();
		$inputs=$this->_prepare_inputs();
		foreach($inputs as $field=>$v) 
			$arr[]=sprintf('<div class="inline"><div>%s</div>%s</div>',$v['label'],$v['input']);

		return implode($delimiter,$arr);
	}


}
class g_forms_jstpl extends g_forms_html {
	function _prepare_inputs(){
		$arr=array();
		foreach($this->htmlforms as $field => $v) {
			$wclass="gs_widget_".(isset($v['widget']) ? $v['widget'] : $v['type']);
			$w =new $wclass($field,array($field=>"<%=t.values.$field%>"),$v);
			$arr[$field]=array('label'=>isset($v['verbose_name']) ? $v['verbose_name']:$field,
						//'input'=>$w->html($field,"<%=t.values.$field%>",$v)
						'input'=>$w->js()
						);
		}
		return $arr;
	}
}

?>
