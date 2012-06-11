<?php 
class gs_dbdriver_mongo extends gs_prepare_sql implements gs_dbdriver_interface {
	private $cinfo;
	private $db_connection;
	private $_res;
	private $_id;
	private $stats;
	function __construct($cinfo) {
		parent::__construct();
		$this->_field_types['serial']='INTEGER PRIMARY KEY';
		$this->cinfo=$cinfo;
		$this->_id=rand();
		$this->_cache=array();
		$this->_que=null;
		$this->stats['total_time']=0;
		$this->stats['total_queries']=0;
		$this->stats['total_rows']=0;
		$this->connect();
	}
	
	function __destruct() {
		if (DEBUG) {
			//var_dump($this->stats);
		}
	}

	function escape_value($v,$c=null) {
		if (is_float($v)) {
			return sprintf('truncate(%s,5)',str_replace(',','.',sprintf('%.05f',$v)));
		}else if (is_numeric($v)) {
			return $v;
		} else if (is_null($v)) {
			return 'NULL';
		} else if (is_array($v)) {
			$arr=array();
			foreach($v as $k=>$l) {
				$arr[]=$this->escape_value($l);
			}
			return sprintf('(%s)',implode(',',$arr));
		} else if ($c=='LIKE') {
			return sprintf('%s',$this->db_connection->escapeString($v));
		} else {
			return sprintf("'%s'",$this->db_connection->escapeString($v));
		}
	}

	function escape($f,$c,$v) {
		$v_type='STRING';
		if (is_float($v)) {$v_type='FLOAT';}
		else if (is_numeric($v)) {$v_type='NUMERIC';}
		else if (is_array($v)) {$v_type=!empty($v) ? 'ARRAY' : 'NULL';}
		else if (is_null($v)) {$v_type='NULL';}


		$escape_pattern=$this->_escape_case[$c][$v_type];
		$ret=$this->replace_pattern($escape_pattern,$v,$f,$c);
		return $ret;

	}

	function replace_pattern($escape_pattern,$v,$f=null,$c=null) {
		preg_match_all('/{v/',$escape_pattern,$value_replaces);
		if (sizeof($value_replaces[0])>1) {
			$ret=str_replace('{f}',$f,$escape_pattern);
			for ($i=0; $i<sizeof($value_replaces[0]); $i++) {
				$ret=str_replace("{v$i}",$this->escape_value($v[$i]),$ret);
			}
		} else {
			$v=$this->escape_value($v,$c);
			$ret=str_replace(array('{f}','{v}'),array($f,$v),$escape_pattern);
		}
		return $ret;
	}


	function connect() {


		if(!class_exists('Mongo')) throw new gs_dbd_exception('gs_dbdriver_mongo: undefined class Mongo');
		$mongo=new Mongo(sprintf("mongodb://%s:%s@%s",
						$this->cinfo['db_username'],
						$this->cinfo['db_password'],
						$this->cinfo['db_hostname']));
		if (!$mongo) {
			throw new gs_dbd_exception('gs_dbdriver_mongo: can not open database '.$this->cinfo['db_hostname']);
		}
		$this->db_connection=$mongo->selectDB($this->cinfo['db_database']);
	}


	function query($que='') {
		$t=microtime(true);

		mlog($que);

		$this->_res=$this->db_connection->query($que);
		
		if ($this->_res===FALSE) {
			throw new gs_dbd_exception('gs_dbdriver_sqlite: '.$this->db_connection->lastErrorMsg().' in query '.$que);
		}
		$t=microtime(true)-$t;
		$rows=count($this->_res);
		mlog(sprintf("%.03f secounds, %d rows",$t, $rows));
		$this->stats['total_time']+=$t;
		$this->stats['total_queries']+=1;
		$this->stats['total_rows']+=$rows;
		return $this->_res;

	}
	function exec($que='') {
		$t=microtime(true);
		$que=trim($que,';').';';
		mlog($que);
		$this->_res=$this->db_connection->exec($que);
		if (!$this->_res) {
			throw new gs_dbd_exception('gs_dbdriver_sqlite: '.$this->db_connection->lastErrorMsg().' in query '.$que);
		}
		$t=microtime(true)-$t;
		$rows=0;
		mlog(sprintf("%.03f secounds, %d rows",$t, $rows));
		$this->stats['total_time']+=$t;
		$this->stats['total_queries']+=1;
		$this->stats['total_rows']+=$rows;
		return $this->_res;

	}
	function get_insert_id() {
		return $this->db_connection->lastInsertRowID();
	}
	public function get_table_names() {
		return $this->db_connection->listCollections();
	}
	public function get_fields_info ($tablename)  {
		return array();
	}
	public function table_exists($tablename) {
		$tables=$this->get_table_names();
		return in_array($tablename,$tables);
	}

	public function get_table_fields($tablename) {
		return array();
	}

	public function get_table_keys($tablename) {
		$c=$this->db_connection->selectCollection($tablename)->getIndexInfo();
		return $c;
	}

	function construct_createtable_fields($options) {
	}
	function construct_altertable_fields($tablename,$options) {
	}
	function construct_indexes($tablename,$structure) {
	}

	public function construct_droptable($tablename) {
		return $this->db_connection->dropCollection($tablename);
	}
	public function construct_altertable($tablename,$structure) {
	}
	public function construct_createtable($tablename,$structure) {
		return $this->db_connection->createCollection($tablename);
	}
	public function insert($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$fields=$values=array();
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ( $st['type']!='serial' && $record->is_modified($fieldname)) {
				$values[$fieldname]=$this->escape_value($record->$fieldname);
			}
		}
		$this->db_connection->selectCollection($rset->db_tablename)->insert($values);
		return $values['_id'];

	}
	public function update($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$values=array();
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ($record->is_modified($fieldname)) {
				//$fields[]=sprintf('%s=%s',$fieldname,$this->escape_value($record->$fieldname));
				$values[$fieldname]=$this->escape_value($record->$fieldname);
			}
		}
		$idname=$rset->id_field_name;
		return $this->db_connection->selectCollection($rset->db_tablename)->update(
									array('_id'=>$record->get_old_value($idname)),
									$values);

	}
	public function delete($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$idname=$rset->id_field_name;
		return $this->db_connection->selectCollection($rset->db_tablename)->remove(array('_id'=>$record->get_old_value($idname)));

	}
	function fetchall() {
		$ret=array();
		if (!$this->_que) {
			while ($r=$this->_res->fetchArray(SQLITE3_ASSOC)) $ret[]=$r;
			return $ret;
		}
		if (!isset($this->_cache[$this->_que])) {
			while ($r=$this->_res->fetchArray(SQLITE3_ASSOC)) $ret[]=$r;
			$this->_cache[$this->_que]=$ret;
		}
		$ret=$this->_cache[$this->_que];
		$this->_que=null;
		return $ret;
	}
	function fetch() {
		$res=$this->_res->fetchArray(SQLITE3_ASSOC);
		return $res;
	}
	function count($rset,$options) {
		$count=$this->db_connection->selectCollection($rset->db_tablename)->count($options);
		return array(array('count'=>$count));
	}
	function select($rset,$options,$fields=NULL) {
		$col=$this->selectCollection($rset);
		if ($fields) $ret=$col->find($options,$fields);
			else $ret=$col->find($options);

		return $ret;	
		/*
		$where=$this->construct_where($options);
		//md($rset->structure['fields'],1);
		$fields = is_array($fields) ? array_filter($fields) : array_keys($rset->structure['fields']);
		$que=sprintf("SELECT %s FROM %s ", implode(',',$fields), $rset->db_tablename);
		if (is_array($options)) foreach($options as $o) {
			if (isset($o['type'])) switch($o['type']) {
				case 'limit':
					$str_limit=sprintf(' LIMIT %d ',$this->escape_value($o['value']));
					break;
				case 'offset':
					$str_offset=sprintf(' OFFSET %d ',$this->escape_value($o['value']));
					break;
				case 'orderby':
					$str_orderby=sprintf(' ORDER BY %s ',$this->db_connection->escapeString($o['value']));
					break;
				case 'groupby':
					$str_groupby=sprintf(' GROUP BY %s ',$this->db_connection->escapeString($o['value']));
					break;
			}
		}
		if (!empty($where)) $que.=sprintf(" WHERE %s", $where);
		if (!empty($str_groupby)) $que.=$str_groupby;
		if (!empty($str_orderby)) $que.=$str_orderby;
		if (!empty($str_limit)) $que.=$str_limit;
		if (!empty($str_offset)) $que.=$str_offset;

		$this->_que=md5($que);
		if(isset($this->_cache[$this->_que])) {
			return true;
		}

		return $this->query($que);
		*/
	}
	private function selectCollection($rset) {
		return $this->db_connection->selectCollection($rset->db_tablename);
	}


}
?>
