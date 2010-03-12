<?php
define ('RECORD_UNCHANGED',0);
define ('RECORD_NEW',1);
define ('RECORD_CHANGED',2);
define ('RECORD_DELETED',4);
define ('RECORD_ROLLBACK',8);
define ('RECORD_CHILDMOD',16);


class gs_fkey {
	private $key_array=array();
	

	function get_instance() {
		static $instance;
		if (!isset($instance)) $instance = new gs_fkey();
		return $instance;
	}

	function __construct() {
		$this->key_array= ($n=gs_cacher::load('gs_fkey_array','gs_recordset')) ? $n : array();
	}
	function __destruct() {
		gs_cacher::save($this->key_array,'gs_recordset','gs_fkey_array');
	}

	public static function register_key($rs_name,$keys) {
		$fk=gs_fkey::get_instance();
		$fk->update_hash($rs_name,$keys);

		}

	private  function update_hash($rs_name,$keys) {
		foreach ($keys as $k) {
			$this->key_array[$k['link']][$rs_name]=$k;
		}
	}
	private function process_event($ev_name,$record) {
		$rs_name=get_class($record->get_recordset());
		$ev_name=strtolower($ev_name);
		if (!isset($this->key_array[$rs_name]) || !is_array($this->key_array[$rs_name])) return true;
		$keys=$this->key_array[$rs_name];
		foreach($keys as $rs_name=>$k) {
			$option=strtolower(str_replace(' ','_',$k[$ev_name]));
			$this->{"action_".$ev_name."_".$option}($record,$rs_name);
		}
	}

	private function action_on_delete_restrict($record,$rs_name) { return true;}
	private function action_on_delete_set_null($record,$rs_name) { return true;}
	private function action_on_delete_no_action($record,$rs_name) { return true;}
	private function action_on_delete_cascade($record,$rs_name) { return true;}

	private function action_on_update_restrict($record,$rs_name) { return true;}
	private function action_on_update_set_null($record,$rs_name) { return true;}
	private function action_on_update_no_action($record,$rs_name) { return true;}
	private function action_on_update_cascade($record,$rs_name) {
		$rs=$record->init_linked_recordset($rs_name);
		$id=$record->get_old_value($rs->local_field_name);
		if ($id===FALSE) return true;
		$rs->find_records(array($rs->foreign_field_name=>$id));
		foreach ($rs as $r) {
			$r->{$rs->foreign_field_name}=$record->__get($rs->local_field_name);
		}
		return true;
	}

	public static function event($ev_name,$record) {
		$fk=gs_fkey::get_instance();
		return $fk->process_event($ev_name,$record);
	}

}

class gs_record implements arrayaccess {
	private $gs_recordset;
	private $values;
	private $modified_values;
	private $old_values;
	public $recordstate=RECORD_UNCHANGED;  // !!!!!!!!!!!!!!!!!! private!
	private $recordsets_array=array();

	public function __construct($gs_recordset,$fields='',$status=RECORD_UNCHANGED) {
		$this->gs_recordset=$gs_recordset;
		$this->recordstate=$status;
	}

	public function clone_record() {
		$values=$this->get_values();
		if (isset($this->gs_recordset->id_field_name)) unset($values[$this->gs_recordset->id_field_name]);
		return $this->gs_recordset->new_record($values);
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
		if (!is_array($values)) return FALSE;
		foreach ($values as $field=>$value) {
			if($this->__get($field)!==NULL && isset($this->recordsets_array[$field]) && $this->recordsets_array[$field] && is_array($value) ) {
				$struct=$this->get_recordset()->structure['recordsets'][$field];
				$local_field_name=$this->__get($field)->local_field_name;
				if (isset($struct['type']) && $struct['type']=='one') $value=$this->$local_field_name ? array($this->$local_field_name=>$value) : array($value);

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
		return isset($this->modified_values[$name]);
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


	public function get_values($fields='') {
		//return $this->unescape($this->values);
		return $this->values;
	}
	
	public function get_id() {
		$field=$this->gs_recordset->id_field_name;
		return isset($this->values[$field]) ?  $this->values[$field] : NULL;
	}

	public function init_linked_recordset ($name) {
			$structure=$this->gs_recordset->structure['recordsets'][$name];
			$rs=new $structure['recordset'];
			$local_field_name=isset($structure['local_field_name']) ? $structure['local_field_name'] : $this->gs_recordset->id_field_name;
			//$foreign_field_name=isset($structure['foreign_field_name']) ? $structure['foreign_field_name'] : $rs->id_field_name;
			$foreign_field_name=isset($structure['foreign_field_name']) ? $structure['foreign_field_name'] : $this->gs_recordset->id_field_name;
			$index_field_name=isset($structure['index_field_name']) ? $structure['index_field_name'] : $rs->id_field_name;

			$rs->local_field_name=$local_field_name;
			$rs->foreign_field_name=$foreign_field_name;
			$rs->index_field_name=$index_field_name;
			$rs->index_type=isset($structure['type']) ? $structure['type'] : NULL;
			$rs->parent_record=$this;

			return  $rs;
	}

	private function lazy_load($name) {
			mlog('lazy_load:'.$name);
			$rs=$this->init_linked_recordset($name);
			$structure=$this->gs_recordset->structure['recordsets'][$name];
			$id=$this->__get($rs->local_field_name);

			$structure['options'][$rs->foreign_field_name]=$id;
			$rs=$rs->find_records($structure['options'],null,$rs->index_field_name);
			$this->values[$name]=$this->recordsets_array[$name]=$rs;
			return $this->__get($name);
	}


	public function __get($name) {
		if (isset($this->values[$name])) return $this->values[$name];
		if (isset($this->gs_recordset->structure['recordsets'][$name])) return $this->lazy_load($name);
		return new gs_null(GS_NULL_XML);
	}

	public function __set($name,$value) {
		$fields=$this->get_recordset()->structure['fields'];
		if ($this->recordstate & RECORD_ROLLBACK) {
			$this->recordstate=RECORD_NEW;
		} elseif((is_array($fields) && array_key_exists($name,$fields) && (!isset($this->values[$name]) || $value!==$this->values[$name])) 
				|| ($this->recordstate & RECORD_NEW)) {
			$this->recordstate=$this->recordstate|RECORD_CHANGED;
			if (isset($this->values[$name])) $this->old_values[$name]=$this->values[$name];
			$this->modified_values[$name]=$value;
		}
		if (($rs=$this->get_recordset()->parent_record)!==NULL) $rs->child_modified();
		return $this->values[$name]=$value;
	}
	function get_old_value($name) {
		return isset($this->old_values[$name]) ? $this->old_values[$name] : false;
	}
	public function child_modified() {
		$this->recordstate=$this->recordstate|RECORD_CHILDMOD;
	}


	public function commit() {
		mlog('+++++++++++'.get_class($this->get_recordset()));
		mlog('recordstate:'.$this->recordstate);
		$ret=NULL;
		if ($this->recordstate!=RECORD_UNCHANGED) {
			$ret=$this->gs_recordset->attache_record($this);
			if ($ret===TRUE) return;
		}
		if ($this->recordstate & RECORD_NEW) {
			$ret=$this->gs_recordset->insert($this);
			$this->set_id($ret);
		} else if ($this->recordstate & RECORD_DELETED) {
			if (gs_fkey::event('on_delete',$this)) return false;
			$ret=$this->gs_recordset->delete($this);
		} else if ( $this->recordstate & RECORD_CHANGED) {
			if (!gs_fkey::event('on_update',$this)) return false;
			$ret=$this->gs_recordset->update($this);
		}
		if ($this->recordstate & RECORD_CHILDMOD) {
			$this->recordstate=RECORD_UNCHANGED;
			$this->commit_childrens();
		}
		$this->recordstate=RECORD_UNCHANGED;
		mlog('---------'.get_class($this->get_recordset()));
		return $ret;
	}
	private function commit_childrens() {
		foreach ($this->recordsets_array as $rs) {
			foreach ($rs as $r) {
				if (isset($this->values[$rs->local_field_name])) $r->{$rs->foreign_field_name}=$this->values[$rs->local_field_name];
			}
			$this->recordstate=RECORD_UNCHANGED;
			$rs->commit();
		}
	}

	public function delete() {
		$this->recordstate=($this->recordstate & RECORD_NEW) ? RECORD_ROLLBACK:RECORD_DELETED;
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
class gs_recordset extends gs_recordset_base {}
class _gs_recordset extends gs_recordset_base { // кеширование
	public function find_records($options=null,$fields=null,$index_field_name=null) {
		if (($ret=$this->load_cache($options))) return $ret;
		$ret=parent::find_records($options,$fields,$index_field_name);
		$this->save_cache($ret,$options);
		return $ret;
	}

	public function count_records($options=null) {
		if (($ret=$this->load_cache($options))) return $ret;
		$ret=parent::count_records($options);
		$this->save_cache($ret,$options);
		return $ret;
	}
	private function save_cache($data,$options) {
		return gs_cacher::save($data,'gs_recordset_'.get_class($this),$this->gen_rs_name($options));
	}
	private function load_cache($options) {
		return gs_cacher::load($this->gen_rs_name($options),'gs_recordset_'.get_class($this));
	}
	public function clear_cache() {
		return gs_cacher::cleardir('gs_recordset_'.get_class($this));
	}


	private function gen_rs_name($options) {
		is_array($options) && asort($options);
		return md5(serialize($options));
	}

	public function commit() {
		if (parent::commit() ) {
			$this->clear_cache();
		}
	}
}
class gs_recordset_view extends gs_recordset {
	protected $rs_o_a;
	public function __construct($gs_connector_id,$db_tablename,$db_scheme=null) {
		$this->structure['fields']=array();
		foreach($this->structure['recordsets'] as $rs_name) {
			$this->rs_o_a[$rs_name]=$obj=new $rs_name;
			if (!isset($this->primary_rs) && isset($obj->id_field_name) && !empty($obj->id_field_name)) $this->primary_rs=$obj;
			$this->structure['fields']=array_merge($this->structure['fields'],$obj->structure['fields']);
		}
		$this->id_field_name=$this->primary_rs->id_field_name;
		parent::__construct($gs_connector_id,$db_tablename,$db_scheme);
	}

	public function attache_record($rec) {
		foreach ($this->rs_o_a as $r) {
			$nrec=clone($rec);
			$nrec->change_recordset($r);
			$r->add($nrec);
		}
		return TRUE;
	}
	public function commit() {		
		parent::commit();
		foreach ($this->rs_o_a as $r) {
			$r->commit();
		}
	}
	public function install() {
		foreach ($this->rs_o_a as $r) {
			$r->install();
		}

		if(!$this->get_connector()->table_exists($this->table_name)) {
			$this->createtable();
			$this->commit();
		} else {
			$this->altertable();
			$this->commit();
		}
	}

}


abstract class gs_recordset_base extends gs_iterator {
	private $gs_recordset_classname;
	private $gs_connector;
	private $gs_connector_id;
	public $id_field_name;
	public $db_tablename;
	public $db_scheme=null;
	public $structure=array();
	public $parent_record=NULL;

	public function __construct($gs_connector_id,$db_tablename,$db_scheme=null) {
		$this->gs_connector=NULL;
		$this->gs_connector_id=$gs_connector_id;
		$this->db_tablename=$db_tablename;
		$this->db_scheme=$db_scheme;
	}
	private  function get_connector() {
		if (!$this->gs_connector) {
			$gs_connector_pool=gs_connector_pool::get_instance();
			$this->gs_connector=$gs_connector_pool->get_connector($this->gs_connector_id);
		} 
		return $this->gs_connector;
	}
	public function __wakeup() {
		$this->gs_connector=NULL;
	}



	public function new_record($values=NULL) {
		$rec=new gs_record($this,'',RECORD_NEW);
		$rec->fill_values($values);
		$this->add($rec);
		if (($rs=$this->parent_record)!==NULL) $rs->child_modified();
		return $rec;
	}
	
	public function attache_record($rec) {
		return false;
	}

	public function get_by_id($id) {
		return $this->find_records(array($this->id_field_name=>$id))->current();
	}


	public function find_records($options=null,$fields=null,$index_field_name=null) {
		$index_field_name = is_string($index_field_name) ? $index_field_name : $this->id_field_name;
		$this->reset();
		$this->get_connector()->select($this,$options,$fields);
		$ret=NULL;
		$records=array();
		while ($r=$this->get_connector()->fetch()) {
			$record=new gs_record($this,$fields);
			$record->fill_values($r);
			if (isset($records[$record->$index_field_name])) 
				$records[]=$record;
				else $records[$record->$index_field_name]=$record;
		}
		if (isset($records)) $this->replace($records);
		return $this;
	}
	public function count_records($options=null) {
		$this->get_connector()->select($this,$options,array('count(*) as count'));
		$ret=NULL;
		while ($r=$this->get_connector()->fetch()) {
			if (isset($r['count'])) return($r['count']);
		}
		return $ret;
	}
	public function commit() {
		$ret=FALSE;
		foreach($this as $record) {
			$ret|=$record->commit();
		}
		if ($this->parent_record && $this->index_type=='one') {
			$obj=$this->first();
			$this->parent_record->{$this->local_field_name} = $obj->get_id();
			$this->parent_record->commit();
		}
		return $ret;
	}

	public function get_values() {
		foreach ($this as $k=>$v) {
			if (is_object($v) && method_exists($v,'get_values')) {
				$d=$v->get_values();
			} else if (is_object($v)) {
				$d=get_object_vars($v);
			} else if (is_array($v)) {
				$d=$v;
			} else {
				$d=$v;
			}
			$id = (is_object($v) && method_exists($v,'get_id')) ? $v->get_id() : $k;
			$ret[$id]=$d;
		}
		return($ret);
	}

	public function update($record) {
		return $this->get_connector()->update($record);
	}

	public function delete($record) {
		return $this->get_connector()->delete($record);
	}

	public function copy($record) {
	}

	public function insert($record) {
		return $record->set_id($this->get_connector()->insert($record));
	}

	public function install() {
		if (isset($this->structure['type']) && $this->structure['type']=='view') {
			foreach($this->structure['recordsets'] as $rs_name) {
				$obj=new $rs_name;
				$obj->install();
			}
		}
		/*
		if (isset($this->structure['recordsets'])) foreach ($this->structure['recordsets'] as $r) {
			$rs=new $r['recordset'];
			$rs->install();
		}
		*/

		if(!$this->get_connector()->table_exists($this->table_name)) {
			$this->createtable();
			$this->commit();
		} else {
			$this->altertable();
			$this->commit();
		}
		if (isset($this->structure['fkeys']) && is_array($this->structure['fkeys'])) {
			gs_fkey::register_key(get_class($this),$this->structure['fkeys']);
		}
	}

	public function altertable() {
		md($this->get_connector()->construct_altertable($this->table_name,$this->structure));
	}

	public function createtable() {
		md($this->get_connector()->construct_createtable($this->table_name,$this->structure));
	}
	public function droptable() {
		md($this->get_connector()->construct_droptable($this->table_name));
	}
	public function fill_values($obj,$data) {
	}
}

class gs_connector_pool {
	private $db_connectors_pool;
	function __construct() {
	}

	private function add_connector($gs_connector_id) {
		$this->db_connectors_pool[$gs_connector_id]=new gs_connector($gs_connector_id);
	}

	public function get_connector($gs_connector_id) {
		if (!isset($this->db_connectors_pool[$gs_connector_id])) {
			$this->add_connector($gs_connector_id);
		}
		return $this->db_connectors_pool[$gs_connector_id]->o_dbd;
	}

	function &get_instance()
	{
		static $instance;
		if (!isset($instance)) $instance = new gs_connector_pool;
		return $instance;
	}
}

class gs_connector  {
	public $o_dbd;
	function __construct($gs_connector_id) {
		$cfg=gs_config::get_instance();
		if (!isset($cfg->gs_connectors[$gs_connector_id])) {
			throw new gs_exception('gs_connector: '.$gs_connector_id.'  not exists in config');
		}
		$cinfo=$cfg->gs_connectors[$gs_connector_id];
		load_dbdriver($cinfo['db_type']);
		$dbd_classname='gs_dbdriver_'.$cinfo['db_type'];
		if (!class_exists($dbd_classname)) {
			throw new gs_exception('gs_connector: '.$dbd_classname.'  not found');
		}
		$this->o_dbd=new $dbd_classname($cinfo);
	}
}

abstract class gs_prepare_sql {
	protected $_sql;
	protected $_where;
	protected $_escape_case;

	function __construct() {
		$this->_index_types=array(
			'key'=>'',
			'unique'=>'UNIQUE',
			//'serial'=>'PRIMARY AUTO_INCREMENT',
			);
		$this->_field_types=array( 'int'=>'INT',
			'serial'=>'INT AUTO_INCREMENT PRIMARY KEY',
			//'serial'=>'INT',
			'tinyint'=>'TINYINT',
			'float'=>'FLOAT',
			'date'=>'DATETIME',
			'timestamp'=>'TIMESTAMP',
			'varchar'=>'VARCHAR ({v})',
			'text'=>'LONGTEXT',
			'set'=>'SET ({v})',
			'enum'=>'ENUM ({v})',
			'blob'=>'BLOB',
			'longblob'=>'LONGBLOB',
			'bool'=>'BOOL',
			);
		$this->_escape_case=array(
			'='=>array('FLOAT'=>'{f} = {v}','NUMERIC'=>'{f} = {v}','STRING'=>'{f} = {v}','NULL'=>'{f} IS {v}','ARRAY'=>'{f} IN {v}'),
			'!='=>array('FLOAT'=>'{f} != {v}','NUMERIC'=>'{f} != {v}','STRING'=>'{f} != {v}','NULL'=>'{f} IS NOT {v}','ARRAY'=>'{f} NOT IN {v}'),
			'>'=>array('FLOAT'=>'{f} > {v}','NUMERIC'=>'{f} > {v}','STRING'=>'{f} > {v}','NULL'=>'{f} IS NOT {v}'),
			'>='=>array('FLOAT'=>'{f} >= {v}','NUMERIC'=>'{f} >= {v}','STRING'=>'{f} >= {v}','NULL'=>'{f} IS NOT {v}'),
			'<'=>array('FLOAT'=>'{f} < {v}','NUMERIC'=>'{f} < {v}','STRING'=>'{f} < {v}','NULL'=>'{f} IS NOT {v}'),
			'<='=>array('FLOAT'=>'{f} <= {v}','NUMERIC'=>'{f} <= {v}','STRING'=>'{f} <= {v}','NULL'=>'{f} IS NOT {v}'),
			'LIKE'=>array('FLOAT'=>'{f}={v}','NUMERIC'=>'{f}={v}','STRING'=>"{f} LIKE '%%{v}%%'",'NULL'=>'{f} IS NOT {v}'),
			'BETWEEN'=>array('FLOAT'=>'FALSE','NUMERIC'=>'FALSE','STRING'=>'FALSE','NULL'=>'FALSE','ARRAY'=>'({f} BETWEEN {v0} AND {v1})'),
			);
	}
	protected function construct_table_fields($options) {
		$table_fields=array();
		if (is_array($options['fields'])) foreach ($options['fields'] as $key=>$field) {
			if (!isset($this->_field_types[$field['type']])) {
				throw new gs_dbd_exception('gs_recordset.construct_createtable: can not find definition for _field_types '.$field['type']);
			}
			$k=$this->_field_types[$field['type']];
			if (isset($field['options'])) {
				$k=$this->replace_pattern($k,$field['options']);
			}
			$name=!isset($field['name'])?$key:$field['name'];
			$table_fields[$name]=sprintf("%s %s %s",$name, $k, isset($field['default']) ? 'DEFAULT '.$this->escape_value($field['default']) : '');

		}
		return $table_fields;

	}
		
	function  construct_where($options,$type='AND') {
		if (is_array($options)) foreach ($options as $kkey=>$value) {
			if ($kkey==="OR") {
				$txt=$this->construct_where($value,'OR');
			} else if ($kkey==="AND") {
				$txt=$this->construct_where($value,'AND');
			} else {
				if (!is_array($value) || !isset($value['value'])) {
					$value=array('type'=>'value', 'field'=>$kkey,'case'=>'=','value'=>$value);
				}
				if (!isset($value['case'])) $value['case']='=';
				if (!isset($value['type'])) $value['type']='value';
				

				switch ($value['type']) {
					case 'value':
						$txt=$this->escape($value['field'],$value['case'],$value['value']);
					break;
				}

			}
			if (!empty($txt)) $tmpsql[]=$txt;
			$txt='';
		}
		$ret=sizeof($tmpsql)>0 ? sprintf ('(%s)',implode(" $type ",$tmpsql)) : '';
		$this->_where=$ret;
		return $ret;
	}
}



interface gs_dbdriver_interface {
	function __construct($cinfo);
	function connect();
	function query();
	function insert($record);
	function update($record);
	function delete($record);
	function fetch();
	function select($rset,$options,$fields=NULL);
	function get_insert_id();
	function table_exists($tablename);
	function get_table_fields($tablename);
}


class gs_dbd_exception extends gs_exception {
}


?>
