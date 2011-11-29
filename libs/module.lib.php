<?php
abstract class gs_base_module {
	static function add_subdir($data,$dir) {
		$subdir=trim(str_replace(cfg('lib_modules_dir'),'',clean_path($dir).'/'),'/');
		$d=array();
		foreach($data as $k=>$a) {
			foreach($a as $t=>$v) {
				if (strpos($t,'/')===0) {
					$d[$k][trim($t,'/')]=$v;
				} else {
					$d[$k][trim($subdir.'/'.$t,'/')]=$v;
				}
			}
		}
		return $d;
	}
	
	static function admin_auth($data,$params) {
		if (strpos($data['gspgid'],'admin')===0) {

			$admin_ip_access=cfg('admin_ip_access');

			if (!$admin_ip_access) return true;  // FREE ACCESS!!!!!!!

			if(is_array($admin_ip_access) && in_array($_SERVER['REMOTE_ADDR'],$admin_ip_access)) return true;
			$o=new admin_handler($data,array('name'=>'auth_error.html'));
			$o->show();
			return false;
		}
		return true;
	}

}
