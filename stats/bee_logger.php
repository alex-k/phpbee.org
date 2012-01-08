#!/usr/local/bin/php
<?php
require_once('vpa_daemon.lib.php');
date_default_timezone_set('Europe/Moscow');
error_reporting(E_ALL | E_STRICT);
$config=array(
		'host'=>'195.182.131.62',
		'port'=>8080,
	);

class bee_logger_daemon extends vpa_daemon_base {
	
	protected $config;
	protected $db_conn;
	
	function __construct($name,$config) {
		$this->config=$config;
		parent::__construct($name);
	}
	
	function init() {
			file_put_contents('sites.log','bee_logger_daemon::init'.PHP_EOL,FILE_APPEND);
	}
	
	function main() {
		$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_bind($socket, $this->config['host'], $this->config['port']);

		$from = '';
		$port = 0;
		while (true) {
			socket_recvfrom($socket, $buf, 128, 0, $from, $port);
			//echo $buf.PHP_EOL;
			file_put_contents('sites.log',$buf.PHP_EOL,FILE_APPEND);
		}
	}
	
	function quit() {
	}
}


$daemon=new bee_logger_daemon('bee_logger',$config);
$daemon->start();

?>
