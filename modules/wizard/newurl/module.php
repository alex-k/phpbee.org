<?php
class module_wizard_newurl extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/wizard/newurl/form'=>array(
				'handler_wizard_newurl.form:name:newurl_form.html:form_class:form_wizard_newurl:return:gs_record',
				//'gs_wizard_handler.commit:return:true',
				'gs_base_handler.redirect_gl:gl:back',
				),
			),
		'get'=>array(
			'/admin/wizard/newurl'=>'gs_base_handler.show:name:newurl.html',
			),
		);
		return self::add_subdir($data,dirname(__file__));
	}
	static function gl($name,$record,$data) {
		switch ($name) {
			case 'back':
				return '/admin/wizard/module/'.$data['module'];
			break;
		}
	}
}
class handler_wizard_newurl extends gs_handler {
	function form() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=gs_base_handler::explode_data($f->clean());

		$module=record_by_id($d['module'],'wz_modules');
		$url=$module->urls->find_records(array('Module_id'=>$module->get_id(),'gspgid_value'=>$d['url'],'type'=>$d['type']))->first(TRUE);
		$url->Handlers->delete();
		$cnt=0;
		foreach(explode("\n",$d['handlers']) as $h) {
			$cnt+=10;
			if($h) $url->Handlers->new_record(array('cnt'=>$cnt,'handler_value'=>$h));
		}
		$url->commit();
		return $url;
	}
}
class form_wizard_newurl extends g_forms_table{
	function __construct($hh,$params=array(),$data=array()) {
		$modules=new wz_modules;
		$modules->find_records(array());
		$hh=array(
			'type'=>array(
				'widget'=>'radio',
				'options'=>'get,handler,post',
				),
			'module'=>array(
				'widget'=>'radio',
				'options'=>$modules->recordset_as_string_array(),
				),
			'url'=>array(
				'verbose_name'=>'url/gspgid',
				'widget'=>'input',
				) ,
			'handlers'=>
				array(
				'widget'=>'TextLines',
				) ,
		);
		return parent::__construct($hh,$params,$data);
	}
}

