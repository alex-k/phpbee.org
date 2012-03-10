<?php
class module_wizard_manager extends gs_wizard_strategy_module implements gs_module {
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/wizard/manager/login'=>array(
				'handler_wizard_manager.form_login:name:admin_form.html:form_class:form_wizard_manager_login:return:true',
				//'gs_wizard_handler.commit:return:true',
				'gs_base_handler.redirect_gl:gl:forms',
				),
			'/admin/wizard/manager/forms'=>array(
				'handler_wizard_manager.form_forms:name:forms_form.html:form_class:form_wizard_manager_forms:return:true',
				//'gs_wizard_handler.commit:return:true',
				),
			),
		'get'=>array(
			'/admin/wizard/manager'=>'gs_base_handler.show:name:login.html',
			'/admin/wizard/manager/forms'=>'gs_base_handler.show:name:forms.html',
			),
		);
		return self::add_subdir($data,dirname(__file__));
	}
	static function gl($name,$record,$data) {
		switch ($name) {
			case 'back':
				return '/admin/wizard/module/'.$data['module'];
			case 'forms':
				return '/admin/wizard/manager/forms/'.$data['handler_params']['Recordset_id'].'/'.$data['handler_params']['Module_id'];
			break;
		}
	}
}
class handler_wizard_manager extends gs_handler {
	function form_login() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=gs_base_handler::explode_data($f->clean());
		gs_session::save($d,'handler_wizard_manager_form_login');
		return true;

	}
	function form_forms() {
		$d=gs_session::load('handler_wizard_manager_form_login');
		$bh=new gs_base_handler(array_merge($d,$this->data),$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=gs_base_handler::explode_data($f->clean());
		md($d['recordset'],1);
		die();

	}
}
class form_wizard_manager_login extends g_forms_table{
	function __construct($hh,$params=array(),$data=array()) {
		$rs=record_by_id($data['handler_params']['Recordset_id'],'wz_recordsets');

		$module=record_by_id($data['handler_params']['Module_id'],'wz_modules');
		$dirname=dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'createlogin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR;
		$login_templates=array_map(basename,glob($dirname."*"));

		$login_fields=$rs->Fields->recordset_as_string_array();
		$login_fields=array_combine($login_fields,$login_fields);

		$recordsets=new wz_recordsets;
		$recordsets=$recordsets->find_records(array())->orderby('name')->recordset_as_string_array();




		$forms=class_members('g_forms');
		$hh=array(
		    'login_form_template_name' => Array
			(
			    'type' => 'select',
			    'options' => array_combine($login_templates,$login_templates),
			    'default'=>'default_form.html',
			),
		    'login_fields' => Array
			(
			    'type' => 'checkboxes',
			    'options'=>$login_fields,
			    'validate'=>'notEmpty',
			),
		    'recordsets' => Array
			(
			    'type' => 'multiselect',
			    'options'=>$recordsets,
			    'validate'=>'notEmpty',
			),
		);
		return parent::__construct($hh,$params,$data);
	}
}
class form_wizard_manager_forms extends g_forms_table{
	function __construct($hh,$params=array(),$data=array()) {
		$rs=record_by_id($data['handler_params']['Recordset_id'],'wz_recordsets');

		$module=$rs->Module->first();
		$dirname=dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'createadmin'.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR;
		$page_templates=array_map(basename,glob($dirname."*"));


		foreach ($data['recordsets'] as $rs_id) {
			$rs=record_by_id($rs_id,'wz_recordsets');
			$all_links=$rs->Links->recordset_as_string_array();
			$links=$rs->Links->find(array('type'=>array('lMany2Many','lOne2One')))->recordset_as_string_array();
			$extlinks=$rs->Links->find(array('type'=>'lMany2One'))->recordset_as_string_array();
			if(!is_array($links)) $links=array();
			if(!is_array($extlinks)) $extlinks=array();
			if(!is_array($all_links)) $all_links=array();


				$hh['recordset:'.$rs_id.':recordset'] = Array
				(
					'type' => 'checkbox',
					'verbose_name'=>$rs->name,	
					'validate'=>'dummyValid',
					'default'=>$rs->name,
				);
				$hh['recordset:'.$rs_id.':page_template_name'] = Array
				(
					'type' => 'select',
					'options' => array_combine($page_templates,$page_templates),
				);
				$hh['recordset:'.$rs_id.':fields'] = Array
				(
					'type' => 'checkboxes',
					'options'=>$rs->Fields->recordset_as_string_array(),
					'validate'=>'notEmpty',
					'default'=>array_keys($rs->Fields->recordset_as_string_array()),
				);
				$hh['recordset:'.$rs_id.':links'] = Array
				(
					'verbose_name'=>'show values',	
					'type' => 'checkboxes',
					'options'=>$all_links,
					'validate'=>'notEmpty',
					'default'=>array_keys($links),
				);
				$hh['recordset:'.$rs_id.':extlinks'] = Array
				(
					'verbose_name'=>'show links',	
					'type' => 'checkboxes',
					'options'=>$all_links,
					'validate'=>'notEmpty',
					'default'=>array_keys($extlinks),
				);
				$hh['recordset:'.$rs_id.':filters'] = Array
				(
					'type' => 'checkboxes',
					'options'=>$all_links,
					'validate'=>'notEmpty',
					'default'=>array_keys($links),
				);

				$fields_data=$data;
				$fields_data['handler_params']['Recordset_id']=$rs->get_id();

				$fields_form=new form_createform_tpl($hh,$params,$fields_data);

				$rs2=record_by_id($rs_id,'wz_recordsets');

				foreach ($fields_form->htmlforms as $k=>$v) {
					if ($v['name']=='enabled') {
						$v['default']=0;
						$fname=end(explode(':',$fields_form->htmlforms[str_replace('enabled','name',$k)]['default']));
						$fname=preg_replace('/_id$/','',$fname);
						if (in_array($fname,$rs->Fields->recordset_as_string_array())) $v['default']=1;
						$links_121=$rs2->Links->find(array('type'=>array('lOne2One'),'required'=>1,'name'=>$fname));
						if ($links_121->count()) $v['default']=1;
					}
					$hh['recordset:'.$rs_id.':formfields:'.$k]=$v;
				}

		}
		return parent::__construct($hh,$params,$data);
	}
}

