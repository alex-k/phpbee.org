<?php
DEFINE ('PERSON_COOKIE','phpbee_person');

class module_person extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('person_session','person_role') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	static function get_handlers() {
		$data=array(
			);
		return self::add_subdir($data,dirname(__file__));
	}

}
function person($var=null) {
    $p=person::get_instance();
    return $var ? $p->$var : $p;
}
class person {
    private $heap=array();
    private $roles_heap=array();
    private $roles_records=array();
    private $roles=array();
	function __construct($init_opts=false) { 
        $this->atime=date('Y-m-d',strtotime('today'));

        $rs=new person_session;
        $this->rec=$rs->find_records(array('cookie'=>$_COOKIE[PERSON_COOKIE]))->first(true);

        if($this->rec->get_id() && unserialize($this->rec->heap)) {
            $this->heap=unserialize($this->rec->heap);

            if(strtotime($this->rec->_atime)!=strtotime($this->atime)) $this->rec->_atime=$this->atime;
        }
	}
	function get_instance() {
		static $instance;
		if (!isset($instance)) $instance = new person();
		return $instance;
	}
    function __set($field,$value) {
        if (!isset($this->rec)) {
            return $this->$field=$value;
        }

        $cfg=record_by_field('variable_name',$field,'person_variable_cfg');
        if ($cfg) foreach ($cfg->Role as $role) {
            $role_name=$role->name;
            if ($this->__get($role_name)) {
                $this->roles_heap[$role_name][$field]=$value;
                $this->roles_records[$role_name]->heap=serialize($this->roles_heap[$role_name]);
            }
            return;
        }

        $this->heap[$field]=$value;
        $this->rec->heap=serialize($this->heap);
        $this->rec->_atime=$this->atime;

        if (!$this->rec->get_id()) {
            $this->rec->cookie=substr(md5(rand(0,time())),-8);
            gs_setcookie(PERSON_COOKIE,$this->rec->cookie);
        }
    }

    function __get($name) {
        $cname=PERSON_COOKIE.'_'.$name;
        if (isset($this->roles[$name])) return $this->roles[$name];

        if (isset($_COOKIE[$cname])) {
            $rs=new person_role;
            $role=$rs->find_records(array('role'=>$name,'cookie'=>$_COOKIE[$cname]))->first();
            if($role) {
                $this->roles[$name]=record_by_id($role->record_id,$role->recordset_name);
                $this->roles_records[$name]=$role;
                $h=unserialize($role->heap);
                if (!$h) $h=array();
                $this->roles_heap[$name]=$h;
                return $this->roles[$name];
            }
            return new gs_null(GS_NULL_XML);
        }

        $cfg=record_by_field('variable_name',$name,'person_variable_cfg');
        if ($cfg) foreach ($cfg->Role as $role) {
            $role_name=$role->name;
            $this->__get($role_name);
            if (isset($this->roles_heap[$role_name]) && isset($this->roles_heap[$role_name][$name])) {
                return $this->roles_heap[$role_name][$name];
            }
        }
        if(isset($this->heap[$name])) return $this->heap[$name];
        return new gs_null(GS_NULL_XML);
    }

    function add_role($role,$rec) {
        $rs=new person_role;
        $role=$rs->find_records(array('role'=>$role,'recordset_name'=>$rec->get_recordset_name(),'record_id'=>$rec->get_id()))->first(true);
        if (!$role->cookie) $role->cookie=substr(md5(rand(0,time())),-8);
        gs_setcookie(PERSON_COOKIE.'_'.$role,$role->cookie);

        $role->commit();
    }
    function remove_role($role) {
        $cname=PERSON_COOKIE.'_'.$role;
        if (isset($_COOKIE[$cname])) {
            //$rs=new person_role;
            //$rs->find_records(array('role'=>$role,'cookie'=>$_COOKIE[$cname]))->first()->delete()->commit();
            gs_setcookie(PERSON_COOKIE.'_'.$role,NULL);
        }
    }

    function __destruct() {
        //if (!count($this->heap)) return;

        if ($this->rec->is_modified('heap') || $this->rec->is_modified('_atime')) $this->rec->commit();
        foreach ($this->roles_records as $rec) {
            if ($rec->is_modified('heap') || $rec->is_modified('_atime')) $rec->commit();
        }
    }

}
class person_session extends gs_recordset_short {
	public $no_urlkey=1;
	function __construct($init_opts=false) { 
        parent::__construct(array(
		'cookie'=> "fString cookie index=true",
        'heap' => "fText",
        '_atime'=>"fTimestamp default=0",
		),$init_opts);
	}
}

class person_role extends gs_recordset_short {
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'role'=> "fString role index=true",
		'cookie'=> "fString cookie index=true",
        'recordset_name' => "fString index=true",
        'record_id' => "fInt index=true",
        'heap' => "fText",
		),$init_opts);
	}
}

