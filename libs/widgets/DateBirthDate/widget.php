<?php

class gs_widget_DateBirthDate extends gs_widget{
	function html() {
		$tpl=gs_tpl::get_instance();
		$tpl->template_dir[]=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
		$params=$this->params;
		$params['value']=$this->value;
		$params['fieldname']=$this->fieldname;

		$tpl->assign('params',$params);
		$tpl->assign('data',$this->data);

		return $tpl->fetch('widget.html');

	}
	function clean() {
		$ret=date('Y-m-d',strtotime(sprintf('%d-%d-%d',
					$this->value['Date_Year'],
					$this->value['Date_Month'],
					$this->value['Date_Day']
					)));
		return $ret;
	}

}
