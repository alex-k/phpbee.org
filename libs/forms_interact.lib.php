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
	function i($ret) {
		$this->old_ret=$ret;
		eval($this->code);
		return $this->actions;
	}

	function field($name) {
		$this->fieldname=$name;
		return $this;
	}
	function display_if($condition) {
		foreach ($this->old_ret as $or) {
			if ($or['field']==$this->interactname && $or['action']=='hide') {
				$this->actions[]=array('field'=>$this->fieldname,'action'=>'hide');
				return;
			}
		}
		$this->actions[]=array('field'=>$this->fieldname,'action'=>($condition==$this->value) ? 'show' : 'hide');
	}
}

