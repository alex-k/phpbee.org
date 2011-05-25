<?php
/*test22*/

DEFINE ('LOAD_CORE',1);
DEFINE ('LOAD_STORAGE',2);
DEFINE ('LOAD_TEMPLATES',4);

if (defined('DEBUG') && DEBUG) {
ini_set('display_errors','On');
error_reporting(E_ALL);
}

class gs_init {
	
	public $config;
	public $tpl;
	public $data;
	private $view;
	
	function __construct($view)
	{
		$this->config=gs_config::get_instance();
		$req=(strpos($_SERVER['REQUEST_URI'],$this->config->www_admin_dir)===0);
		$this->view=$view=='auto' ? ($req ? 'admin' : 'user') : $view;
		
		$this->config->set_view($this->view);
	}
	
	
	public function init($mode)
	{
		if ($mode & LOAD_CORE) {
			$this->load_core();
			$o_data=new gs_data;
			$this->data=$o_data->get_data();
		}
		if ($mode & LOAD_STORAGE) {
			$this->load_storage();
		}
		if ($mode & LOAD_TEMPLATES) {
			$this->load_templates();
		}
	}

	public function load_modules($mask='*module.php') {
		$files = array_merge(glob($this->config->lib_modules_dir.$mask),glob($this->config->lib_modules_dir.'*/'.$mask));
		$classes=get_declared_classes();
		foreach ($files as $f) {
			load_file($f);
			$nc=array_diff(get_declared_classes(),$classes);
			foreach($nc as $c) $this->config->class_files[$c]=$f;
			$classes=get_declared_classes();
		}
		$cfg=gs_config::get_instance();
		$loaded_classes=get_declared_classes();
		foreach ($loaded_classes as $classname) {
			$refl= new ReflectionClass($classname);
			$interfaces=$refl->getInterfaces();
			if (isset($interfaces['gs_module'])) {
				$cfg->register_module($classname);
			}
		}
	}
	public function install_modules() {
		$cfg=gs_config::get_instance();
		$modules=$cfg->get_registered_modules();
		if (is_array($modules)) foreach ($modules as $m) {
			$mod=new $m;
			$mod->install();
		}
	}

	
	public function load_templates()
	{
		load_file($this->config->lib_dir.'tpl.lib.php');
		load_file($this->config->lib_dir.'tpl_static.lib.php');
		load_file($this->config->lib_dir.'forms.lib.php');
		load_file($this->config->lib_dir.'widgets.lib.php');
		load_file($this->config->lib_dir.'helpers.lib.php');
		load_file($this->config->lib_dir.'dict.lib.php');
		load_file($this->config->lib_dir.'vpa_gd.lib.php');
	}
	public function load_core()
	{
		load_file($this->config->lib_dir.'core.lib.php');
		load_file($this->config->lib_dir.'parser.lib.php');
		load_file($this->config->lib_dir.'handler.lib.php');
		load_file($this->config->lib_dir.'validator.lib.php');
		load_file($this->config->lib_dir.'newvalidator.lib.php');
		load_file($this->config->lib_dir.'functions.lib.php');
		load_file($this->config->lib_dir.'vpa_mail.lib.php');
	}

	public function load_storage() {
		load_file($this->config->lib_dir.'fkey.lib.php');
		load_file($this->config->lib_dir.'record.lib.php');
		load_file($this->config->lib_dir.'storage.lib.php');
		load_file($this->config->lib_dir.'recordset_tools.lib.php');
	}
	
	
}

class gs_config {
	
	public $root_dir;
	public $host;
	public $www_dir;
	public $www_admin_dir;
	public $index_filename;
	public $data_dir;
	public $script_dir;
	public $var_dir;
	public $cache_dir;
	public $lib_dir;
	public $lib_tpl_dir;
	public $lib_data_drivers_dir;
	public $lib_handlers_dir;
	public $lib_modules_dir;
	public $lib_dbdrivers_dir;
	public $tpl_blocks;
	public $class_files=array();
	private $view;
	private $registered_gs_modules;
	
	
	function __construct()
	{
		if (!isset($_SERVER['REQUEST_METHOD'])) $_SERVER['REQUEST_METHOD']='UNKNOWN';
		if (!isset($_SERVER['HTTP_HOST'])) $_SERVER['HTTP_HOST']='localhost';
		if (!isset($_SERVER['REQUEST_URI'])) $_SERVER['REQUEST_URI']=__FILE__;

		$this->host=$_SERVER['HTTP_HOST'];
		$this->root_dir=clean_path(dirname(dirname(__FILE__))).'/';
		$this->root_dir=str_replace('\\','/',$this->root_dir);
		$_document_root=clean_path(realpath($_SERVER['DOCUMENT_ROOT'])).'/';

		if ($this->root_dir>$_document_root) {
			$this->www_dir='/'.trim(str_replace($_document_root,'',$this->root_dir),'/');
		} else {
			$this->www_dir='/';
		}

		$this->www_admin_dir=$this->www_dir.'admin/';
                $this->www_image_dir=$this->www_dir.'img/';
		$this->script_dir=rtrim(dirname($_SERVER['PHP_SELF']),'/').'/';
		$this->index_filename=$_SERVER['SCRIPT_NAME'];
		$this->referer_path= isset($_SERVER['HTTP_REFERER']) ?  preg_replace("|^$this->www_dir|",'',parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH)) : '';
		$this->lib_dir=$this->root_dir.'libs/';
		$this->var_dir=$this->root_dir.'var/';
		$this->img_dir=$this->root_dir.$this->www_image_dir;
		$this->log_dir=$this->var_dir.'log/';
		$this->log_file='gs.log';
		$this->cache_dir=$this->var_dir.'cache/';
		$this->session_lifetime='2 hours';
		$this->tmp_dir=$this->var_dir.'tmp/';
		$this->data_dir=$this->root_dir.'data/';
		$this->tpl_data_dir=$this->data_dir.'templates/';
		$this->tpl_var_dir=$this->var_dir.'templates_c/';
		$this->lib_tpl_dir=$this->lib_dir.'smarty/';
		$this->tpl_plugins_dir=$this->lib_tpl_dir.'plugins/';
		$this->controllers_dir=$this->lib_dir.'controllers/';
		$this->lib_data_drivers_dir=$this->lib_dir.'data_drivers/';
		$this->lib_handlers_dir=$this->root_dir.'handlers/';
		$this->lib_modules_dir=$this->root_dir.'modules/';
		$this->lib_dbdrivers_dir=$this->lib_dir.'dbdrivers/';

		foreach(array($this->root_dir.'config.php',$this->lib_modules_dir.'config.php') as $cfg_filename) {
			if (file_exists($cfg_filename)) require_once($cfg_filename);
		}

		if (!defined('DEBUG')) define('DEBUG',FALSE);
		if (DEBUG) {
		ini_set('display_errors','On');
		error_reporting(E_ALL);
		}

	}

	function register_module($name) {
		$this->registered_gs_modules[$name]=$name;
	}
	function get_registered_modules() {
		return $this->registered_gs_modules;
	}

	function set_view($view) {
		$this->tpl_data_dir=$this->data_dir.'templates/'.$view;
		$this->tpl_var_dir=$this->var_dir.'templates_c/'.$view;
		cfg_set('_gs_view',$view);
		check_and_create_dir($this->tpl_var_dir);
	}

	
	static function &get_instance()
	{
		static $instance;
		if (!isset($instance)) $instance = new gs_config;
		return $instance;
	}
}

function cfg_set($name,$value) {
	$config=gs_config::get_instance();
	$config->$name=$value;
	return cfg($name);
}

function cfg($name) {
	$config=gs_config::get_instance();
	return isset($config->$name) ? $config->$name : NULL ;
}
function md($output,$type=false)
{
	if ($type) {
	$txt=htmlentities(print_r($output,true));
	echo "<pre>\n".$txt."</pre>\n";
	} else {
		$log=gs_logger::get_instance();
		$log->log($output);
	}
}
class gs_logger {
	
	private $messages=array();	
	function __construct() {
		$this->time_start=microtime(true);
	}
	static function &get_instance()
	{
		static $instance;
		if (!isset($instance))
		{
			$instance = new gs_logger();
		}
		return $instance;
	}
	function log($data) {
		$this->messages[]=$data;
		//$this->log_to_file($data);
	}
	private function log_to_file($data) {
		if (cfg('log_file')) {
			check_and_create_dir(cfg('log_dir'));
			ob_start();
			print_r($data);
			$txt=ob_get_contents();
			ob_end_clean();
			file_put_contents(cfg('log_dir').cfg('log_file'),$txt."\n\n",FILE_APPEND);
		}
	}
	function show() {
		mlog(sprintf('total time: %.4f seconds',microtime(TRUE)-$this->time_start));
		$ret='';
		if (is_array($this->messages)) foreach ($this->messages as $msg) {
			ob_start();
			print_r($msg);
			$txt=ob_get_contents();
			ob_end_clean();
			$txt=preg_replace("/\n/",'\\r\\n',addslashes($txt));
			$ret.="console.log('$txt');\n";
		}
		return $ret;
	}
	static function dump() {
		$log=gs_logger::get_instance();
		$txt2 = $log->show();
		echo "<pre>\n";
		foreach ($log->messages as $msg) {
			ob_start();
			print_r($msg);
			$txt=ob_get_contents();
			ob_end_clean();
			echo htmlentities($txt)."\n";
		}
		echo "\n<pre>";
	}
	static function console() {
		$log=gs_logger::get_instance();
		$txt2 = $log->show();
		echo <<<TXT
<script>
if (typeof console == 'object') {
	$txt2;
}
</script>
TXT;
	}
	/*
	function __destruct() {
		$this->dump();
		ob_end_flush();
	}
	*/
}

function gs_exception_handler($ex)
{
	md('');
	md("EXCEPTION ".get_class($ex));
	md($ex->getMessage());
	md($ex->getTrace());
	gs_logger::dump();
}

function load_dbdriver($name) {
	$cfg=gs_config::get_instance();
	$name=gs_validator::validate('plain_word',$name);
	load_file($cfg->lib_dbdrivers_dir.$name.'.driver.php');
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

function load_file($file,$return_contents=FALSE,$return_file=FALSE)
{
	if (!file_exists($file))
	{
		throw new gs_exception('load_file: '.$file.'  not found');
	}
	if ($return_contents) return unserialize(file_get_contents($file));
	if ($return_file) return file_get_contents($file);
	require_once($file);
}

function mlog($data) {
	$log=gs_logger::get_instance();
	$log->log($data);
}

function clean_path($path) {
	return str_replace('\\','/',$path);
}

function stripslashes_deep($value)
{
	$value = is_array($value) ?
		array_map('stripslashes_deep', $value) :
		stripslashes($value);

	return $value;
}

class gs_exception extends Exception {

}

class gs_var_storage {
	private $arr=array();
	static function &get_instance()
	{
		static $instance;
		if (!isset($instance)) $instance = new gs_var_storage();
		return $instance;
	}
	static function genid($id) {
		return md5($id);
	}
	static function save($id,$value) {
		//var_dump($this);
		$id=self::genid($id);
		$t=gs_var_storage::get_instance();
		$t->arr[$id]=$value;
	}
	static function load($id) {
		$id=self::genid($id);
		$t=gs_var_storage::get_instance();
		$ret=isset($t->arr[$id]) ? $t->arr[$id] : NULL;
		return $ret;
	}
}


set_exception_handler('gs_exception_handler');

//echo $config->root_dir;

?>
