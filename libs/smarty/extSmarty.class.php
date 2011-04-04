<?php

class extSmarty extends gs_Smarty
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
}
