<?php
abstract class gs_recordset_view extends gs_recordset {
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

		if (!$this->get_connector()->table_exists($this->table_name)) {
			$this->createtable();
			$this->commit();
		} else {
			$this->altertable();
			$this->commit();
		}
	}

}

class field_interface {
	function init($arr,$init_opts) {
		$structure =array('fields'=>array(),
				'recordsets'=>array(),
				'htmlforms'=>array(),
				'fkeys'=>array(),
				);
		$arr=preg_replace('|=\s*([^\'\"][^\s]*)|i','=\'\1\'',$arr);
		//$arr=preg_replace('|=\s*([^\'\"][^\s]*)|i','=\'\1\'',$arr);
		//md($arr,1);
		$ret=array();
		foreach ($arr as $k=>$s) {
			preg_match_all(':(\s(([a-z_]+)=)?[\'\"](.+?)[\'\"]|([^\s]+)):i',$s,$out);
			$j=0;
			$r=array('required'=>'true');
			foreach ($out[3] as $i => $v) {
				$key=$v ? $v : $j++;
				$value = $out[4][$i] ? $out[4][$i] : $out[1][$i];
				$r[$key]=$value;
			}
			$r['func_name']=$r[0];
			if (in_array($r['func_name'],array('lMany2Many','lMany2One','lOne2One'))) {
				$r['linked_recordset']=$r[1];
				if (!isset($r['verbose_name'])) $r['verbose_name']=isset($r[2]) ? $r[2] : $k;
			} else {
				if (!isset($r['verbose_name'])) $r['verbose_name']=isset($r[1]) ? $r[1] : $k;
			}
			$ret[$k]=$r;
		}
		foreach ($ret as $k => $r) {
			if (!method_exists('field_interface',$r['func_name']))
				throw new gs_exception("field_interface: no method '".$r['func_name']."'");

			self::$r['func_name']($k,$r,$structure,$init_opts);
		}
		return $structure;
	}

	function fString($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'varchar','options'=>isset($opts['max_length']) ? $opts['max_length'] : 255);
		$structure['htmlforms'][$field]=array(
			'type'=>'input', 
			'verbose_name'=>$opts['verbose_name'], 
			);

		if (strtolower($opts['required'])=='false') {
			$structure['htmlforms'][$field]['validate']='dummyValid';
		} else {
			$structure['htmlforms'][$field]['validate']='isLength';
			$structure['htmlforms'][$field]['validate_params']=array(
					'min'=>isset($opts['min_length']) ? (int)($opts['min_length']) : 1,
					'max'=>isset($opts['max_length']) ? (int)($opts['max_length']) : $structure['fields'][$field]['options'],
					);
			if (isset($opts['validate_regexp'])) {
				$structure['htmlforms'][]=array(
					'id'=>$field,
					'validate'=>'isRegexp',
					'options'=>$opts['validate_regexp'],
					);
			}
		}
	}
	function fDateTime($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'date');
		$structure['htmlforms'][$field]=array(
			'type'=>'input',
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'isDate'
		);
	}
	function fText($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'text');
		$structure['htmlforms'][$field]=array(
			'type'=>'text',
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty'
		);
	}
	function f___dummy($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'varchar','options'=>255);
		$structure['htmlforms'][$field]=array(
			'type'=>'input',
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty'
		);
	}
	function fSelect($field,$opts,&$structure,$init_opts) { self::f___dummy($field,$opts,&$structure,$init_opts);}
	function fPassword($field,$opts,&$structure,$init_opts) { self::f___dummy($field,$opts,&$structure,$init_opts);}
	function lOne2One($field,$opts,&$structure,$init_opts) {
		$fname=$field.'_id';
		$structure['fields'][$fname]=array('type'=>'int');
		$structure['htmlforms'][$fname]=array(
			'type'=>'input',
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty'
		);
		$structure['recordsets'][$field]=array(
			'recordset'=>$opts['linked_recordset'],
			'local_field_name'=>$fname,
			'foreign_field_name'=>'id',
			);


	}
	function lMany2One($field,$opts,&$structure,$init_opts) {
		if(isset($init_opts['skip_many2many'])) return;
		list($rname,$linkname)=explode(':',$opts['linked_recordset']);
		$obj=new $rname(array('skip_many2many'=>true));
		$obj_rs=$obj->structure['recordsets'][$linkname];
		$structure['recordsets'][$field]=array(
			'recordset'=>$rname,
			'local_field_name'=>'id',
			'foreign_field_name'=>$obj_rs['local_field_name'],
			);
		$structure['fkeys'][]=array('link'=>$field,'on_delete'=>'RESTRICT','on_update'=>'CASCADE');
		
	}
	function lMany2Many($field,$opts,&$structure,$init_opts) {
		@list($rname,$table_name,$foreign_field_name)=explode(':',$opts['linked_recordset']);
		/*
		new gs_rs_links($init_opts['recordset'],$rname,$table_name);	
		нужно переопределить lazy_load в _short чтобы он для rs_links вызывал хитрый конструктор.

		*/
		$structure['recordsets'][$field]=array(
			'recordset'=>$table_name,
			'rs1_name'=>$init_opts['recordset'],
			'rs2_name'=>$rname,
			'local_field_name'=>'id',
			'foreign_field_name'=>$foreign_field_name ? $foreign_field_name : $init_opts['recordset'].'_id',
			);
		//$structure['fkeys'][]=array('link'=>$field,'on_delete'=>'CASCADE','on_update'=>'CASCADE');
	}
	function install()  {

	}
}

class gs_rs_links extends gs_recordset{
        public $id_field_name='id';
        public $structure=array(
                'fields'=>array(
                        'id'=>array('type'=>'serial'),
			),
		);

	function __construct($rs1,$rs2,$table_name) { 
		$this->table_name=$table_name;
		$this->rs1_name=$rs1;
		$this->rs2_name=$rs2;
		$conn_id=key(cfg('gs_connectors'));

		$f1=$rs1.'_id';
		$f2=$rs1!=$rs2 ? $rs2.'_id': 'id2';
		$this->structure['fields'][$f1]=array('type'=>'int');
		$this->structure['fields'][$f2]=array('type'=>'int');

		$this->structure['recordsets']['parents']=array('recordset'=>$rs1,'local_field_name'=>$f1,'foreign_field_name'=>'id');
		$this->structure['recordsets']['childs']=array('recordset'=>$rs2,'local_field_name'=>$f2,'foreign_field_name'=>'id');
		/*
		*/
		$this->structure['fkeys'][]=array('link'=>'parents','on_delete'=>'CASCADE','on_update'=>'CASCADE');
		$this->structure['fkeys'][]=array('link'=>'childs','on_delete'=>'CASCADE','on_update'=>'CASCADE');
                return parent::__construct($conn_id,$table_name);

	}
	/*
	function __get($name) {
		md('-----------',1);
		md($name,1);
		return parent::__get('childs');
	}
	*/
	public function find_records($options=null,$fields=null,$index_field_name=null) {
		parent::find_records($options,$fields,$index_field_name);
		if (isset($this->parent_record)) {
			md('gs_rs_links lazy load',1);
			$idname=$this->structure['recordsets']['childs']['local_field_name'];
			$ids=array();
			foreach ($this as $t) $ids[]=$t->$idname;
			$rsname=$this->structure['recordsets']['childs']['recordset'];
			$rs=new $rsname();
			$rs=$rs->find_records(array('id'=>$ids));
			$this->links=$this->array;
			$this->array=$rs->array;
		}
		return $this;
	}
	public function commit() {
		$ret=parent::commit();
		if (isset($this->links)) foreach ($this->links as $l) $l->commit();
		return $ret;
	}
}
class gs_recordset_short extends gs_recordset {
	function __construct($s=false,$init_opts=false) {
		$this->init_opts=$init_opts;
		$this->init_opts['recordset']=get_class($this);
		if (!$s || !is_array($s)) throw new gs_exception('gs_recordset_short :: empty init values');
		$this->table_name=get_class($this);
		$this->id_field_name='id';
		$this->gs_connector_id=key(cfg('gs_connectors'));
		$this->structure['fields'][$this->id_field_name]=array('type'=>'serial');
		$this->selfinit($s);
		parent::__construct($this->gs_connector_id,$this->table_name);
		//md($this,1);
	}

	function selfinit($arr) {
		$struct=field_interface::init($arr,$this->init_opts);
		foreach ($struct as $k=>$s)
			$this->structure[$k]=isset($this->structure[$k]) ? array_merge($this->structure[$k],$struct[$k]) : $struct[$k];
		//md($this->structure,1);
	}
}

?>
