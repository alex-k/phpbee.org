<?php

abstract class gs_wizard_strategy_module extends gs_base_module {}


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
		$ret[1][]='<a href="/admin/wizard/">Wizard</a>';
		$modules=new wz_modules();
		$modules->find_records(array());
		foreach($modules as $m) {
			$ret[1][]='<a href="/admin/wizard/module/'.$m->id.'">'.$m->title.'</a>';
		}
		$ret[1][]='<a href="/admin/wizard/install">Install</a>';
		return $ret;
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
				'gs_base_handler.redirect_up:level:2',
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
				'gs_base_handler.redirect_if:gl:save_cancel:return:true',
				'gs_base_handler.post:{name:admin_form.html:classname:wz_urls:form_class:g_forms_table}',
				'gs_base_handler.redirect_if:gl:save_continue:return:true',
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
				'gs_base_handler.redirect_if:gl:save_cancel:return:true',
				'gs_wizard_handler.templatespost:return:true:name:admin_form.html:form_class:gs_wizard_template_form',
				'gs_base_handler.redirect_if:gl:save_continue:return:true',
				'gs_base_handler.redirect_up',
			),
			'/admin/wizard/formmacros'=>array(
				'wz_handler_mc.post:name:form_submit.html',
						),
			'/admin/wizard/choosetpl'=>array(
				'gs_wizard_handler.choosetpl:name:form_submit.html:form_class:form_choosetpl:return:true',
				'gs_base_handler.redirect_up',
				),
			'/admin/wizard/macros/list'=>array(
				'gs_wizard_handler.macros_list:name:macros_list.html',
				),
			'/admin/form/strategy'=>array(
				'gs_wizard_handler.strategy:return:true:name:form_submit.html:form_class:gs_wizard_strategy_form',
			),
		),
		'post'=>array(
			'/admin/wizard/iddqdblocksubmit'=>array(
						'gs_wizard_handler.iddqdblocksubmit:return:true',
						'gs_base_handler.redirect_gl:gl:iddqdblocksubmit',
						),
			'/admin/wizard/clone_urls'=>array(
				'gs_wizard_handler.clone_urls',
				'gs_base_handler.redirect',
			),

		),
		'get'=>array(
			'/admin/wizard'=>'gs_base_handler.show',
			'/admin/wizard/install'=>'gs_base_handler.show',
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
                        '/admin/wizard/templates/delete'=>array(
                                        'gs_wizard_handler.deletetemplate:return:true',
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
				if ($data['save_return']) return '/admin/wizard/module/'.$data['gspgid_va'][0];
				return null;
			break;
		}
	}
}


class gs_wizard_handler extends gs_handler {

	function commit($rec=null) {

		if ($rec['last']) $module=$rec['last'];
			else $module=record_by_id($this->data['gspgid_va'][0],'wz_modules');

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
	
	function clone_urls() {
		
		$ids=$this->data['manage'];
		$m_id=$this->data['module_id'];
		$urls=new wz_urls;
		$urls_new=new wz_urls;
		$r_urls=$urls->find_records(array('id'=>array_keys($ids)));
		foreach ($r_urls as $rec) {
				$values=$rec->get_values();
				if (isset($rec->get_recordset()->id_field_name)) unset($values[$rec->get_recordset()->id_field_name]);
				$values['Module_id']=$m_id;
				$r=$urls_new->new_record($values);
				$r_handlers=$rec->Handlers->find(array());
				foreach ($r_handlers as $rec_h) {
					$values=$rec_h->get_values();
					unset($values[$rec_h->get_recordset()->id_field_name]);
					$r->Handlers->new_record($values);
				}
		}
		$urls_new->commit();
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
	function deletetemplate() {
		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->data['gspgid_va'][1];
		return unlink($filename);
	}

	function templatespost() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=$f->clean();


		if(strpos($d['template_name'],'.')===FALSE) $d['template_name'].='.html';

		$module=record_by_id($this->data['handler_params']['Module_id'],'wz_modules');

		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$d['template_name'];
		if (file_exists($filename)) {
			return true;
		}

		$text="";
		if (!empty($d['extends'])) $text='{extends file="'.$this->data['extends'].'"}'.PHP_EOL;
		file_put_contents($filename,$text);

		if (empty($d['url'])) return true;	

		$template=array(
			"get"=>array(
				$d['url']=>array("gs_base_handler.show:name:".$d['template_name']),
				),
		);

		foreach ($template as $type=>$urls) {
			foreach ($urls as $url=>$handlers) {
				$f=$module->urls->find(array('gspgid_value'=>$url));
				if($f->count()) continue;
				$wz_url=$module->urls->new_record();
				$wz_url->gspgid_value=$url;
				$wz_url->type=$type;
				$cnt=0;
				foreach ($handlers as $key=>$value) {
					$cnt++;
					$wz_h=$wz_url->Handlers->new_record();
					$wz_h->cnt=$cnt;
					$wz_h->handler_keyname=$key;
					$wz_h->handler_value=$value;
				}
			}
		}
		$module->commit();



		return true;
	}
	function choosetpl() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;

		$handler=record_by_id($this->data['handler_params']['Handler_id'],'wz_handlers');

		$hv=preg_replace('|:name:[^:]+|','',$handler->handler_value);
		if ($f->clean('template_name')) $hv.=':name:'.$f->clean('template_name');

		$handler->handler_value=$hv;
		$handler->commit();

		return true;
	}
	function strategy() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=$f->clean();
		$filename=basename(dirname(__class_filename($d['strategy'])));	
		$href="/admin/wizard/".$filename."/".$d['recordset']."/".$this->data['handler_params']['Module_id'];
		return html_redirect($href);
	}


	function macros_list() {
		$tpl=gs_tpl::get_instance();
		$cl=class_members('wz_macros');
		$tpl->assign('macros_list',$cl);
		$bh=new gs_base_handler($this->data,$this->params);
		return $bh->show($this->data);
	}

}
class form_choosetpl extends g_forms_inline{
	function __construct($hh,$params=array(),$data=array()) {
		$module=record_by_id($data['handler_params']['Module_id'],'wz_modules');
		$dirname=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;
		$extends=array_map(basename,glob($dirname."*"));
		//array_unshift($extends,'');
		$hh=array(
		    'template_name' => Array
			(
			    'type' => 'select_enter',
			    'validate' => 'dummyValid',
			    'options' => array_combine($extends,$extends),
			),
		);
		return parent::__construct($hh,$params,$data);
	}

}

class wz_modules extends gs_recordset_short {
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'title'=> "fString 'название'",
		'recordsets'=>"lMany2One wz_recordsets:Module",
		'urls'=>"lMany2One wz_urls:Module",
		),$init_opts);
	}
}

class wz_recordsets extends gs_recordset_short {
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'title'=> "fString 'название'",
		'extends'=>"fString 'extends' required=false",
		'Module'=>'lOne2One wz_modules',
		'Fields'=>"lMany2One wz_recordset_fields:Recordset",
		'Links'=>"lMany2One wz_recordset_links:Recordset",
		'Submodules'=>"lMany2One wz_recordset_submodules:Recordset",
		'showadmin'=>"fCheckbox 'show in admin'",
		'no_urlkey'=>"fCheckbox 'No URL key'",
		),$init_opts);
	}
}
class wz_recordset_fields extends gs_recordset_short {
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'verbose_name'=> "fString verbose_name required=false",
		'type'=>"fSelect type values='fString,fText,fInt' widget=select",
		'multilang'=>"fCheckbox multilang",
		'options'=>"fString options required=false",
		'extra_options'=>"fString extra_options required=false",
		'widget'=>"fSelect widget required=false widget=select",
		'default_value'=>"fString default required=false",
		'required'=>"fCheckbox verbose_name=required",
		'Recordset'=>'lOne2One wz_recordsets',
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Recordset','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);



	}
	function gs_data_widget_select($rec,$field) {
		switch($field) {
			case 'type':
				$types=get_class_methods('field_interface');
				$types=array_combine($types,$types);
				$types=array_filter($types,create_function('$a','return  preg_match("|^f[A-Z]|",$a);'));
				return $types;
				break;
			case 'widget':
				$widgets=class_members('gs_widget');
				$widgets=str_replace('gs_widget_','',$widgets);
				array_unshift($widgets,'');
				$widgets=array_combine($widgets,$widgets);
				return $widgets;
				break;
		}
		return array();
	}

	function text($args,$rec) {

		$fields=$this->structure['htmlforms'];
		$ret="";
		foreach ($fields as $f=>$v) {
			if($rec->$f!='' && $f!='Recordset_id') $ret.=sprintf('%s="%s" ',$f,$rec->$f);
		}
		return $ret;
	}
}
class wz_recordset_links extends gs_recordset_short {
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'type'=>"fSelect type values='lOne2One,lMany2One,lMany2Many'",
		'classname'=>"fSelect classname widget=select_enter",
		'linkname'=>"fString linkname required=false",
		'verbose_name'=> "fString verbose_name required=false",
		'options'=>"fString options required=false",
		'extra_options'=>"fString extra_options required=false",
		'widget'=>"fSelect widget required=false widget=select",
		'required'=>"fCheckbox verbose_name=required",
		'fkey_on_delete'=>"fSelect on_delete values='NONE,RESTRICT,CASCADE,SET_NULL' widget=radio ",
		'fkey_on_update'=>"fSelect on_update values='NONE,RESTRICT,CASCADE,SET_NULL' widget=radio ",
		'fkey_name'=>"fString required=false",
		'Recordset'=>'lOne2One wz_recordsets',
		),$init_opts);
		$this->structure['triggers']['before_insert'][]='before_insert';
		$this->structure['fkeys']=array(
			array('link'=>'Recordset','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);




	}
	function before_insert($rec,$type) {
		if (!is_subclass_of($rec->classname,'wz_link')) return;

		$wzl=new $rec->classname;
		$type=$rec->type;

		$wzl->$type($rec);

	}
	function gs_data_widget_select($rec,$field) {
		switch($field) {
			case 'classname':

				$rs=new wz_recordsets();
				$rsets=$rs->find_records(array())->recordset_as_string_array();
				$rsets=array_combine($rsets,$rsets);
				$rsets=array_merge($rsets,class_members('gs_recordset_short'));

				$links=class_members('wz_link');

				$rsets=array(
					'magic'=>$links,
					'recordsets'=>$rsets,
					);
				return $rsets;
				break;
			case 'widget':
				$widgets=str_replace('gs_widget_','',class_members('gs_widget'));
				$widgets=array_combine($widgets,$widgets);
				$widgets=array_merge(array(''=>''),$widgets);
				return $widgets;
				break;
		}
		return array();
	}

	function text($args,$rec) {

		$fields=$this->structure['htmlforms'];
		$ret="";
		foreach ($fields as $f=>$v) {
			if($rec->$f!='' && $f!='Recordset_id') $ret.=sprintf('%s="%s" ',$f,$rec->$f);
		}
		return $ret;
	}
}
class wz_recordset_submodules extends gs_recordset_short {
	public $no_urlkey=1;
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
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'gspgid_value'=> "fString gspgid required=false unique=true",
		'type'=>'fSelect type values="get,handler,post"',
		'Module'=>'lOne2One wz_modules',
		'Handlers'=>"lMany2One wz_handlers:Url",
		),$init_opts);
	}
        function check_unique($field,$value,$params,$record=null) {
		$recs=$this->find_records(array($field=>$value));
		if ($recs->count()==0) return true;
		return $recs->first()->get_id()===$params['rec_id'];
        }

}
class wz_handlers extends gs_recordset_short {
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'cnt'=> "fInt cnt",
		'handler_keyname'=> "fString key",
		'handler_value'=>"fString value",
		'Url'=>'lOne2One wz_urls',
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Url','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
}
class gs_wizard_template_form extends g_forms_table{
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
		    'url' => Array
			(
			    'type' => 'input',
			    'validate' => 'dummyValid',
			),

		);
		return parent::__construct($hh,$params,$data);
	}

}
class gs_wizard_strategy_form extends g_forms_inline{
	function __construct($hh,$params=array(),$data=array()) {
		$module=record_by_id($data['handler_params']['Module_id'],'wz_modules');

				$rs=new wz_recordsets();
				$rsets=$rs->find_records(array())->recordset_as_string_array();

				$rsets=array(
					'module'=>$module->recordsets->recordset_as_string_array(),
					'wizard'=>$rsets,
					//'all'=>class_members('gs_recordset_short'),
					);
		$hh=array(
		    'recordset' => Array
			(
			    'type' => 'select',
			    'options'=>$rsets,
			),
		    'strategy' => Array
			(
			    'type' => 'select',
			    'options' => class_members('gs_wizard_strategy_module'),
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

require('macros.php');
require('links.php');

?>
