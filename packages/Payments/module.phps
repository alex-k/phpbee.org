<?php
gs_dict::append(array(
	));

class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array(
				) as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		$ret=array();
		$item=array();
		$item[]='<a href="/admin/Payments/">Payments</a>';
				$ret[]=$item;
		return $ret;
	}
	
	static function get_handlers() {
		$data=array(
'get'=>array(
'/pay'=>array(
  'gs_base_handler.check_login:classname:Users:return:gs_record^e404', 
  'gs_base_handler.show:name:pay.html', 
 'end'=> 'end', 
 'e404'=> 'gs_base_handler.redirect:href:/login', 
),
'return'=>array(
  'payments_handler.payment_completed:return:true&approved^declined', 
 'approved'=> 'gs_base_handler.check_login:classname:Users:assign:User:return:gs_record^error', 
  'allflac_handler.addfunds:return:gs_record^error', 
  'gs_base_handler.redirect_gl:gl:payment_approved', 
 'end'=> 'end', 
 'declined'=> 'gs_base_handler.redirect_gl:gl:payment_declined', 
 'end'=> 'end', 
 'error'=> 'gs_base_handler.redirect_gl:gl:payment_error', 
),
'approved'=>array(
 ''=> 'gs_base_handler.show:name:payment_approved.html', 
),
'declined'=>array(
  'gs_base_handler.show:name:payment_declined.html', 
),
'error'=>array(
  'gs_base_handler.show:name:payment_error.html', 
),
),
		);
		return self::add_subdir($data,dirname(__file__));
	}

	static function gl($alias,$rec) {
		$fname=dirname(__FILE__).DIRECTORY_SEPARATOR.'gl.php';
		if (file_exists($fname)) {
			$x=include($fname);
			return $x;
		}
	}

	/*
	static function gl($alias,$rec) {
		if(!is_object($rec)) {
			$obj=new tw{%$MODULE_NAME%};
			$rec=$obj->get_by_id(intval($rec));
		}
		switch ($alias) {
			case '___show____':
				return sprintf('/{%$MODULE%}/show/%s/%d.html',
						date('Y/m',strtotime($rec->date)),
						$rec->get_id());
			break;
		}
	}
	*/
}
/*
class handler{%$MODULE_NAME%} extends gs_base_handler {
}
*/






?>
