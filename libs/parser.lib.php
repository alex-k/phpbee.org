<?php


class gs_parser {
	
	private $data;
	private $registered_handlers;
	private $current_handler;
	
	function __construct($data)
	{
		$data['gspgid']=trim($data['gspgid'],'/');
		$this->data=$data;
		$this->registered_handlers=$this->get_handlers();
		$result=$this->registered_handlers->xpath($data['gspgid']);
		$this->current_handler=$result->get_handler();
		$data['handler_key']=$result->handler_key;
		$data['gspgid_v']=ltrim(str_replace($result->handler_key,'',$data['gspgid']),'/');
		$data['gspgid_va']=explode('/',$data['gspgid_v']);
		$this->data=$data;
	}
	
	public function _get_handler()
	{
		return $this->current_handler;
	}

	function process() {
		$config=gs_config::get_instance();
		if (!class_exists($this->current_handler['class_name'],FALSE)) {
			load_file($config->lib_handlers_dir.$this->current_handler['class_name'].'.class.php');
		}
		if (!class_exists($this->current_handler['class_name'],FALSE)) throw new gs_exception('gs_parser.process: Handler class not exists '.$this->current_handler['class_name']);
		if (!method_exists($this->current_handler['class_name'],$this->current_handler['method_name'])) throw new gs_exception('gs_parser.process: Handler class method not exists '.$this->current_handler['class_name'].'.'.$this->current_handler['method_name']);
		$o_h=new $this->current_handler['class_name']($this->data,$this->current_handler['params']);
		return $o_h->{$this->current_handler['method_name']}();
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
						$handlers[$k][$kk]=$hv.":module_name:$module_name";
					}
				}

				$data=array_merge_recursive($data,$handlers);
			}
		}
		krsort ($data['get']);
		krsort ($data['post']);
		return $this->parse_handlers_data($data);
	}
	
	
	private function parse_handlers_data($data)
	{
		$root=new gs_node('root');
		$this->parse_handler_for_type($root,'default',$data['default']);
		$this->parse_handler_for_type($root,$this->data['gspgtype'],$data[$this->data['gspgtype']]);
		return $root;
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
	var $val;
	var $params;
	var $parts;
	var $len;
	var $pos;
	var $type;
	
	function __construct(&$root,$str,$val,$type)
	{
		$this->str=$str;
		$this->parse_val($val);
		$this->type=$type;
		$this->pos=0;
		$this->parts=explode('/',$str);
		$this->len=count($this->parts);
		$this->parse($root);
	}
	
	function parse_val($val)
	{
		$parts=explode(':',str_replace(array("{","}"),"",$val));
		$this->val=$parts[0];
		$len=count($parts);
		$params=$this->params=array();
		if ($len<3) return;
		for ($i=1;$i<$len;$i+=2)
		{
			$params[$parts[$i]]=$parts[$i+1];
		}
		$this->params=$params;
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
				$child=new gs_node($this->parts[$this->pos],$this->val,$this->type,$this->params);
			}
			else
			{
				$child->_set_attibutes($this->val,$this->type,$this->params);
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
	var $controller;
	//static $gs_node_id=1;
	
	function __construct($name,$c_name='',$c_type='',$c_params=null)
	{
		global $gs_node_id;
		$this->name=$name;
		$this->_set_attibutes($c_name,$c_type,$c_params);
		$this->node_name=$gs_node_id;
		$gs_node_id++;
	}
	
	function _set_attibutes($c_name='',$c_type='',$c_params=null)
	{
		$this->controller['name']=$c_name;
		$this->controller['type']=$c_type;
		$this->controller['params']=$c_params;
		// NOTICE !!! 
		@list($this->controller['class_name'],$this->controller['method_name'])=explode('.',$c_name);
	}
	
	function get_handler()
	{
		if (!empty($this->controller['type']))
		{
			return $this->controller;
		}
		return isset($this->parent) ? $this->parent->get_handler() :$this->get_node_by_name('default')->controller;
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
			if (!empty($name) && $child->name=='*') {
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
