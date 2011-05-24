<?php
define ('RECORD_UNCHANGED',0);
define ('RECORD_NEW',1);
define ('RECORD_CHANGED',2);
define ('RECORD_DELETED',4);
define ('RECORD_ROLLBACK',8);
define ('RECORD_CHILDMOD',16);
define ('RECORD_NEW_BIND',32);
class gs_recfabric {
	private $links=array();

	static function get_record($rs,$fields,$values) {
		static $fabric;
		if (!isset($fabric)) $fabric=new gs_recfabric();
		return $fabric->return_record($rs,$fields,$values);
	}
	function return_record($rs,$fields,$values) {
		$conn=$rs->gs_connector_id;
		$table=$rs->db_tablename;
		$idname=$rs->id_field_name;
		$id=$values[$idname];
		if (isset($this->links[$conn]) && is_array($this->links[$conn]) && isset($this->links[$conn][$table]) && is_array($this->links[$conn][$table]) && array_key_exists($id,$this->links[$conn][$table])) {
			return $this->links[$conn][$table][$id];
		}
		$record=new gs_record($rs,$fields);
		$record->fill_values($values);
		$record->recordstate = RECORD_UNCHANGED;
		$this->links[$conn][$table][$id]=&$record;
		return $this->links[$conn][$table][$id];
	}
}
class gs_record implements arrayaccess {
	private $gs_recordset;
	private $values=array();
	private $modified_values=array();
	private $old_values=array();
	public $recordstate=RECORD_UNCHANGED;  // !!!!!!!!!!!!!!!!!! private!
	private $recordsets_array=array();

	public function __construct($gs_recordset,$fields='',$status=RECORD_UNCHANGED) {
		$this->gs_recordset=$gs_recordset;
		$this->recordstate=$status;
	}
	public function __wakeup() {
		if(method_exists($this->get_recordset(),'__record_wakeup')) $this->get_recordset()->__record_wakeup($this);
	}

	public function append_child(&$child) {
		$child->parent_record=$this;
		$this->recordsets_array[]=$child;
		if (($parent=$this->get_recordset()->parent_record)!==NULL) $parent->child_modified();
	}

	public function clone_record() {
		$values=$this->get_values();
		if (isset($this->gs_recordset->id_field_name)) unset($values[$this->gs_recordset->id_field_name]);
		return $this->gs_recordset->new_record($values);
	}
	public function clone_values() {
		$values=$this->get_values();
		foreach ($this->gs_recordset->structure['recordsets'] as $k=>$s) {
			if(substr($k,0,1)!=='_') {
				$val=$this->__get($k)->get_values();
				if (isset($s['rs1_name']) && isset($s['rs2_name'])) {
					$val=array_combine(array_keys($val),array_keys($val)); 
				} else {
					$val=reset($val);
				}
				$values[$k]=$val;
			}
			//$this->__get($k);
		}
		
		unset($values['schedule']);
		unset($values['id']);
		return $values;
	}

	public function change_recordset($gs_recordset) {
		$this->gs_recordset=$gs_recordset;
	}

	public function set_id($id) {
		$field=$this->gs_recordset->id_field_name;
		$this->values[$field]=trim($id);
		return ($id);
	}

	public function fill_values($values) {
		/*
		md('==fill_values=='.get_class($this->get_recordset()),1); 
		md($values,1);
		*/
		if (!is_array($values)) return FALSE;
		foreach ($values as $field=>$value) {
			if (isset($this->recordsets_array[$field]) && $this->recordsets_array[$field] && is_array($value) && $this->__get($field)!==NULL) {
				$struct=$this->get_recordset()->structure['recordsets'][$field];
				$local_field_name=$this->__get($field)->local_field_name;

				/*

				type='one' -¿¿¿¿¿¿¿¿¿¿¿¿ ¿¿¿: ¿¿¿¿ ¿¿¿¿¿ 'one' ¿ ¿¿¿ ¿¿¿¿ ¿¿¿¿¿¿ ¿ ¿¿¿¿¿¿¿¿¿¿ id-¿¿¿¿¿ ¿¿ ¿¿¿ ¿¿¿¿¿¿¿¿¿¿ 
				¿¿¿¿¿¿¿ ¿¿¿¿¿ ¿¿¿¿¿¿¿¿ ¿¿¿¿ record, ¿¿¿¿¿ ¿¿¿¿¿¿¿¿¿ new_record (¿¿¿¿ ¿¿¿¿¿¿¿¿¿¿¿ ¿¿ ¿¿¿¿¿¿¿ ¿¿¿ type != one

				¿¿¿¿¿¿ ¿¿¿¿¿¿¿¿¿ ¿¿¿¿¿¿¿ ¿¿¿¿¿¿¿¿¿

				¿¿¿¿¿¿ ¿¿¿¿¿¿¿ ¿¿¿ ¿¿¿¿¿:
				if (isset($struct['type']) && $struct['type']=='one') $value=$this->$local_field_name ? array($this->$local_field_name=>$value) : array($value);


				*/
				if (!isset($struct['type']) || $struct['type']=='one') $value=$this->$local_field_name ? array($this->$local_field_name=>$value) : array($value);
				foreach ($value as $k=>$v) {
					if ($this->recordsets_array[$field][$k]) {
						$this->recordsets_array[$field][$k]->fill_values($v);
					} else {
						$this->recordsets_array[$field]->new_record($v);
					}
				}
			} else {
				$this->$field=$value;
			}
		}
		$this->gs_recordset->fill_values($this,$values);
	}

	public function is_modified($name) {
		return array_key_exists($name,$this->modified_values);
	}
	public function get_modified_values($name=null) {
		return $name===null ? $this->modified_values : $this->modified_values[$name];
	}

	public function get_recordset() {
		return $this->gs_recordset;
	}

	private function unescape($val) {
		if (is_array($val)) foreach ($val as $k=>$v) {
			if (is_array($v)) $val[$k]=$this->unescape($v);
			if (is_string($v)) $val[$k]=stripslashes($v);
		}
		return($val);
	}


	public function get_values($fields=null,$recursive=true) {
		//return $this->unescape($this->values);
		$ret=array();
		$values=$this->values;
		if ($fields==null) {
			$fields=array_keys($this->get_recordset()->structure['fields']);
			$this->get_recordset()->query_options['late_load_fields']=$fields;
			$this->get_recordset()->late_load_records();
		}
		if ($fields !==null) {
			$values=array();
			if(!is_array($fields)) $fields=explode(',',$fields);
			foreach ($fields as $k)  {
				$this->__get(trim($k));
				//if($v) $values[$k]=$v;
				if (array_key_exists($k,$this->values)) $values[$k]=$this->values[$k];
			}

		} 
		foreach ($values as $k=>$v) {
			$val= (is_object($v)) ? get_class($v) : $v;
			if (is_object($v) && method_exists($v,'get_values')) {
				if ($recursive) {
					$val=$v->get_values();
				} else {
					$val=array();
					foreach ($v as $vv) $val[$vv->get_id()]=$vv->get_id();
				}
			}
			$ret[$k]=$val;
		}
		return $ret;
	}

	public function __toString() {
		return $this->get_recordset()->record_as_string($this);
	}

	public function get_id() {
		$field=$this->gs_recordset->id_field_name;
		return isset($this->values[$field]) ?  $this->values[$field] : NULL;
	}

	public function init_linked_recordset ($name) {
		$structure=$this->gs_recordset->structure['recordsets'][$name];
		if (isset($structure['rs1_name']) && isset($structure['rs2_name'])) 
			$rs=new gs_rs_links($structure['rs1_name'],$structure['rs2_name'],$structure['recordset'],$structure['rs_link'],$name);
		 else 
			$rs=new $structure['recordset'];


		$local_field_name=isset($structure['local_field_name']) ? $structure['local_field_name'] : $this->gs_recordset->id_field_name;
		//$foreign_field_name=isset($structure['foreign_field_name']) ? $structure['foreign_field_name'] : $rs->id_field_name;
		$foreign_field_name=isset($structure['foreign_field_name']) ? $structure['foreign_field_name'] : $this->gs_recordset->id_field_name;
		$index_field_name=isset($structure['index_field_name']) ? $structure['index_field_name'] : $rs->id_field_name;

		$rs->local_field_name=$local_field_name;
		$rs->foreign_field_name=$foreign_field_name;
		$rs->index_field_name=$index_field_name;
		//$this->gs_recordset->index_type=isset($structure['type']) ? $structure['type'] : NULL;
		$rs->parent_record=$this;

		return  $rs;
	}

	private function lazy_load($name) {
		//md('lazy_load:'.$name,1);
		$rs=$this->init_linked_recordset($name);
		$structure=$this->gs_recordset->structure['recordsets'][$name];
		$id=$this->__get($rs->local_field_name);

		$structure['options'][$rs->foreign_field_name]=$id;
		$rs=$rs->find_records($structure['options'],null,$rs->index_field_name);
		$this->values[$name]=$this->recordsets_array[$name]=$rs;
		return $this->__get($name);
	}


	public function __get($name) {
		//var_dump($name.":".$this->gs_recordset->state);
		if (array_key_exists($name,$this->values)) return $this->values[$name];
		if (isset($this->gs_recordset->structure['recordsets'][$name])) return $this->lazy_load($name);

		if(isset($this->get_recordset()->structure['fields'][$name]) && $this->get_recordset()->state==RS_STATE_LATE_LOAD) {
			return $this->get_recordset()->query_options['late_load_fields'][$name]=$name;
		}

		if(isset($this->get_recordset()->structure['fields'][$name]) && $this->get_recordset()->state==RS_STATE_LOADED) {
			$this->get_recordset()->load_records(array($name));
			if (array_key_exists($name,$this->values)) return $this->values[$name];
		}
		/*
		$cname=get_class($this->get_recordset()).'_'.$name;
		if(class_exists($cname) && is_subclass_of($cname,'gs_recordset_base') && property_exists($cname,'parent_id_name')) {
			$this->get_recordset()->structure['recordsets'][$name]=array(
				'recordset' => $cname,
				'local_field_name' => $this->get_recordset()->id_field_name,
				'foreign_field_name' => $cname::$parent_id_name,
				'type' => 'many',
				'mode' => 'link',
			);
			return $this->__get($name);
		}
		*/
		return new gs_null(GS_NULL_XML);
	}

	public function __set($name,$value) {
		if ($this->recordstate==RECORD_UNCHANGED) $this->modified_values=array();
		$fields=$this->get_recordset()->structure['fields'];
		if ($this->recordstate & RECORD_ROLLBACK) {
			$this->recordstate=RECORD_NEW;
		}
		elseif((is_array($fields) && array_key_exists($name,$fields) && (!isset($this->values[$name]) || $value!=$this->values[$name]))
		       || ($this->recordstate & RECORD_NEW)) {
			$this->recordstate=$this->recordstate|RECORD_CHANGED;
			if (isset($this->values[$name])) $this->old_values[$name]=$this->values[$name];
			$this->modified_values[$name]=$value;
		}
		if (($parent=$this->get_recordset()->parent_record)!==NULL) $parent->child_modified();
		return $this->values[$name]=$value;
	}
	function get_old_value($name) {
		return isset($this->old_values[$name]) ? $this->old_values[$name] : $this->__get($name);
	}
	public function child_modified() {
		$this->recordstate=$this->recordstate|RECORD_CHILDMOD;
		if (($rs=$this->get_recordset()->parent_record)!==NULL) $rs->child_modified();
	}
	

	public function commit($level=0) {
/*
		mlog('+++++++++++'.get_class($this->get_recordset()));
		mlog('recordstate:'.$this->recordstate);
*/
		$ret=NULL;


		if ($this->recordstate!=RECORD_UNCHANGED) {
			$ret=$this->gs_recordset->attache_record($this); // works only for gs_recordset_view !!
			if ($ret===TRUE) return;
		}
		if ($this->recordstate & RECORD_NEW) {
			if ($level==0) {
				$parent_record=$this->gs_recordset->parent_record;
				if ($parent_record) $this->__set($this->gs_recordset->foreign_field_name,$parent_record-> {$this->gs_recordset->local_field_name});
			}
			//$this->_ctime=date("c");
			$this->_ctime=date("Y-m-d H:i:s");
			//$this->_mtime=date("c");
			$this->_mtime=date("Y-m-d H:i:s");
			$ret=$this->gs_recordset->insert($this);
			$this->set_id($ret);
		} else if ($this->recordstate & RECORD_DELETED) {
			if (!gs_fkey::event('on_delete',$this)) return false;
			$ret=$this->gs_recordset->delete($this);
		} else if ( $this->recordstate & RECORD_CHANGED) {
			if (!gs_fkey::event('on_update',$this)) return false;
			//$this->_mtime=date("c");
			$this->_mtime=date("Y-m-d H:i:s");
			$ret=$this->gs_recordset->update($this);
		}
		if ($this->recordstate & RECORD_NEW_BIND) {
			$parent_record=$this->gs_recordset->parent_record;
			if ($parent_record) $this->__set($this->gs_recordset->foreign_field_name,$parent_record-> {$this->gs_recordset->local_field_name});
			$ret=$this->gs_recordset->update($this);
		}
		
		if ($level==0 && ($this->recordstate & RECORD_CHILDMOD)) {
			$this->recordstate=RECORD_UNCHANGED;
			$this->commit_childrens();
		}
		$this->recordstate=RECORD_UNCHANGED;
		$this->old_values=$this->modified_values=array();
		return $ret;
	}
	private function commit_childrens() {
		$this->recordstate=RECORD_UNCHANGED;
		foreach ($this->recordsets_array as $rs) {
			if ($rs) {
				$rec=$rs->first();
				$recordstate=$rec->recordstate;
				$rs->commit();
				if ($recordstate & RECORD_NEW) $this->__set($rs->local_field_name,$rec-> {$rs->foreign_field_name});
				
			}
		}
		$this->commit(1);
	}

	public function delete() {
		$this->recordstate=($this->recordstate & RECORD_NEW) ? RECORD_ROLLBACK:RECORD_DELETED;
		if (($parent=$this->get_recordset()->parent_record)!==NULL) $parent->child_modified();
	}

	public function unlink() {
		$pr=$this->get_recordset()->parent_recordset;
		if (!$pr || get_class($pr)!=='gs_rs_links') return;
		$pr->links[$this->get_id()]->delete();
	}

	public function copy() {
	}
	public function offsetGet($offset) {
		return $this->__get($offset);
	}
	public function offsetSet($offset, $value) {
		return $this->__set($offset, $value);
	}
	public function offsetExists($offset) {
		return TRUE && $this->__get($offset);
	}
	public function offsetUnset($offset) {
		unset($this->values[$offset]);
	}

}

?>
