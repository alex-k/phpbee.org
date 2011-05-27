<?php
gs_dict::append(array(
		'LOAD_IMAGES'=>'добавить картинки',
	));


class module_images  extends gs_base_module implements gs_module {
	function __construct() { }
	function install() {
		foreach(array('tw_file_images') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	function get_menu() {
		return '<a href="/admin/file_images/">Images</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'default'=>array(
			'default'=>'gs_base_handler.show404:{name:404.html}',
		),
		'get_post'=>array(
			''=>'gs_base_handler.show:{name:images.html}',
			'/admin/file_images'=>'gs_base_handler.show:{name:adm_images.html:classname:tw_file_images}',
			'/admin/form/tw_file_images'=>'gs_base_handler.post:{name:form.html:classname:tw_file_images:form_class:form_admin}',
			'/admin/file_images/delete'=>'admin_handler.deleteform:{classname:tw_file_images}',
		),
	);
	return self::add_subdir($data,dirname(__file__));
	}
}

class tw_file_images extends gs_recordset_short{
	var $gs_connector_id='file_public';
	var $table_name='images';
	var $fields=array(
		'File'=> "fFile 'Файлик'",
		'Name'=> "fString 'Названице' ",
		//'desc'=> "fText",
	);
	function __construct($init_opts=false) {
		parent::__construct($this->fields,$init_opts);
	}
}



?>
