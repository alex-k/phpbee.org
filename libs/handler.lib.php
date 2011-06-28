<?php
class gs_base_handler {
	protected $blocks;
	protected $data;
	protected $params;
	public function __construct($data=null,$params=null) {
		$this->data=$data;
		$this->params=$params;

		$config=gs_config::get_instance();
		$filename=$config->class_files[$this->params['module_name']];
		$subdir=trim(str_replace(cfg('lib_modules_dir'),'',dirname($filename).'/'),'/');
		$www_subdir=trim(cfg('www_dir').$subdir.'/','/');
		$www_subdir=$www_subdir ? "/$www_subdir/" : '/';
		$subdir=$subdir ? "/$subdir/" : '';
		$tpl=gs_tpl::get_instance();

		$this->tpl_dir= dirname($filename).DIRECTORY_SEPARATOR.'___templates';
		if (!file_exists($this->tpl_dir)) $this->tpl_dir=dirname($filename).DIRECTORY_SEPARATOR.'templates';
		if (is_array($tpl->template_dir))
			array_push($tpl->template_dir, $this->tpl_dir);
		else
			$tpl->template_dir = $this->tpl_dir;

		$tpl->assign('tpl',$this);
		$tpl->assign('_gssession',gs_session::load());
		$tpl->assign('_module_subdir',$subdir);
		$tpl->assign('subdir',$subdir);
		$tpl->assign('www_subdir',$www_subdir);
		$tpl->assign('root_dir',cfg('root_dir'));
		$this->subdir=$subdir;
		$this->www_subdir=$www_subdir;


		//$this->register_blocks();
	}

	function get_data($name=null) {
		return $name ? $this->data[$name] : $this->data ;
	}

	function is_post() {
		return $this->data['gspgtype']==GS_DATA_POST;
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
	function fetch($data) {
		if (empty($this->params['name'])) throw new gs_exception('gs_base_handler.show: empty params[name]');
		$tplname=file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name']) ? $this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'] : $this->params['name'];
		$tpl=gs_tpl::get_instance();
		$tpl->assign('_gsdata',$this->data);
		$tpl->assign('_gsparams',$this->params);
		if(isset($this->params['hkey'])) $tpl->assign('hdata',$data[$this->params['hkey']]);
		if (!$tpl->templateExists($tplname)) throw new gs_exception('gs_base_handler.show: can not find template file for '.$tplname);
		return $tpl->fetch($tplname);
	}

	function validate_gl() {
		$url=trim(call_user_func($this->params['module_name'].'::gl',$this->params['name'],$this->data['gspgid_v']),'/');
		return ($url==$this->data['gspgid']);
	}
	function show404() {
		header("HTTP/1.0 404 Not Found");
		return $this->show();
		return false;
	}
	function show($nodebug=FALSE) {
		if (empty($this->params['name'])) {
			$this->params['name']=basename($this->data['handler_key']).'.html';
		}
		$tplname=file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name']) ? $this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'] : $this->params['name'];

		$tpl=gs_tpl::get_instance();
		$tpl->assign('_gsdata',$this->data);
		$tpl->assign('_gsparams',$this->params);


		if (isset($this->data['handler_params'])) {
			try {
				$html=$tpl->fetch($tplname);
				echo $html;
				return;
			} catch (gs_exception $e) {
				var_dump($this->params);
				var_dump($this->data);
				throw $e;
			}
		}
		$txt=ob_get_contents();
		ob_end_clean();
		$html=$tpl->fetch($tplname);
		echo $html;
		if (DEBUG && !$nodebug) {
			mlog(sprintf('memory usage: %.4f / %.4f Mb ',memory_get_usage(TRUE)/pow(2,20),memory_get_peak_usage(TRUE)/pow(2,20)));
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
		$fields=array_keys($obj->structure['fields']);
		if ($id || is_numeric($id)) {
			$rec=$obj->get_by_id($id,$fields);
		} else {
			$rec=$obj->new_record();
		}

		return self::get_form_for_record($rec,$this->params,$this->data);
	}
	static function get_form_for_record($rec,$params,$data) {
		$h=$rec->get_recordset()->structure['htmlforms'];
		$hh=$h;
		if (!isset($params['fields']) && isset($data['handler_params']['fields'])) $params['fields']=$data['handler_params']['fields'];
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
		$fields=$hh ? array_combine(array_keys($hh),array_keys($hh)) : array();
		foreach ($hh as $k=>$v) {
			switch($v['type']) {
			case 'lMany2Many':
				if (method_exists($rec->get_recordset(),'form_variants_'.$k)) {
					$vrecs=call_user_func(array($rec->get_recordset(),'form_variants_'.$k),$rec,$data);
				} else {
					$rsl=$rec->init_linked_recordset($k);
					$rsname=$rsl->structure['recordsets']['childs']['recordset'];
					$rs=new $rsname();
					$vrecs=$rs->find_records();
				}
				$variants=array();
				foreach ($vrecs as $vrec) $variants[$vrec->get_id()]=trim($vrec);
				$hh[$k]['variants']=$variants;
				if (isset($data[$k])) {
					unset($fields[$k]);
					$data[$k]=(is_array($data[$k])) ? array_combine($data[$k],$data[$k]) : array();
					$rec->$k->flush($data[$k]);
				}
				break;
			case 'lOne2One':
				if ($hh[$k]['hidden']!='false' && $hh[$k]['hidden']) break;
				if (isset($v['widget'])) {
					$dclass='gs_data_widget_'.$v['widget'];
					if (class_exists($dclass)) {
						$d=new $dclass();
						$hh=$d->gd($rec,$k,$hh,$params,$data);
					}
				}
				break;
			case 'lMany2One':
				break;
				if ($v['hidden']=='true') break;
				if (isset($v['widget'])) {
					$dclass='gs_data_widget_'.$v['widget'];
					if (class_exists($dclass)) {
						$d=new $dclass();
						$hh=$d->gd($rec,$k,$hh,$params,$data);
					}
				}
				if (!empty($v['widget'])) {
					break;
				}
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
		$fields=$rec->get_recordset()->id_field_name.','.implode(',',$fields);
		if(isset($data['handler_params']) && is_array($data['handler_params'])) foreach ($data['handler_params'] as $hk=>$hv) {
			if(isset($hh[$hk])) {
				$hh[$hk]['type']='private';
				$hh[$hk]['hidden']=false;
				$data[$hk]=$hv;
			}
		}
		$form_class_name=isset($params['form_class']) ? $params['form_class'] : 'g_forms_html';
		if(isset($data['handler_params']['_default'])) {
		$default=$data['handler_params']['_default'];
		$default=string_to_params($default);
			$data=array_merge($default,$data);
		}
		/* if widget need all data of record */
		//$f=new $form_class_name($hh,$params,array_merge(self::implode_data($rec->get_values()),$data));
		$params['rec_id']=$rec->get_id();
		$f=new $form_class_name($hh,$params,array_merge(self::implode_data($rec->get_values($fields)),$data));
		//$f=new $form_class_name($hh,$params,self::implode_data(array_merge($rec->get_values($fields)),$data));
		$f->rec=$rec;
		return $f;
	}
	function showform() {
		$tpl=gs_tpl::get_instance();
		$f=$this->get_form();
		$tpl->assign('formfields',$f->show());
		$tpl->assign('form',$f);
		$tplname=file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name']) ? $this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'] : $this->params['name'];
		$ret=$tpl->fetch($tplname);
		return $ret;
	}
	function post() {
		//if (!isset($this->data['gspgid_form']) || $this->data['gspgid_form']!=$this->data['gspgid']) return $this->showform();
		if ($this->data['gspgtype']==GS_DATA_GET) return $this->showform();

		$tpl=gs_tpl::get_instance();
		$f=$this->get_form();
		$validate=$f->validate();
		if ($validate['STATUS']===true) {
			$f->rec->fill_values(self::explode_data($f->clean()));
			$f->rec->get_recordset()->commit();
			return $f->rec;
		}
		$tpl->assign('formfields',$f->show($validate));
		$tpl->assign('form',$f);
		$tplname=file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name']) ? $this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'] : $this->params['name'];
		return $tpl->fetch($tplname);
	}

	function postform() {
		//if (!isset($this->data['gspgid_form']) || $this->data['gspgid_form']!=$this->data['gspgid']) return $this->showform();
		if ($this->data['gspgtype']==GS_DATA_GET) return $this->showform();

		$tpl=gs_tpl::get_instance();
		$f=$this->get_form();
		$validate=$f->validate();
		if ($validate['STATUS']===true) {
			$f->rec->fill_values(self::explode_data($f->clean()));
			$f->rec->get_recordset()->commit();
			if (isset($this->params['href'])) {
				$href=$this->params['href'];
				if (strpos($this->params['href'],'/')!==0) {
					$href=$this->subdir.$href;
				}
				return html_redirect($href,array(
				                         'id'=>$f->rec->get_id(),
				                         'classname'=>get_class($f->rec->get_recordset()),
				                     ));
			}
			return html_redirect($this->data['gspgid_handler']);
		}
		$tpl->assign('formfields',$f->show($validate));
		$tpl->assign('form',$f);
		$tplname=file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name']) ? $this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'] : $this->params['name'];
		return $tpl->fetch($tplname);
	}
	function displayform() {
		$tpl=gs_tpl::get_instance();
		$tpl->assign('gspgid_form',$this->data['gspgid']);
		$tpl->assign('gspgid_handler',$this->data['gspgid']);
		echo $this->postform();
	}
	function deleteform() {
		
		//if (!isset($this->data['gspgid_form']) || $this->data['gspgid_form']!=$this->data['gspgid']) return $this->showform();
		if ($this->data['gspgtype']==GS_DATA_GET) return $this->showform();
		$f=$this->get_form();
		$f->rec->delete();
		$f->rec->commit();
		
		if (isset($this->params['href'])) return html_redirect($this->subdir.$this->params['href'].'/'.$f->rec->get_id().'/'.get_class($f->rec->get_recordset()).'/'.$this->data['gspgid_v']);
		return html_redirect($this->data['gspgid_handler']);
	}
	function redirect() {
		return html_redirect($this->params['href']);
	}
	function many2one() {
		if (isset($this->data['gspgid_va'][4]) && $this->data['gspgid_va'][4]=='delete') {
			$rid=intval($this->data['gspgid_va'][5]);
			$rs_name=$this->data['gspgid_va'][0];
			$rs=new $rs_name;
			$rec=$rs->get_by_id($rid);
			if ($rec) {
				$rec->delete();
				$rec->commit();
			}
			$res=preg_replace("|/delete/\d+|is","//",$this->data['gspgid']);
			return html_redirect($res);
		}
		$params=array(
		            $this->data['gspgid_va'][1]=>$this->data['gspgid_va'][2],
		        );
		$url=$this->data['gspgid_va'][0].'/'.$this->data['gspgid_va'][1].'/'.$this->data['gspgid_va'][2].'/'.$this->data['gspgid_va'][3];
		if ($this->data['gspgid_va'][2]==0) {
			$params[$this->data['gspgid_va'][1].'_hash']=$this->data['gspgid_va'][3];
		}
		$tpl=gs_tpl::get_instance();
		$tpl->assign('url',$url);
		$tpl->assign('params',$params);
		$this->show();
	}

	static function implode_data($data,$prefix='') {
		$newdata=array();
		foreach ($data as $k=>$v) {
			if(is_array($v)) {
				$newdata=array_merge($newdata,self::implode_data($v,$prefix.':'.$k));
			} else {
				$newdata[trim("$prefix:$k",':')]=$v;
			}
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
	static function process_handler($params,$smarty) {
		ob_start();
		$s_data=$data=$smarty->getTemplateVars('_gsdata');
		if (isset($params['_params']) && is_array($params['_params'])) $params=array_merge($params,$params['_params']);
		$params['gspgid']=trim($params['gspgid'],'/');
		if (isset($data['gspgid_form']) && $data['gspgid_form']==$params['gspgid']) {
			$gspgid_form=$data['gspgid_form'];
			$c=new gs_data_driver_post;
			$data=$c->import();
			$data['gspgid_form']=$gspgid_form;
			$data['gspgid']=$params['gspgid'];
		}
		if (!isset($data['gspgid_root'])) {
			$data['gspgid_root']=$data['gspgid'];
		}
		$data['gspgid_handler']=$data['gspgid'];
		$data['gspgid']=$params['gspgid'];
		$data['handler_params']=$params;
		


		$tpl=gs_tpl::get_instance();
		$tpl->assign($params);

		if (isset($params['_record'])) {
			$tpl->assign('_record',$params['_record']);
		}
		$assign=array();
		$assign['gspgdata_form']=$data;
		$assign['gspgid_form']=$data['gspgid'];
		$assign['gspgid_handler']=$data['gspgid_handler'];
		$assign['gspgid_root']=$data['gspgid_root'];
		$assign['handler_params']=$params;

		$tpl->assign($assign);

		
		$o_p=gs_parser::get_instance($data,'handler');
		if (isset($params['scope'])) {
			$hndl=$o_p->get_current_handler();
			if ($hndl[0]['params']['module_name']!=$params['scope']) return '';
		}
		$ret=$o_p->process();
		$ret_ob=ob_get_contents();
		ob_end_clean();

		$smarty->assign('_gsdata',$s_data);
		return $ret_ob.$ret;

	}

	function hpar($data,$name='hkey',$default=null) {
		return isset($this->params[$name]) ? $data[$this->params[$name]] : $default;
	}

	function send_email($data) {


		$to=$this->hpar($data,'email',array());
		$to=$this->hpar($data,'hkey',$to);

		$txt=$this->hpar($data,'txt','');

		$subj='lalala';

		pmail($to,$subj,$txt);

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
