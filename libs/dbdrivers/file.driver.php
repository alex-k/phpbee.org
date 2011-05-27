<?php 
define('GS_DB_FILE_ID_LENGTH',4);
class gs_dbdriver_file extends gs_prepare_sql implements gs_dbdriver_interface {
	private $cinfo;
	private $db_connection;
	private $_res;
	private $_id;
	private $stats;
	function __construct($cinfo) {
		parent::__construct();
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

	function connect() {
		$cinfo=$this->cinfo;
		check_and_create_dir($cinfo['db_root']);
		$this->root=$cinfo['db_root'];
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
		$t=microtime(true)-$t;
		$rows=mysql_affected_rows($this->db_connection);
		if (DEBUG) {
			md(sprintf("%.03f secounds, %d rows",$t, $rows));
		}
		$this->stats['total_time']+=$t;
		$this->stats['total_queries']+=1;
		$this->stats['total_rows']+=$rows;
		return $this->_res;

	}
	public function table_exists($tablename) {
		$fname=$this->root.DIRECTORY_SEPARATOR.$tablename;
		return is_dir($fname);
	}

	public function get_table_fields($tablename) {
		$r=array();
		$fname=$this->root.DIRECTORY_SEPARATOR.$tablename.DIRECTORY_SEPARATOR.'fields';
		if (file_exists($fname)) $r=unserialize(file_get_contents($fname));
		return $r;
	}

	public function get_table_keys($tablename) {
		$r=array();
		return $r;
	}

	function construct_createtable_fields($options) {
		$table_fields=$this->construct_table_fields($options);
		return sprintf ('(%s)',implode(",",$table_fields));
	}
	function construct_altertable_fields($tablename,$options) {
		$fname=$this->root.DIRECTORY_SEPARATOR.$tablename;
		$tf=array();
		$table_fields=$this->construct_table_fields($options);
		$old_fields=$this->get_table_fields($tablename);

		$add_fields=array_diff(array_keys($table_fields),array_keys($old_fields));
		$mod_fields=array_intersect(array_keys($old_fields),array_keys($table_fields));
		$drop_fields=array_diff(array_keys($old_fields),array_keys($table_fields));
		foreach($drop_fields as $k=>$v) {
			$files=glob($fname.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.$v);
			foreach ($files as $del_fname) unlink ($del_fname);
		}
	}
	function construct_indexes($tablename,$structure) {
	}

	public function construct_droptable($tablename) {
		$fname=$this->root.DIRECTORY_SEPARATOR.$tablename;
		return rmdir($fname);
	}
	public function construct_altertable($tablename,$structure) {
		switch (isset($structure['type']) ? $structure['type'] : '') {
		case 'view':
			$this->construct_droptable($tablename);
			return $this->construct_createtable($tablename,$structure);
		break;
		default:
			$this->construct_altertable_fields($tablename,$structure);
			$table_fields=$this->construct_table_fields($structure);
			$fname=$this->root.DIRECTORY_SEPARATOR.$tablename;
			file_put_contents($fname.DIRECTORY_SEPARATOR.'fields',serialize($table_fields));
		}
	}
	public function construct_createtable($tablename,$structure) {
		switch (isset($structure['type']) ? $structure['type'] : '') {
		case 'view':
			throw new gs_dbd_exception('gs_dbdriver_file.construct_createtable: view have not implemented for file dbdriver');
		break;
		default:
			$fname=$this->root.DIRECTORY_SEPARATOR.$tablename;
			check_and_create_dir($fname);
			$table_fields=$this->construct_table_fields($structure);
			file_put_contents($fname.DIRECTORY_SEPARATOR.'fields',serialize($table_fields));
			break;
		}
	}
	function get_id($tablename) {
		$cname=$this->root.DIRECTORY_SEPARATOR.$tablename.DIRECTORY_SEPARATOR.'counter';
		$counter=file_exists($cname) ?  file_get_contents($cname)+1 : 0;

		$r_id=$this->_get_id($tablename,$counter);
		while (file_exists($r_id)) {
			$counter++;
			$r_id=$this->_get_id($tablename,$counter);
		}
		file_put_contents($cname,$counter);
		return $r_id;
	}
	
	function _get_id($tablename,$id) {
		$d=array(
			'0'=>'a','1'=>'b','2'=>'c','3'=>'d','4'=>'e','5'=>'f','6'=>'g','7'=>'h',
			'8'=>'i','9'=>'j','a'=>'k','b'=>'l','c'=>'m','d'=>'n','e'=>'o','f'=>'p',
			'g'=>'q','h'=>'r','i'=>'s','j'=>'t','k'=>'u','l'=>'v','m'=>'w','n'=>'x',
			'o'=>'y','p'=>'z');
		$id=str_pad(strtr(base_convert($id,10,26),$d),GS_DB_FILE_ID_LENGTH,'a',STR_PAD_LEFT);
		$id=$this->split_id($id);
		$ret=$this->root.DIRECTORY_SEPARATOR.$tablename.DIRECTORY_SEPARATOR.$id;
		return $ret;
	}
	function split_id($id) {
		$id=str_split($id,1);
		for($i=1;$i<GS_DB_FILE_ID_LENGTH;$i++) {
			$id[$i]=$id[$i-1].$id[$i];
		}
		return implode(DIRECTORY_SEPARATOR,$id);
	}
	
	public function insert($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$fields=$values=array();
		$id=$this->get_id($rset->db_tablename);
		check_and_create_dir($id);
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ( $st['type']!='serial' && $record->is_modified($fieldname)) {
				//$fields[]=$fieldname;
				//$values[]=$this->escape_value($record->$fieldname);
				file_put_contents($id.DIRECTORY_SEPARATOR.escapeshellcmd($fieldname),$record->$fieldname);
			}
		}
		return basename($id);

	}
	public function update($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$id=$this->root.DIRECTORY_SEPARATOR.$rset->db_tablename.DIRECTORY_SEPARATOR.$this->split_id($record->get_id());
		$fields=array();
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ($record->is_modified($fieldname)) {
				file_put_contents($id.DIRECTORY_SEPARATOR.escapeshellcmd($fieldname),$record->$fieldname);
			}
		}
	}
	public function delete($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$id=$this->root.DIRECTORY_SEPARATOR.$rset->db_tablename.DIRECTORY_SEPARATOR.$this->split_id($record->get_id());
		$files=glob($id.DIRECTORY_SEPARATOR.'*');
		foreach($files as $f) {
			unlink($f);
		}
		rmdir($id);
	}
	function fetchall() {
		return $this->_res;
	}
	function fetch() {
		return next($this->_res);
	}
	function select($rset,$options,$fields=NULL) {
		$t=microtime(true);
		$this->_res=array();
		$fields = is_array($fields) ? $fields : array_keys($rset->structure['fields']);
		$fname=$this->root.DIRECTORY_SEPARATOR.$rset->db_tablename;
		$where=$this->construct_where($options);
		if (isset($options[$rset->id_field_name])) {
			$mask=DIRECTORY_SEPARATOR.$this->split_id($options[$rset->id_field_name]);
		} else {
			$mask=str_repeat(DIRECTORY_SEPARATOR.'*',GS_DB_FILE_ID_LENGTH);
		}
		$mask=$fname.$mask;

		$files=glob($mask);
		foreach ($files as $f) {
			$d=array(
				$rset->id_field_name=>basename($f)
				);
			foreach ($fields as $field) {
				if (!isset($d[$field])) $d[$field]=file_exists($f.DIRECTORY_SEPARATOR.$field) ? file_get_contents($f.DIRECTORY_SEPARATOR.$field) : NULL;
			}
			$this->_res[basename($f)]=$d;

		}
		mlog(sprintf('File query: %s fields: %s (%.06f sec)',$mask,implode(',',$fields),(microtime(1)-$t)));
		return $this->_res;
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


}
?>
