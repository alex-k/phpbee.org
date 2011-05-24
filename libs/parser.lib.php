<?php


class gs_parser {
	
	private $data;
	private $registered_handlers;
	private $current_handler;

	static function &get_instance($data)
	{
		static $instance;
		if (!isset($instance)) {
			$instance = new gs_parser();
		}
		$instance->prepare($data);
		return $instance;
	}
	
	function __construct($data=null)
	{
		if($data) $this->data=$data;
		$this->get_handlers_data=$this->get_handlers();
		$this->registered_handlers=$this->parse_handlers_data($this->get_handlers_data);
		if ($data) {
			$this->prepare($data);
		}
	}
	function prepare($data) {
		$data['gspgid']=trim($data['gspgid'],'/');
		$this->data=$data;
		$result=$this->registered_handlers[$data['gspgtype']]->xpath($data['gspgid']);
		$this->current_handler=$result->get_handler();
		$data['handler_key']=$result->handler_key;
		$data['gspgid_v']=ltrim(preg_replace("|$result->handler_key|",'',$data['gspgid'],1),'/');
		$data['gspgid_va']=explode('/',$data['gspgid_v']);
		$data['gspgid_a']=explode('/',$data['gspgid']);
		$this->data=$data;
	}
	
	public function _get_handler()
	{
		return $this->current_handler;
	}

	function process() {
		$config=gs_config::get_instance();
		$ret= new gs_null(GS_NULL_XML);
		foreach ($this->current_handler as $handler) {
			if (!class_exists($handler['class_name'],FALSE)) {
				load_file($config->lib_handlers_dir.$handler['class_name'].'.class.php');
			}
			if (!class_exists($handler['class_name'],FALSE)) throw new gs_exception('gs_parser.process: Handler class not exists '.$handler['class_name']);
			if (!method_exists($handler['class_name'],$handler['method_name'])) throw new gs_exception('gs_parser.process: Handler class method not exists '.$handler['class_name'].'.'.$handler['method_name']);
			$module_name=$handler['params']['module_name'];
			if (call_user_func(array($module_name, 'admin_auth'),$this->data,$handler['params'])===false) return false;
			if (method_exists($handler['params']['module_name'],'auth')) {
				
				$ret=call_user_func(array($module_name, 'auth'),$this->data,$handler['params']);
				if ($ret===false) return false;
			}
			$o_h=new $handler['class_name']($this->data,$handler['params']);
			//return $o_h->{$handler['method_name']}($this->data);
			$ret=$o_h->{$handler['method_name']}();
			if(!$this->continue_if(isset($handler['params']['return']) ? $handler['params']['return'] : 'not_false',$ret)) return $ret;
		}
		return $ret;
	}
	function continue_if($type,$result) {
		//var_dump($type); var_dump($result);
		switch (strtolower($type)) {
			case 'true': 
				return $result===TRUE;
			case 'false': 
				return $result===FALSE;
			case 'not_false': 
				return $result!==FALSE;
			case 'gs_record':
				return is_object($result) && is_a($result,'gs_record');
			case 'gs_recordset':
				return is_object($result) && is_a($result,'gs_recordset');

		}
		return false;
	}
	
	private function get_handlers()
	{
		$config=gs_config::get_instance();
		$data=array();
		$modules=$config->get_registered_modules();
		if (is_array($modules)) foreach ($modules as $module_name) {
			$handlers=call_user_func(array($module_name,'get_handlers'));
			if(is_array($handlers)) {
				if (isset($handlers['get_post'])) {
					$handlers['get']=isset($handlers['get']) ? array_merge($handlers['get_post'],$handlers['get']) : $handlers['get_post'];
					$handlers['post']=isset($handlers['post']) ? array_merge($handlers['get_post'],$handlers['post']) : $handlers['get_post'];
				}


				foreach ($handlers as $k=>$h) {
					foreach ($h as $kk=>$hv) {
						if (!is_array($hv)) $hv=array($hv);
						$hv_arr=array();
						foreach ($hv as $handler_value) {
							$hv_arr[]=$handler_value.":module_name:$module_name";
						}
						$handlers[$k][$kk]=$hv_arr;
					}
				}

				$data=array_merge_recursive($data,$handlers);
			}
		}
		krsort ($data['get']);
		krsort ($data['post']);
		return $data;
	}
	
	
	private function parse_handlers_data($data)
	{
		$ret=array();
		foreach (array('default','get','post') as $type) {
			$root=new gs_node('root');
			$this->parse_handler_for_type($root,$type,$data[$type]);
			$ret[$type]=$root;
		}
		return $ret;
	}
	
	private function parse_handler_for_type(&$node,$type,$data)
	{
		if (is_array($data)) foreach ($data as $url => $item) {
			new gs_recurseparser($node,$url,$item,$type);
		}
	}
}

class gs_recurseparser {
	var $node;
	var $str;
	var $params;
	var $parts;
	var $len;
	var $pos;
	var $type;
	
	function __construct(&$root,$str,$item,$type)
	{
		$this->str=$str;
		$this->parse_val($item);
		$this->type=$type;
		$this->pos=0;
		$this->parts=explode('/',$str);
		$this->len=count($this->parts);
		$this->parse($root);
	}
	
	function parse_val($vals)
	{
		$this->params=array();
		foreach ($vals as $val) {
			$params=array();
			$parts=explode(':',str_replace(array("{","}"),"",$val));
			$len=count($parts);
			if ($len<3) return;
			for ($i=1;$i<$len;$i+=2)
			{
				$params[$parts[$i]]=$parts[$i+1];
			}
		$this->params[]=array('val'=>$parts[0],'params'=>$params);
		}
	}
	
	function parse(&$node)
	{
		if ($this->pos<$this->len-1)
		{
			$child=$node->get_node_by_name($this->parts[$this->pos]);
			$child=is_null($child) ? new gs_node($this->parts[$this->pos]) : $child;
			$node->append_child($child);
			$this->pos+=1;
			$this->parse($child);
		}
		else
		{
			$child=$node->get_node_by_name($this->parts[$this->pos]);
			if (is_null($child))
			{
				$child=new gs_node($this->parts[$this->pos],$this->type,$this->params);
			}
			else
			{
				$child->_set_attibutes($this->type,$this->params);
			}
			$node->append_child($child);
		}
		
	}
}

class gs_node {
	var $parent;
	var $parent_name;
	var $childs;
	var $name;
	var $controller=array();
	//static $gs_node_id=1;
	
	function __construct($name,$c_type='',$c_params=array())
	{
		global $gs_node_id;
		$this->name=$name;
		$this->_set_attibutes($c_type,$c_params);
		$this->node_name=$gs_node_id;
		$gs_node_id++;
	}
	
	function _set_attibutes($c_type='',$c_params=array())
	{
		foreach ($c_params as $par) {
			$controller=array(
				'name'=>$par['val'],
				'type'=>$c_type,
				'params'=>$par['params'],
				);
			@list($controller['class_name'],$controller['method_name'])=explode('.',$controller['name']);
			$this->controller[]=$controller;
		}
		/*
		$this->controller['name']=$c_name;
		$this->controller['type']=$c_type;
		$this->controller['params']=$c_params;
		// NOTICE !!! 
		@list($this->controller['class_name'],$this->controller['method_name'])=explode('.',$c_name);
		*/
	}
	
	function get_handler()
	{
		if ($this->controller)
		{
			return $this->controller;
		}
		return isset($this->parent) ? $this->parent->get_handler() : $this->get_node_by_name('default')->controller;
	}
	
	function append_child($node)
	{
		if (!$this->node_has_child($node->name))
		{
			$this->childs[]=$node;
		}
		$node->set_parent($this);
	}
	
	function get_node_by_name($name)
	{
		if (empty($this->childs)) return null;
		foreach ($this->childs as $i => $child)
		{
			if ($child->name==$name) {
				return $child;
			}
		}
		foreach ($this->childs as $i => $child)
		{
			if (strlen($name) && $child->name=='*') {
				return $child;
			}
		}
		return null;
	}
	
	function node_has_child($name)
	{
		if (empty($this->childs)) return false;
		foreach ($this->childs as $i => $child)
		{
			if ($child->name==$name) {
				return true;
			}
		}
		return false;
	}
	
	function set_parent($node)
	{
		$this->parent=$node;
		$this->parent_name=$node->node_name;
	}
	
	function xpath($path, $mypath="")
	{
		$parts=explode('/',$path);
		$current=array_shift ($parts);
		$ret=$this->get_node_by_name($current);
		//md($ret,1);
		if (!is_null($ret)) {
			return $ret->xpath(implode('/',$parts),$mypath.'/'.$current);
		}
		$this->handler_key=ltrim($mypath,'/');
		return $this;
	}
}

?>
