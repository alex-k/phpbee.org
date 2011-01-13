<?php
class gs_base_handler {
	protected $blocks;
	protected $data;
	protected $params;
	public function __construct($data=null,$params=null) {
		$this->data=$data;
		$this->params=$params;

		/*
		$cfg=gs_config::get_instance();
		foreach ($cfg->tpl_blocks as $blockname) {
			if (!isset($this->blocks[$blockname])) $this->blocks[$blockname]=new gs_tpl_block($this->data);
		}
		*/

		$this->register_blocks();
	}
	function register_blocks() {
		$this->assign('_blocks',$this->blocks);
	}
	function assign($name,$value=NULL) {
		$tpl=gs_tpl::get_instance();
		if (is_array($name)) {
			return $tpl->assign($name);
		}
		return $tpl->assign($name,$value);
	}
	function fetch() {
		if (empty($this->params['name'])) throw new gs_exception('gs_base_handler.show: empty params[name]');
		$tpl=gs_tpl::get_instance();
		$tpl->assign('_gsdata',$this->data);
		$tpl->assign('_gsparams',$this->params);
		if (!$tpl->template_exists($this->params['name'])) throw new gs_exception('gs_base_handler.show: can not find template file for '.$this->params['name']);
		return $tpl->fetch($this->params['name']);
	}
	protected function show($nodebug=FALSE) {
		//if (empty($this->params['name'])) throw new gs_exception('gs_base_handler.show: empty params[name]');
		if (empty($this->params['name'])) {
			$this->params['name']=str_replace('/','_',$this->data['handler_key']).'.html';
		}
		$tpl=gs_tpl::get_instance();
		$tpl->assign('_gsdata',$this->data);
		$tpl->assign('_gsparams',$this->params);
		if (!$tpl->template_exists($this->params['name'])) {
			md($this->data,1);
			md($this->params,1);
			throw new gs_exception('gs_base_handler.show: can not find template file for '.$this->params['name']);
		}
		$txt=ob_get_contents();
		ob_end_clean();
		$html=$tpl->fetch($this->params['name']);
		echo $html;
		if (DEBUG && !$nodebug) {
			$log=gs_logger::get_instance();
			$txt2=$log->show();
			if (trim($txt) || trim($txt2)) {
				$txt=preg_replace("/\n/",'\\r\\n',addslashes($txt));
				echo <<<TXT
				<script>
				if (typeof console == 'object') {
					console.log('$txt');
					$txt2;
				}
				</script>
TXT;
			}
		}
	}
}
class gs_tpl_block {
	protected $tpl_filename;
	protected $data;
	function __construct($data=null,$tpl_filename='default/empty_block.html') {
		$this->data=$data;
		$this->tpl_filename=$tpl_filename;
	}
	function show() {
		$tpl=gs_tpl::get_instance();
		return $tpl->fetch($this->tpl_filename);
	}
}
?>
