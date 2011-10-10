<?php
class module_wizard_createadmin extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/wizard/createadmin/form'=>array(
				'gs_strategy_createadmin_handler.createadmin:name:form.html:form_class:form_createadmin:return:gs_record',
				'gs_wizard_handler.commit:return:true',
				'gs_base_handler.redirect_up:{level:2}',
				),
			),
		'get'=>array(
			'/admin/wizard/createadmin'=>'gs_base_handler.show',
			),
		);
		return self::add_subdir($data,dirname(__file__));
	}
}
class gs_strategy_createadmin_handler extends gs_handler {
	function createadmin() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=$f->clean();

		$fields=new wz_recordset_fields();
		$fields->find_records(array('id'=>$d['fields']));
		$links=new wz_recordset_links();
		$links->find_records(array('id'=>$d['links']));
		$filters=new wz_recordset_links();
		$filters->find_records(array('id'=>$d['filters']));


		$rs=record_by_id($this->data['handler_params']['Recordset_id'],'wz_recordsets');
		$module=$rs->Module->first();



		$tpl=new gs_tpl();
		$tpl=$tpl->init();
		$tpl->left_delimiter='{%';
		$tpl->right_delimiter='%}';

		$tpl->assign('rs',$rs);
		$tpl->assign('module',$module);
		$tpl->assign('fields',$fields);
		$tpl->assign('links',$links);
		$tpl->assign('filters',$filters);


		$out=$tpl->fetch('file:'.dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$d['template_name']);


		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'adm_'.$rs->name.'.html';

		file_put_contents($filename,$out);

		$rs->showadmin=1;

		$modulename=$module->name;
		$recordsetname=$rs->name;

		$template=array(
			"get"=>array(
				"/admin/$modulename/$recordsetname"=>array("gs_base_handler.show:name:adm_$recordsetname.html"),
				"/admin/$modulename/$recordsetname/delete"=>array(
						"gs_base_handler.delete:{classname:$recordsetname}",
						"gs_base_handler.redirect",
						),
				),
			"handler"=>array(
				"/admin/form/$recordsetname"=>array(
					"gs_base_handler.post:{name:admin_form.html:classname:$recordsetname:form_class:form_admin}",
					"gs_base_handler.redirect_up:level:2",
					),
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
					//$wz_h->commit();
				}
			}
		}
		$rs->commit();
		//die();
		return $module;
	}
}
class form_createadmin extends form_admin{
	function __construct($hh,$params=array(),$data=array()) {
		$rs=record_by_id($data['handler_params']['Recordset_id'],'wz_recordsets');

		$module=$rs->Module->first();
		$dirname=dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR;
		$extends=array_map(basename,glob($dirname."*"));


		$hh=array(
		    'template_name' => Array
			(
			    'type' => 'select',
			    'options' => array_combine($extends,$extends),
			),
		    'fields' => Array
			(
			    'type' => 'checkboxes',
			    'options'=>$rs->Fields->recordset_as_string_array(),
			    'validate'=>'notEmpty',
			    'default'=>array_keys($rs->Fields->recordset_as_string_array()),
			),
		    'links' => Array
			(
			    'type' => 'checkboxes',
			    'options'=>$rs->Links->recordset_as_string_array(),
			    'validate'=>'notEmpty',
			    'default'=>array_keys($rs->Links->recordset_as_string_array()),
			),
		    'filters' => Array
			(
			    'type' => 'checkboxes',
			    'options'=>$rs->Links->recordset_as_string_array(),
			    'validate'=>'notEmpty',
			    'default'=>array_keys($rs->Links->recordset_as_string_array()),
			),
		);
		return parent::__construct($hh,$params,$data);
	}
}

