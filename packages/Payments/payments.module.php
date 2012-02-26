<?php

class payments_handler extends gs_handler {

	function payment_completed($ret) {

		$classname="payments_gw_".$this->data['gspgid_va'][0];
		$gateway=new $classname($this->data,$this->params);
		$result=$gateway->validate();

		gs_var_storage::save('payments_gateway',$gateway);

		return $result;
		
	}
}

abstract class payments_gateway {
	function __construct($data,$params) {
		$this->data=$data;
		$this->params=$params;
	}
	function validate() {
		return FALSE;
	}
	function get_transaction_number() {
		return NULL;
	}

}
