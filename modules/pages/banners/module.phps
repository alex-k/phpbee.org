{%capture assign=DATA%}
	LINKS::Banners::lMany2Many tw{%$MODULE_NAME%}:link{%$MODULE_NAME%} 'Баннеры' required=false
{%/capture%}
<?php
class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('tw{%$MODULE_NAME%}') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		return '<a href="/admin/banners/">Баннеры</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'default'=>array(
			'default'=>'gs_base_handler.show404:{name:404.html}',
		),
		'get_post'=>array(
			'list'=>'gs_base_handler.show',
			'/admin/banners'=>'gs_base_handler.show:{name:adm_banners.html:classname:tw{%$MODULE_NAME%}}',
			'/admin/form/tw{%$MODULE_NAME%}'=>array(
					'gs_base_handler.post:return:gs_record:{name:form.html:form_class:g_forms_table:classname:tw{%$MODULE_NAME%}:href:/admin/banners:form_class:form_admin}',
					'gs_base_handler.redirect:{href:/admin/banners}',
			),
			'/admin/banners/delete'=>'admin_handler.deleteform:{classname:tw{%$MODULE_NAME%}}',
		),
	);
	return self::add_subdir($data,dirname(__file__));
	}
}

class handler{%$MODULE_NAME%} extends gs_base_handler {
}

class tw{%$MODULE_NAME%} extends gs_recordset_short {
	const superadmin=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'subject'=>"fString 'Название'",
		'Image'=>"fFile 'Баннер'",
		'Pages'=> "lMany2Many tw_{%$PARENT_MODULE%}:link{%$MODULE_NAME%}",
		{%foreach from=$SUBMODULES_DATA.LINKS key=K item=L%}
			'{%$K%}'=>"{%$L%}",
		{%/foreach%}
		),$init_opts);
	}
}

?>
