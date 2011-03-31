<?php
interface g_forms_interface {
	function __construct($rs);
	function as_table();
	function as_list();
	function as_labels();
	function clean();
}
class gs_glyph {
	private $tagName;
	private $parent=NULL;
	private $children=array();
	private $attributes=array();
	function __construct($name='',$attributes=array()) {
		$this->tagName=$name;
		foreach ($attributes as $k=>$v) {
			$this->addAttribute($k,$v);
		}
	}
	function setParent($obj) {
		$this->parent=$obj;
	}
	function addNode($name,$attributes=array(),$childs=array()) {
		$node=$this->addChild($name);
		foreach ($attributes as $k=>$v) {
			$node->addAttribute($k,$v);
		}
		foreach ($childs as $k=>$v) {
			$node->addNode('field',array('name'=>$v));
		}
		return $node;
	}
	function addAttribute($k,$v) {
		$this->attributes[$k]=$v;
	}
	function addChild($name) {
		$c= is_object($name) && is_a($name,'gs_glyph') ? $name :  new gs_glyph($name);
		$c->setParent($this);
		$this->children[]=$c;
		return $c;
	}
	function replaceNode($new) {
		$this->parent->replaceChild($this,$new);
	}
	function replaceChild($old,$new) {
		$k=array_search($old,$this->children);
		if ($k) {
			$this->children[$k]=$new;
			$new->setParent($this);
		}
	}
	function removeNode(&$nodes) {
		if (!is_array($nodes)) $nodes=array($nodes);
		foreach ($this->children as $k=>$c) {
			$c->removeNode($nodes);
			if (in_array($c,$nodes)) {
				$c->setParent(NULL);
				unset($this->children[$k]);
			}
		}
		return $nodes;
	}
	function __get($name) {
		return $this->attributes[$name];
	}
	function getName() {
		return $this->tagName;
	}
	function children() {
		return $this->children;
	}
	function find($name,$value) {
		$ret=array();
		if (strpos($this->$name,$value)===0) $ret[]=&$this;
		foreach ($this->children as $c) {
			$ret=array_merge($ret,$c->find($name,$value));
		}
		return $ret;
	}
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
		$this->view = new gs_glyph('helper',array('class'=>'dl'));
		$this->view->addNode('helper',array('class'=>'dt'),array_keys($h));
	}
	function show($validate=array(),$view=NULL) {
		$delimiter="\n";
		$arr=array();
		$inputs=$this->_prepare_inputs();
		if($view===NULL) $view=$this->view;
		$hclass='helper_'.$view->class;
		$helper=new $hclass();
		$str='';
		foreach ($view->children() as $k=>$e) {
			if($e->getName()=='helper') { 
				$value=array('label'=>(string)$e->label,'input'=>$this->show($validate,$e));
				$arr[]=$helper->show($value['label'],$value['input']);
			} else {
				$name=(string)$e->name;
				$field=$this->htmlforms[$name];
				$value=$inputs[$name];

				if ($field['type']=='private') continue;

				if ($field['type']=='hidden' || $field['widget']=='hidden') {
					$arr[]=$value['input'];
				} else {
					$arr[]=$helper->show($value['label'],$value['input'],$validate['FIELDS'][$name]);
				}
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
	function add_helper_clone($fieldname) {
		$posts=$this->view->find("name",$fieldname);
		if($posts) {
			$ids=array();
			foreach (array_keys($this->data) as $data_field_name) {
				if (strpos($data_field_name,$fieldname)===0) {
					preg_match("/$fieldname:(-?\d+):/",$data_field_name,$id);
					$ids[$id[1]]=$id[1];
				}
			}

			$helper=new gs_glyph('helper',array('class'=>'clone'));
			$posts[0]->replaceNode($helper);
			$helper=$helper->addNode('helper',array('class'=>'dl','label'=>$fieldname))->addNode('helper',array('class'=>'dt'));
			$this->view->removeNode($posts);
			foreach($posts as $p) {
				$helper->addChild($p);
				$this->htmlforms[$p->name]['clonable']=TRUE;
			}
		
			$first_id=reset($ids);
			foreach ($ids as $id) {
				foreach($posts as $p) {
					$newname=str_replace("$fieldname:$first_id","$fieldname:$id",$p->name);
					if ($p->name!=$newname) {
						$this->htmlforms[$newname]=$this->htmlforms[$p->name];
					}
				}
			}

		}
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

class g_forms_table extends  g_forms_html {
    function __construct($h,$data=array(),$rec=null)  {
         parent::__construct($h,$data,$rec);
         $this->view = new gs_glyph('helper',array('class'=>'table'));
         $this->view->addNode('helper',array('class'=>'tr'),array_keys($h));
    }
}
class g_forms_table_submit extends  g_forms_html {
    function __construct($h,$data=array(),$rec=null)  {
         parent::__construct($h,$data,$rec);
         $this->view = new gs_glyph('helper',array('class'=>'table_submit'));
         $this->view->addNode('helper',array('class'=>'tr'),array_keys($h));
    }
}


?>
