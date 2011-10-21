<?php

define ('DBD_GSNULL_CALL',2);
define ('DBD_UPD_RESTRICT',4);
define ('DBD_DEL_RESTRICT',8);
define ('DBD_TRIGGER_FUNC_NOT_EXISTS',16);

define ('RS_STATE_NULL',0);
define ('RS_STATE_UNLOADED',1);
define ('RS_STATE_COUNTED',2);
define ('RS_STATE_LOADED',4);
define ('RS_STATE_LATE_LOAD',8);

abstract class gs_recordset_base extends gs_iterator {
	const superadmin = 0;
	public $state=RS_STATE_NULL;
	public $query_options=array();
	private $gs_recordset_classname;
	private $gs_connector;
	private $gs_connector_id;
	public $id_field_name;
	public $db_tablename;
	public $db_scheme=null;
	public $structure=array();
	public $parent_record=NULL;
	protected $handler_cache_status=1;

	public function __construct($gs_connector_id,$db_tablename,$db_scheme=null) {
		$this->gs_connector=NULL;
		$this->gs_connector_id=$gs_connector_id;
		$this->db_tablename=$db_tablename;
		$this->db_scheme=$db_scheme;
		$this->query_options['late_load_fields']=array();
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
		$this->preload();
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
	
	public function bind() {
		foreach ($this as $rec) {
			$this->add($rec,$rec->get_id());
			$rec->recordstate=$rec->recordstate|RECORD_NEW_BIND;
			if (($rs=$this->parent_record)!==NULL) $rs->child_modified();
		}
		return $this;
	}


	public function record_as_string($rec) {
		reset($this->structure['fields']);
		next($this->structure['fields']);
		$fieldname=key($this->structure['fields']);
		reset($this->structure['fields']);
		return $rec->$fieldname ? $rec->$fieldname : '';
	}
	public function __toString() {
		return implode(', ',$this->recordset_as_string_array());
	}
	public function recordset_as_string_array() {
		$ret=array();
		foreach ($this as $rec) {
			if($rec->get_id()) $ret[$rec->get_id()]=trim($rec);
		}
		return $ret;
	}

	// works only in PHP > 5.3
	static function record_by_id($id,$fields=array()) {
		if(function_exists('get_called_class')) {
			$name=get_called_class();
			$rs=new $name;
			return $rs->get_by_id($id,$fields);
		}
		throw new gs_exception('static function record_by_id tot works prior php 5.3!');
	}

	public function get_by_id($id,$fields=null) {
		if (!empty($fields) && is_string($fields)) {
			$fields=explode(',',$fields);
		} else {
			$fields=array($this->id_field_name);
		}
		foreach ($this->structure['recordsets'] as $link) {
			$fields[]=$link['local_field_name'];
		}
		$fields=array_unique($fields);
		return $this->find_records(array($this->id_field_name=>$id),$fields)->current();
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

	private function string2options($options) {
		if (!is_string($options)) return $options;

		$options=preg_replace('|=\s*([^\'\"][^\s]*)|i','=\'\1\'',$options);
		preg_match_all(':(([a-z_]+)=)?[\'\"]([^a-zA-Z0-9]*)(.+?)[\'\"]:i',$options,$out);
		$options=array();
		foreach($out[2] as $k=>$v) {
			$case=isset($out[3][$k]) && !empty($out[3][$k]) ? $out[3][$k] : '=';
			$options[]=array('field'=>$v,'case'=>$case,'value'=>$out[4][$k]);
		}
		return $options;

	}


	function find($options,$linkname=null) {
		 $options=$this->string2options($options);
		if (!$this->first()) return new gs_null(GS_NULL_XML);
		$ids=array();
		if ($linkname!==null) {
			if (!isset($this->structure['recordsets'][$linkname])) return new gs_null(GS_NULL_XML);
			$rs=$this->first()->init_linked_recordset($linkname);
			$s=$this->structure['recordsets'][$linkname];
			foreach ($this as $r) $ids[]=$r->$s['local_field_name'];
			$options=array_merge($options,array($rs->foreign_field_name=>$ids));
		} else {
			foreach ($this as $r) $ids[]=$r->get_id();
			$cur_class_name=get_class($this);
			$rs=new $cur_class_name;
			$options=array_merge($options,array($rs->id_field_name=>$ids));
		}
		$rs->find_records($options);
		$rs->preload();
		return $rs;
	}
	
	
	function preload() {
		if ($this->state==RS_STATE_UNLOADED || $this->state==RS_STATE_COUNTED) {
			$this->load_records();
		}
	}
	
	function first($create_record_if_null=false) {
		$rec=parent::first();
		if ($rec) return $rec;
		return $create_record_if_null ? $this->new_record(isset($this->query_options['options']) ? $this->query_options['options'] : array() ) : new gs_null(GS_NULL_XML);
	}

	function valid() {
		$this->preload();
		return parent::valid();
	}
    function offsetSet($offset, $value) {
		$this->preload();
		return parent::offsetSet($offset, $value);
    }
    function offsetExists($offset) {
		$this->preload();
		return parent::offsetExists($offset);
    }
    function offsetUnset($offset) {
		$this->preload();
		return parent::offsetUnset($offset);
    }
    function offsetGet($offset) {
		$this->preload();
		return parent::offsetGet($offset);
    }

	function count() {
		if ($this->state==RS_STATE_UNLOADED) {
			$cnt=$this->count_records($this->query_options['options']);
			$this->array=$cnt>0 ? array_fill(0,$cnt,NULL) : array();
			$this->state=RS_STATE_COUNTED;
		}
		return parent::count();
	}
	
	function limit ($offset,$limit=null) {
		if (is_null($limit)) {
			$this->query_options['options'][]=array('type'=>'limit','value'=>$offset);
		} else {
			$this->query_options['options'][]=array('type'=>'offset','value'=>$offset);
			$this->query_options['options'][]=array('type'=>'limit','value'=>$limit);
		}
		return $this;
	}

	function offset ($num) {
		$this->query_options['options'][]=array('type'=>'offset','value'=>$num);
		return $this;
	}
	
	function orderby ($orderby) {
		$this->query_options['options'][]=array('type'=>'orderby','value'=>$orderby);
		return $this;
	}

	public function find_records($options=null,$fields=null,$index_field_name=null) {
		mlog('RS_INIT on '.get_class($this));//.' options:'.print_r($options,TRUE));
		$this->query_options['options']=$this->string2options($options);
		$this->query_options['index_field_name'] = is_string($index_field_name) ? $index_field_name : $this->id_field_name;
		if ($fields && !is_array($fields)) {
			$fields=array_filter(explode(',',$fields));
		}
		foreach ($this->structure['recordsets'] as $link) {
			$fields[]=$link['local_field_name'];
		}
		if (is_array($fields)) $fields=array_unique($fields);
		$this->query_options['fields']=$fields;
		$this->reset();
		$this->state=RS_STATE_UNLOADED;
		cfg_set('handler_cache_status',cfg('handler_cache_status') | $this->handler_cache_status);
		mlog('RS_STATE_UNLOADED on '.get_class($this));//.' options:'.print_r($options,TRUE));
		return $this;
	}
	public function late_load_records() {
		$this->load_records($this->query_options['late_load_fields']);
		$this->query_options['late_load_fields']=array();
	}
	public function load_records($fields=NULL) {
		if (!isset($this->query_options['options'])) {
			$this->state=RS_STATE_LOADED;
			return $this;
		}
		$options=$this->query_options['options'];
		$index_field_name=$this->query_options['index_field_name'];
		//$fields=$fields ? array_merge($fields,array($index_field_name)) : $this->query_options['fields'];

		if (!$fields) $fields=$this->query_options['fields'];
		if (!$fields) $fields=array();
		$fields=array_unique(array_merge($fields,array($index_field_name)));


		if(!$fields) $fields=array($index_field_name);
		if (!in_array($this->id_field_name,$fields)) $fields[]=$this->id_field_name;
		$this->get_connector()->select($this,$options,$fields);
		$ret=NULL;
		$records=$this->state==RS_STATE_LOADED ? $this->array : array();
		$res=$this->get_connector()->fetchall();
		foreach ($res as $r) {
			/*
			$record=gs_recfabric::get_record($this,$fields,$r);
			*/
			if ($this->state==RS_STATE_LOADED) {
				if(isset($records[$r[$index_field_name]])) {
					$records[$r[$index_field_name]]->fill_values($r);
				}
				continue;
			} 
			$record=new gs_record($this,$fields);
			$record->fill_values($r);
			$record->recordstate = RECORD_UNCHANGED;
			if (!$record->$index_field_name || isset($records[$record->$index_field_name])) {
				$records[]=$record;
			} else {
				$records[$record->$index_field_name]=$record;
			}
		}
		if (isset($records)) $this->replace($records);
		$this->state=RS_STATE_LOADED;
		return $this;
	}
	public function count_records($options=array()) {
		$options=$this->string2options($options);
		if (isset($this->query_options['options'])) $options=array_merge($this->query_options['options'],$options); // Add to array of options all options what already used from lazy load (c) Andrey Pakhomov
		foreach($options as $k=>$o) {
			if (in_array(strtolower($o['type']),array('limit','offset','orderby'))) unset($options[$k]);
		}
		$this->get_connector()->select($this,$options,array('count(*) as count'));
		$res=reset($this->get_connector()->fetchall());
		return $res['count'];
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

	public function delete($record=null) {
		if ($record===null) {
			foreach ($this as $r) $r->delete();
			return $this;
		}
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
		foreach($this->structure['recordsets'] as $name=>$structure) {
			if (isset($structure['rs1_name']) && isset($structure['rs2_name']))  {
				$obj=new gs_rs_links($structure['rs1_name'],$structure['rs2_name'],$structure['recordset'],false,$name);
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
			//$this->commit();
		} else {
			$this->altertable();
			//$this->commit();
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
		$this->preload();
		return ($r=parent::current()) ? $r : new gs_null(GS_NULL_XML);
	}

	public function process_trigger($event,&$rec) {
		if (isset($this->structure['triggers']) && isset($this->structure['triggers'][$event])) {
			$triggers=$this->structure['triggers'][$event];
			if (!is_array($triggers)) $triggers=array($triggers);
			foreach ($triggers as $k => $t) {
				$args=explode(':',$t);
				$fname=array_shift($args);
				array_unshift($args,$k);
				if (!method_exists($this,$fname)) throw new gs_dbd_exception("triggers: no method '$fname' exists:".get_class($this).":$event:$fname",DBD_TRIGGER_FUNC_NOT_EXISTS);
				$this->$fname($rec,$event,$args);
			}
		}
	}

	public function get_recordset_name() {
		return get_class($this);
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

	static function &get_instance()
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
			throw new gs_dbd_exception('gs_connector: '.$gs_connector_id.'  not exists in config');
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
		                        'fulltext'=>'FULLTEXT',
		                        //'serial'=>'PRIMARY AUTO_INCREMENT',
		                        //'serial'=>'PRIMARY AUTO_INCREMENT',
		                    );
		$this->_field_types=array( 
		                           'serial'=>'INT AUTO_INCREMENT PRIMARY KEY',
					   'int'=>'INT',
					   'bigint'=>'BIGINT',
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
			$table_fields[$name]=sprintf("%s %s %s",$name, $k, isset($field['default']) ? 'NOT NULL DEFAULT '.$this->escape_value($field['default']) : '');

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
			} else if (is_array($value) && isset($value['type']) && $value['type']=='condition') {
				unset($value['type']);
				$condition=$value['condition'];
				unset($value['condition']);
				$txt=$this->construct_where($value,$condition);
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
	function table_exists($tablename);
	function get_table_fields($tablename);
}


class gs_dbd_exception extends gs_exception {
}


?>
