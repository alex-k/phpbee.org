<?php

$config=gs_config::get_instance();
load_file($config->lib_tpl_dir.'Smarty.class.php');

class gs_Smarty extends Smarty {
	function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false) {
		if(!is_string($template)) return parent::fetch($template, $cache_id , $compile_id , $parent);
		$id=md5($template);
		if (!isset($this->_tpl_arr[$id])) {
			if (!$this->templateExists($template)) {
				throw new gs_exception('gs_base_handler.show: can not find template file for '.$template);
			}
			$this->_tpl_arr[$id]=$this->createTemplate($template, $cache_id , $compile_id , $parent);
		}
		$t=$this->_tpl_arr[$id];
		$t->assign($this->getTemplateVars());
		return $t->fetch();
	}
	function get_var($name) {
		$t=reset($this->_tpl_arr);
		return  ($t && isset($t->tpl_vars[$name])) ? $t->tpl_vars[$name]->value : NULL;
	}

}
class extSmarty extends gs_Smarty {}
