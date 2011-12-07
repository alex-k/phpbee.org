<?php

class form_interact {
	var $actions=array();
	function __construct($form,$interact,$str) {
		$this->interactname=$interact;
		$this->form=$form;
		$this->code=$str;
		$form->validate();
		$this->data=$form->clean();
		$this->value=$form->clean($interact);
	}
	function i() {
		eval($this->code);
		return $this->actions;
	}

	function field($name) {
		$this->fieldname=$name;
		return $this;
	}
	function display_if($condition) {
		$this->actions[]=array('field'=>$this->fieldname,'action'=>($condition==$this->value) ? 'show' : 'hide');
	}
}

