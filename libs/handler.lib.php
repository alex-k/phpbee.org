<?php
class gs_base_handler {
	protected $blocks;
	protected $data;
	protected $params;
	public function __construct($data=null,$params=null) {
		$this->data=$data;
		$this->params=$params;

		$tpl=gs_tpl::get_instance();
		$config=gs_config::get_instance();
		$filename=$config->class_files[$this->params['module_name']];
		$subdir=trim(str_replace(cfg('lib_modules_dir'),'',dirname($filename).'/'),'/');
		$www_subdir=trim(cfg('www_dir').$subdir.'/','/');
		$www_subdir=$www_subdir ? "/$www_subdir/" : '/';
		$tpl=gs_tpl::get_instance();
		$tpl->template_dir=cfg('lib_modules_dir')."/$subdir/templates";
		$tpl->assign('tpl',$this);
		$tpl->assign('_module_subdir',$subdir);
		$tpl->assign('subdir',$subdir);
		$tpl->assign('www_subdir',$www_subdir);
		$tpl->assign('root_dir',cfg('root_dir'));
		$this->subdir=$subdir;
		$this->www_subdir=$www_subdir;

		$this->register_blocks();
	}
	function register_blocks() {
		$this->assign('_blocks',$this->blocks);
	}
	function assign($name,$value=NULL) {
		$tpl=gs_tpl::get_instance();
		if (is_array($name)) {
			return $tpl->assign($name);
		}
		return $tpl->assign($name,$value);
	}
	function fetch() {
		if (empty($this->params['name'])) throw new gs_exception('gs_base_handler.show: empty params[name]');
		$tpl=gs_tpl::get_instance();
		$tpl->assign('_gsdata',$this->data);
		$tpl->assign('_gsparams',$this->params);
		if (!$tpl->template_exists($this->params['name'])) throw new gs_exception('gs_base_handler.show: can not find template file for '.$this->params['name']);
		return $tpl->fetch($this->params['name']);
	}
	function show($nodebug=FALSE) {
		//if (empty($this->params['name'])) throw new gs_exception('gs_base_handler.show: empty params[name]');
		if (empty($this->params['name'])) {
			$this->params['name']=str_replace('/','_',$this->data['handler_key']).'.html';
		}

		$tpl=gs_tpl::get_instance();
		$tpl->assign('_gsdata',$this->data);
		$tpl->assign('_gsparams',$this->params);


		if (!$tpl->template_exists($this->params['name'])) {
			md($this->data,1);
			md($this->params,1);
			throw new gs_exception('gs_base_handler.show: can not find template file for '.$this->params['name']);
		}
		$txt=ob_get_contents();
		ob_end_clean();
		$html=$tpl->fetch($this->params['name']);
		echo $html;
		if (DEBUG && !$nodebug) {
			$log=gs_logger::get_instance();
			$txt2=$log->show();
			if (trim($txt) || trim($txt2)) {
				$txt=preg_replace("/\n/",'\\r\\n',addslashes($txt));
				echo <<<TXT
				<script>
				if (typeof console == 'object') {
					console.log('$txt');
					$txt2;
				}
				</script>
TXT;
			}
		}
	}
	protected function get_form() {
		$params=$this->params;
		$data=$this->data;
		$id=isset($data['gspgid_va'][1]) ? $data['gspgid_va'][1] : null;
		$classname=$params['classname'];

		$obj=new $classname;
		if (is_numeric($id)) {
			$rec=$obj->get_by_id($id);
		} else {
			$rec=$obj->new_record();
		}

		return self::get_form_for_record($rec,$this->params,$this->data);
	}
	static function get_form_for_record($rec,$params,$data) {
		$h=$rec->get_recordset()->structure['htmlforms'];
		$hh=$h;
		if(isset($params['fields'])) {
		$fields=array_filter(explode(',',$params['fields']));
		$fields_minus=array_filter($fields,create_function('$a','return substr($a,0,1)=="-";'));
		$fields_plus=array_diff($fields,$fields_minus);
		$fields_minus=array_map(create_function('$a','return substr($a,1);'),$fields_minus);
		if (count($fields_plus)>0) {
			$hh=array();
			foreach ($fields_plus as $f) if(isset($h[$f])) $hh[$f]=$h[$f]; 
		} else if (count($fields_minus)>0) {
			$hh=array();
			foreach ($h as $f=>$v)  if (!in_array($f,$fields_minus)) $hh[$f]=$h[$f];
		}
		}
		foreach ($hh as $k=>$v) {
			switch($v['type']) {
				case 'lMany2Many':
					$rsl=$rec->init_linked_recordset($k);
					$rsname=$rsl->structure['recordsets']['childs']['recordset'];
					$rs=new $rsname();
					$vrecs=$rs->find_records();
					foreach ($vrecs as $vrec) $variants[$vrec->get_id()]=trim($vrec);
					$hh[$k]['variants']=$variants;
				break;
				case 'lOne2One':
					$variants=array();
					$vrecs=$rec->init_linked_recordset($v['linkname'])->find_records();
					foreach ($vrecs as $vrec) $variants[$vrec->get_id()]=trim($vrec);
					$hh[$k]['variants']=$variants;
				break;
				case 'lMany2One':
						$nrs=$rec->$k;
						$nrs->new_record();
						foreach($nrs as $nobj) {
							$f=self::get_form_for_record($nobj,$params,$data);
							$forms=$f->htmlforms;
							$i=intval($nobj->get_id());
							foreach($forms as $fk=>$fv) {
								$pfx_key="$k:$i:$fk";
								$key="$k:$fk";
								$hh[$pfx_key]=$fv;
								if(isset($data['handler_params'][$key])) {
									$data['handler_params'][$pfx_key]=$data['handler_params'][$key];
								}
							}
						}
						unset($hh[$k]);
				break;
				default: 
			}
		}
		if(isset($data['handler_params']) && is_array($data['handler_params'])) foreach ($data['handler_params'] as $hk=>$hv) {
			if(isset($hh[$hk])) {
				$hh[$hk]['type']='private';
				$hh[$hk]['hidden']=false;
				$data[$hk]=$hv;
			}
		}
		$form_class_name=isset($params['form_class']) ? $params['form_class'] : 'g_forms_html';
		$fields=implode(',',array_keys($hh));
		if(isset($data['handler_params']['_default'])) {
			$default=$data['handler_params']['_default'];
			$default=string_to_params($default);
			$data=array_merge($default,$data);
		}
		$f=new $form_class_name($hh,$params,array_merge(self::implode_data($rec->get_values($fields)),$data));
		$f->rec=$rec;
		return $f;
	}
	function showform() {
		$tpl=gs_tpl::get_instance();
		$f=$this->get_form();
		$tpl->assign('formfields',$f->show());
		$tpl->assign('form',$f);
		return $tpl->fetch($this->params['name']);
	}
	function postform() {
		if (!isset($this->data['gspgid_form']) || $this->data['gspgid_form']!=$this->data['gspgid']) return $this->showform();

		$tpl=gs_tpl::get_instance();
		$f=$this->get_form();
		$validate=$f->validate();
		if ($validate['STATUS']===true) {
			$f->rec->fill_values(self::explode_data($f->clean()));
			$f->rec->get_recordset()->commit();
			if (isset($this->params['href'])) return html_redirect($this->subdir.$this->params['href'].'/'.$f->rec->get_id().'/'.get_class($f->rec->get_recordset()).'/'.$this->data['gspgid_v']);
			return html_redirect($this->data['gspgid_handler']);
			//return $tpl->fetch($this->params['name']);
		}
		$tpl->assign('formfields',$f->show($validate));
		$tpl->assign('form',$f);
		return $tpl->fetch($this->params['name']);
	}
	function displayform() {
		$tpl=gs_tpl::get_instance();
		$tpl->assign('gspgid_form',$this->data['gspgid']);
		$tpl->assign('gspgid_handler',$this->data['gspgid']);
		echo $this->postform();
	}
	static function implode_data($data,$prefix='') {
		$newdata=array();
		foreach ($data as $k=>$v) {
			if(is_array($v)) $newdata=array_merge($newdata,self::implode_data($v,$prefix.':'.$k));
			else $newdata[trim("$prefix:$k",':')]=$v;
		}
		return $newdata;
	}
	static function explode_data($data) {
			$newdata=array();
			foreach ($data as $k=>$v) {
					$s=explode(':',$k);
					while (($i=array_pop($s))!==NULL) {
							$dd=array();
							$dd[$i]=$v;
							$v=$dd;
					}       
					$newdata=array_merge_recursive_distinct($newdata,$v);
			}       
			return array_merge($data,$newdata);
	}
}
class gs_tpl_block {
	protected $tpl_filename;
	protected $data;
	function __construct($data=null,$tpl_filename='default/empty_block.html') {
		$this->data=$data;
		$this->tpl_filename=$tpl_filename;
	}
	function show() {
		$tpl=gs_tpl::get_instance();
		return $tpl->fetch($this->tpl_filename);
	}
}
?>
