<?php


class module_wizard extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('wz_modules','wz_recordsets','wz_recordset_fields','wz_recordset_links','wz_recordset_submodules','wz_urls','wz_handlers') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		return '<a href="/admin/wizard/">Wizard</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/form/wz_modules'=>array(
				'gs_base_handler.post:{name:form.html:classname:wz_modules:form_class:form_admin}',
				'gs_base_handler.redirect_up',
			),
			'/admin/form/wz_recordsets'=>array(
				'gs_base_handler.post:{name:form.html:classname:wz_recordsets:form_class:form_admin}',
				'gs_base_handler.redirect_up',
			),
			'/admin/form/wz_recordset_links'=>array(
				'gs_base_handler.post:{name:form.html:classname:wz_recordset_links:form_class:form_admin}',
				'gs_base_handler.redirect_up',
			),
			'/admin/form/wz_recordset_fields'=>array(
				'gs_base_handler.post:{name:form.html:classname:wz_recordset_fields:form_class:form_admin}',
				'gs_base_handler.redirect_up',
			),
			'/admin/form/wz_recordset_submodules'=>array(
				'gs_base_handler.post:{name:form.html:classname:wz_recordset_submodules:form_class:form_admin}',
				'gs_base_handler.redirect_up',
			),
			'/admin/form/wz_urls'=>array(
				'gs_base_handler.post:{name:form.html:classname:wz_urls:form_class:form_admin}',
				'gs_base_handler.redirect_up',
			),
			'/admin/form/wz_handlers'=>array(
				'gs_base_handler.post:{name:form.html:classname:wz_handlers:form_class:form_admin}',
				'gs_base_handler.redirect_up',
			),
			'/admin/wizard/templates'=>array(
				'gs_wizard_handler.templates',
			),
			'/admin/form/templates'=>array(
				'gs_wizard_handler.templatespost:return:true:name:form_submit.html:form_class:gs_wizard_template_form',
				'gs_base_handler.redirect',
			),
			'/admin/wizard/formmacros'=>array(
						'wz_handler_mc.post:name:form_submit.html',
						),
		),
		'post'=>array(
			'/admin/wizard/iddqdblocksubmit'=>array(
						'gs_wizard_handler.iddqdblocksubmit:return:true',
						'gs_base_handler.redirect_gl:gl:iddqdblocksubmit',
						),
		),
		'get'=>array(
			'/admin/wizard'=>'gs_base_handler.show',
			'/admin/wizard/iddqd'=>'gs_wizard_handler.iddqd',
			'/admin/wizard/iddqdblock'=>array(
						'gs_wizard_handler.iddqdblock:return:true',
						'gs_base_handler.show',
						),
			'/admin/wizard/module'=>'gs_base_handler.show',
			'/admin/wizard/commit'=>array(
					'gs_wizard_handler.commit:return:true',
					'gs_base_handler.redirect',
					),
			'/admin/wizard/recordsets'=>'gs_base_handler.show',
			'/admin/wizard/recordset_fields'=>'gs_base_handler.show',
			'/admin/wizard/urls'=>'gs_base_handler.show',
			'/admin/wizard/handlers'=>'gs_base_handler.show',
			'/admin/wizard/macros'=>'gs_base_handler.show',
                        '/admin/wizard/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_modules}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/recordsets/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_recordsets}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/recordset_fields/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_recordset_fields}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/recordset_links/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_recordset_links}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/recordset_submodules/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_recordset_submodules}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/handlers/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_handlers}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/urls/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_urls}',
                                        'gs_base_handler.redirect',
                                        ),

		),
	);
	return self::add_subdir($data,dirname(__file__));
	}
	static function gl($name,$record,$data) {
		switch ($name) {
			case 'iddqdblocksubmit':
				if ($data['save_view']) return '/admin/wizard/iddqd/'.$data['gspgid_v'];
				return '/admin/wizard/module/'.$data['gspgid_va'][0];
			break;
		}
	}
}


class gs_wizard_handler extends gs_handler {

	function commit() {
		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');
		$dirname=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR;
		check_and_create_dir($dirname);
		check_and_create_dir($dirname.'templates');

		foreach ($module->recordsets as $rs) 
		  foreach ($rs->Submodules as $sm) {
			  copy_directory(cfg('lib_distsubmodules_dir').$sm,$dirname.$sm->name);
			  $files=glob($dirname.$sm->name.DIRECTORY_SEPARATOR.'*.phps');
			  foreach($files as $fname) {
				  $txt=file_get_contents($fname);
				  $txt=str_replace('{%$PARENT_RECORDSET%}',$rs->name,$txt);
				  file_put_contents($fname,$txt);
			  }
		}

		$tpl=new gs_tpl();
		$tpl=$tpl->init();
		$tpl->left_delimiter='<*';
		$tpl->right_delimiter='*>';

		$tpl->assign('module',$module);

		$urls=array();
		foreach ($module->urls as $u) {
			$urls[$u->type][]=$u;
		}
		$tpl->assign('urls',$urls);

		$out=$tpl->fetch('file:'.dirname(__FILE__).DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'compile_phps.html');

		return file_put_contents($dirname.'module.phps',$out)!==FALSE;


	}

	function iddqd($data) {
		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->data['gspgid_va'][1];

		$tpl=gs_tpl::get_instance();
		$tpl->force_compile=true;
		$tpl->iddqd=true;

		$out=$tpl->fetch('string:'.file_get_contents($filename));
		$out=str_ireplace('</head',"<script src=\"/js/admin_iddqd.js\"></script>\n<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/admin_iddqd.css\" media=\"screen\" />\n</head",$out);
		echo($out);


	}
	function iddqdblock() {
		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->data['gspgid_va'][1];

		$template=file_get_contents($filename);
		$tpl=gs_tpl::get_instance();

		if (isset($this->data['gspgid_va'][2])) {
			preg_match("|{block name=\"".$this->data['gspgid_va'][2]."\"}(.*?){/block}|is",$template,$block);
			$template=$block[1];
		}

		$tpl->assign('block_content',$template);
		return true;

	}
	function iddqdblocksubmit() {
		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->data['gspgid_va'][1];
	
		$template=$this->data['block_content'];
		if (isset($this->data['gspgid_va'][2])) {
			$template=file_get_contents($filename);
			$template=preg_replace("|{block name=\"".$this->data['gspgid_va'][2]."\"}.*?{/block}|is",'',$template);
			$template.='{block name="'.$this->data['gspgid_va'][2].'"}'.$this->data['block_content'].'{/block}'.PHP_EOL;
		}
		file_put_contents($filename,$template);
		return true;
	}

	function templates() {

		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');
		$dirname=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;

		$templates=array_map(basename,glob($dirname.'*'));


		$tpl=gs_tpl::get_instance();

		$tpl->assign('templates',$templates);
		$tpl->assign('module',$module);

		return $tpl->fetch('templates.html');
	}

	function templatespost() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;

		$module=record_by_id($this->data['handler_params']['Module_id'],'wz_modules');
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->data['template_name'];
		if (file_exists($filename)) {
			return $bh->fetch($this->data);
		}


		$text="";
		if (!empty($this->data['extends'])) $text='{extends file="'.$this->data['extends'].'"}'.PHP_EOL;
		file_put_contents($filename,$text);
		return true;
	}

}

class wz_modules extends gs_recordset_short {
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'title'=> "fString 'название'",
		'recordsets'=>"lMany2One wz_recordsets:Module",
		'urls'=>"lMany2One wz_urls:Module",
		),$init_opts);
	}
}

class wz_recordsets extends gs_recordset_short {
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'Module'=>'lOne2One wz_modules',
		'Fields'=>"lMany2One wz_recordset_fields:Recordset",
		'Links'=>"lMany2One wz_recordset_links:Recordset",
		'Submodules'=>"lMany2One wz_recordset_submodules:Recordset",
		),$init_opts);

		$this->structure['triggers']['after_insert']='after_insert';
	}
	function after_insert($rec,$type) {
		$module=$rec->Module->first();
		$modulename=$module->name;
		$recordsetname=$rec->name;

		$template=array(
			"get"=>array(
				"$recordsetname"=>array("gs_base_handler.show"),
				"/admin/$modulename/$recordsetname"=>array("gs_base_handler.show:name:admin_$recordsetname.html"),
				"/admin/$modulename/$recordsetname/delete"=>array(
						"gs_base_handler.delete:{classname:$recordsetname}",
						"gs_base_handler.redirect",
						),
				),
			"handler"=>array(
				"/admin/form/$recordsetname"=>array(
					"gs_base_handler.post:{name:form.html:classname:$recordsetname:form_class:form_admin}",
					"gs_base_handler.redirect_up",
					),
				),
		);

		foreach ($template as $type=>$urls) {
			foreach ($urls as $url=>$handlers) {
				$wz_url=$rec->Module->first()->urls->new_record();
				$wz_url->gspgid_value=$url;
				$wz_url->type=$type;
				//$wz_url->commit();
				$cnt=0;
				foreach ($handlers as $key=>$value) {
					$cnt++;
					$wz_h=$wz_url->Handlers->new_record();
					$wz_h->cnt=$cnt;
					$wz_h->handler_keyname=$key;
					$wz_h->handler_value=$value;
					//$wz_h->commit();
				}
			}
		}
		$rec->Module->first()->commit();
	}
}
class wz_recordset_fields extends gs_recordset_short {
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'verbose_name'=> "fString verbose_name",
		'type'=>"fSelect type values='fString,fText,fInt' widget=select",
		'options'=>"fString options required=false",
		'widget'=>"fSelect widget required=false widget=select",
		'default_value'=>"fString default required=false",
		'required'=>"fCheckbox verbose_name=required",
		'Recordset'=>'lOne2One wz_recordsets',
		),$init_opts);



	}
	function gs_data_widget_select($rec,$field) {
		switch($field) {
			case 'type':
				$types=get_class_methods('field_interface');
				$types=array_filter($types,create_function('$a','return  preg_match("|^f[A-Z]|",$a);'));
				return $types;
				break;
			case 'widget':
				$widgets=gs_cacher::load('classes','config');
				$widgets=array_filter(array_keys($widgets),create_function('$a','return  is_subclass_of($a,"gs_widget");'));
				$widgets=str_replace('gs_widget_','',$widgets);
				array_unshift($widgets,'');
				return $widgets;
				break;
		}
		return array();
	}

	function record_as_string($rec) {

		$fields=$this->structure['htmlforms'];
		$ret="";
		foreach ($fields as $f=>$v) {
			if($rec->$f!='' && $f!='Recordset_id') $ret.=sprintf('%s="%s" ',$f,$rec->$f);
		}
		return $ret;
	}
}
class wz_recordset_links extends gs_recordset_short {
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'type'=>"fSelect type values='lOne2One,lMany2One,lMany2Many'",
		'classname'=>"fSelect classname widget=select_enter",
		'linkname'=>"fString linkname required=false",
		'verbose_name'=> "fString verbose_name required=false",
		'options'=>"fString options required=false",
		'widget'=>"fSelect widget required=false",
		'Recordset'=>'lOne2One wz_recordsets',
		),$init_opts);




	}
	function gs_data_widget_select($rec,$field) {
		switch($field) {
			case 'classname':
				$rsets=gs_cacher::load('classes','config');
				$rsets=array_filter(array_keys($rsets),create_function('$a','return  is_subclass_of($a,"gs_recordset_short");'));
				$rs=new wz_recordsets();
				$rs->find_records(array());
				foreach($rs as $r) {
					array_unshift($rsets,$r->name);
				}

				return $rsets;
				break;
			case 'widget':
				$widgets=gs_cacher::load('classes','config');
				$widgets=array_filter(array_keys($widgets),create_function('$a','return  is_subclass_of($a,"gs_widget");'));
				$widgets=str_replace('gs_widget_','',$widgets);
				array_unshift($widgets,'');
				return $widgets;
				break;
		}
		return array();
	}

	function record_as_string($rec) {

		$fields=$this->structure['htmlforms'];
		$ret="";
		foreach ($fields as $f=>$v) {
			if($rec->$f!='' && $f!='Recordset_id') $ret.=sprintf('%s="%s" ',$f,$rec->$f);
		}
		return $ret;
	}
}
class wz_recordset_submodules extends gs_recordset_short {
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=>"fSelect name widget=select",
		'Recordset'=>'lOne2One wz_recordsets',
		),$init_opts);

	}
	function gs_data_widget_select($rec,$field) {
		$ret=array_map(basename,glob(cfg('lib_distsubmodules_dir').'*'));
		return $ret;
	}
}
class wz_urls extends gs_recordset_short {
	function __construct($init_opts=false) { parent::__construct(array(
		'gspgid_value'=> "fString gspgid required=false unique=true",
		'type'=>'fSelect type values="get,handler,post"',
		'Module'=>'lOne2One wz_modules',
		'Handlers'=>"lMany2One wz_handlers:Url",
		),$init_opts);
	}
        function check_unique($field,$value,$params,$record=null) {
		/*
		md($params,1);
		md($record,1);
		return false;
		$recs=$this->find_records(array($field=>$value,'Module_id'=>$record->Module_id));
		*/
		$recs=$this->find_records(array($field=>$value));
		if ($recs->count()==0) return true;
		return $recs->first()->get_id()===$params['rec_id'];
        }

}
class wz_handlers extends gs_recordset_short {
	function __construct($init_opts=false) { parent::__construct(array(
		'cnt'=> "fInt cnt",
		'handler_keyname'=> "fString key required=false",
		'handler_value'=>"fString value",
		'Url'=>'lOne2One wz_urls',
		),$init_opts);
	}
}
class gs_wizard_template_form extends g_forms_inline{
	function __construct($hh,$params=array(),$data=array()) {
		$extends=array_map(basename,glob(cfg('tpl_data_dir')."*"));
		array_unshift($extends,'');
		$hh=array(
		    'extends' => Array
			(
			    'type' => 'select',
			    'validate' => 'dummyValid',
			    'options' => array_combine($extends,$extends),
			),
		    'template_name' => Array
			(
			    'type' => 'input',
			    'validate' => 'notEmpty',
			),

		);
		return parent::__construct($hh,$params,$data);
	}

}

class wz_handler_mc extends gs_handler {

	function post() {
		$this->params['form_class']=$this->data['gspgid_va'][1];
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
	
		
		$tpl=gs_tpl::get_instance();
		$tpl->assign('macros',json_encode($f->macros()));
		return $tpl->fetch('macros_insert_close.html');
	}

}


class wz_macros extends g_forms_html{
	function escape($txt) {
		$tpl=gs_tpl::get_instance();
		$txt=str_replace(array('{','}'),array($tpl->left_delimiter,$tpl->right_delimiter),$txt);
		return ($txt);
	}
}

class wz_macros_handler extends wz_macros {
	function __construct($hh,$params=array(),$data=array()) {
		$module=record_by_id($data['gspgid_va'][0],'wz_modules');
		$rs=new wz_urls();
		$rs->find_records(array('Module_id'=>$module->get_id(),
					array('field'=>'gspgid_value','case'=>'!=','value'=>''),
					array('type'=>'orderby','value'=>'gspgid_value'),
					));
		$urls=array();
		foreach ($rs as $r) {
			$url=$r->gspgid_value;
			if (substr($url,0,1)!='/') $url='/'.$module->name.'/'.$url;
			$urls[$url]=$url;
		}
		$hh=array(
		    'gspgid_value' => Array
			(
			    'verbose_name'=>'gspgid',
			    'type' => 'select',
			    'options' => $urls,
			    'widget'=>'select_enter',
			),
		    'extra_params' => Array
			(
			    'type' => 'input',
			    'validate' => 'dummyValid',
			),

		);
		return parent::__construct($hh,$params,$data);
	}
	function macros() {
		$d=$this->clean();

		$str='{handler gspgid="%s" %s}';

		return $this->escape(sprintf($str,$d['gspgid_value'],$d['extra_params']));

	}
}
