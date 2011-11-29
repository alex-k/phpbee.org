<?php


class module extends gs_base_module implements gs_module {
	function __construct() {}
	
	function install() {
		$n=new tw_handlers;
		$n->install();
		$n=new tw_handlers_cache;
		$n->install();
	}
	
	static function get_handlers() {
		$data=array(
			'get_post'=>array(
				''=>'gs_base_handler.show:{name:index.html}',
				'/admin'=>'admin_handler.show:{name:admin_page.html}',
				'/admin/window_form'=>'admin_handler.many2one:{name:window_form.html}',
				'/admin/many2one'=>'admin_handler.many2one:{name:many2one.html}',
				'*'=>'gs_base_handler.show404:{name:404.html}',
			),
			'handler'=>array(
				'/admin/menu'=>'admin_handler.show_menu',
				'/filter'=>'gs_filters_handler.init',
				'/filter/show'=>'gs_filters_handler.show',
			),
		);
		return self::add_subdir($data,dirname(__file__));
	}
	static function gl($name,$record,$data) {
		switch ($name) {
			case 'save_cancel':
				return $data['handler_key_root'];
			case 'save_continue':
				return $data['gspgid_root'];
			case 'save_return':
				return $data['handler_key_root'];
			break;
			}
		return null;
	}
}



class admin_handler extends gs_base_handler {
	function show_menu () {
		$init=new gs_init('auto');
		$init->load_modules();

		$cfg=gs_config::get_instance();
		$modules=$cfg->get_registered_modules();
		$menu=array();
		if (is_array($modules)) foreach ($modules as $m) {
			$mod=new $m;
			if (method_exists($mod,'get_menu') && $menuitem=$mod->get_menu()) {
				if (!is_array($menuitem)) $menuitem=array($menuitem);
				$menu=array_merge($menu,$menuitem);
			}
		}
		$tpl=gs_tpl::get_instance();
		$tpl->assign('menu',$menu);
		return $tpl->fetch('admin_menu.html');
	}
	
	function show() {
		parent::show();
		return false;
	}
	
	function deleteform() {
		$id=$this->data['gspgid_va'][0];
		$res=preg_replace("|/delete/\d+|is","/",$this->data['gspgid']);
		$rs=new $this->params['classname'];
		$rec=$rs->get_by_id($id);
		$rec->delete();
		$rec->commit();
		$query=array();
		parse_str(parse_url(cfg('referer'),PHP_URL_QUERY),$query);
		return html_redirect($res,$query);
	}
	
	function many2one($ret) {
		if (isset($this->data['gspgid_va'][5]) && $this->data['gspgid_va'][5]=='delete') {
			$rid=intval($this->data['gspgid_va'][6]);
			$rs_name=$this->data['gspgid_va'][0];
			$rs=new $rs_name;
			$rec=$rs->get_by_id($rid);
			if ($rec) {
				$rec->delete();
				$rec->commit();
			}
			$res=preg_replace("|/delete/\d+|is","//",$this->data['gspgid']);
			return html_redirect($res);
		}
		
		if ($this->data['action']=='delete') {
			$ids=$this->data['act'];
			$rs_name=$this->data['gspgid_va'][0];
			$rs=new $rs_name;
			$recs=$rs->find_records(array('id'=>$ids));
			foreach ($recs as $rec) {
				$rec->delete();
				$rec->commit();
			}
			return html_redirect($this->data['gspgid']);
		}
		
		
		$params=array(
			$this->data['gspgid_va'][1]=>$this->data['gspgid_va'][2],
		);
		$g=array_slice($this->data['gspgid_va'],0,5);
		$url=implode('/',$g);
		if ($this->data['gspgid_va'][2]==0) {
			$params[$this->data['gspgid_va'][1].'_hash']=$this->data['gspgid_va'][3];
		}
		$tpl=gs_tpl::get_instance();
		$tpl->assign('url',$url);
		$tpl->assign('params',$params);
		parent::show($ret);
	}
}


class form_admin extends  g_forms_html {
	function __construct($h,$data=array(),$rec=null)  {
		parent::__construct($h,$data,$rec);
		$this->view = new gs_glyph('helper',array('class'=>'table_admin'));
		$this->view->addNode('helper',array('class'=>'tr'),array_keys($h));
	}
}
class form_table extends  g_forms_html {
	function __construct($h,$data=array(),$rec=null)  {
		parent::__construct($h,$data,$rec);
		$this->view = new gs_glyph('helper',array('class'=>'table_submit'));
		$this->view->addNode('helper',array('class'=>'tr'),array_keys($h));
	}
}
?>
