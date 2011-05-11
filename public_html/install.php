<?php
require_once(dirname(__FILE__).'/../libs/config.lib.php');
$gs_node_id=1;
$init=new gs_init('user');
$init->init(LOAD_CORE | LOAD_STORAGE | LOAD_TEMPLATES);


$init->load_modules();
$init->install_modules();
gs_fkey::update_fkeys();

gs_logger::dump();

$a=new tw_articles();
for($i=0;$i<100;$i++) {
	$r=$a->new_record(array('name'=>rand(),'description'=>str_repeat(md5(rand()),100)));
	for ($j=0;$j<4;$j++) {
		$r->Images->new_record(array('Name'=>rand()));
	}
	$r->commit();
}

?>
