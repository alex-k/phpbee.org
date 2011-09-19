<?php


class module_wizard extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('wz_modules','wz_recordsets','wz_recordset_fields','wz_urls','wz_handlers') as $r){
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
			'/admin/form/wz_recordset_fields'=>array(
				'gs_base_handler.post:{name:form.html:classname:wz_recordset_fields:form_class:form_admin}',
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
		),
		'get'=>array(
			'/admin/wizard'=>'gs_base_handler.show',
			'/admin/wizard/commit'=>'gs_wizard_handler.commit',
			'/admin/wizard/recordsets'=>'gs_base_handler.show',
			'/admin/wizard/recordset_fields'=>'gs_base_handler.show',
			'/admin/wizard/urls'=>'gs_base_handler.show',
			'/admin/wizard/handlers'=>'gs_base_handler.show',
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

		),
	);
	return self::add_subdir($data,dirname(__file__));
	}
}

class gs_wizard_handler extends gs_handler {

	function commit() {
		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');
		$dirname=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR;
		check_and_create_dir($dirname);
		check_and_create_dir($dirname.'templates');

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

		header("Content-type: text/plain");
		echo $out;

		file_put_contents($dirname.'module.phps',$out);


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
		'type'=>"fSelect type values='fString,fText,fInt'",
		'options'=>"fString options required=false",
		'widget'=>"fSelect widget required=false",
		'default_value'=>"fString default required=false",
		'required'=>"fCheckbox verbose_name=required",
		'Recordset'=>'lOne2One wz_recordsets',
		),$init_opts);


		$types=get_class_methods('field_interface');
		$types=array_filter($types,create_function('$a','return  preg_match("|^f[A-Z]|",$a);'));

		$this->structure['htmlforms']['type']['options']=array_combine($types,$types);

		$widgets=gs_cacher::load('classes','config');
		$widgets=array_filter(array_keys($widgets),create_function('$a','return  is_subclass_of($a,"gs_widget");'));
		$widgets=str_replace('gs_widget_','',$widgets);
		array_unshift($widgets,'');

		$this->structure['htmlforms']['widget']['options']=array_combine($widgets,$widgets);
	}
}
class wz_urls extends gs_recordset_short {
	function __construct($init_opts=false) { parent::__construct(array(
		'gspgid_value'=> "fString gspgid",
		'type'=>'fSelect type values="get,handler,post"',
		'Module'=>'lOne2One wz_modules',
		'Handlers'=>"lMany2One wz_handlers:Url",
		),$init_opts);
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

