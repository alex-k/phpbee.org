<?php
class handler_multilang_base  extends gs_base_handler {
	function setlocale($ret) {
		$name=$this->params['name'];
		$value=$this->data[$name];
		$langs=languages();
		if(isset($langs[$value]))  {
			gs_session::save($value,'multilanguage_lang');
			if (class_exists('sys_languages')) {
				$rs=new sys_languages();
				$r=$rs->find_records(array('lang'=>$value))->first();
				if ($r) {
					gs_session::save($r->id,'filter_'.$name);
					//gs_session::save($r->locale,'multilanguage_locale');
					return $r;
				}
			}
		}
	}
	function setlocale_handler($ret) {
		$name=$this->params['name'];
		$filter=gs_filters_handler::get($name);
		if (!$filter) return;
		$f=$filter->current();
		gs_var_storage::save('multilanguage_lang',$f->lang);
		setlocale(LC_ALL,$f->locale);
	}
	function __setlocale($ret) {
		$name=$this->params['name'];
		if (isset($this->data[$name])) {
			$value=$this->data[$name];
			$langs=languages();
			if(isset($langs[$value])) {
				gs_session::save($value,'multilanguage_lang');
				if (class_exists('sys_languages')) {
					$rs=new sys_languages();
					$r=$rs->find_records(array('lang'=>$value))->first();
					if ($r) {
						gs_session::save($r->locale,'multilanguage_locale');
						//gs_session::save($r->id,'filter_'.$name);
						return $r;
					}
				}
			}

				
		}
		$s_lang=gs_session::load('multilanguage_lang');
		if ($s_lang) gs_var_storage::save('multilanguage_lang',$s_lang);
		//if (!$s_lang) $s_lang=key(languages());
		//gs_var_storage::save('multilanguage_lang',$s_lang);

		$s_locale=gs_session::load('multilanguage_locale');
		if ($s_locale) setlocale(LC_ALL,$s_locale);
	}
}
?>
