<?php
ob_start();
require_once(dirname(__FILE__).'/../libs/config.lib.php');
$gs_node_id=1;
$cfg=gs_config::get_instance();
if (strpos($_SERVER['REQUEST_URI'],'/superadmin')===0) $init=new gs_init('superadmin');
	else $init=new gs_init('auto');

$init->init(LOAD_CORE | LOAD_STORAGE | LOAD_TEMPLATES);
$init->load_modules();

session_start();

$o_h=new gs_parser($init->data);
$o_h->process();


?>