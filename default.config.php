<?php
	$this->gs_connectors=array (
			'mysql'=>array( 
				'db_type'=>'mysql',
				'db_hostname'=>'127.0.0.1',
				'db_port'=>'3306',
				'db_username'=>'root',
				'db_password'=>'root',
				'db_database'=>'test',
				'codepage'=>'utf8',
				),
			'wizard'=>array( 
				'db_type'=>'sqlite',
				'db_file'=>$this->var_dir.'wizard.db',
				),
			'file_public'=>array( 
				'db_type'=>'file',
				'db_root'=>$this->document_root.'files',
				'www_root'=>'/files',
				),
			'handlers_cache'=>array( 
				'db_type'=>'file',
				'db_root'=>$this->var_dir.'handlers_cache/',
				),
			);

	date_default_timezone_set('Europe/Moscow');
	setlocale(LC_ALL,'ru_RU.UTF-8');
	$this->mail_smtp_host='127.0.0.1';
	$this->mail_smtp_port='25';
	$this->mail_smtp_username='';
	$this->mail_smtp_password='';
	$this->mail_smtp_auth=0;
	$this->mail_from='info';
	$this->mail_type='smtp';

	$this->languages=NULL;
	//$this->languages=array('ru'=>'RUS','en'=>'ENG');
	//$this->languages='tw_languages';

	$this->admin_ip_access=explode(',','127.0.0.1,192.168.1.102');

	DEFINE ('DEBUG',1);
?>
