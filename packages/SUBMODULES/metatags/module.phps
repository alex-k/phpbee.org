{%capture assign=DATA%}
	FIELDS::title::fString Заголовок required=false
	FIELDS::keywords::fText Keywords required=false
	FIELDS::description::fString Description required=false
{%/capture%}
<?php
class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array() as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		return '';
	}
	
	static function get_handlers() {
		$data=array('get_post'=>array(),);
		return self::add_subdir($data,dirname(__file__));
	}
}

class handler{%$MODULE_NAME%} extends gs_base_handler {
}

?>
