<?php


if (!class_exists('gs_base_handler',0)) {
	date_default_timezone_set('GMT');
	require_once(dirname(__FILE__).'/../../../../../config.lib.php');
	$cfg=gs_config::get_instance();
	$init=new gs_init('auto');
	$init->init(LOAD_CORE | LOAD_STORAGE | LOAD_TEMPLATES | LOAD_EXTRAS);
}


if(isset($_FILES['Filedata'])) {
	$rs_name=$_REQUEST['recordset'];
	$f_name=$_REQUEST['foreign_field_name'];
	$f_hash_name=$f_name.'_hash';
	

	$f=new $rs_name;
	$f=$f->new_record();

	$f->$f_hash_name=$_REQUEST['hash'];
	$f->$f_name=$_REQUEST['rid'];

	$values=$_FILES['Filedata'];


	$ret=array(
			'File_data'=>file_get_contents($values['tmp_name']),
			'File_filename'=>$values['name'],
			'File_mimetype'=>$values['type'],
			'File_size'=>$values['size'],
			'File_width'=>max($_REQUEST['thumbnailWidth'],$_REQUEST['imageWidth']),
			'File_height'=>max($_REQUEST['thumbnailHeight'],$_REQUEST['imageHeight']),
		 );
	
	$ff=$f->File->new_record($ret);

	$f->commit();
	
	echo $f->src1('admin');
}
