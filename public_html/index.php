<?php
ob_start();
if (class_exists('Phar',0) && file_exists(dirname(__FILE__).'/../gs_libs.phar.gz')) {
	require_once('phar://'.dirname(__FILE__).'/../gs_libs.phar.gz/config.lib.php');
} else {
	require_once(dirname(__FILE__).'/../libs/config.lib.php');
}
mlog('1');//starts time counter in debug


$init=new gs_init();
$init->init(LOAD_CORE);
$o_h=new gs_parser($init->data);
$o_h->process();

?>
