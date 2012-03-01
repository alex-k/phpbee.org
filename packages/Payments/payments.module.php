<?php

class payments_handler extends gs_handler {

	function trigger_payment_changed($rec) {
		$o=$rec->Order->first();
		$o->payment_status=$rec->status;
		$o->commit();

		if (!$rec->profits_created && $rec->status=='approved') {

			$totals=array('Production'=>array('amount'=>$o->production_cost));

			$sellers=array('Place','Photograph','Manager');
			$minus_before_percent=array('Production');
			$minus_after_percent=array('Place','Photograph','Manager');

			foreach ($sellers as $link) {
				$seller=$o->$link->first();
				if ($seller && $seller->comission) {
					md('--------',1);
					md($link,1);
					md($seller->comission,1);
					md($seller->fees,1);
					$amount=$o->amount;

					$fees=explode(',',$seller->fees);
					if ($link=='Manager' && $o->Photograph->first()) { 
						$fees[]='Photograph';
					}

					foreach ($minus_before_percent as $fee) {
						if(in_array($fee,$fees) && isset($totals[$fee])) $amount-=$totals[$fee]['amount'];
					}
					$totals[$link]['comission']=$seller->comission;
					$amount=$amount*$seller->comission*.01;

					foreach ($minus_after_percent as $fee) {
						if(in_array($fee,$fees) && isset($totals[$fee])) $amount-=$totals[$fee]['amount'];
					}

					$totals[$link]['fees']=implode(',',$fees);
					$totals[$link]['amount']=$amount;
					$transactions[$link]['info']=$totals[$link];
					$transactions[$link]['seller']=$seller;
				}
			}

			$o->profits_info=$totals;
			foreach ($transactions as $link => $tr) {
				$profit=$rec->Profits->new_record();
				$profit->order_amount=$rec->amount;
				$profit->order_type=$rec->type;
				$profit->amount=$tr['info']['amount'];
				$lname=$link.'_id';
				$profit->$lname=$tr['seller']->get_id();
				$profit->Currency_id=$rec->Currency_id;
			}
			$rec->Profits->commit();


			$rec->profits_info=var_export($totals);
			$rec->profits_created=1;
	
		}

	}

	function payment_completed($ret) {

		$classname="payments_gw_".$this->data['gspgid_va'][0];
		$gateway=new $classname($this->data,$this->params);
		$result=$gateway->validate();

		$pmnt=record_by_id($gateway->get_payment_id(),'payments');
		$pmnt->status=$gateway->get_transaction_status();
		$pmnt->status_message=$gateway->get_transaction_message();
		$pmnt->Order->first()->payment_status=$pmnt->status;

		$pmnt->commit();

		gs_var_storage::save('payments_gateway',$gateway);

		return $result;
		
	}
	function start_payment($ret) {

	
		$classname="payments_gw_".$this->data['gspgid_va'][0];
		$order=gs_session::load($this->data['gspgid_va'][1]);
		$method=$order->Payment_method->first();
		$pmnt=$order->Payments->new_record();
		$pmnt->Currency_id=$order->Currency_id;
		$amount=$order->amount;
		if ($method->Currency->first()) {
			$curr_from=$order->Currency->first()->code;
			$curr_to=$method->Currency->first()->code;
			if ($curr_to!=$curr_from) {
				$amount=currency_converter::convert_google($amount.$curr_from,$curr_to);
				$pmnt->Currency_id=$method->Currency_id;
			}
		}
		$pmnt->amount=$amount;
		$pmnt->Payment_method_id=$order->Payment_method_id;
		$pmnt->status='new';
		$pmnt->type='sale';
		$pmnt->invoiceID=$order->number;
		$items=array();

		foreach ($order->Cart->Images as $i) {
			foreach ($i->Prices as $p) {
				//$items[]=sprintf("Image #%d, %s, qty: %d, %.02f %s",$i->get_id(),$p->title,$p->quantity,$p->price*$p->quantity,$p->Currency);
				$items[]=sprintf("Image %d, %s, qty: %d",$i->get_id(),$p->title,$p->quantity);
			}

		}
		$pmnt->description=implode("\r\n",$items);

		$order->commit();

		$gateway=new $classname($this->data,$this->params);

		gs_var_storage::save('payments_gateway',$gateway);
		gs_var_storage::save('payment',$pmnt);

		$result=$gateway->start($pmnt);

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
