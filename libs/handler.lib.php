<?php
abstract class gs_handler {
	protected $blocks;
	protected $data;
	protected $params;
	public function __construct($data=null,$params=null) {
		$this->data=$data;
		$this->params=$params;
	}
}
class gs_base_handler extends gs_handler {
	public function __construct($data=null,$params=null) {
		parent::__construct($data,$params);

		$config=gs_config::get_instance();
		//$filename=$config->class_files[$this->params['module_name']];
		$classes=gs_cacher::load('classes','config');
		$filename=$classes[$this->params['module_name']];
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
			$tpl->template_dir = array($tpl->template_dir,$this->tpl_dir);

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
		if (empty($this->params['name'])) throw new gs_exception('gs_base_handler.fetch: empty params[name]');
		$tplname=file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name']) ? $this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'] : $this->params['name'];
		$tpl=gs_tpl::get_instance();
		$tpl->assign('_gsdata',$this->data);
		$tpl->assign('_gsparams',$this->params);
		if(isset($this->params['hkey'])) $tpl->assign('hdata',$data[$this->params['hkey']]);
		if (!$tpl->templateExists($tplname)) throw new gs_exception('gs_base_handler.fetch: can not find template file for '.$tplname);
		mlog($tplname);
		return $tpl->fetch($tplname);
	}

	function validate_gl() {
		$url=trim(call_user_func($this->params['module_name'].'::gl',$this->params['name'],$this->data['gspgid_v']),'/');
		/*md($this->data,1);
		var_dump($url);
		md($_SERVER,1);*/
		return ($url==$this->data['gspgid'] || $url==trim($_SERVER['REQUEST_URI'],'/'));
	}
	function show404($ret) {
		header("HTTP/1.0 404 Not Found");
		return $this->show($ret);
		return false;
	}

	function flush($str) {
		while (ob_get_level()) ob_end_clean();
		echo $str;
		die();
	}
	
	function show($ret,$nodebug=FALSE) {
		$tpl=gs_tpl::get_instance();

		if (empty($this->params['name'])) {
			$this->params['name']=basename($this->data['handler_key']).'.html';
		}

		$tplname=false;
		if (!$tplname && file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'])) $tplname=$this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'];
		if (!$tplname && file_exists(cfg('tpl_data_dir').DIRECTORY_SEPARATOR.$this->params['name'])) $tplname=cfg('tpl_data_dir').DIRECTORY_SEPARATOR.$this->params['name'];
		if (!$tplname) $tplname=$this->params['name'];


		$language=false;
		if (!$language) $language=gs_var_storage::load('multilanguage_lang');
		if (!$language) $language=gs_session::load('multilanguage_lang');

		if ($language) {
			$langs=languages();
			if ($langs) {
				$default_lang=key($langs);
				array_shift($langs);
				if ($langs && $language!=$default_lang) {
					$newtplname=dirname($tplname).DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.(basename($tplname));
					if (file_exists($newtplname)) {
						$tplname=$newtplname;
						$old_tpl_dir=$dir=$tpl->getTemplateDir();
						if (!is_array($dir)) $dir=array($dir);
						array_unshift($dir,'.',dirname($newtplname));
						$tpl->setTemplateDir($dir);
					}

				}
			}
			
		}

		$tpl->assign('_gsdata',$this->data);
		$tpl->assign('_gsparams',$this->params);
		$tpl->assign('_gsstack',$ret);

		if (isset($this->data['handler_params'])) {
			try {
				mlog($tplname);
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
		mlog($tplname);
		$html=$tpl->fetch($tplname);
		echo $html;
		//if (isset($old_tpl_dir)) $tpl->setTemplateDir($old_tpl_dir);
		if (function_exists('memory_get_peak_usage')) mlog(sprintf('memory usage: %.4f / %.4f Mb ',memory_get_usage(TRUE)/pow(2,20),memory_get_peak_usage(TRUE)/pow(2,20)));
		if (DEBUG && !$nodebug) {
			$g=gs_logger::get_instance();
			$g->console();
		}
	}
	protected function get_form() {
		$params=$this->params;
		$data=$this->data;
		if (isset($params['classname'])) {
			$id=isset($data['gspgid_va'][1]) ? $data['gspgid_va'][1] : null;
			$classname=$params['classname'];
			$obj=new $classname;
			$fields=array_keys($obj->structure['fields']);
			if ($id && is_numeric($id)) {
				$options=array();
				$options[$obj->id_field_name]=$id;
				foreach($data['handler_params'] as $hk=>$hv) {
					if (isset($obj->structure['fields'][$hk])) $options[$hk]=$hv;
				}
				//$rec=$obj->get_by_id($id,$fields);
				$rec=$obj->find_records($options,$fields)->first(true);
			} else {
				$rec=$obj->new_record();
			}
			return self::get_form_for_record($rec,$this->params,$this->data);
		}
		$form_class_name=isset($params['form_class']) ? $params['form_class'] : 'g_forms_html';
		$f=new $form_class_name(array(),$params,$data);
		return $f;
	}

	static function minus_fields($hh_fields,$params,$data) {
		$custom_fields=NULL;
		if (isset($data['handler_params']['fields'])) $custom_fields=$data['handler_params']['fields'];
		if (isset($params['fields'])) $custom_fields=$params['fields'];
		if ($custom_fields) $custom_fields=explode(',',$custom_fields);
		if (count($custom_fields)) {
			$fields_minus=array_filter($custom_fields,create_function('$a','return substr($a,0,1)=="-";'));
			$fields_plus=array_diff($custom_fields,$fields_minus);
			$fields_minus=array_map(create_function('$a','return substr($a,1);'),$fields_minus);
			if(count($fields_plus)) $hh_fields=$fields_plus;
			//if ($fields_minus) foreach ($hh_fields as $k=>$v)  if (in_array($v,$fields_minus)) unset($hh_fields[$k]);
			foreach ($fields_minus as $name) unset($hh_fields[array_search($name,$hh_fields)]);
		}
		return $hh_fields;
	}

	static function apply_data_widgets($f,$hh,$params,$data) {
		$rec=$f->rec;
		foreach ($f->htmlforms as $k=>$v) {
			$v=$hh[$k];
			$hhh=array($k=>$v);
			switch($v['type']) {
			case 'lMany2Many':
				if (method_exists($rec->get_recordset(),'form_variants_'.$k)) {
					$vrecs=call_user_func(array($rec->get_recordset(),'form_variants_'.$k),$rec,$data);
				} else {
					$rsl=$rec->init_linked_recordset($k);
					$rsname=$rsl->structure['recordsets']['childs']['recordset'];
					$rs=new $rsname();
					$vrecs=$rs->find_records(array());
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
						$hhh=$d->gd($rec,$k,$hhh,$params,$data);
					}
				}
				break;
			case 'lMany2One':
				if ($v['hidden']=='true') break;
				if (isset($v['widget'])) {
					$dclass='gs_data_widget_'.$v['widget'];
					if (class_exists($dclass)) {
						$d=new $dclass();
						$hhh=$d->gd($rec,$k,$hhh,$params,$data);
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
						$f->add_field($pfx_key,$fv);
						//$hh[$pfx_key]=$fv;
						if(isset($data['handler_params'][$key])) {
							$f->set_value($pfx_key,$data['handler_params'][$key]);
							//$data['handler_params'][$pfx_key]=$data['handler_params'][$key];
						}
					}
				}
				unset($hh[$k]);
				break;
			default:
				break;
			}
			if (isset($v['widget'])) {
				$dclass='gs_data_widget_'.$v['widget'];
				if (class_exists($dclass)) {
					$d=new $dclass();
					$hhh=$d->gd($rec,$k,$hhh,$params,$data);
				}
			}
			if (isset($hhh[$k]['variants']))  $f->set_variants($k,$hhh[$k]['variants']);
		}
	}

	static function get_form_for_record($rec,$params,$data) {

		$default_values=array();
		if(isset($data['handler_params']['_default'])) {
			$default_values=string_to_params($data['handler_params']['_default']);
		}
		$rec->fill_values($default_values);

		$form_class_name=isset($params['form_class']) ? $params['form_class'] : 'g_forms_html';
		$f=new $form_class_name(array());
		$f->rec=$rec;
		$f->force_set_value($rec->get_recordset()->id_field_name,$rec->get_id());

		$hh=$rec->get_recordset()->structure['htmlforms'];
		$hh_fields=array_keys($hh);
		$hh_fields=self::minus_fields($hh_fields,$params,$data);

		if (!count($f->htmlforms)) foreach ($hh_fields as $name) {
			$params=$hh[$name];
			if (!$params['hidden']) {
				$f->add_field($name,$params);
			}
		}



		self::apply_data_widgets($f,$hh,$params,$data);

		$fields=$rec->get_recordset()->id_field_name.','.implode(',',$hh_fields);

		$f->set_values($default_values);
		$f->set_values(self::implode_data($rec->get_values($fields)));
		$f->set_values($data);

		return $f;

	}


	static function __get_form_for_record($rec,$params,$data) {
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
					$vrecs=$rs->find_records(array());
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
				break;
			}


			if (isset($v['widget'])) {
				$dclass='gs_data_widget_'.$v['widget'];
				if (class_exists($dclass)) {
					$d=new $dclass();
					$hh=$d->gd($rec,$k,$hh,$params,$data);
				}
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
		$params['rec_id']=$rec->get_id();
		mlog($hh);
		$f=new $form_class_name($hh,$params,array_merge(self::implode_data($rec->get_values($fields)),$data));
		$f->rec=$rec;
		return $f;
	}
	function showform() {
		$tpl=gs_tpl::get_instance();
		$f=$this->get_form();
		$tpl->assign('formfields',$f->show());
		/*
		$tpl->assign('forminputs',$f->get_inputs());
		$tpl->assign('formerrors',$f->validate_errors['FIELDS']);
		*/
		$tpl->assign('form',$f);
		$tplname=file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name']) ? $this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'] : $this->params['name'];
		mlog($tplname);
		$ret=$tpl->fetch($tplname);
		return $ret;
	}
	function validate() {
		if(isset($this->data['handler_params']['form_class'])) {
			$this->params['form_class']=$this->data['handler_params']['form_class'];
		}
		if (!$this->is_post()) return $this->showform();

		$tpl=gs_tpl::get_instance();
		$f=$this->get_form();
		if (isset($this->data['gsform_interact'])) {
			$this->flush($f->interact($this->data['gsform_interact']));
		}
		$validate=$f->validate();
		$tpl->assign('formfields',$f->show($validate));
		$tpl->assign('form',$f);
		if ($validate['STATUS']===true) {
			return $f;
		}
		$tplname=file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name']) ? $this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'] : $this->params['name'];
		mlog($tplname);
		$ret=$tpl->fetch($tplname);
		return $ret;
	}
	function post() {
		$f=$this->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$f->rec->fill_values(self::explode_data($f->clean()));
		$f->rec->get_recordset()->commit();
		return $f->rec;
	}

	function postform() {
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
		mlog($tplname);
		return $tpl->fetch($tplname);
	}
	function displayform() {
		$tpl=gs_tpl::get_instance();
		$tpl->assign('gspgid_form',$this->data['gspgid']);
		$tpl->assign('gspgid_handler',$this->data['gspgid']);
		echo $this->postform();
	}
	function deleteform() {
		
		if ($this->data['gspgtype']==GS_DATA_GET) return $this->showform();
		$f=$this->get_form();
		$f->rec->delete();
		$f->rec->commit();
		
		if (isset($this->params['href'])) return html_redirect($this->subdir.$this->params['href'].'/'.$f->rec->get_id().'/'.get_class($f->rec->get_recordset()).'/'.$this->data['gspgid_v']);
		return html_redirect($this->data['gspgid_handler']);
	}
	/**
	* NEVER use this handler on client side, use delete_link instead
	**/
	function delete() {
		$id=$this->data['gspgid_va'][0];
		$rs=new $this->params['classname'];
		$rec=$rs->get_by_id($id);
		$rec->delete();
		$rec->commit();
		return $rec;
	}
	/**
	*	'gs_base_handler.check_login:return:gs_record^redirect:classname:customers:assign:customer'
	*	'gs_base_handler.delete_link:{link:customer.Shipping_address}'
	*	'redirect'=>'gs_base_handler.redirect'
	**/
	function delete_link() {
		list($classname,$linkname)=explode('.',$this->params['link']);
		$rec=gs_var_storage::load($classname);
		if (!$rec) return;
		$id=$this->data['gspgid_va'][0];
		$links=$rec->$linkname;
		if ($links && $links[$id]) {
			$links[$id]->delete();
			$links->commit();
		}
		return $rec;
	}
	function set_value($data) {
		$rec=$this->hpar($data);
		if (!$rec) return $rec;
		$name=$this->params['name'];
		$rec->$name=$this->data['gspgid_va'][0];
		$rec->commit();
		return $rec;
	}
	function copy() {
		$id=$this->data['gspgid_va'][0];
		$rs=new $this->params['classname'];
		$rec=$rs->get_by_id($id);
		/*
		$rec->delete();
		$rec->commit();
		*/
		$values=$rec->get_values();
		unset($values[$rs->id_field_name]);
		unset($values['_ctime']);
		unset($values['_mtime']);
		unset($values['urlkey']);
		$newrec=$rs->new_record($values);
		$newrec->commit();
		$newrec->urlkey=$newrec->get_id();
		$newrec->commit();
		return $rec;
	}
	function xml_clone() {
		$id=$this->data['gspgid_va'][0];
		$rs=new $this->params['classname'];
		$rec=$rs->get_by_id($id);
		if (!$rec) return $rec;
		$xml=$rec->xml_export();
		//md($xml,1);
		$newrs=xml_import($xml);
		$newrs->commit();
		return $rec;
	}
	function redirect_gl($ret) {
		if (isset($this->params['gl'])) {
			$this->params['href']=call_user_func($this->params['module_name'].'::gl',$this->params['gl'],$ret['last'],$this->data);
		} else if (isset($this->params['href'])) {
			$this->params['href']=trim($this->params['href'],'/').'/'.$ret['last']->get_id();
		}
		return $this->redirect();
	}
	function redirect_if($ret) {
		if (!isset($this->data[$this->params['gl']])) return true;
		$this->params['href']=call_user_func('module::gl',$this->params['gl'],$ret['last'],$this->data);
		$this->redirect();
		return false;
	}
	function redirect() {
		return html_redirect(isset($this->params['href']) ? $this->params['href']: null,array(),'302',$this->params['clean_get']);
	}
	
	function get_record($data) {
		return record_by_id($this->data['gspgid_va'][$this->params['key']],$this->params['rs']);
	}
	
	function set_record($data) {
		$rec=$this->hpar($data);
		if (!$rec) {
			$rec=record_by_id($this->data['gspgid_va'][$this->params['key']],$this->params['rs']);
		}
		$f=$this->params['field'];
		$v=$this->params['value'];
		$rec->$f=$v;
		$rec->commit();
		return $rec;
	}
	
	/**
	* Analogue "redirect" but put in sprintf pattern of "href" field`s value of record from previous handler  
	**/
	function redirect_rs_hkey($data) {
		$rec=$this->hpar($data);
		return html_redirect(sprintf($this->params['href'],$rec->{$this->params['field']}));
	}
	
	function redirect_up() {
		$level=isset($this->params['level'])? intval($this->params['level']) :1;
		$href=$this->data['gspgid_root'];
		for($i=0;$i<$level;$i++) $href=dirname($href);
		return html_redirect($href);
		//return (isset($this->data['gspgid_va'][1])) ? html_redirect($href) : html_redirect();
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
		$ret=array_merge($data,$newdata);
		return $ret;
	}
	static function process_handler($params,$smarty) {
		$params['gspgid']=trim($params['gspgid'],'/');
		$s_data=$data=$smarty->getTemplateVars('_gsdata');
		$s_gspgid_form=$smarty->getTemplateVars('gspgid_form');
		$s_gspgid=cfg('s_gspgid');
		cfg_set('s_gspgid',$params['gspgid']);

		$s_handler_cnt=cfg_set('s_handler_cnt',cfg('s_handler_cnt')+1);

		if (isset($params['_params']) && is_array($params['_params'])) $params=array_merge($params,$params['_params']);

		if (isset($data['gspgid_form']) && $data['gspgid_form']==$params['gspgid']) {
			$gspgid_form=$data['gspgid_form'];
			$c=new gs_data_driver_post;
			$data=$c->import();
			$data['gspgid_form']=$gspgid_form;
			$data['gspgid']=$params['gspgid'];
		}

		if (cfg('use_handler_cache') && $data['gspgtype']!==GS_DATA_POST) {
			$hh=new tw_handlers_cache();
			$h=$hh->find_records(array('md5'=>md5($params['gspgid']),'gspgid'=>$params['gspgid']),'text')->first();
			if ($h) {
				mlog('RETRUN '.$params['gspgid'].' data from cache');
				return $h->text;
			}
		}

		cfg_set('handler_cache_status',0);

		if (!isset($data['gspgid_root'])) {
			$data['gspgid_root']=$s_data['gspgid'];
			$data['handler_key_root']=$s_data['handler_key'];
		}
		$data['gspgid_handler']=isset($data['gspgid']) ? $data['gspgid'] : '';
		$data['gspgid_handler_va']=$data['gspgid_va'];
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
		
		if (isset($params['gspgtype'])) $data['gspgtype'] = $params['gspgtype'] ;
		$o_p=gs_parser::get_instance($data,isset($params['gspgtype']) ? $params['gspgtype'] : 'handler');
		if (isset($params['scope'])) {
			$hndl=$o_p->get_current_handler();
			if ($hndl['params']['module_name']!=$params['scope']) return '';
		}
		ob_start();
		try {
			$ret=$o_p->process();
		} catch (gs_dbd_exception $e) {
			throw $e;
		} catch (gs_exception $e) {
			throw $e;
		}
		$ret_ob=ob_get_contents();
		ob_end_clean();
		$smarty->assign('_gsdata',$s_data);
		$tpl->assign('gspgid_form',$s_gspgid_form);
		cfg_set('s_gspgid',$s_gspgid);
		$ret=$ret_ob.$ret;

		if(cfg('use_handler_cache') &&  $s_handler_cnt==cfg('s_handler_cnt') && cfg('handler_cache_status')==2 &&  $data['gspgtype']!==GS_DATA_POST) {
			$h=$hh->find_records(array('md5'=>md5($params['gspgid'])))->first(true);
			$h->gspgid=$params['gspgid'];
			$h->text=$ret;
			$hh->commit();
		}

		return $ret;

	}

	function hpar($data,$name='hkey',$default=null) {
		if ($name=='hkey' && !isset($this->params[$name])) {
			return $data['last'];
		}
		return isset($this->params[$name]) ? $data[$this->params[$name]] : $default;
	}

	function send_email($data) {
		$to=$this->hpar($data,'email',array());
		$to=$this->hpar($data,'hkey',$to);

		$txt=$this->hpar($data,'txt','');

		$subj='lalala';

		pmail($to,$subj,$txt);

	}
	
	/**
	* Send email with data from record
	* If field $this->params['email'] contains @ - get address from her, else not contains - use her as name of record`s field with address 
	**/
	function email4record($data) {
		$rec=$this->hpar($data);
		if(isset($this->params['email']) && strpos($this->params['email'],'@')!==false){
			$to=$this->params['email'];
		} else {
			$to=$rec->{$this->params['email']};
		}
		// if email incorrect - don`t send letter
		if(empty($to) || strpos($to,'@')===false) return false;
		
		$tpl=gs_tpl::get_instance();
		$tpl->assign('rec',$rec);
		$subj=$tpl->fetch(str_replace(".html","_title.html",$this->params['template']));
		$txt=$tpl->fetch($this->params['template']);
		bee_mail($to,$subj,$txt);
		return $rec;
	}

	function test_id($data) {
		//$code=$this->hpar($data);
		$code=$this->data['gspgid_va'][0];
		md($code,1);
		md($this->params,1);
		$res=preg_match("|(\d+)a(.*)|is",$code,$out);
		if (count($out)<2) return false;
		if (md5($out[1])!=$out[2]) return false;
		$id=intval($out[1]);
		return record_by_id($id,$this->params['rs']);
	}


	function check_login($data) {
		$id=gs_session::load('login_'.$this->params['classname']);
		$rec=record_by_id($id,$this->params['classname']);
		if(isset($this->data['handler_params']['assign'])) {
			gs_var_storage::save($this->data['handler_params']['assign'],$rec);
		}
		if(isset($this->params['assign'])) {
			gs_var_storage::save($this->params['assign'],$rec);
		}
		return $rec;
	}
	function post_logout($data) {
		$h=new handler_registry;
		$rec=$this->check_login();
		if($rec) $h->before_logout($rec);
		gs_session::clear('login_'.$this->params['classname']);
		return true;
	}

	function post_login($data) {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=$f->clean();

		$rsname=$this->params['classname'];

		$rs=new $rsname;


		foreach ($this->data['handler_params'] as $n=>$v) {
			if (isset($rs->structure['fields'][$n])) $d[$n]=$v;
		}

		$rec=$rs->find_records($d)->first();


		if (!$rec) return $this->showform();

		gs_session::save($rec->get_id(),'login_'.$rsname);
		$h=new handler_registry;
		$h->after_login($rec);
		return $rec;
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
