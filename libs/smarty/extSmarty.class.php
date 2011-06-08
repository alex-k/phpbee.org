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
class ___extSmarty extends gs_Smarty
{
    /**
     * Список зарегистрированных блоков в шаблонизаторе
     *
     * @var  array
     */
    protected $_blocks = array();

    /**
     * Конструктор класса
     *
     * @param   void
     * @return  void
     */
    public function __construct()
    {
        $this->Smarty();
    }

    /**
     * Регистрирует наследуемый блок шаблона
     *
     * @param   string  $key
     * @param   string  $value
     * @return  void
     */
    public function setBlock($key, $value)
    {
        if (array_key_exists($key, $this->_blocks) === false) {
            $this->_blocks[$key] = array(); 
        }

        if (in_array($value, $this->_blocks[$key]) === false) {
            array_push($this->_blocks[$key], $value);
        }
    }

    /**
     * Возвращает код блока согласно иерархии наследования
     *
     * @param   string  $key
     * @return  string
     */
    public function getBlock($key)
    {
        if (array_key_exists($key, $this->_blocks)) {
            return $this->_blocks[$key][count($this->_blocks[$key])-1];
        }

        return '';
    }

	public function templateExists($name) {
	       return $this->template_exists($name);
	}
	public function getTemplateVars($name) {
	       return $this->get_template_vars($name);
	}

}
