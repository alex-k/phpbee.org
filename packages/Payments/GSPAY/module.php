<?php

class payments_gw_gspay extends payments_gateway {

	function validate() {
		$this->validate_result=$this->checkGspayOrder($this->data['transactionTransactionID']);
		return $this->validate_result['info']['RESULTSTATUS']=='approved';
		
	}
	function get_transaction_number() {
		return $this->validate_result['info']['TRANSACTIONID'];

	}
	function get_transaction_message() {
		return $this->validate_result['info']['TRANSACTIONMESSAGE'];
	}
	function get_merchant_order_id() {
		return $this->validate_result['info']['ORDERID'];
	}
	function get_transaction_amount() {
		return sprintf("%.02f",$this->validate_result['info']['TRANSACTIONAMOUNT']);
	}

	function checkGspayOrder($transactionTransactionID) {

		$ret=array();
		$ret['info']['RESULTSTATUS']=NULL;
		$ret['info']['TRANSACTIONID']=NULL;

	      $url="https://secure.redirect2pay.com/payment/api.php";
		$values=array(
		'request'=>"
			<xml>
			<request>
			<transaction>
				<transactionType>transactionStatus</transactionType>
				<transactionTransactionID>".$transactionTransactionID."</transactionTransactionID>
			</transaction>

			
			</request>
			</xml>
		
		",
		);


		$ret['request']=$values;

		try {
			$result=$ret['result']=html_fetch($url,$values,'POST');
		} catch (gs_exception $e) {
			$ret['ERROR']=$e->get_message();
			return $ret;
		}



		$requestxml=$result;
		$p = xml_parser_create();
		if (!  xml_parse_into_struct($p, $requestxml, $vals, $index)) {
			$ret['ERROR']='can not parse XML result';
			return $ret;
		}
		xml_parser_free($p);


		if (is_array($vals)) foreach ($vals as $key=>$value) {
			if ($value[type]=='complete') {
				$transinfo[$value[tag]]=trim($value[value]);
			}
		}

		$ret['info']=$transinfo;

		return $ret;


	}



}
