<?php
define ('RECORD_UNCHANGED',0);
define ('RECORD_NEW',1);
define ('RECORD_CHANGED',2);
define ('RECORD_DELETED',4);
define ('RECORD_ROLLBACK',8);
define ('RECORD_CHILDMOD',16);

define ('DBD_GSNULL_CALL',2);
define ('DBD_UPD_RESTRICT',4);
define ('DBD_DEL_RESTRICT',8);
define ('DBD_TRIGGER_FUNC_NOT_EXISTS',16);



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
			if ($this->__get($field)!==NULL && isset($this->recordsets_array[$field]) && $this->recordsets_array[$field] && is_array($value) ) {
				$struct=$this->get_recordset()->structure['recordsets'][$field];
				$local_field_name=$this->__get($field)->local_field_name;

				/*

				type='one' -їїїїїїїїїїїї їїї: їїїї їїїїї 'one' ї їїї їїїї їїїїїї ї їїїїїїїїїї id-їїїїї її їїї їїїїїїїїїї 
				їїїїїїї їїїїї їїїїїїїї їїїї record, їїїїї їїїїїїїїї new_record (їїїї їїїїїїїїїїї її їїїїїїї їїї type != one

				їїїїїї їїїїїїїїї їїїїїїї їїїїїїїїї

				їїїїїї їїїїїїї їїї їїїїї:
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
		/*
		       'recordset'=>$table_name,
		                               'rs1_name'=$init_opts['recordset'],
					                               'rs2_name'=$rname,


		*/
		if (isset($structure['rs1_name']) && isset($structure['rs2_name'])) 
			$rs=new gs_rs_links($structure['rs1_name'],$structure['rs2_name'],$structure['recordset']);
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
		//mlog('lazy_load:'.$name);
		$rs=$this->init_linked_recordset($name);
		$structure=$this->gs_recordset->structure['recordsets'][$name];
		$id=$this->__get($rs->local_field_name);

		$structure['options'][$rs->foreign_field_name]=$id;
		$rs=$rs->find_records($structure['options'],null,$rs->index_field_name);
		$this->values[$name]=$this->recordsets_array[$name]=$rs;
		return $this->__get($name);
	}


	public function __get($name) {
		if (array_key_exists($name,$this->values)) return $this->values[$name];
		if (isset($this->gs_recordset->structure['recordsets'][$name])) return $this->lazy_load($name);
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
			$ret=$this->gs_recordset->insert($this);
			$this->set_id($ret);
		} else if ($this->recordstate & RECORD_DELETED) {
			if (!gs_fkey::event('on_delete',$this)) return false;
			$ret=$this->gs_recordset->delete($this);
		} else if ( $this->recordstate & RECORD_CHANGED) {
			if (!gs_fkey::event('on_update',$this)) return false;
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
abstract class gs_recordset extends gs_recordset_base {}
abstract class _gs_recordset extends gs_recordset_base { // кеширование
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

function new_rs($classname) {
        return new $classname;
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
		//$this->make_forms();
	}
	function make_forms() {
		$htmlforms=array();
		$myforms=array();
		foreach ($this->structure['fields'] as $n=>$f) {
			$type='input';
			//if ($f['type']=='serial') $type='hidden';
			if ($f['type']=='serial') continue;
			if ($f['type']=='text') $type='textarea';
			$htmlforms[$n]=array('type'=> $type);
		}
		$myforms['all.add']=$myforms['all.edit']=$myforms['all.show']=array( 'fields'=>array_keys($htmlforms) );

		if (isset($this->structure['recordsets'])) foreach ($this->structure['recordsets'] as $n=>$f) {
			$htmlforms[$n]=array('type'=>'recordset');
		}
		$this->structure['htmlforms']=isset($this->structure['htmlforms']) ? array_merge($htmlforms,$this->structure['htmlforms']) : $htmlforms;
		$this->structure['myforms']=isset($this->structure['myforms']) ? array_merge($myforms,$this->structure['myforms']) : $myforms;
	}
	protected function get_connector() {
		if (!$this->gs_connector) {
			$gs_connector_pool=gs_connector_pool::get_instance();
			$this->gs_connector=$gs_connector_pool->get_connector($this->gs_connector_id);
		}
		return $this->gs_connector;
	}
	public function __wakeup() {
		$this->gs_connector=NULL;
	}



	public function new_record($values=NULL,$id=NULL) {
		$rec=new gs_record($this,'',RECORD_NEW);
		$rec->fill_values($values);
		//$this->add_element($rec,$id);
		$this->add($rec,$id);
		if (($rs=$this->parent_record)!==NULL) $rs->child_modified();
		return $rec;
	}

	public function attache_record($rec) {
		return false;
	}

	public function record_as_string($rec) {
		reset($this->structure['fields']);
		next($this->structure['fields']);
		$fieldname=key($this->structure['fields']);
		reset($this->structure['fields']);
		return $rec->$fieldname;
	}
	public function recordset_as_string_array() {
		$ret=array();
		foreach ($this as $rec) {
			$ret[$rec->get_id()]=trim($rec);
		}
		return $ret;
	}

	public function get_by_id($id) {
		return $this->find_records(array($this->id_field_name=>$id))->current();
	}
	public function set($values=array()) {
		foreach ($this as $i) {
			$i->fill_values($values);
		}
		return $this;
	}

	public function __get($name) {
		if (isset($this->structure['recordsets'][$name]))
			return $this->find(array(),$name);

		return new gs_null(GS_NULL_XML);
	}


	function find($options,$linkname=null) {
		if (!$this->first()) return new gs_null(GS_NULL_XML);

		$ids=array();
		foreach ($this as $r) $ids[]=$r->get_id();

		if ($linkname!==null) {
			//if (!isset($this->recordsets[$linkname])) return new gs_null(GS_NULL_XML);
			if (!isset($this->structure['recordsets'][$linkname])) return new gs_null(GS_NULL_XML);
			$rs=$this->first()->init_linked_recordset($linkname);
			$options=array_merge($options,array($rs->foreign_field_name=>$ids));
		} else {
			$cur_class_name=get_class($this);
			$rs=new $cur_class_name;
			$options=array_merge($options,array($rs->id_field_name=>$ids));
		}
		$rs->find_records($options);
		return $rs;
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
			$record->recordstate = RECORD_UNCHANGED;
			if (isset($records[$record->$index_field_name]))
				$records[]=$record;
			else $records[$record->$index_field_name]=$record;
		}
		if (isset($records)) $this->replace($records);
		return $this;
	}
	public function count_records($options=null) {
		if (is_array($options)) foreach($options as $k=>$o) {
			if (in_array(strtolower($o['type']),array('limit','offset','orderby'))) unset($options[$k]);
		}
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
		return $ret;
	}
	public function get_fields() {
		return array_keys($this->structure['fields']);
	}
	public function get_values($fields=null,$recursive=true) {
		$ret=array();
		foreach ($this as $k=>$v) {
			if (is_object($v) && method_exists($v,'get_values')) {
				$d=$v->get_values($fields,$recursive);
			} else if (is_object($v)) {
				$d=get_object_vars($v);
			} else if (is_array($v)) {
				$d=$v;
			} else {
				$d=$v;
			}
			/*
			$id = (is_object($v) && method_exists($v,'get_id')) ? $v->get_id() : $k;
			$ret[$id]=$d;
			*/
			$ret[$k]=$d;
		}
		return($ret);
	}
	public function get_elements_by_name($name) {
		$ret=new gs_null(GS_NULL_XML);
		foreach ($this as $k=>$v) {
			if ($v->$name) {
				if (!$ret) {
					$classname=get_class($v->$name);
					$ret=new $classname;
				}
				foreach ($v->$name as $i) {
					$ret->add_element($i);
				}
			}
		}
		return($ret);
	}

	public function update($record) {
		$this->process_trigger('before_update',$record);
		$r=$this->get_connector()->update($record);
		$this->process_trigger('after_update',$record);
		return $r;
	}

	public function delete($record) {
		$this->process_trigger('before_delete',$record);
		$r=$this->get_connector()->delete($record);
		$this->process_trigger('after_delete',$record);
		return $r;
	}

	public function copy($record) {
	}

	public function insert($record) {
		$this->process_trigger('before_insert',$record);
		$r=$record->set_id($this->get_connector()->insert($record));
		$this->process_trigger('after_insert',$record);
		return $r;
	}

	public function install() {
		if (isset($this->structure['type']) && $this->structure['type']=='view') {
			foreach($this->structure['recordsets'] as $rs_name) {
				$obj=new $rs_name;
				$obj->install();
			}
		}
		foreach($this->structure['recordsets'] as $structure) {
			if (isset($structure['rs1_name']) && isset($structure['rs2_name']))  {
				$obj=new gs_rs_links($structure['rs1_name'],$structure['rs2_name'],$structure['recordset']);
				$obj->install();
			}
		}
		/*
		if (isset($this->structure['recordsets'])) foreach ($this->structure['recordsets'] as $r) {
			$rs=new $r['recordset'];
			$rs->install();
		}
		*/

		if (!$this->get_connector()->table_exists($this->table_name)) {
			$this->createtable();
			$this->commit();
		} else {
			$this->altertable();
			$this->commit();
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
	public function current() {
		return ($r=parent::current()) ? $r : new gs_null(GS_NULL_XML);
	}

	public function process_trigger($event,&$rec) {
		if (isset($this->structure['triggers']) && isset($this->structure['triggers'][$event])) {
			$triggers=$this->structure['triggers'][$event];
			if (!is_array($triggers)) $triggers=array($triggers);
			foreach ($triggers as $t) {
				if (!method_exists($this,$t)) throw new gs_dbd_exception("triggers: no method '$t' exists:".get_class($this).":$event:$t",DBD_TRIGGER_FUNC_NOT_EXISTS);
				$this->$t($rec);
			}
		}
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
		$this->_field_types=array( 
		                           'serial'=>'INT AUTO_INCREMENT PRIMARY KEY',
					   'int'=>'INT',
		                           'tinyint'=>'TINYINT',
		                           'float'=>'FLOAT',
		                           'date'=>'DATETIME',
		                           'timestamp'=>'TIMESTAMP',
		                           'varchar'=>'VARCHAR ({v})',
		                           'text'=>'LONGTEXT',
		                           'set'=>'SET ({v})',
		                           'enum'=>'ENUM {v}',
		                           'blob'=>'BLOB',
		                           'longblob'=>'LONGBLOB',
		                           'bool'=>'BOOL',
		                         );
		$this->_escape_case=array(
		                        '='=>array('FLOAT'=>'{f} = {v}','NUMERIC'=>'{f} = {v}','STRING'=>'{f} = {v}','NULL'=>'{f} IS NULL','ARRAY'=>'{f} IN {v}'),
		                        '!='=>array('FLOAT'=>'{f} != {v}','NUMERIC'=>'{f} != {v}','STRING'=>'{f} != {v}','NULL'=>'{f} IS NOT NULL','ARRAY'=>'{f} NOT IN {v}'),
		                        '>'=>array('FLOAT'=>'{f} > {v}','NUMERIC'=>'{f} > {v}','STRING'=>'{f} > {v}','NULL'=>'{f} IS NOT NULL'),
		                        '>='=>array('FLOAT'=>'{f} >= {v}','NUMERIC'=>'{f} >= {v}','STRING'=>'{f} >= {v}','NULL'=>'{f} IS NOT NULL'),
		                        '<'=>array('FLOAT'=>'{f} < {v}','NUMERIC'=>'{f} < {v}','STRING'=>'{f} < {v}','NULL'=>'{f} IS NOT NULL}'),
		                        '<='=>array('FLOAT'=>'{f} <= {v}','NUMERIC'=>'{f} <= {v}','STRING'=>'{f} <= {v}','NULL'=>'{f} IS NOT NULL'),
		                        'LIKE'=>array('FLOAT'=>'{f}={v}','NUMERIC'=>'{f}={v}','STRING'=>"{f} LIKE '%%{v}%%'",'NULL'=>'FALSE'),
		                        'FULLTEXT'=>array('FLOAT'=>'{f}={v}','NUMERIC'=>'{f}={v}','STRING'=>" MATCH ({f}) AGAINST  ({v})",'NULL'=>'FALSE'),
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
		$tmpsql=array();
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
				case 'field':
					$txt=sprintf("%s %s %s",$value['field'],$value['case'],$value['value']);
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
