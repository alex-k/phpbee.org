<?php
/*test22*/
set_time_limit(2);
DEFINE ('LOAD_CORE',1);
DEFINE ('LOAD_STORAGE',2);
DEFINE ('LOAD_TEMPLATES',4);
DEFINE ('LOAD_EXTRAS',8);

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
		if ($mode & LOAD_EXTRAS) {
			$this->load_extras();
		}
	}

	function save_handlers() {
		gs_cacher::clear('handlers','config');
		gs_cacher::clear('classes','config');
		$o_h=new gs_parser();
		$handlers=$o_h->get_registered_handlers();
		gs_cacher::save($handlers,'config','handlers');

		$cl_array=array();

		$classes=get_declared_classes();
		foreach ($classes as $cl) {
			$r=new ReflectionClass($cl);
			if($r->getFileName()) $cl_array[$cl]=$r->getFileName();
		}
		mlog($cl_array);
		gs_cacher::save($cl_array,'config','classes');
		//var_dump(gs_cacher::load('handlers','config'));
	}

	function check_compile_modules($path='') {
		$modified=false;
		$dir=$this->config->lib_modules_dir;
		$subdirs=glob($dir.$path.'*',GLOB_ONLYDIR);
		$dir=rtrim($dir,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		$path=trim($path,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		foreach ($subdirs as $s) {
			if ($this->check_compile_modules($path.basename($s).DIRECTORY_SEPARATOR)) return true;
		}
		
		$files=glob($dir.$path.'*.phps');
		$tpldir=$dir.$path.'templates';
		$tplcdir=$dir.$path.DIRECTORY_SEPARATOR.'___templates';
		foreach ($files as $f) {
			$pf=str_replace(basename($f),'___'.basename($f),$f);
			$pf=preg_replace('/.phps$/','.xphp',$pf);
			if (!file_exists($pf) || filemtime($pf) < filemtime($f)) return true;
			//if (strpos(strtoupper(PHP_OS),'WIN')!==false && PHP_VERSION_ID<50300) {

			if (file_exists($tpldir)) {
				check_and_create_dir($tplcdir);
				$mtime=filemtime($tpldir);
				$mctime=filemtime($tplcdir);
				$tpls=glob($tpldir.DIRECTORY_SEPARATOR.'*');
				foreach ($tpls as $t) {
					$mt=filemtime($t);
					$mct=file_exists($tplcdir.DIRECTORY_SEPARATOR.basename($t)) ? filemtime($tplcdir.DIRECTORY_SEPARATOR.basename($t)) : $mt+1;
					//md($t.') '.$mt.' - '.$mct.' = '.intval($mt - $mct),1);
					if ($mt>$mct) return true;
				}
			}
			/*} else {
				md($tpldir.') '.filemtime($tplcdir).' - '.filemtime($tpldir).' = '.intval(filemtime($tplcdir) - filemtime($tpldir)),1);
				if (file_exists($tpldir) && (!file_exists($tplcdir) || filemtime($tplcdir) < filemtime($tpldir))) return true;
			}*/
			
		}
		return false;
	}

	function compile_modules($path='') {
		//throw new gs_exception('gs_base_handler.show: empty params[name]');
		mlog('COMPILE_MODULES');
		$tpl=null;
		$data=array('LINKS'=>array(),'FIELDS'=>array());
		$ret=array();
		$dir=$this->config->lib_modules_dir;
		$subdirs=glob($dir.$path.'*',GLOB_ONLYDIR);
		foreach ($subdirs as $s) {
			$d=$this->compile_modules($path.basename($s).DIRECTORY_SEPARATOR);
			$data=array_merge_recursive($data,$d);
		}
		$files=glob($dir.$path.'*.phps');
		$module_name=str_replace(DIRECTORY_SEPARATOR,'_',trim($path,DIRECTORY_SEPARATOR));
		$module_dir_name=str_replace(DIRECTORY_SEPARATOR,'_',basename($path,DIRECTORY_SEPARATOR));
		$parent_module=str_replace(DIRECTORY_SEPARATOR,'_',dirname($path));
		if (empty($files)) return $ret;
		$tpl=new gs_tpl();
		$tpl=$tpl->init();
		$tpl->left_delimiter='{%';
		$tpl->right_delimiter='%}';
		$tpl->assign('MODULE_NAME','_'.$module_name);
		//$tpl->assign('MODULE',$module_name);
		$tpl->assign('MODULE',$module_dir_name);
		$tpl->assign('PARENT_MODULE',$parent_module);
		$tpl->assign('SUBMODULE_NAME',basename($path));
		$tpl->assign('SUBMODULES_DATA',$data);
		foreach ($files as $f) {
			$pf=str_replace(basename($f),'___'.basename($f),$f);
			$pf=preg_replace('/.phps$/','.xphp',$pf);
			
			/*$s=file_get_contents($f);
			$s=$tpl->fetch('string:'.$s);*/
			$s=$tpl->fetch('file:'.$f);
			if ($tpl->get_var('DATA')) {
				$r=$tpl->get_var('DATA');
				//$r=array_filter(array_map('trim',explode(PHP_EOL,$r)));
				preg_match_all('|(\w+)::(.+?)::(.*)|i',$r,$r);
				foreach ($r[0] as $k=>$v) {
					$ret[$r[1][$k]][$r[2][$k]]=trim($r[3][$k]);
					$ret['MODULE'][$module_dir_name][$r[1][$k]][$r[2][$k]]=trim($r[3][$k]);
				}
			}
			file_put_contents($pf,$s);
		}
		$tpldir=$dir.$path.'templates';
		$tplcdir=$dir.$path.'___templates';
		if (file_exists($tpldir)) {
			check_and_create_dir($tplcdir);
			@touch($tplcdir);
			$files=glob($tpldir.DIRECTORY_SEPARATOR.'*');
			foreach ($files as $f) {
				/*$s=file_get_contents($f);
				$s=$tpl->fetch('string:'.$s);*/
				$s=$tpl->fetch($f);
				$pf=$tplcdir.DIRECTORY_SEPARATOR.basename($f);
				if(!file_put_contents($pf,$s)) {
					throw new gs_exception('Can`t move template '.$f.' into '.$pf);
				}
			}
		}
		return $ret;
	}


	public function clear_cache() {
		rrmdir(cfg('tpl_var_dir'));
	}


	public function load_modules($mask='*module.{php,xphp}') {
		if ($this->check_compile_modules()) {
			$this->compile_modules();
			$this->save_handlers();
		}

		$path=$this->config->lib_modules_dir;
		while (($files = glob($path.$mask,GLOB_BRACE)) && !empty($files)) {
			$classes=get_declared_classes();
			foreach ($files as $f) {
				load_file($f);
				$nc=array_diff(get_declared_classes(),$classes);
				foreach($nc as $c) $this->config->class_files[$c]=$f;
				$classes=get_declared_classes();
			}
			$path.='*'.DIRECTORY_SEPARATOR;
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
		load_file($this->config->lib_dir.'forms.lib.php');
		load_file($this->config->lib_dir.'widgets.lib.php');
		load_file($this->config->lib_dir.'helpers.lib.php');
		load_file($this->config->lib_dir.'dict.lib.php');
	}
	public function load_core()
	{
		//load_file($this->config->lib_dir.'__all.php'); return;

		load_file($this->config->lib_dir.'core.lib.php');
		load_file($this->config->lib_dir.'parser.lib.php');
		load_file($this->config->lib_dir.'handler.lib.php');
		load_file($this->config->lib_dir.'functions.lib.php');
	}

	public function load_storage() {
		load_file($this->config->lib_dir.'fkey.lib.php');
		load_file($this->config->lib_dir.'indexator.lib.php');
		load_file($this->config->lib_dir.'record.lib.php');
		load_file($this->config->lib_dir.'storage.lib.php');
		load_file($this->config->lib_dir.'recordset_tools.lib.php');
		load_file($this->config->lib_dir.'recordset_handler.lib.php');
	}

	public function load_extras() {
		load_file($this->config->lib_dir.'vpa_mail.lib.php');
		load_file($this->config->lib_dir.'vpa_normalizator.lib.php');
		load_file($this->config->lib_dir.'validator.lib.php');
		load_file($this->config->lib_dir.'newvalidator.lib.php');
		load_file($this->config->lib_dir.'vpa_gd.lib.php');
		load_file($this->config->lib_dir.'tpl_static.lib.php');
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
		$this->document_root=$_document_root;

		if ($this->root_dir>$_document_root) {
			$this->www_dir='/'.trim(str_replace($_document_root,'',$this->root_dir),'/');
		} else {
			$this->www_dir='/';
		}

		$this->www_admin_dir=$this->www_dir.'admin/';
		$this->www_image_dir=$this->www_dir.'img/';
		$this->script_dir=rtrim(dirname($_SERVER['PHP_SELF']),'/').'/';
		$this->index_filename=$_SERVER['SCRIPT_NAME'];
		$this->referer= isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$this->referer_path= isset($_SERVER['HTTP_REFERER']) ?  preg_replace("|^$this->www_dir|",'',parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH)) : '';
		$this->lib_dir=$this->root_dir.'libs/';
		$this->var_dir=$this->root_dir.'var/';
		$this->img_dir=$this->root_dir.$this->www_image_dir;
		$this->log_dir=$this->var_dir.'log/';
		$this->log_file=NULL;//'gs.log';
		$this->cache_dir=$this->var_dir.'cache/';
		$this->session_lifetime='2 hours';
		$this->tmp_dir=$this->var_dir.'tmp/';
		$this->data_dir=$this->root_dir.'data/';
		$this->tpl_data_dir_default=$this->data_dir.'templates/';
		$this->tpl_data_dir=$this->tpl_data_dir_default;
		$this->tpl_var_dir=$this->var_dir.'templates_c/';
		$this->lib_tpl_dir=$this->lib_dir.'smarty/';
		$this->tpl_plugins_dir=$this->lib_tpl_dir.'plugins/';
		$this->controllers_dir=$this->lib_dir.'controllers/';
		$this->lib_data_drivers_dir=$this->lib_dir.'data_drivers/';
		$this->lib_handlers_dir=$this->root_dir.'handlers/';
		$this->lib_modules_dir=$this->root_dir.'modules/';
		$this->lib_dbdrivers_dir=$this->lib_dir.'dbdrivers/';

		$this->use_handler_cache=FALSE;
		$this->s_handler_cnt=0;

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
		if ($this->tpl_data_dir==$this->tpl_data_dir_default) {
			$this->tpl_data_dir=$this->data_dir.'templates/'.$view;
			$this->tpl_var_dir=$this->var_dir.'templates_c/'.$view;
		}
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
function mlog($data) {
	$log=gs_logger::get_instance();
	$log->log($data);
}
function md($output,$type=false)
{
	if ($type) {
		$txt=htmlentities(print_r($output,true));
		echo "<pre>\n".$txt."</pre>\n";
	} else {
		$log=gs_logger::get_instance();
		mlog($output);
	}
}
class gs_logger {
	
	private $messages=array();	
	private $t,$tt;
	function __construct() {
		$this->tt=$this->time_start=microtime(true);
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

			ob_start();
			print_r($data);
			$txt=ob_get_contents();
			ob_end_clean();

		$t=microtime(true);
		$this->messages[]=sprintf("%.3f/%.4f > ",$t-$this->time_start,$t-$this->tt).$txt;
		$this->tt=$t;
		$this->log_to_file($data);
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
	if (class_exists('gs_dbdriver_'.$name,FALSE)) return;
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
	mlog('LOAD_FILE '.$file);
	if (!file_exists($file))
	{
		throw new gs_exception('load_file: '.$file.'  not found');
	}
	if ($return_contents) return unserialize(file_get_contents($file));
	if ($return_file) return file_get_contents($file);
	require_once($file);
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


function __gs_autoload($class_name) {
	mlog('AUTOLOAD '.$class_name);
	$classes=gs_cacher::load('classes','config');
	if (array_key_exists($class_name,$classes)) load_file($classes[$class_name]);
}

spl_autoload_register('__gs_autoload');


set_exception_handler('gs_exception_handler');

//echo $config->root_dir;

?>
