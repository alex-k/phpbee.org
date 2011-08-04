<?php
ob_start();
if (class_exists('Phar',0) && file_exists(dirname(__FILE__).'/../gs_libs.phar.gz')) {
	require_once('phar://'.dirname(__FILE__).'/../gs_libs.phar.gz/config.lib.php');
} else {
	require_once(dirname(__FILE__).'/../libs/config.lib.php');
}
$gs_node_id=1;
$cfg=gs_config::get_instance();
mlog('1');

if (strpos($_SERVER['REQUEST_URI'],'/superadmin')===0) $init=new gs_init('superadmin');
	else $init=new gs_init('auto');
cfg_set('tpl_data_dir',array(
	cfg('tpl_data_dir'),
	realpath(cfg('root_dir').'html'),
));
//$init->init(LOAD_CORE | LOAD_STORAGE | LOAD_TEMPLATES | LOAD_EXTRAS);
//$init->load_modules();
$init->init(LOAD_CORE);
session_start();

$o_h=new gs_parser($init->data);
$o_h->process();



?>
