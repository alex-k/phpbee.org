<?php
class gs_fkey {
	

	function get_instance() {
		static $instance;
		if (!isset($instance)) $instance = new gs_fkey();
		return $instance;
	}

	function reset() {
		$this->key_array=array();
		$this->__destruct();
	}

	function __construct() {
		$this->key_array= ($n=gs_cacher::load('gs_fkey_array','gs_recordset')) ? $n : array();
	}
	function __destruct() {
		gs_cacher::save($this->key_array,'gs_recordset','gs_fkey_array');
		md($this->key_array,1);
	}

	public static function register_key($rs_name,$keys,$recordsets) {
		$fk=gs_fkey::get_instance();
		$fk->update_hash($rs_name,$keys,$recordsets);

		}

	private  function update_hash($rs_name,$keys,$recordsets) {
		foreach ($keys as $k) {
			$linked_rs_name=$recordsets[$k['link']]['recordset'];

			$rs=new $rs_name;
			$newrec=$rs->new_record();
			$linked_rs=$newrec->init_linked_recordset($k['link']);


			$k['local_field_name']=$linked_rs->local_field_name;
			$k['foreign_field_name']=$linked_rs->foreign_field_name;
			$k['index_field_name']=$linked_rs->index_field_name;

			$this->key_array[$linked_rs_name][$rs_name][]=$k;
		}
	}
	private function process_event($ev_name,$record) {
		$rs_name=get_class($record->get_recordset());
		$ev_name=strtolower(str_replace(' ','_',$ev_name));
		if (!isset($this->key_array[$rs_name]) || !is_array($this->key_array[$rs_name])) return true;
		$keys=$this->key_array[$rs_name];
		$r=true;
		foreach($keys as $rs_name=>$k_arr) {
			foreach($k_arr as $k) {

				$this->local_field_name=$k['local_field_name'];
				$this->foreign_field_name=$k['foreign_field_name'];
				$this->oldid=$record->get_old_value($this->foreign_field_name);
				if ($ev_name=='on_update' && $this->oldid==$record->{$this->foreign_field_name} ) continue;
				$this->rs=new $rs_name;
				$option=strtolower(str_replace(' ','_',$k[$ev_name]));
				$r&=$this->{"action_".$ev_name."_".$option}($record);
			}
		}
		return $r;
	}

	private function action_on_delete_restrict(&$record) {
		if ($this->rs->count_records(array($this->local_field_name=>$this->oldid))>0) throw new gs_dbd_exception("on_delete_restrict:".get_class($this->rs),DBD_DEL_RESTRICT); 
		return true;
	}
	private function action_on_delete_set_null(&$record) {
		return $this->action_on_update_set_null($record);
	}
	private function action_on_delete_cascade(&$record) {
		$this->rs->find_records(array($this->local_field_name=>$this->oldid));
		$record->append_child($this->rs);
		foreach ($this->rs as $r) {
			$r->delete();
		}
		return true;
	}

	private function action_on_update_restrict(&$record) {
		if ($this->rs->count_records(array($this->local_field_name=>$this->oldid))>0) throw new gs_dbd_exception("on_update_restrict:".get_class($this->rs),DBD_UPD_RESTRICT); 
		return true;
	}
		
	private function action_on_update_set_null(&$record) { 
		$this->rs->find_records(array($this->local_field_name=>$this->oldid));
		$record->append_child($this->rs);
		foreach ($this->rs as $r) {
			$r->{$this->local_field_name}=NULL;
		}
		return true;
	}
	private function action_on_update_cascade(&$record) {
		$this->rs->find_records(array($this->local_field_name=>$this->oldid));
		$record->append_child($this->rs);
		foreach ($this->rs as $r) {
			$r->{$this->local_field_name}=$record->{$this->foreign_field_name};
		}
		return true;
	}

	public static function event($ev_name,$record) {
		$fk=gs_fkey::get_instance();
		return $fk->process_event($ev_name,$record);
	}

}

?>
