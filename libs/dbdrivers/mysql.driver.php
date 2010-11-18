<?php 
class gs_dbdriver_mysql extends gs_prepare_sql implements gs_dbdriver_interface {
	private $cinfo;
	private $db_connection;
	private $_res;
	private $_id;
	function __construct($cinfo) {
		parent::__construct();
		$this->cinfo=$cinfo;
		$this->_id=rand();
		$this->connect();
	}

	function escape_value($v,$c=null) {
		if (is_float($v)) {
			return sprintf('truncate(%s,5)',$v);
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
			return sprintf('%s',mysql_real_escape_string($v));
		} else {
			return sprintf("'%s'",mysql_real_escape_string($v));
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


		$cinfo=$this->cinfo;
		if(!function_exists('mysql_connect')) throw new gs_dbd_exception('gs_dbdriver_mysql: undefined function mysql_connect() ');
		$this->db_connection=@mysql_connect($cinfo['db_hostname'].':'.$cinfo['db_port'],$cinfo['db_username'],$cinfo['db_password']);
		if ($this->db_connection ===FALSE) {
			throw new gs_dbd_exception('gs_dbdriver_mysql: '.mysql_error());
		}
		if (mysql_select_db($cinfo['db_database'],$this->db_connection)===FALSE) {
			throw new gs_dbd_exception('gs_dbdriver_mysql: '.mysql_error());
		}
	}


	function query($que='') {
		$t=microtime(true);
		if (DEBUG) {
			md($que);
		}
		$this->_res=mysql_query($que,$this->db_connection);
		if ($this->_res===FALSE) {
			throw new gs_dbd_exception('gs_dbdriver_mysql: '.mysql_error().' in query '.$que);
		}
		if (DEBUG) {
			md(sprintf("%.03f secounds, %d rows",microtime(true)-$t, mysql_affected_rows($this->db_connection)));
		}
		return $this->_res;

	}
	function get_insert_id() {
		return mysql_insert_id($this->db_connection);
	}
	public function table_exists($tablename) {
		$que=sprintf("SHOW TABLES LIKE '%s'",$tablename);
		$this->query($que);
		return $this->fetch();
	}

	public function get_table_fields($tablename) {
		$que=sprintf("SHOW FIELDS from %s",$tablename);
		$this->query($que);
		$r=array();
		while ($a=$this->fetch()) { 
			$r[$a['Field']]=$a['Field'];
		}
		return $r;
	}

	public function get_table_keys($tablename) {
		$que=sprintf("SHOW KEYS from %s",$tablename);
		$this->query($que);
		$r=array();
		while ($a=$this->fetch()) { 
			$r[$a['Column_name']]=$a['Key_name'];
		}
		return $r;
	}

	function construct_createtable_fields($options) {
		$table_fields=$this->construct_table_fields($options);
		return sprintf ('(%s)',implode(",",$table_fields));
	}
	function construct_altertable_fields($tablename,$options) {
		$tf=array();
		$table_fields=$this->construct_table_fields($options);
		$old_fields=$this->get_table_fields($tablename);

		$add_fields=array_diff(array_keys($table_fields),array_keys($old_fields));
		foreach($add_fields as $k=>$v) {
			$tf[]="ADD ".str_ireplace('AUTO_INCREMENT PRIMARY KEY','',$table_fields[$v]);
		}

		$mod_fields=array_intersect(array_keys($old_fields),array_keys($table_fields));
		foreach($mod_fields as $k=>$v) {
			if (!isset($options['fields'][$k]['type']) && $options['fields'][$v]['type']!='serial') 
				$tf[]="MODIFY ".str_ireplace('AUTO_INCREMENT PRIMARY KEY','',$table_fields[$v]);
		}

		$drop_fields=array_diff(array_keys($old_fields),array_keys($table_fields));
		foreach($drop_fields as $k=>$v) {
			if (!isset($options['fields'][$v]['type']) || $options['fields'][$v]['type']!='serial') 
				$tf[]="DROP $v";
		}

		return sprintf ('%s',implode(",",$tf));
	}
	function construct_indexes($tablename,$structure) {
			$construct_indexes=isset($structure['indexes']) && is_array($structure['indexes']) ? $structure['indexes'] : array();
			/*
			if (is_array($structure['fields'])) foreach ($structure['fields'] as $key=>$field) {
				if (isset($field['type']) && $field['type']=='serial') {
					 $construct_indexes[$key]=array('type'=>'serial');
					 break;
				}
			}
			*/
			$old_keys=$this->get_table_keys($tablename);
			foreach ($construct_indexes as $name=>$index) {
					if (!is_array($index)) {
					$name=$index;
					$index=array();
					}
					if (!isset($index['type'])) $index['type']='key';
					if (!isset($this->_index_types[$index['type']])) {
						throw new gs_dbd_exception('gs_dbdriver_mysql.construct_altertable: can not find definition for _index_types_'.$index['type']);
					}
					if (isset($old_keys[$name])) {
						//$que=sprintf('ALTER TABLE %s DROP %s KEY',$tablename,$old_keys[$name]);
						//$this->query($que);
					} else {
						$que=sprintf('CREATE %s INDEX %s ON %s(%s%s)',$this->_index_types[$index['type']],$name,$tablename,$name,isset($index['options'])?$index['options']:'');
						$this->query($que);
					}
				}
	}

	public function construct_droptable($tablename) {
			$que=sprintf('DROP TABLE IF EXISTS  %s',$tablename);
			return $this->query($que);
	}
	public function construct_altertable($tablename,$structure) {
		switch (isset($structure['type']) ? $structure['type'] : '') {
		case 'view':
			$this->construct_droptable($tablename);
			return $this->construct_createtable($tablename,$structure);
		break;
		default:
			$construct_fields=$this->construct_altertable_fields($tablename,$structure);
			$que=sprintf('ALTER TABLE  %s %s',$tablename, $construct_fields);
			$this->query($que);
			$this->construct_indexes($tablename,$structure);
			break;
		}
	}
	public function construct_createtable($tablename,$structure) {
		switch (isset($structure['type']) ? $structure['type'] : '') {
		case 'view':
			foreach($structure['recordsets'] as $rs_name) {
				$obj[$rs_name]=new $rs_name;
				if (isset($obj[$rs_name]->structure['keys'])) foreach ($obj[$rs_name]->structure['keys'] as $key) {
					if ($key['type']=='foreign' && in_array($key['recordset'],$structure['recordsets'] )) {
						$que=sprintf('CREATE VIEW %s AS SELECT * FROM %s LEFT JOIN  %s ON (%s.%s=%s.%s)',
								$tablename,$obj[$key['recordset']]->table_name,$obj[$rs_name]->table_name,
								$obj[$rs_name]->table_name, $key['local_field_name'],
								$obj[$key['recordset']]->table_name,$key['foreign_field_name']);

						var_dump($que);
						$this->query($que);
						return TRUE;

					}
				}

			}
				
		break;
		default:
			$construct_fields=$this->construct_createtable_fields($structure);
			$this->construct_droptable($tablename);
			$que=sprintf('CREATE TABLE  %s %s',$tablename, $construct_fields);
			$this->query($que);
			$this->construct_indexes($tablename,$structure);
			break;
		}
	}
	public function insert($record) {
		$rset=$record->get_recordset();
		$fields=$values=array();
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ( $st['type']!='serial') {
				$fields[]=$fieldname;
				$values[]=$this->escape_value($record->$fieldname);
			}
		}
		$que=sprintf('INSERT INTO %s (%s) VALUES  (%s)',$rset->db_tablename,implode(',',$fields),implode(',',$values));
		$this->query($que);
		return $this->get_insert_id();

	}
	public function update($record) {
		$rset=$record->get_recordset();
		$fields=array();
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ($record->is_modified($fieldname)) {
				$fields[]=sprintf('%s=%s',$fieldname,$this->escape_value($record->$fieldname));
			}
		}
		if (sizeof($fields)==0) return;
		$idname=$rset->id_field_name;
		$que=sprintf('UPDATE %s SET %s WHERE %s=%s',$rset->db_tablename,implode(',',$fields),$idname,$this->escape_value($record->get_old_value($idname)));
		return $this->query($que);

	}
	public function delete($record) {
		$rset=$record->get_recordset();
		$idname=$rset->id_field_name;
		$que=sprintf('DELETE FROM %s  WHERE %s=%s',$rset->db_tablename,$idname,$this->escape_value($record->get_old_value($idname)));
		return $this->query($que);

	}
	function fetch() {
		$ret=mysql_fetch_assoc($this->_res);
		return($ret);
	}
	function select($rset,$options,$fields=NULL) {
		$where=$this->construct_where($options);
		//md($rset->structure['fields'],1);
		$fields = is_array($fields) ? $fields : array_keys($rset->structure['fields']);
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
					$str_orderby=sprintf(' ORDER BY %s ',mysql_real_escape_string($o['value']));
					break;
				case 'groupby':
					$str_groupby=sprintf(' GROUP BY %s ',mysql_real_escape_string($o['value']));
					break;
			}
		}
		if (!empty($where)) $que.=sprintf(" WHERE %s", $where);
		if (!empty($str_groupby)) $que.=$str_groupby;
		if (!empty($str_orderby)) $que.=$str_orderby;
		if (!empty($str_limit)) $que.=$str_limit;
		if (!empty($str_offset)) $que.=$str_offset;
		return $this->query($que);
	}


}
?>
