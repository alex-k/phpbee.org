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
				'indexes'=>array(),
				);
		$ret=array();
		/*
		$arr=preg_replace('|=\s*([^\'\"][^\s]*)|i','=\'\1\'',$arr);
		foreach ($arr as $k=>$s) {
			preg_match_all(':(\s(([a-z_]+)=)?[\'\"](.+?)[\'\"]|([^\s]+)):i',$s,$out);
			*/
		$arr=string_to_params($arr);
		foreach ($arr as $k=>$r) {
			if(!isset($r['required'])) $r['required']='true';

			$r['func_name']=$r[0];
			if (in_array($r['func_name'],array('lMany2Many','lMany2One','lOne2One'))) {
				$r['linked_recordset']=$r[1];
				if (!isset($r['hidden'])) $r['hidden']=!(isset($r['verbose_name']) || isset($r[2])) ;
				if (!isset($r['verbose_name'])) $r['verbose_name']=isset($r[2]) ? $r[2] : $k;
				$r['counter'] = isset($r['counter']) && (!$r['counter'] || strtolower($r['counter'])=='false') ? false : true;
			} else {
				if (!isset($r['hidden'])) $r['hidden']=!(isset($r['verbose_name']) || isset($r[1])) ;
				if (!isset($r['verbose_name'])) $r['verbose_name']=isset($r[1]) ? $r[1] : $k;
			}
			$ret[$k]=$r;
		}
		foreach ($ret as $k => $r) {
			if (!method_exists('field_interface',$r['func_name']))
				throw new gs_exception("field_interface: no method '".$r['func_name']."'");
			self::$r['func_name']($k,$r,$structure,$init_opts);
			if (isset($r['default']) && !isset($structure['fields'][$k]['default'])) $structure['fields'][$k]['default']=$r['default'];
		}
		return $structure;
	}

	function fString($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'varchar','options'=>isset($opts['max_length']) ? $opts['max_length'] : 255);
		$structure['htmlforms'][$field]=array(
			'type'=>'input', 
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'], 
			);

		if (strtolower($opts['required'])=='false') {
			$structure['htmlforms'][$field]['validate']='dummyValid';
		} else {
			$structure['htmlforms'][$field]['validate'][]='isLength';
			$structure['htmlforms'][$field]['validate_params']['min']=isset($opts['min_length']) ? (int)($opts['min_length']) : 1;
			$structure['htmlforms'][$field]['validate_params']['max']=isset($opts['max_length']) ? (int)($opts['max_length']) : $structure['fields'][$field]['options'];
			if (isset($opts['validate_regexp'])) {
				$structure['htmlforms'][$field]['validate'][]='isRegexp';
				$structure['htmlforms'][$field]['validate_params']['validate_regexp']=$opts['validate_regexp'];
			}
		}
		if (isset($opts['unique']) && strtolower($opts['unique'])=='true') {
			$structure['htmlforms'][$field]['validate'][]='checkField';
			$structure['htmlforms'][$field]['validate_params']['class']=get_class($this);
			$structure['htmlforms'][$field]['validate_params']['field']=$field;
			//'validate_params'=>array('class'=>'users','field'=>'userLogin','message'=>'Login invalid or occupied'
		}
		if (isset($opts['default'])) {
			$structure['htmlforms'][$field]['default']=$opts['default'];
		}
	}
	function fPassword($field,$opts,&$structure,$init_opts) {
		return self::fString($field,$opts,$structure,$init_opts);
	}
	function fCheckbox($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'int');
		$structure['htmlforms'][$field]=array(
			'type'=>'checkbox',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'isNumber'
		);
	}
	function fInt($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'int');
		$structure['htmlforms'][$field]=array(
			'type'=>'input',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'isNumber'
		);
	}
	
	function fFloat($field,$opts,&$structure,$init_opts) {
		self::fInt($field,$opts,$structure,$init_opts);
		$structure['fields'][$field]=array('type'=>'float');
	}
	
	function fEmail($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'varchar','options'=>isset($opts['max_length']) ? $opts['max_length'] : 255);
		$structure['htmlforms'][$field]=array(
			'type'=>'email',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
		);
		$structure['htmlforms'][$field]['validate'][]=strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty';
		
		if (isset($opts['unique']) && strtolower($opts['unique'])=='true') {
			$structure['htmlforms'][$field]['validate'][]='checkField';
			$structure['htmlforms'][$field]['validate_params']['class']=get_class($this);
			$structure['htmlforms'][$field]['validate_params']['field']=$field;
			//'validate_params'=>array('class'=>'users','field'=>'userLogin','message'=>'Login invalid or occupied'
		}
	}
	function fDateTime($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'date');
		$structure['htmlforms'][$field]=array(
			'type'=>'datetime',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'isDate'
		);
	}
	function fText($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'text');
		$structure['htmlforms'][$field]=array(
			'type'=>'text',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty'
		);
	}
	
	function fFile($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'longblob');
		$structure['htmlforms'][$field]=array(
			'type'=>'file',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty'
		);
	}
	
	function fSelect($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'varchar','options'=>isset($opts['max_length']) ? $opts['max_length'] : 255);
		$structure['htmlforms'][$field]=array(
			'type'=>'Select',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty',
			'options'=>explode(',',$opts['values']),
		);
		$structure['indexes'][$field]=$field;
	}
	function f___dummy($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'varchar','options'=>255);
		$structure['htmlforms'][$field]=array(
			'type'=>'input',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty'
		);
	}
	function lOne2One($field,$opts,&$structure,$init_opts) {
		$fname=$field.'_id';
		$structure['fields'][$fname]=array('type'=>'int');
		$structure['htmlforms'][$fname]=array(
			'type'=>'lOne2One',
			'linkname'=>$field,
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty',
			'nulloption'=>(isset($opts['nulloption']) && $opts['nulloption'] && strtolower($opts['nulloption'])!='false') ? true : false ,
		);
		$structure['indexes'][$fname]=$fname;
		$structure['recordsets'][$field]=array(
			'recordset'=>$opts['linked_recordset'],
			'local_field_name'=>$fname,
			'foreign_field_name'=>'id',
			'update_recordset'=>$opts['linked_recordset'],
			);
		$structure['fkeys'][]=array('link'=>$field,'on_delete'=>'RESTRICT','on_update'=>'CASCADE');


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
		if($opts['counter']) {
			$counter_fieldname='_'.$field.'_count';
			$structure['recordsets'][$field]['counter_fieldname']=$counter_fieldname;
			$structure['fields'][$counter_fieldname]=array('type'=>'int','default'=>0);
			$structure['htmlforms'][$counter_fieldname]=array( 'type'=>'fInt', 'hidden'=>'true',);
		}
	}
	function lMany2Many($field,$opts,&$structure,$init_opts) {
		@list($rname,$table_name,$foreign_field_name)=explode(':',$opts['linked_recordset']);
		/*
		new gs_rs_links($init_opts['recordset'],$rname,$table_name);	
		нужно переопределить lazy_load в _short чтобы он для rs_links вызывал хитрый конструктор.

		*/
		$structure['htmlforms'][$field]=array(
			'type'=>'lMany2Many',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty',
		);

		$structure['recordsets'][$field]=array(
			'recordset'=>$table_name,
			'rs1_name'=>$init_opts['recordset'],
			'rs2_name'=>$rname,
			'rs_link'=>false,
			'local_field_name'=>'id',
			'foreign_field_name'=>$foreign_field_name ? $foreign_field_name : $init_opts['recordset'].'_id',
			'type'=>'many',
			);
		$structure['recordsets']['_'.$field]=$structure['recordsets'][$field];
		$structure['recordsets']['_'.$field]['rs_link']=true;

		if ($opts['counter']) {
			$counter_fieldname='_'.$field.'_count';
			$structure['fields'][$counter_fieldname]=array('type'=>'int','default'=>0);
			$structure['htmlforms'][$counter_fieldname]=array( 'type'=>'fInt', 'hidden'=>'true',);
			$structure['recordsets'][$field]['counter_fieldname']=$counter_fieldname;
		}

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

	function __construct($rs1,$rs2,$table_name,$rs_link=false,$link_name='') { 
		$this->table_name=$table_name;
		$this->link_name=$link_name;
		$this->rs1_name=$rs1;
		$this->rs2_name=$rs2;
		$this->rs_link=$rs_link;
		$conn_id=key(cfg('gs_connectors'));

		$f1=$rs1.'_id';
		$f2=$rs1!=$rs2 ? $rs2.'_id': 'id2';
		$this->structure['fields'][$f1]=array('type'=>'int');
		$this->structure['fields'][$f2]=array('type'=>'int');

		$this->structure['indexes'][$f1]=$f1;
		$this->structure['indexes'][$f2]=$f2;

		$this->structure['recordsets']['parents']=array('recordset'=>$rs1,'local_field_name'=>$f1,'foreign_field_name'=>'id','update_recordset'=>$rs2,'update_link'=>$link_name);
		$this->structure['recordsets']['childs']=array('recordset'=>$rs2,'local_field_name'=>$f2,'foreign_field_name'=>'id','update_recordset'=>$rs1,'update_link'=>$link_name);
		/*
		*/
		$this->structure['fkeys'][]=array('link'=>'parents','on_delete'=>'CASCADE','on_update'=>'CASCADE');
		$this->structure['fkeys'][]=array('link'=>'childs','on_delete'=>'CASCADE','on_update'=>'CASCADE');
                return parent::__construct($conn_id,$table_name);

	}
	public function find($opts) {
		return $this->first()->get_recordset()->find($opts);
	}
	public function find_records($options=null,$fields=null,$index_field_name=null) {
		parent::find_records($options,$fields,$index_field_name);
		if ($this->rs_link) return $this;
		if (isset($this->parent_record)) {
			$idname=$this->structure['recordsets']['childs']['local_field_name'];
			$ids=array();
			foreach ($this as $t) $ids[]=$t->$idname;
			$rsname=$this->structure['recordsets']['childs']['recordset'];
			$rs=new $rsname();
			$rs=$rs->find_records(array('id'=>$ids));
			$rs->parent_recordset=$this;
			$links=array();
			foreach ($this->array as $l) {
				$links[$l->$idname]=$l;
			}
			//$this->links=$this->array;
			$this->links=$links;
			$this->array=$rs->array;
		}
		return $this;
	}
	function implode($d=':') {
		return implode($d,$this->array_keys());
	}
	function array_keys() {
		return array_keys($this->array);
	}
	public function new_record($data=null) {
		if ($data) {$arr=array($this->structure['recordsets']['parents']['local_field_name']=>$this->parent_record->get_id(),
				$this->structure['recordsets']['childs']['local_field_name']=>$data);
		return parent::new_record($arr);
		}
		return parent::new_record($data);
	}
	public function flush($data) {
		$fname=$this->structure['recordsets']['childs']['local_field_name'];
		if (isset($this->links)) foreach ($this->links as $k=>$l) {
			if (!array_key_exists($l->$fname,$data))  {
				$l->delete();
			}
		}
	}
	public function commit() {
		$ret=parent::commit();
		if (isset($this->links)) foreach ($this->links as $l) $l->commit();

		foreach ($this->structure['recordsets'] as $l=>$rs) {
			if(isset($rs['update_recordset'])) {
				$ids=array();
				foreach($this->links as $l) {
					$ids[$l->{$rs['local_field_name']}]=$l->{$rs['local_field_name']};
				}
				if (count($ids)>0) {
					$u=new $rs['recordset'];
					$u=$u->find_records(array($rs['foreign_field_name']=>$ids));
					if($u) $u->update_counters($rs['update_recordset'],$rs['update_link']);
				}
			}
		}

		return $ret;
	}
}
class gs_recordset_short extends gs_recordset {
	function __construct($s=false,$init_opts=false) {
		$this->init_opts=$init_opts;
		$this->init_opts['recordset']=get_class($this);
		if (!$s || !is_array($s)) throw new gs_exception('gs_recordset_short :: empty init values');
		if (!$this->table_name) $this->table_name=get_class($this);
		if (!$this->id_field_name) $this->id_field_name='id';
		if (!$this->gs_connector_id) $this->gs_connector_id=key(cfg('gs_connectors'));
		$this->structure['fields'][$this->id_field_name]=array('type'=>'serial');
		$this->selfinit($s);
		parent::__construct($this->gs_connector_id,$this->table_name);
	}

	function selfinit($arr) {
		$struct=field_interface::init($arr,$this->init_opts);
		foreach ($struct as $k=>$s)
			$this->structure[$k]=isset($this->structure[$k]) ? array_merge($this->structure[$k],$struct[$k]) : $struct[$k];
	}
	function commit() {
		$ret=parent::commit();
		//md($this->structure['recordsets'],1);
		foreach ($this->structure['recordsets'] as $l=>$rs) {
			if(isset($rs['update_recordset'])) {
				$u=$this->get_elements_by_name($l);
				if($u) $u->update_counters(get_class($this));
			}
		}
		return $ret;
	}
	function update_counters($l,$link=false) {
		/*
		md('====',1);
		md('this:'.get_class($this),1);
		md('+++',1);
		md('l:'.$l,1);
		md('-=-',1);
		md('link:'.$link,1);*/
		//md($this->structure['recordsets'],1);
		
		$ss=array();
		if ($link && isset($this->structure['recordsets'][$link])) {
			$ss[]=$this->structure['recordsets'][$link];
		} else {
			foreach ($this->structure['recordsets'] as $n=>$r)  {
				if ($r['recordset']==$l || (isset($r['rs2_name']) && $r['rs2_name']==$l)) $ss[$n]=$r;
			}
		}
		foreach ($ss as $s) {
			if (isset($s['counter_fieldname'])) {
				$counter_fieldname=$s['counter_fieldname'];
				foreach($this as $rec) {
					$rec->$counter_fieldname=$rec->$link->count();
					$rec->commit(1);
				}
			}
		}
	}
}

?>
