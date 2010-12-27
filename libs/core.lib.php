<?php
DEFINE ('GS_DATA_STDIN','stdin');
DEFINE ('GS_DATA_POST','post'); // operator
DEFINE ('GS_DATA_GET','get'); // operator
DEFINE ('GS_DATA_SEF','sef'); // Search Engines Frendly URI
DEFINE ('GS_DATA_SESSION','session');
DEFINE ('GS_DATA_COOKIE','cookie');
DEFINE ('GS_NULL_XML',"<null></null>");

class gs_null extends SimpleXMLElement implements arrayaccess {
	public function get_id() {
		return $this;
	}
	public function __get($name) {
		return $this;
	}
	public function offsetGet($offset) {
		return $this;
	}
	public function first() {
		return $this;
	}
	public function get_recordset() { return $this; }
	public function find() { return $this; }
	public function count() {
		return 0;
	}
	public function get_values() {
		return array();
	}
	    public function offsetSet($offset, $value) {
	    }
	    public function offsetExists($offset) {
	    }
	    public function offsetUnset($offset) {
	    }
	     public function __call($name, $arg) {
		     throw new gs_dbd_exception('trying call '.$name.' in gs_null object',DBD_GSNULL_CALL);
	     }
}
class gs_data {
	
	static private $data;
	private $data_drivers;
	
	public function __construct()
	{
		$this->data_drivers=array(
			GS_DATA_COOKIE,
			GS_DATA_SESSION,
			GS_DATA_GET,
			GS_DATA_SEF,
			GS_DATA_POST,
			GS_DATA_STDIN,
		);
		$this->data=array('gspgid'=>'','gspgtype'=>'');
		$config=gs_config::get_instance();
		foreach ($this->data_drivers as $key => $class_name)
		{
			load_file($config->lib_data_drivers_dir.$class_name.'.lib.php');
			$s_name='gs_data_driver_'.$class_name;
			$c=new $s_name;
			if ($c->test_type())
			{
				$this->data=array_merge($this->data,$c->import());
			}
		}
		//md($this->data);
	}
	

	public function get_data()
	{
		return $this->data;
	}
}

interface gs_data_driver {

	function test_type();
	
	function import();
}

interface gs_module {
	function install();
	static function get_handlers();
	//static function register();
}

class gs_iterator implements Iterator, arrayaccess {
    public $array = array();  


    function add_element(&$element, $id=NULL) {
	    if ((is_subclass_of($element,'gs_record') || get_class($element)=='gs_record') && ($id!==NULL || $element->get_id() ) ) {
		    return $this->array[ $id!==NULL ? $id : $element->get_id()]=$element;
	    }
	    return $this->array[]=$element;
    }

    function add($elements,$id=NULL) {
	    if (is_subclass_of($elements,'gs_iterator') || is_array($elements)) {
		    foreach($elements as $e) {
			    $this->add_element($e,$id);
		    }
		    return true;
	    }
	    return $this->add_element($elements,$id);
    }
    function replace($elements) {
	    $this->reset();
	    $this->array=(array)$elements;
    }
    function reset() {
	    $this->array=array();
	    $this->rewind();
    }

    function shift() {
	    $ret=$this->first();
	    reset($this->array);
	    $key=key($this->array);
	    unset($this->array[$key]);
	    return $ret;
    }

    function rewind() {
	    reset($this->array);
    }
    function first() {
	    $this->rewind();
	    return $this->current();
    }
    function reverse() {
	    $this->array=array_reverse($this->array);
	    return $this;
    }
    function current() {
	    return current($this->array);
    }

    function key() {
        return key($this->array);
    }

    function next() {
	    return next($this->array);
    }
    function end() {
	    return end($this->array);
    }

    function pop() {
	    return array_pop($this->array);
    }

    function count() {
	    return count($this->array);
    }

    function valid() {
        return current($this->array);
    }
    public function offsetSet($offset, $value) {
        $this->array[$offset] = $value;
    }
    public function offsetExists($offset) {
        return isset($this->array[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->array[$offset]);
    }
    public function offsetGet($offset) {
        return isset($this->array[$offset]) ? $this->array[$offset] : new gs_null(GS_NULL_XML);
    }

    function sort($flag=1) {
	    if (is_string($flag)) {
		    if (substr($flag,0,1)=='-') {
		    $field=substr($flag,1);
			    usort($this->array,create_function('$a,$b','return -strcmp($a->'.$field.',$b->'.$field.');'));
		    } else {
			    usort($this->array,create_function('$a,$b','return strcmp($a->'.$flag.',$b->'.$flag.');'));
		    }


	    } 
	    else if ($flag<0) krsort($this->array);
	    else ksort($this->array);
	    return $this;
    }
}


class gs_cacher {
	static function save($data,$subdir='.',$id=NULL) {
		$dirname=cfg('cache_dir').'/'.$subdir.'/';
		check_and_create_dir($dirname);
		if (!$id) {
			$fn=tempnam($dirname,'');
			$id=basename($fn);
			//$id.=substr(base64_encode(md5(rand())),0,8);
		} else {
			//$id=substr($id,0,-8);
			$fn=$dirname.$id;
		}
		file_put_contents($fn,serialize($data));
		return $id;
	}
	static function load($id,$subdir='.') {
		//$id=substr($id,0,-8);
		$dirname=cfg('cache_dir').'/'.$subdir.'/'.$id;
		if (!file_exists($dirname)) return NULL;
		$ret=unserialize(file_get_contents($dirname));
		//unlink($dirname);
		return $ret;
	}
	static function clear($id,$subdir='.') {
		//$id=substr($id,0,-8);
		$dirname=cfg('cache_dir').'/'.$subdir.'/'.$id;
		return file_exists($dirname) && unlink($dirname);
	}
	static function cleardir($subdir=false) {
		if (!$subdir) return false;
		$dirname=cfg('cache_dir').'/'.$subdir;
		foreach (glob("$dirname/*") as $filename) {
			unlink($filename);
		}
		return file_exists($dirname) && is_dir($dirname) && rmdir($dirname);
	}

}

class gs_session {
	static function save($obj,$name='') {
		$data=array();
		if (isset($_COOKIE['gs_session'])) {
			$data=gs_cacher::load($_COOKIE['gs_session'],'gs_session');
		}
		$data[$name]=$obj;
		$id=gs_cacher::save($data,'gs_session',isset($_COOKIE['gs_session']) ? $_COOKIE['gs_session'] : NULL);
		$t=strtotime("now +".cfg('session_lifetime'));
		return setcookie('gs_session',$id,$t,cfg('www_dir'));
	}

	static function load($name=NULL) {
		if (!isset($_COOKIE['gs_session'])) return FALSE;
		$ret=gs_cacher::load($_COOKIE['gs_session'],'gs_session');
		return $name!==NULL  ? $ret[$name] : $ret;
		//return isset($ret[$name]) ? $ret[$name] : $ret;
	}

	static function clear($name=NULL) {
		if (!isset($_COOKIE['gs_session'])) return FALSE;
		return gs_cacher::clear($_COOKIE['gs_session'],'gs_session');
		//return isset($ret[$name]) ? $ret[$name] : $ret;
	}

}






?>
