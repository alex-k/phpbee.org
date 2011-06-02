<?php
require_once(dirname(__FILE__).'/../libs/config.lib.php');
$gs_node_id=1;
$init=new gs_init('user');
$init->init(LOAD_CORE | LOAD_STORAGE | LOAD_TEMPLATES);


$init->compile_modules();
$init->load_modules();
$init->install_modules();
gs_fkey::update_fkeys();

gs_logger::dump();

?>
