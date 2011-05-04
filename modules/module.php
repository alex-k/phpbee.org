<?php

class module  implements gs_module {
	function __construct() {}
	
	function install() {}
	
	static function get_handlers() {
		$data=array(
			'default'=>array(
				'default'=>'gs_base_handler.show404:{name:404.html}',
			),
			'get'=>array(
				''=>'gs_base_handler.show:{name:index.html}',
				'/admin'=>'admin_handler.show:{name:admin_page.html}',
				'/admin/menu'=>'admin_handler.show_menu',
			),
		);
		return self::add_subdir($data);
	}
	
	static function add_subdir($data) {
		$subdir=trim(str_replace(cfg('lib_modules_dir'),'',clean_path(dirname(__file__)).'/'),'/');
		$d=array();
		foreach($data as $k=>$a) {
			foreach($a as $t=>$v) {
				if (strpos($t,'/')===0) {
					$d[$k][trim($t,'/')]=$v;
				} else {
					$d[$k][rtrim($subdir.'/'.$t,'/')]=$v;
				}
			}
		}
		return $d;
	}
}

class admin_handler extends gs_base_handler {
	function show_menu () {
		$cfg=gs_config::get_instance();
		$modules=$cfg->get_registered_modules();
		$menu=array();
		if (is_array($modules)) foreach ($modules as $m) {
			$mod=new $m;
			if (method_exists($mod,'get_menu')) {
				$menu[]=$mod->get_menu();
			}
		}
		$tpl=gs_tpl::get_instance();
		$tpl->assign('menu',$menu);
		return $tpl->fetch('admin_menu.html');
	}
}