<?php

$config=gs_config::get_instance();
if (!class_exists('Smarty',FALSE)) load_file($config->lib_tpl_dir.'Smarty.class.php');

class gs_Smarty extends Smarty {
	function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false) {
		if(!is_string($template)) return parent::fetch($template, $cache_id , $compile_id , $parent);
		mlog($template);
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
	function multilang($tplname=4) {
		mlog($tplname);
		$language=false;
		if (!$language) $language=gs_var_storage::load('multilanguage_lang');
		if (!$language) $language=gs_session::load('multilanguage_lang');
		if (!$language) $language=cfg('multilang_default_language');

		mlog($language);

		if ($language) {
				$newtplname=dirname($tplname).DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.(basename($tplname));
				if (file_exists($newtplname)) {
					$tplname=$newtplname;
					$old_tpl_dir=$dir=$this->getTemplateDir();
					if (!is_array($dir)) $dir=array($dir);
					array_unshift($dir,'.',dirname($newtplname));
					$this->setTemplateDir($dir);
				}
		}
		mlog($tplname);
		return $tplname;
	}

}
class extSmarty extends gs_Smarty {}
