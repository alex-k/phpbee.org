<?php
class gsmtpl {
	private $vars=array();
	public $template_dir='';
	public $plugins_dir='plugins';
	public $compile_dir='tpl_c';
	public $left_delimiter='{';
	public $right_delimiter='}';
	private $assign=array();
	private $page=NULL;
	
	function __construct() {
		$this->compile_dir=dirname(__FILE__).DIRECTORY_SEPARATOR.$this->compile_dir;
		$this->plugins_dir=dirname(__FILE__).DIRECTORY_SEPARATOR.$this->plugins_dir;
	}
	
	public function assign () {
		$params=func_get_args();
		if (count($params)==2) {
			$this->assign[$params[0]]=$params[1];
		} else {
			foreach ($params[0] as $key => $value) {
				$this->assign[$key]=$value;
			}
		}
	}
	public function get_var($name) {
		return $this->page ? $this->page->get_var($name) : NULL;
	}
	
	public function display($name) {
		echo $this->fetch($name);
	}
	
	public function fetch($name) {
		$info=$this->load_template($name);
		$class_name='__gs_page_'.$info['id'];
		$this->page=new $class_name($this->plugins_dir);
		$res=$this->page->main($this->assign);
		return stripslashes($res);
	}
	
	public function load_template($name) {
		$info=$this->get_source_info($name);
		if (!$this->is_compiled($info)) {
			$this->compile($info);
		}
		return $info;
	}
	
	private function compile($info) {
		$result=$this->load_source($info);
		$cpl=new gstpl_compiler($result,$info['id'],$this);
		$code=$cpl->get();
		$file=$this->compile_dir.DIRECTORY_SEPARATOR.$info['type'].DIRECTORY_SEPARATOR.$info['compile_id'];
		file_put_contents($file,$code);
		include_once($file);
	}
	
	private function load_source($info) {
		if (class_exists('gstpl_source_'.$info['type'],false)) {
			$result=call_user_func(array('gstpl_source_'.$info['type'],'get_source'),$info['url']);
		}
		return $result;
	}
	
	private function mtime_source($info) {
		if (class_exists('gstpl_source_'.$info['type'],false)) {
			$result=call_user_func(array('gstpl_source_'.$info['type'],'get_source_mtime'),$info['url']);
		}
		return $result;
	}
	
	private function is_compiled($info) {
		$dir=$this->compile_dir.DIRECTORY_SEPARATOR.$info['type'];
		check_and_create_dir($dir);
		if (false && file_exists($dir.DIRECTORY_SEPARATOR.$info['compile_id']) && $this->mtime_source($info)<=filemtime($dir.DIRECTORY_SEPARATOR.$info['compile_id'])) {
			//md('old version');
			include_once($dir.DIRECTORY_SEPARATOR.$info['compile_id']);
			return true;
		}
		//md('compile');
		return false;
	}
	
	private function is_abs_path($path) {
		$abs_path=realpath($path);
		$path=str_replace("/",DIRECTORY_SEPARATOR,$path);
		return $path==$abs_path;
	}
	
	private function find_file($file) {
		$rname=$file;
		if ($this->is_abs_path($file)) {
			if(!file_exists($file)) throw new gs_exception('gstpl: template '.$file.' not found');
			return $file;
		}
		if (is_string($this->template_dir)) {
			$file=realpath($this->template_dir.DIRECTORY_SEPARATOR.$rname);
			if(!file_exists($file)) throw new gs_exception('gstpl: template '.$file.' not found');
			return $file;
		}
		foreach ($this->template_dir as $dir) {
			$file=realpath($dir.DIRECTORY_SEPARATOR.$rname);
			if(file_exists($file)) return $file;
		}
		throw new gs_exception('gstpl: template '.$rname.' not found');
	}
	
	public function get_source_info($url) {
		preg_match_all("|^(([\w_]{2,})?:)?(.+)$|is",$url,$d);
		$info=array();
		$type=(!empty($d[2][0])) ? $d[2][0] : 'file';
		switch ($type) {
			case 'file':
				$url=$this->find_file($d[3][0]);
				$id=preg_replace("|(.*)\.\w+$|i","\\1",basename($url));
			break;
			default:
				$url=$d[3][0];
				$id=md5($url);
			break;
		}
		
		$info=array(
			'type'=>$type,
			'url'=>$url,
			'id'=>$id,
			'compile_id'=>md5($url).'.'.$id,
			);
		return $info;
	}

	function getTemplateVars($name=NULL) {
		$ret=$this->page ? $this->page->assign : $this->assign ;
		if ($name===NULL) return $ret;
		return isset($ret[$name]) ? $ret[$name] : NULL;
	}

}

class gstpl_compiler {
	private $source='';
	private $code='';
	private $id='';
	private $ld='';
	private $rd='';
	private $extend_file=null;
	private $extend='gs_page_blank';
	private $methods=array();
	private $tpl=null;
	private $reserved=array('block','capture','foreach','for','section','if','else');
	
	function __construct($source,$id,$gstpl) {
		$this->id=$id;
		$this->source=$source;
		$this->ld=$gstpl->left_delimiter;
		$this->rd=$gstpl->right_delimiter;
		$this->tpl=$gstpl;
	}
	
	// we need learn parse nested blocks
	private function parse_blocks ($blockname) {
		$blocks=array();
		$result=$this->code;
		$counter=0;
		$parts=array();
		$spos=strpos($result,$this->ld.$blockname,0);
		while($spos!==false) {
			$counter++;
			$len=strlen($this->ld.$blockname);
			$result=substr_replace($result,$this->ld.$blockname.':'.$counter,$spos,$len);
			//$blocks[$counter]['start']=$spos;
			$spos=strpos($result,$this->ld.$blockname,$spos+$len);
		}
		while ($counter>0) {
			$spos=strpos($result,$this->ld.$blockname.':'.$counter,0);
			$epos=strpos($result,$this->ld.'/'.$blockname.$this->rd,$spos);
			if ($epos===false) throw new gs_exception('gstpl: closed tag of section not found');
			$len=strlen($this->ld.'/'.$blockname.$this->rd);
			$result=substr_replace($result,$this->ld.'/'.$blockname.':'.$counter.$this->rd,$epos,$len);
			$blocks[$counter]['start']=$spos;
			$blocks[$counter]['end']=$epos+strlen($this->ld.'/'.$blockname.':'.$counter.$this->rd);
			$counter--;
		}
		$res=$result;
		foreach ($blocks as $i => $block) {
			$regexp=sprintf("|%s(%s:%d)(.*?)%s(.*?)%s/\\1%s|is",$this->ld,$blockname,$i,$this->rd,$this->ld,$this->rd);
			preg_match_all($regexp,$result,$out);
			$blocks[$i]['params']=string_to_params($out[2][0]);
			if (!isset($blocks[$i]['params']['name'])) $blocks[$i]['params']['name']='default_'.$i;
			$blocks[$i]['code']=$out[3][0];
			$blocks[$i]['mode']=isset($blocks[$i]['params']['mode']) ? $blocks[$i]['params']['mode'] : 'replace';
			
			$fname='compile_'.$blockname;
			$res=$this->$fname($blockname,$blocks[$i],$i,$res);
			
		}
		$this->add_method('main',$res,'main');
		foreach ($blocks as $i => $block) {
			foreach ($blocks as $j => $subblock) {
				$regexp=sprintf("|%s(%s:%d)(.*?)%s(.*?)%s/\\1%s|is",$this->ld,$blockname,$j,$this->rd,$this->ld,$this->rd);
				$params=preg_replace("|\s|is",'',var_export($subblock['params'],true));
				$fname='compile_'.$blockname;
				$blocks[$i]['code']=$this->$fname($blockname,$subblock,$j,$blocks[$i]['code']);
			}
			$this->add_method($blockname."_".$block['params']['name'],$blocks[$i]['code'],$block['mode']);
		}
		$this->code=$res;
	}
	
	private function compile_block($blockname,$block,$counter,$code) {
		$regexp=sprintf("|%s(%s:%d)(.*?)%s(.*?)%s/\\1%s|is",$this->ld,$blockname,$counter,$this->rd,$this->ld,$this->rd);
		$params=preg_replace("|\s|is",'',var_export($block['params'],true));
		return preg_replace($regexp,PHP_EOL."\$res.=\$this->".$blockname."_".$block['params']['name'].'('.$params.');'.PHP_EOL,$code);
	}
	
	private function compile_capture($blockname,$block,$counter,$code) {
		$regexp=sprintf("|%s(%s:%d)(.*?)%s(.*?)%s/\\1%s|is",$this->ld,$blockname,$counter,$this->rd,$this->ld,$this->rd);
		$params=preg_replace("|\s|is",'',var_export($block['params'],true));
		return preg_replace($regexp,PHP_EOL."\$this->assign(array('var'=>'".$block['params']['assign']."','value'=>\$this->".$blockname."_".$block['params']['name'].'('.$params.')));'.PHP_EOL,$code);
	}
	
	private function add_method($name,$code,$mode) {
		$this->methods[$name]['code']=$code;
		$this->methods[$name]['mode']=$mode;
	}
	
	
	private function make_class() {
		$str=sprintf("<?php\n\n%s\n\nclass __gs_page_%s extends %s {\n",(!empty($this->extend_file)) ? 'require_once("'.$this->extend_file.'");' : '',$this->id,$this->extend);
		foreach ($this->methods as $func => $info) {
			$str.=sprintf("\t%s function %s(\$params) {\n\t\t\$this->assign_vars(\$params);\n\t\t\$res='';%s\n\t\t%s\n%s\n\t\treturn \$res;\n\t}\n\n",
					$func=='main' ? 'public' : 'protected',
					$func,
					$info['mode']=='prepend' ? "\n\t\t\$res.=parent::".$func."(\$params);" : "",
					($info['mode']=='main' && !empty($this->extend_file)) ? "\n\t\t\$res=parent::".$func."(\$params);" : str_replace("\n","\n\t\t",$info['code']),
					$info['mode']=='append' ? "\n\t\t\$res.=parent::".$func."(\$params);" : ""
			);
		}
		$str.="\n}\n?>";
		$str=preg_replace("|\n+|is","\n",$str);
		$this->code=$str;
	}
	
	function get() {
		$this->code=$this->source;
		$this->compile_comments();
		$this->compile_html();
		$this->compile_extends();
		$this->compile_strings();
		$this->compile_vars();
		$this->compile_functions();
		$this->compile_foreach();
		$this->compile_if();
		
		$this->parse_blocks('block');
		$this->parse_blocks('capture');
		//md($this->code);
		//$this->
		$this->make_class();
		return $this->code;
	}
	
	function compile_comments() {
		$regexp=sprintf("|%s\*(.*?)\*%s|is",$this->ld,$this->rd);
		$this->code=preg_replace($regexp,'',$this->code);
	}
	
	function compile_extends() {
		$res=$this->code;
		$regexp=sprintf("|%sextends(.*?)%s|is",$this->ld,$this->rd);
		$res=preg_replace_callback($regexp,array($this,'parse_extends'),$res);
		$this->code=$res;
	}
	
	function parse_extends($matches) {
		$params=string_to_params($matches[1]);
		if (!isset($params['file'])) throw new gs_exception('gstpl: method "extends" must have param "file"');
		$info=$this->tpl->load_template($params['file']);
		//$this->tpl->get_source_info($params['file']);
		$class_name=sprintf("__gs_page_%s",$info['id']);
		$this->extend=$class_name;
		$this->extend_file=$info['compile_id'];
		return '';
	}
	
	function compile_modifiers($res) {
		$regexp=sprintf("|%s(.*?)\|(.*?)[\|\%s]|i",$this->ld,$this->rd);
		$r=$res[0];
		while(1){
			$res[0]=preg_replace_callback($regexp,array($this,'parse_modifiers'),$r);
			if ($res[0]==$r) break;
			$r=$res[0];
		}
		$value=preg_replace("|\.([^\.><=\[\s\,\)\(\}]*)|i","[\\1]",$res[0]);
		$res[0]=preg_replace("|\[([a-z_][a-z0-9\_]*)\]|i","['\\1']",$value);
		return $res[0];
	}
	
	function parse_modifiers($matches) {
		foreach ($matches as $key => $value) {
			$value=str_replace("\$this->","\$",$value);
			$matches[$key]=str_replace("\$","\$this->",$value);
		}
		$res=preg_replace("|(^[\w_]+)(.*)|is","\$this->_\\1(".$matches[1]."\\2)",$matches[2]);
		$res=preg_replace_callback("|[\"\'].*?[\"\']|i",array($this,'escaper'),$res);
		$res=preg_replace("|([^:]):([^:])|i","\\1,\\2",$res);
		$res=preg_replace_callback("|[\"\'].*?[\"\']|i",array($this,'unescaper'),$res);
		$matches[0]=str_replace($matches[1].'|'.$matches[2],$res,$matches[0]);
		
		return $matches[0];
	}
	
	function escaper ($matches) {
		return str_replace(":","::",$matches[0]);
	}
	
	function unescaper ($matches) {
		return str_replace("::",":",$matches[0]);
	}
	
	function compile_if() {
		$res=$this->code;
		$regexp=sprintf("|%sif(.*?)%s|is",$this->ld,$this->rd);
		$res=preg_replace_callback($regexp,array($this,'parse_if'),$res);
		$res=str_replace(sprintf("%selse%s",$this->ld,$this->rd),"} else {",$res);
		$res=str_replace(sprintf("%s/if%s",$this->ld,$this->rd),"}",$res);
		$this->code=$res;
	}
	
	function parse_if($matches) {
		$res=preg_replace("|\.([^\.><=\[]*)|i","[\\1]",$matches[1]);
		$res=preg_replace("|\[([a-z_][a-z0-9_]*)\]|i","['\\1']",$res);
		return sprintf("\nif (%s) {\n",str_replace("\$","\$this->",$res));
	}
	
	function compile_foreach() {
		$res=$this->code;
		$regexp=sprintf("|%sforeach(.*?)%s|is",$this->ld,$this->rd);
		$res=preg_replace_callback($regexp,array($this,'parse_foreach'),$res);
		$res=str_replace(sprintf("%s/foreach%s",$this->ld,$this->rd),"}\n",$res);
		$this->code=$res;
	}
	
	function parse_foreach($matches) {
		$params=string_to_params($matches[1]);
		if (isset($params['key'])) {
			$res=preg_replace("|\.([^\.]*)|i","[\\1]",$params['from']);
			$res=preg_replace("|\[([a-z_].*)\]|i","['\\1']",$res);
			return sprintf("\nforeach (%s as \$this->%s => \$this->%s) {\n",str_replace("\$","\$this->",$res),$params['key'],$params['item']);
		}
		return sprintf("\nforeach (%s as \$this->%s) {\n",str_replace("\$","\$this->",$params['from']),$params['item']);
	}
	
	function compile_strings() {
		//$regexp=sprintf("|%s[\\\"\'](.*?)[\\\"\'](.*?)%s|is",$this->ld,$this->rd);
		$regexp=sprintf("|%s[\\\"](.*?)[\\\"](.*?)%s|is",$this->ld,$this->rd);
		$this->code=preg_replace_callback($regexp,array($this,'compile_modifiers'),$this->code);
		$this->code=preg_replace_callback($regexp,array($this,'parse_string'),$this->code);
		$regexp=sprintf("|%s[\'](.*?)[\'](.*?)%s|is",$this->ld,$this->rd);
		$this->code=preg_replace($regexp,"\\1",$this->code);
	}
	
	function parse_string($matches) {
		return preg_replace("|(\`(.*?)\`)|i",sprintf('%s\\2%s',$this->ld,$this->rd),$matches[1]);
	}
	
	function compile_vars() {
		$res=$this->code;
		$regexp=sprintf("|%s\\\$(.*?)%s|is",$this->ld,$this->rd);
		$res=preg_replace_callback($regexp,array($this,'compile_modifiers'),$res);
		$res=preg_replace_callback($regexp,array($this,'parse_var'),$res);
		$this->code=$res;
	}
	
	function parse_var($matches) {
		if (substr($matches[1],0,4)=='this') return sprintf("\n\$res.=\$%s;\n",$matches[1]);
		$res=preg_replace("|\.([^\.]*)|i","[\\1]",$matches[1]);
		$res=preg_replace("|\[([a-z_].*)\]|i","['\\1']",$res);
		return sprintf("\n\$res.=\$this->%s;\n",$res);
	}
	
	function compile_functions() {
		$res=$this->code;
		$regexp=sprintf("|%s(.*?)%s|is",$this->ld,$this->rd);
		$res=preg_replace_callback($regexp,array($this,'parse_func'),$res);
		$this->code=$res;
	}
	
	function parse_func($matches) {
		$res=preg_replace("|\.([^\.><=\[\s]*)|i","[\\1]",$matches[1]);
		$res=preg_replace("|\[([a-z_][a-z0-9\_]*)\]|i","['\\1']",$res);
		$res=str_replace("\$","\$this->",$res);
		//$params=string_to_params($matches[1]);
		$params=string_to_params($res);
		$params[0]=ltrim($params[0],'/');
		if (in_array($params[0],$this->reserved)) {
			return $matches[0];
		}
		$func_name=$params[0];
		unset($params[0]);
		foreach ($params as $key => $value) {
			$d=$this->compile_modifiers(array($this->ld.$value.$this->rd));
			$d=rtrim(ltrim($d,$this->ld),$this->rd);
			$params[$key]=$d;
		}
		//$params=preg_replace("|\s|is",'',var_export($params,true));
		$params=$this->make_params_string($params);
		return sprintf("\n\$res.=\$this->%s(%s);\n",$func_name,$params);
	}
	
	private function make_params_string($params) {
		$d=array();
		foreach ($params as $key => $value) {
			if (is_numeric($value) || strpos($value,'$')===0) {
				$d[]='"'.$key.'" => '.$value;
			} else {
				$d[]='"'.$key.'" => "'.$value.'"';
			}
		}
		return sprintf('array(%s)',implode(',',$d));
	}
	
	function compile_html() {
		$res=$this->code;
		$spos=0;
		$epos=strpos($res,$this->ld,$spos);
		$s='';
		$len=strlen($res);
		do {
			if ($epos!==false) {
				$s.=sprintf("\$res.='%s';%s",addslashes(substr($res,$spos,$epos-$spos)),PHP_EOL);
				$spos=strpos($res,$this->rd,$epos)+strlen($this->rd);
				$s.=substr($res,$epos,$spos-$epos);
			} else {
				$epos=$len;
				$s.=sprintf("\$res.='%s';%s",addslashes(substr($res,$spos,$epos-$spos)),PHP_EOL);
				break;
			}
			$epos=strpos($res,$this->ld,$spos);
			
		} while($epos<$len-1);
		$this->code=$s;
	}
	
	function parse_html_left($matches) {
		return sprintf("\$res.='%s';%s",addslashes($matches[1]),$this->ld);
	}
	
	function parse_html_center($matches) {
		return sprintf("%s\$res.='%s';%s",$this->rd,addslashes($matches[1]),$this->ld);
	}
	
	function parse_html_right($matches) {
		return sprintf("%s\$res.='%s%s';%s",$this->rd,addslashes($matches[1]),PHP_EOL,PHP_EOL);
	}
}

class gs_page_blank {
	public $assign=array();
	public $plugins_dir;
	
	function __construct($plugins_dir) {
		$this->plugins_dir=$plugins_dir;
	}
	
	function __call($func,$params) {
		if (substr($func,0,1)!='_') {
			$params=reset($params);
			md($params,1);
		} else {
			$func=substr($func,1);
		}
		if (function_exists($func)) {
			return call_user_func_array($func,$params);
		}
		$func_file=$this->plugins_dir.DIRECTORY_SEPARATOR.'function.'.$func.'.php';
		$func_name=sprintf('smarty_function_%s',$func);
		if (!function_exists($func_name)) {
			if (!file_exists ($func_file)) {
				throw new gs_exception('gstpl: function '.$func_name.' not found');
			}
			include_once($func_file);
		}
		return $func_name($params,$this);
	}
	
	function __get($name) {
		return null;
	}
	
	public function main($params) {
		$this->assign_vars($params);
	}
	
	public function assign($params) {
		$args=func_get_args();
		if (count($args)==2) {
			$this->assign[$args[0]]=$args[1];
			$this->{$args[0]}=$args[1];
			return;
		}
		//md($args,1);
		$this->assign[$params['var']]=$params['value'];
		$this->{$params['var']}=$params['value'];
	}
	
	protected function assign_vars($params) {
		foreach ($params as $key => $value) {
			$this->assign[$key]=$value;
			$this->{$key}=$value;
		}
	}
	
	function get_var($name) {
		return isset($this->$name) ? $this->$name : NULL;
	}
	
	function getTemplateVars($name=NULL) {
		if ($name===NULL) return $this->assign;
		return isset($this->assign[$name]) ? $this->assign[$name] : NULL;
	}
	

}

abstract class gstpl_source {}

class gstpl_source_file extends gstpl_source {
	
	static function get_source($url) {
		return file_get_contents($url);
	}
	
	static function get_source_mtime($url) {
		return time();
	}
}

class gstpl_source_string extends gstpl_source {
	
	static function get_source($url) {
		return $url;
	}
	
	static function get_source_mtime($url) {
		return filemtime($url);
	}
}


/**
* Functions from our engine
**/
/*
function md($output)
{
	$txt=htmlentities(print_r($output,true));
	echo "<pre>\n".$txt."</pre>\n";
}

function check_and_create_dir($dir) {
		if (!file_exists($dir)) {
			if (!mkdir($dir,0777,TRUE)) {
				throw new gs_exception('check_and_create_dir: '.$dir.'  can not create directory');
			}
		} else if (!is_writable($dir)) {
			if (!is_dir($dir)) {
				throw new gs_exception('check_and_create_dir: '.$dir.'  is not a directory');
			}
			throw new gs_exception('check_and_create_dir: '.$dir.'   not writeble');
		}
		return $dir;
}

function string_to_params($inp) {
	$arr=is_array($inp) ? $inp : array($inp);
	$ret=array();
	$arr=preg_replace('|=\s*([^\'\"][^\s]*)|i','=\'\1\'',$arr);
	foreach ($arr as $k=>$s) {
		preg_match_all(':(\s*(([a-z_]+)=)?[\'\"](.+?)[\'\"]|([^\s]+)):i',$s,$out);
		$r=array();
		$j=0;
		foreach ($out[3] as $i => $v) {
			$key= $v ? $v : $j++;
			$value = $out[4][$i] ? $out[4][$i] : $out[1][$i];
			if (strtolower($value)=='false') $value=false;
			if (strtolower($value)=='true') $value=true;
			$prefix=explode(':',$value,2);
			if(strtoupper($prefix[0])=='ARRAY') $value=explode(':',$prefix[1]);
			$r[$key]=$value;
		}
		$ret[$k]=$r;
	}
	return is_array($inp) ? $ret : reset($ret);
}

function gs_exception_handler($ex)
{
	md('');
	md("EXCEPTION ".get_class($ex));
	md($ex->getMessage());
	md($ex->getTrace());
}

class gs_exception extends Exception {

}

set_exception_handler('gs_exception_handler');
*/
?>
