<?
	$this->gs_connectors=array (
			'mysql'=>array( 'db_type'=>'mysql', 'db_hostname'=>'127.0.0.1','db_port'=>'3306','db_username'=>'root','db_password'=>'root','db_database'=>'test'),
			);

	date_default_timezone_set('Europe/Moscow');
	$this->mail_smtp_host='127.0.0.1';
	$this->mail_smtp_port='25';
	$this->mail_smtp_username='';
	$this->mail_smtp_password='';
	$this->mail_smtp_auth=0;
	$this->mail_from='info';
	$this->mail_type='smtp';

	DEFINE ('DEBUG',1);
?>
