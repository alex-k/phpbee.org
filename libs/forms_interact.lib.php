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
	function display_if($condition, $eq='==') {
		foreach ($this->old_ret as $or) {
			if ($or['field']==$this->interactname && $or['action']=='hide') {
				$this->actions[]=array('field'=>$this->fieldname,'action'=>'hide');
				return;
			}
		}
		var_dump($condition);
		var_dump($this->value);
		$str='$res= ($condition '.$eq.' $this->value );';
		eval($str);
		$this->actions[]=array('field'=>$this->fieldname,'action'=>$res ? 'show' : 'hide');
	}
	function hide_if($condition) {
		$this->display_if($condition,'!=');
	}
	function link_values($condition) {
		list($rsname,$linkname)=explode('.',$condition);
		$rec=record_by_id($this->value,$rsname);
		foreach ($rec->$linkname as $r) {
			$data[$r->get_id()]=trim($r);
		}
		$this->form->set_variants($this->fieldname,$data);
		$this->form->_prepare_inputs();
		$html=($this->form->get_input($this->fieldname));
		//$html=$this->form->get_inputs();
		$this->actions[]=array('field'=>$this->fieldname,'action'=>'replace_element','html'=>$html);
	}
}

